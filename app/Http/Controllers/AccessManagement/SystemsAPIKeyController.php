<?php

namespace App\Http\Controllers\AccessManagement;

use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use App\Http\Resources\SystemResource;
use App\Models\System;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpFoundation\Response;

class SystemsAPIKeyController extends Controller
{
    /**
     * GENERATE API KEY
     * this end point expect for an System ID
     * The ID will be validated if it is has a record in the system record
     * if TRUE then the system will generate a API Key that will be encrypted before storing in the System Details Record.
     */
    public function store(Request $request): SystemResource
    {
        $system_id = $request->query('id');
        
        $system = System::find($system_id);

        if(!$system){
            return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
        }

        $apiKey = base64_encode(random_bytes(32));

        $encrypted_api_key = Crypt::encrypt($apiKey);
        $system -> api_key = $encrypted_api_key;
        $system -> updated_at = now();
        $system -> save();

        return (new SystemResource($system))
            ->additional([
                'meta' => [
                    "methods" => '[POST,DELETE]'
                ],
                'message' => "Successfully generate API key."
                ]);
    }

    public function destroy(Request $request)
    {
        $system_id = $request->query('id');
        
        $system = System::find($system_id);

        if(!$system){
            return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
        }

        $system->update(['api_key' => null]);

        return (new SystemResource($system))
            ->additional([
                'meta' => [
                    "methods" => '[POST,DELETE]'
                ],
                'message' => "Successfully delete system API key."
                ]);
    }
}
