<?php

namespace App\Http\Controllers\DigitalSignature;

use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;


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
    public function store(Request $request)
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

            // Check if files are uploaded and valid
            if (!$request->hasFile('cert_file') || !$request->file('cert_file')->isValid()) {
                return response()->json(['message' => 'cert_file is missing or invalid.'], Response::HTTP_BAD_REQUEST);
            }

            if (!$request->hasFile('cert_img_file') || !$request->file('cert_img_file')->isValid()) {
                return response()->json(['message' => 'cert_img_file is missing or invalid.'], Response::HTTP_BAD_REQUEST);
            }

            // get cert password
            $cert_password = $request->cert_password;
            // Store cert_file
            $cert_file = $request->file('cert_file');
            $cert_file_name = pathinfo($cert_file->getClientOriginalName(), PATHINFO_FILENAME);
            $cert_file_extension = $cert_file->getClientOriginalExtension();
            $cert_file_size = $cert_file->getSize();
            $cert_file_name_encrypted = Helpers::checkSaveFile($cert_file, 'storage/certificates');
//            $cert_file_path = $cert_file->storeAs('certificates', $cert_file_name_encrypted, 'public');

            // Store cert_img_file
            $cert_img_file = $request->file('cert_img_file');
            $cert_img_file_name = pathinfo($cert_img_file->getClientOriginalName(), PATHINFO_FILENAME);
            $cert_img_file_extension = $cert_img_file->getClientOriginalExtension();
            $cert_img_file_size = $cert_img_file->getSize();
            $cert_img_file_name_encrypted = Helpers::checkSaveFile($cert_img_file, 'storage/e_signatures');
//            $cert_img_file_path = $cert_img_file->storeAs('e_signatures', $cert_img_file_name_encrypted, 'public');

            $cert_file_path = Storage::disk('public')->url("certificates/{$cert_file_name_encrypted}");
            $cert_img_file_path = Storage::disk('public')->url("e_signatures/{$cert_img_file_name_encrypted}");

            // Return response with file paths and metadata
            return response()->json([
                'cert_file' => [
                    'original_name' => $cert_file_name,
                    'encrypted_name' => $cert_file_name_encrypted,
                    'extension' => $cert_file_extension,
                    'size' => $cert_file_size,
                    'storage_path' => $cert_file_path,
                ],
                'cert_img_file' => [
                    'original_name' => $cert_img_file_name,
                    'encrypted_name' => $cert_img_file_name_encrypted,
                    'extension' => $cert_img_file_extension,
                    'size' => $cert_img_file_size,
                    'storage_path' => $cert_img_file_path,
                ],
                'cert_password' => $cert_password,
                'message' => 'Certificate files successfully uploaded.'
            ]);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
