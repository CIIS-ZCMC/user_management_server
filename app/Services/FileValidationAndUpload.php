<?php

namespace App\Services;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

class FileValidationAndUpload {

    public function check_save_file($request, $FILE_URL)
    {
        $fileName = '';

        if ($request->file('attachment')->isValid()) {
            $file = $request->file('attachment');
            $filePath = $file->getRealPath();

            $finfo = new \finfo(FILEINFO_MIME);
            $mime = $finfo->file($filePath);
            $mime = explode(';', $mime)[0];

            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];

            if (!in_array($mime, $allowedMimeTypes)) {
                return response()->json(['message' => 'Invalid file type'], Response::HTTP_BAD_REQUEST);
            }

            // Check for potential malicious content
            $fileContent = file_get_contents($filePath);

            if (preg_match('/<\s*script|eval|javascript|vbscript|onload|onerror/i', $fileContent)) {
                return response()->json(['message' => 'File contains potential malicious content'], Response::HTTP_BAD_REQUEST);
            }

            $file = $request->file('attachment');
            $fileName = Hash::make(time()) . '.' . $file->getClientOriginalExtension();

            $file->move(public_path($FILE_URL), $fileName);
        }
        
        return $fileName;
    }
}


