<?php

namespace App\Http\Controllers\DigitalSignature;

use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use App\Models\CertificateAttachments;
use App\Models\CertificateDetails;
use App\Models\CertificateLogs;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;


class DigitalSignatureController extends Controller
{
    private string $CONTROLLER_NAME = 'DigitalSignature';

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = $request->user;

            // Validate the incoming request
            $request->validate([
                'cert_file' => 'required|file',
                'cert_img_file' => 'required|file|mimes:png,jpg,jpeg',
                'cert_password' => 'required|string',
                'pin' => 'required|numeric',
            ]);

            $clean_data['pin'] = strip_tags($request->input('pin'));

            if ($user['authorization_pin'] !== $clean_data['pin']) {
                return response()->json(['message' => 'Invalid authorization pin.'], Response::HTTP_UNAUTHORIZED);
            }

            $existing_attachments = CertificateAttachments::where('employee_profile_id', $user->id)->first();

            if ($existing_attachments) {
                return response()->json([
                    'message' => 'User already has certificate attachments. You may update or replace them if needed.'
                ], Response::HTTP_CONFLICT);
            }

            // Check if files are uploaded and valid
            if (!$request->hasFile('cert_file') || !$request->file('cert_file')->isValid()) {
                return response()->json(['message' => 'cert_file is missing or invalid.'], Response::HTTP_BAD_REQUEST);
            }

            if (!$request->hasFile('cert_img_file') || !$request->file('cert_img_file')->isValid()) {
                return response()->json(['message' => 'cert_img_file is missing or invalid.'], Response::HTTP_BAD_REQUEST);
            }

            // get cert password
            $cert_password = $request->cert_password;
            $encrypted_password = Crypt::encryptString($cert_password);

            // Store cert_file
            $cert_file = $request->file('cert_file');
            $cert_file_name = pathinfo($cert_file->getClientOriginalName(), PATHINFO_FILENAME);
            $cert_file_extension = $cert_file->getClientOriginalExtension();
            $cert_file_size = $cert_file->getSize();
            $cert_file_name_encrypted = Helpers::checkSaveFile($cert_file, 'storage/certificates');

            // Store cert_img_file
            $cert_img_file = $request->file('cert_img_file');
            $cert_img_file_name = pathinfo($cert_img_file->getClientOriginalName(), PATHINFO_FILENAME);
            $cert_img_file_extension = $cert_img_file->getClientOriginalExtension();
            $cert_img_file_size = $cert_img_file->getSize();
            $cert_img_file_name_encrypted = Helpers::checkSaveFile($cert_img_file, 'storage/e_signatures');

            $certificate_attachments = CertificateAttachments::create([
                'employee_profile_id' => $user->id,
                'filename' => $cert_file_name_encrypted,
                'file_path' => $cert_file_name_encrypted,
                'file_extension' => $cert_file_extension,
                'file_size' => $cert_file_size,
                'img_name' => $cert_img_file_name_encrypted,
                'img_path' => $cert_img_file_name_encrypted,
                'img_extension' => $cert_img_file_extension,
                'img_size' => $cert_img_file_size,
                'cert_password' => $encrypted_password,
            ]);

            if (!$certificate_attachments) {
                return response()->json(['message' => 'Failed to store certificate attachments.'], Response::HTTP_BAD_REQUEST);
            }

            $certificate_logs = CertificateLogs::create([
                'certificate_attachment_id' => $certificate_attachments->id,
                'employee_profile_id' => $user->id,
                'action' => "upload",
                'description' => "Upload certificate attachment",
            ]);

            if (!$certificate_logs) {
                return response()->json(['message' => 'Failed to store certificate logs.'], Response::HTTP_BAD_REQUEST);
            }

            $extracted_cert_details = $this->extractCertificateDetails($certificate_attachments->id);

            if (!$extracted_cert_details) {
                return response()->json(['message' => 'Failed to store extract certificate details.'], Response::HTTP_BAD_REQUEST);
            }

            $save_cert_details = $this->saveCertificateDetails($extracted_cert_details, $certificate_attachments->employee_profile_id, $certificate_attachments->id);

