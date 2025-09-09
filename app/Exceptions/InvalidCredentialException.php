<?php

namespace App\Exceptions;

use Exception;

class InvalidCredentialException extends Exception
{
    public function __construct($message = "Invalid credentials.")
    {
        parent::__construct($message);
    }

    public function render($request)
    {
        return response()->json([
            'message' => $this->getMessage()
        ], Response::HTTP_FORBIDDEN);
    }
}
