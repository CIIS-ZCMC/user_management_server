<?php 

namespace App\Services\Auth;

use App\Exceptions\InvalidCredentialException;
use App\Models\EmployeeProfile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;

class LoginService
{
    public function handle($credentials)
    {
        $employee_profile = EmployeeProfile::where('employee_id', $credentials['employee_id'])->first();

        if (!$employee_profile) {
            throw new InvalidCredentialException("Account not found.");
        }

        $decryptedPassword = Crypt::decryptString($employee_profile['password_encrypted']);

        if (!Hash::check($credentials['password'] . config("app.salt_value"), $decryptedPassword)) {
            throw new InvalidCredentialException("Employee id or password incorrect.");
        }

        $token = $employee_profile->generateSession();

        return [
            'employee' => $employee_profile,
            'token' => $token
        ];
    }
}
