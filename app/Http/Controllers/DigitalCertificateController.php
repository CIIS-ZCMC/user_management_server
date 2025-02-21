<?php

namespace App\Http\Controllers;

use App\Models\DigitalCertificate;
use App\Http\Controllers\Controller;
use App\Services\DigitalSignatureService;
use App\Services\DtrSigningService;
use Illuminate\Http\Request;
use App\Helpers\Helpers;
use App\Models\DigitalCertificateFile;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Crypt;
use App\Traits\DigitalCertificateLoggable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\DigitalSignedDtr;

class DigitalCertificateController extends Controller
{
    use DigitalCertificateLoggable;

    private string $CONTROLLER_NAME = 'DigitalCertificateController';
    protected $signatureService;
    protected $dtrSigningService;

    public function __construct(
        DigitalSignatureService $signatureService,
        DtrSigningService $dtrSigningService
    ) {
        $this->signatureService = $signatureService;
        $this->dtrSigningService = $dtrSigningService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user;

            $certificates = DigitalCertificate::with(['digitalCertificateFile'])
                ->where('employee_profile_id', $user->id)
                ->get();

            return response()->json([
                'data' => $certificates,
                'message' => 'Digital certificates retrieved successfully'
            ]);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return response()->json(['message' => 'Method not allowed'], Response::HTTP_METHOD_NOT_ALLOWED);
    }

