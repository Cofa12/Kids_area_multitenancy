<?php

namespace App\Services\V1;

class RegisterService
{

    public function __construct(private LoginService $loginService, private ExceptionsHandling $exception)
    {
    }

    public function register(array $credentials): array
    {
        $registrationAbstraction = new WebsiteRegisterService($this->exception);

        $registrationAbstraction->register($credentials);

        return $this->loginService->Authenticate([
            'phone' => $credentials['phone'],
            'password' => $credentials['password']
        ]);
    }
}
