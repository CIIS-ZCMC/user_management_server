<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Services\RequestLogger;
use Illuminate\Support\Facades\Session;

class CsrfTokenController extends Controller
{
    private $CONTROLLER_NAME = 'CsrfToken';

    protected $requestLogger;

    public function __construct(RequestLogger $requestLogger)
    {
        $this->requestLogger = $requestLogger;
    }

    public function generateCsrfToken(Request $request)
    {
        try{ 
            $ip_address = $request->ip();
            $token = bin2hex(random_bytes(32));
            
            Session::put($ip_address, $token);

            return response()->json(['data' => $token, 'message' => 'New token generated.' ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function validateToken(Request $request)
    {
        try{ 
            $ip_address = $request->ip();
            $token = $request->token;
            
            $csrf_token = Session::get($ip_address);

            if($csrf_token !== $token)
            {
                return response()->json(['message' => 'Invalid token request.'], Response::HTTP_BAD_REQUEST);
            }

            return response()->json(['message' => 'Token Validated. PROCEED Request' ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
