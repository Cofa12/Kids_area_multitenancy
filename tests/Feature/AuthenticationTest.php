<?php

namespace Tests\Feature;

use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login()
    {

        $this->seed(AdminSeeder::class);

        $payload = [
            'email' => 'ynsglobalcompany@gmail.com',
            'password' => 'Ju>yg]G9SMt*#$BW105;',
        ];

        // Use the admin/login endpoint for landlord admin
        $sut = $this->postJson('/api/v1/admin/login', $payload, [
            'Accept' => 'application/json',
            'x-api-key' => 'qNw0_Is6InOnG1HiDCT2fstk33JGwuD-6ftdGa4d8hn3RcXx5GT86kvTLop6BgZx732rdGWXnqhUhUJGjQU6pr-40PYzLceAX-up8hiDfyPQ1IJcTR84YPC_IBF2FzKr3QIX6LroF-lZYr67cg8-hNiSeK39cJWlAoZjbKUU6FSLOO3-8kW2xmejNSTR3FQBbLpGFgsfmuJra90jbI1dI7SNO9TDqOZgD6kYZYyEdGA684Iri2-mSB-zKvYLON7vJtadbFcpbHkac1F6Iqil7ZsDSJFrQVYLVGt9kYJDkf3wgkgOmRpsOijWeQ9eE63sywD4sGMmckdqZ27kU2cl6A'
        ]);
        $sut->assertStatus(JsonResponse::HTTP_OK);
        $sut->assertJsonStructure([
            'user' => [
                'name',
                'email',
                'created_at',
            ],
            'access_token',
            'expires_in',
            'refresh_token',
            'refresh_expires_in'
        ]);


        $this->assertIsString($sut->json('access_token'));
        $this->assertIsString($sut->json('refresh_token'));
        $this->assertIsInt($sut->json('expires_in'));
        $this->assertIsInt($sut->json('refresh_expires_in'));
    }

    public function test_should_throw_exception_when_credentials_are_invalid(): void
    {
        $this->seed(AdminSeeder::class);

        $payload = [
            'email' => 'ynsglobalcompany@gmail.com',
            'password' => 'Ju>yg]G9SMt*#$BW105;ff',
        ];

        $sut = $this->postJson('/api/v1/admin/login', $payload, [
            'Accept' => 'application/json',
            'x-api-key' => 'qNw0_Is6InOnG1HiDCT2fstk33JGwuD-6ftdGa4d8hn3RcXx5GT86kvTLop6BgZx732rdGWXnqhUhUJGjQU6pr-40PYzLceAX-up8hiDfyPQ1IJcTR84YPC_IBF2FzKr3QIX6LroF-lZYr67cg8-hNiSeK39cJWlAoZjbKUU6FSLOO3-8kW2xmejNSTR3FQBbLpGFgsfmuJra90jbI1dI7SNO9TDqOZgD6kYZYyEdGA684Iri2-mSB-zKvYLON7vJtadbFcpbHkac1F6Iqil7ZsDSJFrQVYLVGt9kYJDkf3wgkgOmRpsOijWeQ9eE63sywD4sGMmckdqZ27kU2cl6A'
        ]);

        $sut->assertStatus(JsonResponse::HTTP_UNAUTHORIZED);
        $sut->assertJsonStructure([
            'error'
        ]);
        $sut->assertJson([
            'error' => 'Email or password is incorrect'
        ]);
    }
}
