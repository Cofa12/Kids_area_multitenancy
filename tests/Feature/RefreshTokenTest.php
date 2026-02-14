<?php

namespace Tests\Feature;

use App\Models\LandlordUser;
use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class RefreshTokenTest extends TestCase
{
    use RefreshDatabase;

    private array $headers = [
        'Accept' => 'application/json',
        'x-api-key' => 'qNw0_Is6InOnG1HiDCT2fstk33JGwuD-6ftdGa4d8hn3RcXx5GT86kvTLop6BgZx732rdGWXnqhUhUJGjQU6pr-40PYzLceAX-up8hiDfyPQ1IJcTR84YPC_IBF2FzKr3QIX6LroF-lZYr67cg8-hNiSeK39cJWlAoZjbKUU6FSLOO3-8kW2xmejNSTR3FQBbLpGFgsfmuJra90jbI1dI7SNO9TDqOZgD6kYZYyEdGA684Iri2-mSB-zKvYLON7vJtadbFcpbHkac1F6Iqil7ZsDSJFrQVYLVGt9kYJDkf3wgkgOmRpsOijWeQ9eE63sywD4sGMmckdqZ27kU2cl6A',
    ];

    public function test_landlord_can_refresh_token(): void
    {
        $this->withoutExceptionHandling();
        $this->seed(AdminSeeder::class);
        $user = LandlordUser::on('landlord')->where('email', 'ynsglobalcompany@gmail.com')->first();

        // Generate a refresh token for the landlord user
        $refreshToken = JWTAuth::claims(['token_type' => 'refresh'])->fromUser($user);

        $response = $this->postJson('/api/v1/landlord/refresh-token', [
            'refreshToken' => $refreshToken,
        ], $this->headers);

        $response->assertStatus(JsonResponse::HTTP_OK);
        $response->assertJsonStructure([
            'accessToken',
            'expiresIn',
        ]);
    }

    public function test_tenant_can_refresh_token(): void
    {
        $tenant = \App\Models\Tenant::first();
        $user = User::factory()->create();

        // Generate a refresh token for the tenant user
        $refreshToken = JWTAuth::claims(['token_type' => 'refresh'])->fromUser($user);

        $headers = array_merge($this->headers, ['X-Tenant' => $tenant->domain]);

        $response = $this->postJson('/api/v1/tenant/refresh-token', [
            'refreshToken' => $refreshToken,
        ], $headers);

        $response->assertStatus(JsonResponse::HTTP_OK);
        $response->assertJsonStructure([
            'accessToken',
            'expiresIn',
        ]);
    }

    public function test_refresh_token_fails_with_invalid_token(): void
    {
        $response = $this->postJson('/api/v1/landlord/refresh-token', [
            'refreshToken' => 'invalid-token',
        ], $this->headers);

        $response->assertStatus(JsonResponse::HTTP_UNAUTHORIZED);
    }
}
