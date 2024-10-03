<?php

namespace Test\Integration\Auth;
use Exception;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Test\Integration\TestCase;
use Psr\Http\Message\ResponseInterface;

use function React\Async\await;

final class RefreshTokenTest extends TestCase
{
    private string $token="anything";

    public function testCorrectRefreshToken() :void
    {
        $this->initialize("running test correct refresh token");
        // $this->firebase_token = $this->getFirebaseToken(); // user has to login before refreshing token
        $this->token = $this->authenticate(true);
        $refresh_response = $this->refreshToken();
        $this->assertIsArray($refresh_response);
        $this->assertEquals(200, $refresh_response["status_code"]);
        $this->assertArrayHasKey("data", $refresh_response['body']);
    }

    public function testWrongRefreshToken() : void{
        $this->initialize("running test wrong refresh token");
        $refresh_response = $this->refreshToken();
        $this->assertIsArray($refresh_response);
        $this->assertNotEquals(200, $refresh_response["status_code"]);
        $this->assertArrayNotHasKey("data", $refresh_response['body']);
        $this->assertArrayHasKey("message", $refresh_response['body']);
        $this->assertStringContainsStringIgnoringCase("unauthorized", $refresh_response['body']['message']);
    }

    private function refreshToken() : array|string
    {
        try {
            $response = $this->makeRequest("get","auth/refresh-token", header:["Authorization"=> "Bearer ".$this->token]);
            // echo $response->getBody()->getContents();
        } catch (Exception $err) {
            // echo $err->getMessage();
            if ($err instanceof RequestException) {
                $response = $err->getResponse();
                return [
                    'body' => json_decode($response->getBody(), true),
                    'headers' => $response->getHeaders(),
                    'status_code' => $response->getStatusCode()
                ];
            }
            throw $err;
            // echo $response->getBody()->getContents();
        }
        return [
            'body' => json_decode($response->getBody(), true),
            'headers' => $response->getHeaders(),
            'status_code' => $response->getStatusCode()
        ];
    }
}
