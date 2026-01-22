<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Tests\TestCase;

class SafaricomCallbackTest extends TestCase
{
    use RefreshDatabase;

//    public function test_callback_creates_and_activates_user_with_phone_only(): void
//    {
//        $phone = '254700123456';
//
//        $response = $this->postJson('/api/v1/safaricom/callback', $this->fakeCallbackPayload($phone), [
//            'Accept' => 'application/json',
//        ]);
//
//        $response->assertStatus(JsonResponse::HTTP_CREATED);
//        $response->assertJsonStructure([
//            'message'
//        ]);
//
//        $this->assertDatabaseHas('users', [
//            'phone' => $phone,
//            'subscription_status' => true,
//        ]);
//
//    }
//
//    private function fakeCallbackPayload(?string $msisdn = null): array
//    {
//        $data = [
//            ['name' => 'OfferCode', 'value' => 'OFFER123'],
//            ['name' => 'Language', 'value' => 'EN'],
//            ['name' => 'Cpld', 'value' => 'abc123'],
//        ];
//
//        if ($msisdn !== null) {
//            array_splice($data, 1, 0, [[
//                'name' => 'Msisdn',
//                'value' => $msisdn,
//            ]]);
//        }
//
//        return [
//            'requestId' => 123456,
//            'channel' => 'APIGW',
//            'operation' => 'ACTIVATE',
//            'requestParam' => [
//                'data' => $data,
//            ],
//        ];
//    }
//
//    public function test_callback_returns_bad_request_when_msisdn_missing(): void
//    {
//        $response = $this->postJson('/api/v1/safaricom/callback', $this->fakeCallbackPayload(), []);
//        $response->assertStatus(JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
//        $response->assertJsonStructure([
//            'message'
//        ]);
//    }
}
