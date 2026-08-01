<?php

namespace Tests\Unit;

use App\Http\Controllers\V1\LandingPage;
use App\Services\V1\LoginService;
use App\Services\V1\SubscriptionHandling;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class HeaderEnrichmentUnitTest extends TestCase
{
    private SubscriptionHandling $subscriptionHandlingMock;
    private LoginService $loginServiceMock;
    private LandingPage $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subscriptionHandlingMock = Mockery::mock(SubscriptionHandling::class);
        $this->loginServiceMock = Mockery::mock(LoginService::class);
        $this->controller = new LandingPage($this->subscriptionHandlingMock, $this->loginServiceMock);
    }

    public function test_he_entry_redirects_to_guest_when_x_msisdn_header_is_missing(): void
    {
        $request = Request::create('http://backend.kids-station.com.ng/api/v1/mtn/he/entry', 'GET');

        $response = $this->controller->heEntry($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('https://kids-station.com.ng/guest', $response->getTargetUrl());
    }

    public function test_he_entry_redirects_to_guest_when_x_msisdn_header_is_empty(): void
    {
        $request = Request::create('http://backend.kids-station.com.ng/api/v1/mtn/he/entry', 'GET');
        $request->headers->set('X-MSISDN', '');

        $response = $this->controller->heEntry($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('https://kids-station.com.ng/guest', $response->getTargetUrl());
    }

    public function test_he_entry_reads_case_insensitive_x_msisdn_header(): void
    {
        $request = Request::create('http://backend.kids-station.com.ng/api/v1/mtn/he/entry', 'GET');
        $request->headers->set('x-msisdn', '2348000000000');

        $response = $this->controller->heEntry($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('https://kids-station.com.ng/guest', $response->getTargetUrl());
    }

    public function test_he_echo_returns_json_response_with_received_headers(): void
    {
        $request = Request::create('http://backend.kids-station.com.ng/api/v1/mtn/he/echo', 'GET');
        $request->headers->set('x-msisdn', '2348036800903');

        $response = $this->controller->heEcho($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertTrue($data['success']);
        $this->assertEquals('2348036800903', $data['headersReceived']['x-msisdn']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
