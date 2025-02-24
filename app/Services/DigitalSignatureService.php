<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Helpers\JwtHelpers;
use Illuminate\Support\Facades\Log;

class DigitalSignatureService
{
    protected $apiUrl;

    public function __construct()
    {
        $this->apiUrl = env('FASTAPI_URL'); // Ensure this is set in .env
    }

    public function signDtrDocument($pdf_path, $p12_path, $password, $image_path, $wholeMonth, $signer = 'owner', $documentType = 'dtr')
    {
        if (!in_array($signer, ['owner', 'incharge'])) {
            throw new \Exception("Invalid signer type: $signer");
        }

        if (!in_array($documentType, ['dtr'])) {
            throw new \Exception("Invalid document type: $documentType");
        }

        // Generate JWT token
        $jwt = JwtHelpers::generateToken();

        // Ensure files exist and are readable
        if (!file_exists($pdf_path) || !is_readable($pdf_path)) {
            throw new \Exception("PDF file not found or not readable at path: $pdf_path");
        }
        if (!file_exists($p12_path) || !is_readable($p12_path)) {
            throw new \Exception("P12 file not found or not readable at path: $p12_path");
        }
        if (!file_exists($image_path) || !is_readable($image_path)) {
            throw new \Exception("Signature image file not found or not readable at path: $image_path");
        }

        try {
            // Prepare request parameters
            $params = [
                'p12_password' => $password
            ];

            // Only include wholeMonth parameter for DTR documents
            if ($documentType === 'dtr' && $wholeMonth !== null) {
                $params['whole_month'] = $wholeMonth;
            }

            // Create multipart form data
            $response = Http::withHeaders([
                'Authorization' => "Bearer $jwt",
                'Accept' => 'application/json'
            ])
                ->withOptions([
                    'verify' => false, // Only if needed for self-signed certificates
                    'timeout' => 60 // Increase timeout for larger files
                ])
                ->attach(
                    'input_pdf',
                    file_get_contents($pdf_path),
                    basename($pdf_path)
                )
                ->attach(
                    'p12_file',
                    file_get_contents($p12_path),
                    basename($p12_path)
                )
                ->attach(
                    'image',
                    file_get_contents($image_path),
                    basename($image_path)
                )
                ->post("{$this->apiUrl}/sign-{$documentType}-{$signer}", $params);

            if ($response->failed()) {
                $error_message = $response->json() ? json_encode($response->json()) : $response->body();
                Log::error('FastAPI Error Response:', ['response' => $error_message]);
                throw new \Exception("Failed to sign document: " . $error_message);
            }

            // Log successful response for debugging
            Log::debug('FastAPI Success Response', [
                'status' => $response->status(),
                'content_type' => $response->header('Content-Type')
            ]);

            // The response should be a PDF file
            return $response->body();
        } catch (\Throwable $th) {
            Log::error('Error in signDocument service:', [
                'error' => $th->getMessage(),
                'pdf_path' => $pdf_path,
                'p12_path' => $p12_path,
                'image_path' => $image_path
            ]);
            throw $th;
        }
    }

    public function signLeaveApplicationDocument($pdf_path, $p12_path, $password, $image_path, $signer = 'owner', $documentType = 'leave-application')
    {
        if (!in_array($signer, ['owner', 'head', 'sao', 'cao'])) {
            throw new \Exception("Invalid signer type: $signer");
        }

        if (!in_array($documentType, ['leave_application'])) {
            throw new \Exception("Invalid document type: $documentType");
        }

        // Generate JWT token
        $jwt = JwtHelpers::generateToken();

        // Ensure files exist and are readable
        if (!file_exists($pdf_path) || !is_readable($pdf_path)) {
            throw new \Exception("PDF file not found or not readable at path: $pdf_path");
        }
        if (!file_exists($p12_path) || !is_readable($p12_path)) {
            throw new \Exception("P12 file not found or not readable at path: $p12_path");
        }
        if (!file_exists($image_path) || !is_readable($image_path)) {
            throw new \Exception("Signature image file not found or not readable at path: $image_path");
        }

        try {
            // Prepare request parameters
            $params = [
                'p12_password' => $password
            ];

            // Create multipart form data
            $response = Http::withHeaders([
                'Authorization' => "Bearer $jwt",
                'Accept' => 'application/json'
            ])
                ->withOptions([
                    'verify' => false, // Only if needed for self-signed certificates
                    'timeout' => 60 // Increase timeout for larger files
                ])
                ->attach(
                    'input_pdf',
                    file_get_contents($pdf_path),
                    basename($pdf_path)
                )
                ->attach(
                    'p12_file',
                    file_get_contents($p12_path),
                    basename($p12_path)
                )
                ->attach(
                    'image',
                    file_get_contents($image_path),
                    basename($image_path)
                )
                ->post("{$this->apiUrl}/sign-{$documentType}-{$signer}", $params);

            if ($response->failed()) {
                $error_message = $response->json() ? json_encode($response->json()) : $response->body();
                Log::error('FastAPI Error Response:', ['response' => $error_message]);
                throw new \Exception("Failed to sign document: " . $error_message);
            }

            // Log successful response for debugging
            Log::debug('FastAPI Success Response', [
                'status' => $response->status(),
                'content_type' => $response->header('Content-Type')
            ]);

            // The response should be a PDF file
            return $response->body();
        } catch (\Throwable $th) {
            Log::error('Error in signDocument service:', [
                'error' => $th->getMessage(),
                'pdf_path' => $pdf_path,
                'p12_path' => $p12_path,
                'image_path' => $image_path
            ]);
            throw $th;
        }
    }
}