            if (!$save_cert_details) {
                return response()->json(['message' => 'Failed to store certificate details.'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Return response with file paths and metadata
            return response()->json([
                'message' => 'Certificate files and details saved successfully.'
            ]);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    private function extractCertificateDetails($id): JsonResponse|array
    {
        try {
            // Step 1: Retrieve and validate resources
            $certificate_attachment = CertificateAttachments::where('id', $id)->first();

            if (!$certificate_attachment) {
                return response()->json(['message' => 'Certificate attachment not found.'], Response::HTTP_NOT_FOUND);
            }

            // Retrieve the cert file and password
            $cert_file_path = storage_path('app\\public\\certificates\\' . $certificate_attachment->filename);
            $cert_password = Crypt::decryptString($certificate_attachment->cert_password);
            $cert_img_path = storage_path('app\\public\\e_signatures\\' . $certificate_attachment->img_name);

            if (!file_exists($cert_file_path)) {
                return response()->json(['message' => 'Certificate file not found.'], Response::HTTP_NOT_FOUND);
            }
            if (!file_exists($cert_img_path)) {
                return response()->json(['message' => 'Signature image file not found.'], Response::HTTP_NOT_FOUND);
            }

            // Step 2: Extract private key and certificate
            $cert_content = file_get_contents($cert_file_path);
            $certs = [];
            if (!openssl_pkcs12_read($cert_content, $certs, $cert_password)) {
                return response()->json(['message' => 'Failed to extract private key from certificate.'], Response::HTTP_BAD_REQUEST);
            }

            $private_key = $certs['pkey'];
            $certificate = $certs['cert'];

            // Ensure both the certificate and private key are available
            if (!isset($certs['cert']) || !isset($certs['pkey'])) {
                return response()->json(['message' => 'Certificate or private key missing in PFX file.'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Define paths for the PEM files
            $cert_pem_path = storage_path('app\\public\\certificates\\' . $id . '_cert.pem');
            $key_pem_path = storage_path('app\\public\\certificates\\' . $id . '_key.pem');

            // Save certificate to cert.pem
            file_put_contents($cert_pem_path, $certs['cert']);
            // Save private key to key.pem
            file_put_contents($key_pem_path, $certs['pkey']);

            $certificate_info = openssl_x509_parse($certs['cert']);
            return [
                'private_key' => $private_key,
                'public_key' => $certificate,
                'cert_info' => $certificate_info,
            ];
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'extractCertificateDetails', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function saveCertificateDetails($cert_details, $employee_profile_id, $certificate_attachment_id): bool|JsonResponse
    {
        try {
            // Extract details from the certificate
            $subject_owner = $cert_details['cert_info']['subject']['CN'] ?? null;
            $issued_by = $cert_details['cert_info']['issuer']['CN'] . ', ' .
                ($cert_details['cert_info']['issuer']['O'] ?? '') . ', ' .
                ($cert_details['cert_info']['issuer']['C'] ?? '');
            $organization_unit = $cert_details['cert_info']['subject']['OU'] ?? null;
            $country = $cert_details['cert_info']['subject']['C'] ?? null;
            $valid_from = date('Y-m-d H:i:s', $cert_details['cert_info']['validFrom_time_t']);
            $valid_till = date('Y-m-d H:i:s', $cert_details['cert_info']['validTo_time_t']);

            // Correctly access private_key and public_key from cert_details root level
            $private_key = Crypt::encryptString($cert_details['private_key']);
            $public_key = Crypt::encryptString($cert_details['public_key']);

            // Save certificate details to the database
            $certificate_details = CertificateDetails::create([
                'employee_profile_id' => $employee_profile_id,
                'certificate_attachment_id' => $certificate_attachment_id,
                'subject_owner' => $subject_owner,
                'issued_by' => $issued_by,
                'organization_unit' => $organization_unit,
                'country' => $country,
                'valid_from' => $valid_from,
                'valid_till' => $valid_till,
                'public_key' => $public_key,
                'private_key' => $private_key
            ]);

            $certificate_logs = CertificateLogs::create([
                'certificate_attachment_id' => $certificate_attachment_id,
                'employee_profile_id' => $employee_profile_id,
                'action' => "upload",
                'description' => "Upload certificate details",
            ]);

            if (!$certificate_logs) {
                return response()->json(['message' => 'Failed to store certificate logs.'], Response::HTTP_BAD_REQUEST);
            }

            // Check if the insertion was successful
            if (!$certificate_details || !$certificate_details->exists) {
                return response()->json(['message' => 'Failed to store certificate details.'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Return success response
            return true;

        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'saveCertificateDetails', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function signDtrOwner(Request $request): JsonResponse
    {
        try {
            $client = new Client();
            $response = $client->post('http://127.0.0.1:8000/sign-dtr-owner/', [
                'multipart' => [
                    [
                        'name'     => 'input_pdf',
                        'contents' => fopen($request->file('dtr_file')->path(), 'r'),
                    ],
                    [
                        'name'     => 'p12_file',
                        'contents' => fopen($request->file('cert_file')->path(), 'r'),
                    ],
                    [
                        'name'     => 'p12_password',
                        'contents' => $request->cert_password,
                    ],
                    [
                        'name'     => 'image',
                        'contents' => fopen($request->file('cert_img_file')->path(), 'r'),
                    ],
                    [
                        'name'     => 'whole_month',
                        'contents' => $request->whole_month,
                    ]
                ]
            ]);

            if (!$response->getStatusCode() == 200) {
                return response()->json(['message' => 'Failed to sign document'], $response->getStatusCode());
            }

            return response()->json(['message' => 'Document signed successfully', 'file' => $response->getBody()], 200);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'signDTR', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
