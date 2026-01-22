<?php

namespace App\Payloads;

class SafaricomLoginPayload
{
    private string $userName = 'globtechDCB_API';
    private string $password = 'GLOBTECHDCB_API@ps2932';

    public function getCredentials(): array
    {
        return [
            'username' => $this->userName,
            'password' => $this->password,
        ];
    }

    public function getHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-Requested-With'=>'XMLHttpRequest'
        ];
    }
}
