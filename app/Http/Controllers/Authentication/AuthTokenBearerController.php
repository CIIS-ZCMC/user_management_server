<?php

namespace App\Http\Controllers\Authentication;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Exceptions\InvalidCredentialException;
use App\Http\Resources\v2\SigninResource;
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
            $employee = $data['employee'];

            return (new SigninResource($employee))
                ->additional([
                    'token' => $data['token'],
                    'message' => 'Login successful.'
                ]);
        } catch (InvalidCredentialException $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_FORBIDDEN);
        }
    }

    public function delete(Request $request)
    {
        $user = $request->user;
        $user->accessToken()->delete();

        return response()->json([
            'message' => 'Logout successful.'
        ]);
    }
}   