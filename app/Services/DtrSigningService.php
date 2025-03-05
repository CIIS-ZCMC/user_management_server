<?php

namespace App\Services;

use App\Models\DigitalCertificate;
use App\Models\DigitalSignedDtr;
use App\Traits\DigitalCertificateLoggable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class DtrSigningService
{
    use DigitalCertificateLoggable;
    protected $signatureService;

    public function __construct(DigitalSignatureService $signatureService)
    {
        $this->signatureService = $signatureService;
    }

    /**
     * Process owner DTR signing
     */
    public function processOwnerSigning(
        UploadedFile $pdfFile,
        DigitalCertificate $certificate,
        bool $wholeMonth,
        int $signatureRequestId
    ): array {
        $pdfPath = $this->storeTemporaryFile($pdfFile);

        try {
            $signedDocument = $this->signDocument(
                Storage::disk('private')->path($pdfPath),
                $certificate,
                $wholeMonth,
                'owner'
            );

            $signedFilename = $this->generateSignedFilename($certificate->employee_profile_id, $pdfFile->getClientOriginalName(), 'owner');

            $storedPath = $this->storeSignedDocument($signedFilename, $signedDocument);

            $signedDtr = $this->createSignedDtrRecord(
                $certificate,
                $signedFilename,
                $storedPath,
                'owner',
                $wholeMonth,
                $pdfFile->getClientOriginalName(),
                $signatureRequestId
            );

            $this->logCertificateAction(
                $certificate->digital_certificate_file_id,
                $certificate->employee_profile_id,
                'SIGNED',
                $certificate->employee_profile_id . ': Owner signed DTR'
            );

            return [
                'id' => $signedDtr->id,
                'file_name' => $signedDtr->file_name,
                'signed_at' => $signedDtr->signed_at
            ];
        } finally {
            $this->cleanupTemporaryFile($pdfPath);
        }
    }

    /**
     * Process incharge DTR signing
     */
    public function processInchargeSigning(
        array $documentIds,
        DigitalCertificate $certificate,
        bool $wholeMonth,
        int $signatureRequestId
    ): array {
        $documents = $this->getValidOwnerSignedDocuments($documentIds);
        $signedDocuments = [];
        $tempFiles = [];

        try {
            foreach ($documents as $doc) {
                $tempPath = $this->copyToTemporary($doc->file_path);
                $tempFiles[] = $tempPath;

                $signedDocument = $this->signDocument(
                    Storage::disk('private')->path($tempPath),
                    $certificate,
                    $wholeMonth,
                    'incharge'
                );

                // Get original filename without any signing suffixes
                $originalFilename = $this->getOriginalFilename($doc->file_name);
                $signedFilename = $this->generateSignedFilename($certificate->employee_profile_id, $originalFilename, 'incharge');
                $storedPath = $this->storeSignedDocument($signedFilename, $signedDocument);

                $signedDtr = $this->createSignedDtrRecord(
                    $certificate,
                    $signedFilename,
                    $storedPath,
                    'incharge',
                    $wholeMonth,
                    $doc->file_name,
                    $signatureRequestId
                );

                $this->logCertificateAction(
                    $certificate->digital_certificate_file_id,
                    $certificate->employee_profile_id,
                    'SIGNED',
                    $certificate->employee_profile_id . ': Incharge signed DTR'
                );

                $signedDocuments[] = [
                    'id' => $signedDtr->id,
                    'file_name' => $signedDtr->file_name,
                    'signed_at' => $signedDtr->signed_at
                ];
            }

            return $signedDocuments;
        } finally {
            foreach ($tempFiles as $temp) {
                $this->cleanupTemporaryFile($temp);
            }
        }
    }

    /**
     * Store a temporary file
     */
    protected function storeTemporaryFile(UploadedFile $file): string
    {
        $path = Storage::disk('private')->putFile('temp_documents', $file);
        if (!$path) {
            throw new \Exception('Failed to store temporary file.');
        }
        return $path;
    }

    /**
     * Sign a document using the signature service
     */
    protected function signDocument(
        string $pdfPath,
        DigitalCertificate $certificate,
        bool $wholeMonth,
        string $signer
    ): string {
        // Verify file exists and is readable
        if (!file_exists($pdfPath) || !is_readable($pdfPath)) {
            throw new \Exception("PDF file not found or not readable at path: $pdfPath");
        }

        $p12Path = Storage::disk('private')->path('certificates/' . $certificate->digitalCertificateFile->filename);
        $signaturePath = Storage::disk('private')->path('e_signatures/' . $certificate->digitalCertificateFile->img_name);

        // Verify certificate files exist and are readable
        if (!file_exists($p12Path) || !is_readable($p12Path)) {
            throw new \Exception("P12 certificate file not found or not readable");
        }
        if (!file_exists($signaturePath) || !is_readable($signaturePath)) {
            throw new \Exception("Signature image file not found or not readable");
        }

        return $this->signatureService->signDtrDocument(
            $pdfPath,
            $p12Path,
            $certificate->digitalCertificateFile->cert_password,
            $signaturePath,
            $wholeMonth,
            $signer,
            'dtr'
        );
    }

    /**
     * Generate filename for signed document
     */
    protected function generateSignedFilename(int $employeeId, string $originalName, string $signer): string
    {
        $signerSuffix = $signer === 'owner' ? '_signed_by_owner' : '_signed_by_incharge';

        return $employeeId . '_' .
            date('Y_m_d') . '_' .
            pathinfo($originalName, PATHINFO_FILENAME) .
            $signerSuffix .
            '.pdf';
    }

    /**
     * Get original filename by removing signing suffixes and date pattern
     */
    protected function getOriginalFilename(string $filename): string
    {
        // Remove employee ID, date pattern, and signing suffixes
        $name = pathinfo($filename, PATHINFO_FILENAME);

        // Remove employee ID and date pattern (e.g., "2398_2025_02_19_")
        $name = preg_replace('/^\d+_\d{4}_\d{2}_\d{2}_/', '', $name);

        // Remove signing suffixes
        $name = str_replace(['_signed_by_owner', '_signed_by_incharge'], '', $name);

        return $name;
    }

    /**
     * Store the signed document
     */
    protected function storeSignedDocument(string $filename, string $content): string
    {
        $path = 'signed_dtr/' . $filename;
        if (!Storage::disk('private')->put($path, $content)) {
            throw new \Exception('Failed to store signed document.');
        }
        return $path;
    }

    /**
     * Create a record for the signed DTR
     */
    protected function createSignedDtrRecord(
        DigitalCertificate $certificate,
        string $filename,
        string $path,
        string $signerType,
        bool $wholeMonth,
        string $originalFilename,
        int $signatureRequestId, 
    ): DigitalSignedDtr {

        return DigitalSignedDtr::create([
            'employee_profile_id' => $certificate->employee_profile_id,
            'digital_certificate_id' => $certificate->id,
            'digital_dtr_signature_request_id' => $signatureRequestId,
            'file_name' => $filename,
            'file_path' => $path,
            'signer_type' => $signerType,
            'whole_month' => $wholeMonth,
            'signing_details' => [
                'original_filename' => $originalFilename,
                'signed_at' => now()->toIso8601String(),
                'certificate_used' => $certificate->id,
                'signer_type' => $signerType,
                'digital_dtr_signature_request_id' => $signatureRequestId
            ]
        ]);
    }

    /**
     * Get valid owner-signed documents
     */
    protected function getValidOwnerSignedDocuments(array $documentIds): \Illuminate\Database\Eloquent\Collection
    {
        $documents = DigitalSignedDtr::whereIn('id', $documentIds)
            ->where('signer_type', 'owner')
            ->where('status', 'signed')
            ->get();

        if ($documents->isEmpty()) {
            throw new \Exception('No valid owner-signed documents found.');
        }

        return $documents;
    }

    /**
     * Copy a file to temporary storage
     */
    protected function copyToTemporary(string $sourcePath): string
    {
        if (!Storage::disk('private')->exists($sourcePath)) {
            throw new \Exception("Source document not found in storage.");
        }

        $tempPath = 'temp_documents/' . basename($sourcePath);
        if (!Storage::disk('private')->copy($sourcePath, $tempPath)) {
            throw new \Exception("Failed to copy file to temporary storage.");
        }

        return $tempPath;
    }

    /**
     * Clean up a temporary file
     */
    protected function cleanupTemporaryFile(string $path): void
    {
        if (Storage::disk('private')->exists($path)) {
            Storage::disk('private')->delete($path);
        }
    }
}
