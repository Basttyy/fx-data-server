<?php

namespace Test\Integration\Pair;
use GuzzleHttp\Exceptions\RequestException;
use Psr\Http\Message\StreamInterface; 
use Psr\Http\Message\ResponseInterface;
use Exception;
use Test\Integration\TestCase;
// use PHPUnit\Framework\Testcase;

final class CreateTest extends TestCase
{
    public function testAdminCanCreatePair()
    {
        $this->initialize("running test admin can create pair");

        // $token = $this->authenticate(true, env('TEST_USER'), env('TEST_PASS'));

        $token = $this->authenticate(only_token: true);
        $pairRandomName = $this->faker->currencyCode . $this->faker->currencyCode . '  Test Pair ' . $this->faker->uuid;
        $pairRandomDescription = "Description for " . $pairRandomName;

        $response = $this->makeRequest("POST", "pairs", [
            'name' => $pairRandomName,
            'description' => $pairRandomDescription,
            'status' => 'enabled',
            'dollar_per_pip' => 0.1,
            'history_start' => '2024-01-01',
            'history_end' => '2024-12-31',
            'exchange' => 'Test Exchange',
            'market' => 'forex',
            'short_name' => 'TP',
            'ticker' => 'TEST',
            'price_precision' => 2,
            'volume_precision' => 2,
            'price_currency' => 'USD',
            'type' => 'test',
            'logo' => 'test_logo_url'
        ], header: ["Authorization" => "Bearer " . $token]);

        // Assert that the response is not null
        $this->assertNotNull($response);

        // Decode the response JSON content into an array. Assert that the response data matches the expected data
        $responseData = json_decode($response->getBody()->getContents(), true);

        // Assert that the response contains a 'message' key with value 'pair creation successful'
        $this->assertArrayHasKey('message', $responseData);
        $this->assertEquals('pair creation successful', $responseData['message']);

        // Assert that the response contains a 'pair' key with expected pair data
        $this->assertArrayHasKey('data', $responseData);
        $this->assertEquals($pairRandomName, $responseData['data']['name']);
        $this->assertEquals($pairRandomDescription, $responseData['data']['description']);
    }




}

