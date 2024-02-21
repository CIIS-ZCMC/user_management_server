<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Models\User;
use App\Models\PersonalAccessToken;
use App\Models\System;
use Illuminate\Support\Facades\Auth;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        Auth::viaRequest('custom-bearer-token', function (Request $request) {
            // Custom logic to retrieve the authenticated user based on the request
            // You can replace this with your own implementation

            // For example, if you are using a custom token in the request header

            $encryptedToken = $request->bearerToken();
            $domain = $request->getHost();
            $system = System::where('domain', $domain)->first();

            if (!$encryptedToken) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            // $token = openssl_decrypt($encryptedToken, env("ENCRYPT_DECRYPT_ALGORITHM"), $env("DECRYPT_KEY"), 0, substr(md5(env("DECRYPT_KEY")), 0, 16));

            // $isAuthenticated = PersonalAccessToken::where('accessToken', $token)->where('FK_system_ID', $sytem->id)->first();

            // if (!$isAuthenticated) {
            //     return response()->json(['error' => 'Unauthorized'], 401);
            // }

            // Retrieve the user based on the token or any other authentication mechanism
            // Implement your own logic to authenticate and retrieve the user
            $user = User::where('custom-bearer-token', $token)->first();

            return $user;
        });
    }
}
