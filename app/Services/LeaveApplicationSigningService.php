<?php

namespace App\Services;

use App\Models\DigitalCertificate;
use App\Models\DigitalSignedLeave;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class LeaveApplicationSigningService
{
    protected $signatureService;

    public function __construct(DigitalSignatureService $signatureService)
    {
        $this->signatureService = $signatureService;
    }

    /**
     * Process owner leave application signing
     */
    public function processOwnerSigning(
        UploadedFile $pdfFile,
        DigitalCertificate $certificate
    ): array {
        $pdfPath = $this->storeTemporaryFile($pdfFile);
        
        try {
            $signedDocument = $this->signDocument(
                Storage::disk('private')->path($pdfPath),
                $certificate,
                'owner'
            );

            $signedFilename = $this->generateSignedFilename($certificate->employee_profile_id, $pdfFile->getClientOriginalName(), 'owner');
            
            $storedPath = $this->storeSignedDocument($signedFilename, $signedDocument);
            
            $signedLeave = $this->createSignedLeaveRecord(
                $certificate,
                $signedFilename,
                $storedPath,
                'owner',
                $pdfFile->getClientOriginalName()
            );

            return [
                'id' => $signedLeave->id,
                'file_name' => $signedLeave->file_name,
                'signed_at' => $signedLeave->signed_at
            ];
        } finally {
            $this->cleanupTemporaryFile($pdfPath);
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

        return $this->signatureService->signDocument(
            $pdfPath,
            $p12Path,
            $certificate->digitalCertificateFile->cert_password,
            $signaturePath,
            null, // wholeMonth is not applicable for leave applications
            $signer,
            'leave'
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
        $path = 'signed_leave/' . $filename;
        if (!Storage::disk('private')->put($path, $content)) {
            throw new \Exception('Failed to store signed document.');
        }
        return $path;
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

    /**
     * Copy a file to temporary storage
     */
    protected function copyToTemporary(string $sourcePath): string
    {
        $tempPath = 'temp_documents/' . uniqid('leave_') . '.pdf';
        
        if (!Storage::disk('private')->copy($sourcePath, $tempPath)) {
            throw new \Exception('Failed to copy file to temporary storage.');
        }
        
        return $tempPath;
    }

    /**
     * Get valid owner signed documents
     */
    protected function getValidOwnerSignedDocuments(array $documentIds): array
    {
        $documents = DigitalSignedLeave::whereIn('id', $documentIds)
            ->where('signer_type', 'owner')
            ->get();

        if ($documents->isEmpty()) {
            throw new \Exception('No valid owner-signed documents found.');
        }

        return $documents;
    }

    /**
     * Create a signed leave record
     */
    protected function createSignedLeaveRecord(
        DigitalCertificate $certificate,
        string $filename,
        string $filePath,
        string $signerType,
        string $originalFilename,
        ?int $previousSignedId = null
    ): DigitalSignedLeave {
        return DigitalSignedLeave::create([
            'employee_profile_id' => $certificate->employee_profile_id,
            'digital_certificate_id' => $certificate->id,
            'file_name' => $filename,
            'file_path' => $filePath,
            'signer_type' => $signerType,
            'original_filename' => $originalFilename,
            'previous_signed_id' => $previousSignedId,
            'signed_at' => now()
        ]);
    }
}
