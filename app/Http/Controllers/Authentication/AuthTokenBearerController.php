<?php

namespace App\Http\Controllers\Authentication;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Exceptions\InvalidCredentialException;
use Symfony\Component\HttpFoundation\Response;
use App\Services\Auth\LoginService;

class AuthTokenBearerController extends Controller
{
    public function __construct(
        private LoginService $loginService
    ){}

    public function store(Request $request)
    {
        try {
            /**
             * Fields Needed:
             *  employee_id
             *  password   
             */
            $credentials = $request->only(['employee_id', 'password']);

            $data = $this->loginService->handle($credentials);

            return response()->json([
                'data' => $data,
                'message' => 'Login successful.'
            ]);
        } catch (InvalidCredentialException $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_FORBIDDEN);
        }
    }
}   