    /**
     * Store a newly created resource in storage.
     * Registration of the users to upload their digital certificates 
     */
    public function store(Request $request): JsonResponse
    {
        // Start database transaction
        DB::beginTransaction();

        try {
            $user = $request->user;
            // Validate the incoming request
            $request->validate([
                'cert_file' => 'required|file',
                'cert_img_file' => 'required|file',
                'cert_password' => 'required|string',
                'pin' => 'required|numeric',
            ]);

            $clean_data['pin'] = strip_tags($request->input('pin'));

            if ($user['authorization_pin'] !== $clean_data['pin']) {
                DB::rollBack();
                return response()->json(['message' => 'Invalid authorization pin.'], Response::HTTP_UNAUTHORIZED);
            }

            $existing_attachments = DigitalCertificateFile::where('employee_profile_id', $user->id)->first();

            if ($existing_attachments) {
                DB::rollBack();
                return response()->json([
                    'message' => 'User already has certificate attachments. You may update or replace them if needed.'
                ], Response::HTTP_CONFLICT);
            }

            // Check if files are uploaded and valid
            if (!$request->hasFile('cert_file') || !$request->file('cert_file')->isValid()) {
                DB::rollBack();
                return response()->json(['message' => 'cert_file is missing or invalid.'], Response::HTTP_BAD_REQUEST);
            }

            if (!$request->hasFile('cert_img_file') || !$request->file('cert_img_file')->isValid()) {
                DB::rollBack();
                return response()->json(['message' => 'cert_img_file is missing or invalid.'], Response::HTTP_BAD_REQUEST);
            }

            // get cert password
            $cert_password = $request->cert_password;

            try {
                // Store cert_file
                $cert_file = $request->file('cert_file');
                $cert_file_name = pathinfo($cert_file->getClientOriginalName(), PATHINFO_FILENAME);
                $cert_file_extension = $cert_file->getClientOriginalExtension();
                $cert_file_size = $cert_file->getSize();
                $cert_file_name_encrypted = Helpers::checkSaveFileForDigitalSignature($cert_file, 'certificates');

                // Verify if cert_file was stored properly
                if (!Storage::disk('private')->exists('certificates/' . $cert_file_name_encrypted)) {
                    DB::rollBack();
                    throw new \Exception('Certificate file was not stored properly');
                }

                // Store cert_img_file
                $cert_img_file = $request->file('cert_img_file');
                $cert_img_file_name = pathinfo($cert_img_file->getClientOriginalName(), PATHINFO_FILENAME);
                $cert_img_file_extension = $cert_img_file->getClientOriginalExtension();
                $cert_img_file_size = $cert_img_file->getSize();
                $cert_img_file_name_encrypted = Helpers::checkSaveFileForDigitalSignature($cert_img_file, 'e_signatures');
                // Verify if cert_img_file was stored properly
                if (!Storage::disk('private')->exists('e_signatures/' . $cert_img_file_name_encrypted)) {
                    DB::rollBack();
                    throw new \Exception('Signature image was not stored properly');
                }

                // The model will handle the password encryption via its mutator
                $certificate_attachments = DigitalCertificateFile::create([
                    'employee_profile_id' => $user->id,
                    'filename' => $cert_file_name_encrypted,
                    'file_path' => $cert_file_name_encrypted,
                    'file_extension' => $cert_file_extension,
                    'file_size' => $cert_file_size,
                    'img_name' => $cert_img_file_name_encrypted,
                    'img_path' => $cert_img_file_name_encrypted,
                    'img_extension' => $cert_img_file_extension,
                    'img_size' => $cert_img_file_size,
                    'cert_password' => $request->cert_password, // Model will encrypt this
                ]);

                if (!$certificate_attachments) {
                    throw new \Exception('Failed to store certificate attachments.');
                }
            } catch (\Throwable $th) {
                DB::rollBack();
                // Clean up any stored files if they exist
                if (isset($cert_file_name_encrypted)) {
                    Storage::disk('private')->delete('certificates/' . $cert_file_name_encrypted);
                }
                if (isset($cert_img_file_name_encrypted)) {
                    Storage::disk('private')->delete('e_signatures/' . $cert_img_file_name_encrypted);
                }
                return response()->json(['message' => $th->getMessage()], Response::HTTP_BAD_REQUEST);
            }

            // Log the certificate upload action
            $this->logCertificateAction(
                $certificate_attachments->id,
                $user->id,
                'UPLOAD',
                $user->id . ': Digital certificate and signature uploaded successfully'
            );

            try {
                $extracted_cert_details = $this->extractCertificateDetails($certificate_attachments->id);
                $this->logCertificateAction($certificate_attachments->id, $user->id, 'EXTRACT', $user->id . ': Extract certificate details successfully');

                $save_cert_details = $this->saveCertificateDetails($extracted_cert_details, $certificate_attachments->employee_profile_id, $certificate_attachments->id);
                $this->logCertificateAction($certificate_attachments->id, $user->id, 'SAVE', $user->id . ': Certificate details saved successfully');

                if (!$save_cert_details) {
                    throw new \Exception('Failed to store certificate details.');
                }
            } catch (\Throwable $th) {
                DB::rollBack();
                // Clean up any stored files
                Storage::disk('private')->delete([
                    'certificates/' . $cert_file_name_encrypted,
                    'e_signatures/' . $cert_img_file_name_encrypted
                ]);
                return response()->json(['message' => $th->getMessage()], Response::HTTP_BAD_REQUEST);
            }

            // Commit the transaction
            DB::commit();

            // Return response with success message
            return response()->json([
                'message' => 'Certificate files and details saved successfully.',
                'data' => $request->user
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            DB::rollBack();
            Helpers::errorLog($this->CONTROLLER_NAME, 'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user;

            $certificate = DigitalCertificate::with(['digitalCertificateFile'])
                ->where('id', $id)
                ->where('employee_profile_id', $user->id)
                ->first();

            if (!$certificate) {
                return response()->json(['message' => 'Digital certificate not found'], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'data' => $certificate,
                'message' => 'Digital certificate retrieved successfully'
            ]);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DigitalCertificate $digitalCertificate)
    {
        return response()->json(['message' => 'Method not allowed'], Response::HTTP_METHOD_NOT_ALLOWED);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id): JsonResponse
    {
        // Start database transaction
        DB::beginTransaction();

        try {
            $user = $request->user;

            // Validate the incoming request
            $request->validate([
                'cert_file' => 'nullable|file',
                'cert_img_file' => 'nullable|file',
                'cert_password' => 'required|string',
                'pin' => 'required|numeric',
            ]);

            $clean_data['pin'] = strip_tags($request->input('pin'));

            if ($user['authorization_pin'] !== $clean_data['pin']) {
                DB::rollBack();
                return response()->json(['message' => 'Invalid authorization pin.'], Response::HTTP_UNAUTHORIZED);
            }

            // Find existing certificate
            $certificate = DigitalCertificate::with('digitalCertificateFile')
                ->where('id', $id)
                ->where('employee_profile_id', $user->id)
                ->first();

            if (!$certificate) {
                DB::rollBack();
                return response()->json(['message' => 'Digital certificate not found'], Response::HTTP_NOT_FOUND);
            }

            $cert_file_name_encrypted = null;
            $cert_img_file_name_encrypted = null;

            // Handle file updates if provided
            if ($request->hasFile('cert_file')) {
                $cert_file = $request->file('cert_file');
                if ($cert_file->isValid()) {
                    $cert_file_name_encrypted = Helpers::checkSaveFileForDigitalSignature($cert_file, 'certificates');

                    // Delete old certificate file
                    Storage::disk('private')->delete('certificates/' . $certificate->digitalCertificateFile->filename);

                    // Update certificate file details
                    $certificate->digitalCertificateFile->update([
                        'filename' => $cert_file_name_encrypted,
                        'file_path' => $cert_file_name_encrypted,
                        'file_extension' => $cert_file->getClientOriginalExtension(),
                        'file_size' => $cert_file->getSize(),
                        'cert_password' => $request->cert_password
                    ]);
                }
            }

            if ($request->hasFile('cert_img_file')) {
                $cert_img_file = $request->file('cert_img_file');
                if ($cert_img_file->isValid()) {
                    $cert_img_file_name_encrypted = Helpers::checkSaveFileForDigitalSignature($cert_img_file, 'e_signatures');

                    // Delete old signature file
                    Storage::disk('private')->delete('e_signatures/' . $certificate->digitalCertificateFile->img_name);

                    // Update signature file details
                    $certificate->digitalCertificateFile->update([
                        'img_name' => $cert_img_file_name_encrypted,
                        'img_path' => $cert_img_file_name_encrypted,
                        'img_extension' => $cert_img_file->getClientOriginalExtension(),
                        'img_size' => $cert_img_file->getSize()
                    ]);
                }
            }

            // Log the update action
            $this->logCertificateAction(
                $certificate->digitalCertificateFile->id,
                $user->id,
                'UPDATE',
                $user->id . ': Digital certificate and signature updated successfully'
            );

            // If certificate file was updated, extract and save new details
            if ($cert_file_name_encrypted) {
                try {
                    $extracted_cert_details = $this->extractCertificateDetails($certificate->digitalCertificateFile->id);
                    $this->logCertificateAction(
                        $certificate->digitalCertificateFile->id,
                        $user->id,
                        'EXTRACT',
                        $user->id . ': Updated certificate details extracted successfully'
                    );

                    // Update certificate details
                    $certificate->update([
                        'subject_owner' => $extracted_cert_details['cert_info']['subject']['CN'] ?? null,
                        'issued_by' => $extracted_cert_details['cert_info']['issuer']['CN'] . ', ' .
                            ($extracted_cert_details['cert_info']['issuer']['O'] ?? '') . ', ' .
                            ($extracted_cert_details['cert_info']['issuer']['C'] ?? ''),
                        'organization_unit' => $extracted_cert_details['cert_info']['subject']['OU'] ?? null,
                        'country' => $extracted_cert_details['cert_info']['subject']['C'] ?? null,
                        'valid_from' => date('Y-m-d H:i:s', $extracted_cert_details['cert_info']['validFrom_time_t']),
                        'valid_till' => date('Y-m-d H:i:s', $extracted_cert_details['cert_info']['validTo_time_t']),
                        'public_key' => Crypt::encryptString($extracted_cert_details['public_key'])
                    ]);

                    $this->logCertificateAction(
                        $certificate->digitalCertificateFile->id,
                        $user->id,
                        'SAVE',
                        $user->id . ': Updated certificate details saved successfully'
                    );
                } catch (\Throwable $th) {
                    DB::rollBack();
                    // Clean up any new files
                    if ($cert_file_name_encrypted) {
                        Storage::disk('private')->delete('certificates/' . $cert_file_name_encrypted);
                    }
                    if ($cert_img_file_name_encrypted) {
                        Storage::disk('private')->delete('e_signatures/' . $cert_img_file_name_encrypted);
                    }
                    return response()->json(['message' => $th->getMessage()], Response::HTTP_BAD_REQUEST);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Digital certificate updated successfully',
                'data' => $certificate->fresh(['digitalCertificateFile'])
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            Helpers::errorLog($this->CONTROLLER_NAME, 'update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $user = $request->user;

            // Validate PIN
            $request->validate([
                'pin' => 'required|numeric',
            ]);

            $clean_data['pin'] = strip_tags($request->input('pin'));

            if ($user['authorization_pin'] !== $clean_data['pin']) {
                DB::rollBack();
                return response()->json(['message' => 'Invalid authorization pin.'], Response::HTTP_UNAUTHORIZED);
            }

            // Find the certificate
            $certificate = DigitalCertificate::with('digitalCertificateFile')
                ->where('id', $id)
                ->where('employee_profile_id', $user->id)
                ->first();

            if (!$certificate) {
                DB::rollBack();
                return response()->json(['message' => 'Digital certificate not found'], Response::HTTP_NOT_FOUND);
            }

            // Store file names for deletion
            $cert_filename = $certificate->digitalCertificateFile->filename;
            $img_name = $certificate->digitalCertificateFile->img_name;
            $cert_id = $certificate->digitalCertificateFile->id;

            // Log the deletion
            $this->logCertificateAction(
                $cert_id,
                $user->id,
                'DELETE',
                $user->id . ': Digital certificate and signature deletion initiated'
            );

            // Delete the files
            Storage::disk('private')->delete([
                'certificates/' . $cert_filename,
                'e_signatures/' . $img_name,
                'certificates/' . Crypt::encryptString($user->id) . '_cert.pem',
                'certificates/' . Crypt::encryptString($user->id) . '_key.pem'
            ]);

            // Delete the database records
            $certificate->digitalCertificateFile->delete();
            $certificate->delete();

            $this->logCertificateAction(
                $cert_id,
                $user->id,
                'DELETE',
                $user->id . ': Digital certificate and signature deleted successfully'
            );

            DB::commit();

            return response()->json(['message' => 'Digital certificate deleted successfully']);
        } catch (\Throwable $th) {
            DB::rollBack();
            Helpers::errorLog($this->CONTROLLER_NAME, 'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function extractCertificateDetails($id): array
    {
        try {
            // Step 1: Retrieve and validate resources
            $certificate_file = DigitalCertificateFile::where('id', $id)->first();

            if (!$certificate_file) {
                DB::rollBack();
                throw new \Exception('Certificate attachment not found.');
            }

            // Ensure directories exist
            Storage::disk('private')->makeDirectory('certificates');
            Storage::disk('private')->makeDirectory('e_signatures');

            // Get the file contents using Storage facade
            if (!Storage::disk('private')->exists('certificates/' . $certificate_file->filename)) {
                DB::rollBack();
                throw new \Exception('Certificate file not found.');
            }

            if (!Storage::disk('private')->exists('e_signatures/' . $certificate_file->img_name)) {
                DB::rollBack();
                throw new \Exception('Signature image file not found.');
            }

            // Get cert password - the model accessor will handle decryption
            $cert_password = $certificate_file->cert_password;

            // Step 2: Extract private key and certificate
            $cert_content = Storage::disk('private')->get('certificates/' . $certificate_file->filename);

            // Log certificate details for debugging
            Log::debug('Certificate Content Length: ' . strlen($cert_content));
            Log::debug('Certificate File Extension: ' . pathinfo($certificate_file->filename, PATHINFO_EXTENSION));

            // Clear any existing OpenSSL errors
            while (openssl_error_string() !== false);

            $certs = [];
            if (!openssl_pkcs12_read($cert_content, $certs, $cert_password)) {
                $ssl_errors = [];
                while ($ssl_error = openssl_error_string()) {
                    $ssl_errors[] = $ssl_error;
                }

                DB::rollBack();
                // Clean up any stored files
                if (isset($cert_file_name_encrypted)) {
                    Storage::disk('private')->delete('certificates/' . $cert_file_name_encrypted);
                }
                if (isset($cert_img_file_name_encrypted)) {
                    Storage::disk('private')->delete('e_signatures/' . $cert_img_file_name_encrypted);
                }
                $error_message = 'Failed to extract private key from certificate. OpenSSL Errors: ' . implode(', ', $ssl_errors);
                Log::error($error_message);
                throw new \Exception($error_message);
            }

            // Verify the structure of extracted certificate
            if (!is_array($certs)) {
                DB::rollBack();
                throw new \Exception('Invalid certificate structure: not an array');
            }

            $required_keys = ['cert', 'pkey'];
            foreach ($required_keys as $key) {
                if (!isset($certs[$key]) || empty($certs[$key])) {
                    DB::rollBack();
                    throw new \Exception("Missing or empty required certificate component: {$key}");
                }
            }

            $private_key = $certs['pkey'];
            $certificate = $certs['cert'];

            // encrypt pem file
            $pem_certificate = Crypt::encryptString($certificate_file->employee_profile_id);
            $pem_private_key = Crypt::encryptString($certificate_file->employee_profile_id);

            // Save PEM files using Storage facade
            if (!Storage::disk('private')->put('certificates/' . $pem_certificate . '_cert.pem', $certificate)) {
                DB::rollBack();
                // Clean up any stored files
                Storage::disk('private')->delete([
                    'certificates/' . $certificate_file->filename,
                    'e_signatures/' . $certificate_file->img_name
                ]);
                throw new \Exception('Failed to save certificate PEM file.');
            }

            if (!Storage::disk('private')->put('certificates/' . $pem_private_key . '_key.pem', $private_key)) {
                DB::rollBack();
                // Clean up any stored files
                Storage::disk('private')->delete([
                    'certificates/' . $certificate_file->filename,
                    'e_signatures/' . $certificate_file->img_name,
                    'certificates/' . $pem_certificate . '_cert.pem'
                ]);
                throw new \Exception('Failed to save private key PEM file.');
            }

            $certificate_info = openssl_x509_parse($certificate);
            if (!$certificate_info) {
                DB::rollBack();
                // Clean up any stored files
                Storage::disk('private')->delete([
                    'certificates/' . $certificate_file->filename,
                    'e_signatures/' . $certificate_file->img_name,
                    'certificates/' . $pem_certificate . '_cert.pem',
                    'certificates/' . $pem_private_key . '_key.pem'
                ]);
                throw new \Exception('Failed to parse certificate information.');
            }

            return [
                'private_key' => $private_key,
                'public_key' => $certificate,
                'cert_info' => $certificate_info,
            ];
        } catch (\Throwable $th) {
            DB::rollBack();
            Helpers::errorLog($this->CONTROLLER_NAME, 'extractCertificateDetails', $th->getMessage());
            throw $th;
        }
    }

    private function saveCertificateDetails($cert_details, $employee_profile_id, $digital_certificate_id)
    {
        try {
            // Validate input parameters
            if (empty($cert_details) || !is_array($cert_details) || empty($employee_profile_id) || empty($digital_certificate_id)) {
                DB::rollBack();
                throw new \Exception('Invalid input parameters for saveCertificateDetails');
            }

            // Extract details from the certificate
            $subject_owner = $cert_details['cert_info']['subject']['CN'] ?? null;
            $issued_by = $cert_details['cert_info']['issuer']['CN'] . ', ' .
                ($cert_details['cert_info']['issuer']['O'] ?? '') . ', ' .
                ($cert_details['cert_info']['issuer']['C'] ?? '');
            $organization_unit = $cert_details['cert_info']['subject']['OU'] ?? null;
            $country = $cert_details['cert_info']['subject']['C'] ?? null;
            $valid_from = date('Y-m-d H:i:s', $cert_details['cert_info']['validFrom_time_t']);
            $valid_till = date('Y-m-d H:i:s', $cert_details['cert_info']['validTo_time_t']);

            // Encrypt public key only
            try {
                $public_key = Crypt::encryptString($cert_details['public_key']);
            } catch (\Throwable $th) {
                DB::rollBack();
                throw new \Exception('Failed to encrypt public key: ' . $th->getMessage());
            }

            // Store Certificate
            $certificate_details = DigitalCertificate::create([
                'employee_profile_id' => $employee_profile_id,
                'digital_certificate_file_id' => $digital_certificate_id,
                'subject_owner' => $subject_owner,
                'issued_by' => $issued_by,
                'organization_unit' => $organization_unit,
                'country' => $country,
                'valid_from' => $valid_from,
                'valid_till' => $valid_till,
                'public_key' => $public_key,
            ]);

            if (!$certificate_details || !$certificate_details->exists) {
                DB::rollBack();
                throw new \Exception('Failed to store certificate details.');
            }

            return true;
        } catch (\Throwable $th) {
            DB::rollBack();
            Helpers::errorLog($this->CONTROLLER_NAME, 'saveCertificateDetails', $th->getMessage());
            throw $th;
        }
    }

    public function signDtr(Request $request)
    {
        try {
            $request->validate([
                'file' => 'nullable|file|mimes:pdf',
                'employee_profile_id' => 'integer|required',
                'signer' => 'required|string|in:owner,incharge',
                'whole_month' => 'required|boolean',
                'document_ids' => 'required_if:signer,incharge'
            ]);

            $employee_profile_id = $request->input('employee_profile_id');
            $signer = $request->input('signer');
            $whole_month = $request->input('whole_month');

            // Convert document_ids to array
            $document_ids = [];
            if ($signer === 'incharge') {
                $input = $request->input('document_ids');
                $document_ids = explode(',', $input);
                $document_ids = array_map('intval', $document_ids);

                if (empty($document_ids)) {
                    throw new \Exception('At least one document ID is required for incharge signing.');
                }
            }

            // Get the certificate for the signer
            $certificate = DigitalCertificate::with('digitalCertificateFile')
                ->where('employee_profile_id', $employee_profile_id)
                ->first();

            if (!$certificate) {
                throw new \Exception('No digital certificate found for the employee.');
            }

            $this->validateCertificateFiles($certificate);

            if ($request->input('signer') === 'owner') {
                if (!$request->hasFile('file')) {
                    throw new \Exception('PDF file is required for owner signing.');
                }

                $signedDocuments = [$this->dtrSigningService->processOwnerSigning(
                    $request->file('file'),
                    $certificate,
                    $request->boolean('whole_month')
                )];
            } else {
                $signedDocuments = $this->dtrSigningService->processInchargeSigning(
                    $document_ids,  // Pass the processed array instead of raw input
                    $certificate,
                    $request->boolean('whole_month')
                );
            }

            return response()->json([
                'message' => 'Documents signed successfully',
                'signed_documents' => $signedDocuments
            ], HTTP_OK);
        } catch (\Throwable $th) {
            Log::error('Error in signDtr: ' . $th->getMessage());
            Helpers::errorLog($this->CONTROLLER_NAME, 'signDtr', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function signLeaveApplication(Request $request)
    {
        try {
            // TODO:
            // > Get the input file from leave attachment id of the
            // > Pass the employee profile id
            // > Pass the signer [owner, head, sao, cao]

            $request->validate([
                'employee_profile_id' => 'integer|required',
                'signer' => 'required|string|in:owner,head,sao,cao',
                'document_ids' => 'required_if:signer,head,sao,cao'
            ]);

            $employee_profile_id = $request->input('employee_profile_id');
            $signer = $request->input('signer');

            $document_ids = [];
            if ($signer === 'head' || $signer === 'sao' || $signer === 'cao') {
                $input = $request->input('document_ids');
                $document_ids = explode(',', $input);
                $document_ids = array_map('intval', $document_ids);

                if (empty($document_ids)) {
                    throw new \Exception('At least one document ID is required for signing');
                }
            }

            // Get the certificate for the signer
            $certificate = DigitalCertificate::with('digitalCertificatFile')
                ->where('employee_profile_id', $employee_profile_id)
                ->first();

            if (!$certificate) {
                throw new \Exception('No digital certificate found for the employee.');
            }

            $this->validateCertificateFiles($certificate);

            if ($signer === 'owner') {
            } else {
            }

            return response()->json([
                'message' => 'Document signed successfully',
                // 'signed_documents' => $signedDocuments
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Log::error('Error in signDtr: ' . $th->getMessage());
            Helpers::errorLog($this->CONTROLLER_NAME, 'signDtr', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Validate certificate files existence
     */
    protected function validateCertificateFiles(DigitalCertificate $certificate): void
    {
        $p12FilePath = 'certificates/' . $certificate->digitalCertificateFile->filename;
        if (!Storage::disk('private')->exists($p12FilePath)) {
            throw new \Exception('P12 file not found.');
        }

        $signatureImagePath = 'e_signatures/' . $certificate->digitalCertificateFile->img_name;
        if (!Storage::disk('private')->exists($signatureImagePath)) {
            throw new \Exception('Signature image file not found.');
        }
    }
}
