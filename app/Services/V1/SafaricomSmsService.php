<?php

namespace App\Services\V1;

use App\Models\User;
use App\RequestHandlers\SafaricomLoginRequest;
use Exception;
use Illuminate\Support\Facades\Http;

class SafaricomSmsService
{
    private string $endpoint = 'https://dsvc.safaricom.com:9480/api/public/SDP/sendSMSRequest';

    public function __construct(private SafaricomLoginRequest $request)
    {
    }

    /**
     * Forwards payload to Safaricom SDP sendSMSRequest endpoint via cURL
     *
     * @return bool
     */
    public function send(string $phoneNumber): bool
    {
        $payload = [
            "requestId" => User::max('id') + 1,
            "channel" => "APIGW",
            "operation" => "SendSMS",
            "requestParam" => [
                "data" => [
                    [
                        "name" => "OfferCode",
                        "value" => "001048938129",
                    ],
                    [
                        "name" => "Msisdn",
                        "value" => $phoneNumber,
                    ],
                    [
                        'name' => 'Content',
                        'value' => 'Test Subscription'
                    ],
                    [
                        "name" => "CpId",
                        "value" => "489",
                    ],
//                    [
//                        'name' => 'LinkId',
//                        'value' => ''
//                    ]
                ],
            ],
        ];

        $token = $this->login();
        $ch = curl_init();

        $headers = [
            "cache-control: no-cache",
            "content-type: application/json",
            "x-authorization: Bearer " . $token,
        ];

        curl_setopt_array($ch, [
            CURLOPT_URL => $this->endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HEADER => true,
        ]);

        $rawResponse = curl_exec($ch);

        if ($rawResponse === false) {
            throw new Exception(curl_error($ch));
        }

        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // separate headers from body
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headerRaw = substr($rawResponse, 0, $headerSize);
        $bodyRaw = substr($rawResponse, $headerSize);

        curl_close($ch);

        $body = $this->decodeBody($bodyRaw);

        return $body['responseParam']['status'] === $body['responseParam']['statusCode'];
    }

    public function login(): string
    {
        $requestCredentials = $this->request->getRequestCredentials();
        $response = Http::withHeaders($requestCredentials['headers'])->withBody($requestCredentials['payload'])->post($requestCredentials['url']);
        return $response->json()['token'];
    }

    private function decodeBody(string $raw)
    {
        $decoded = json_decode($raw, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $raw;
    }

    private function parseHeaders(string $headerString): array
    {
        $headers = [];
        foreach (explode("\r\n", $headerString) as $line) {
            if (strpos($line, ":") !== false) {
                [$key, $value] = explode(":", $line, 2);
                $headers[trim($key)] = trim($value);
            }
        }
        return $headers;
    }
}
