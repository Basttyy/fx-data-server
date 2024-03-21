<?php

namespace Test\Integration\Pair;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\StreamInterface; 
use Psr\Http\Message\ResponseInterface;
// use Exception;
use Test\Integration\TestCase;

final class ShowPairTest extends TestCase
{
    
    public function testShow()
    {
        $this->initialize("running test show pair");

        // try {
            // Authenticate the user and get the token
            $token = $this->authenticate(only_token: true);

            // Create a new pair to retrieve
            $pairRandomName = $this->faker->currencyCode . $this->faker->currencyCode . '  Test Pair ' . $this->faker->uuid;
            $pairRandomDescription = "Description for " . $pairRandomName;

            $tempResponse = $this->makeRequest("POST", "pairs", [
                'name' => $pairRandomName,
                'description' => $pairRandomDescription,
                'status' => 'enabled',
                'dollar_per_pip' => 0.1,
                'history_start' => '2024-01-01',
                'history_end' => '2024-12-31',
                'exchange' => 'Test Exchange',
                'market' => 'fx',
                'short_name' => 'TP',
                'ticker' => 'TEST',
                'price_precision' => 2,
                'volume_precision' => 2,
                'price_currency' => 'USD',
                'type' => 'test',
                'logo' => 'test_logo_url'
            ], header: ["Authorization" => "Bearer " . $token]);

            // Assert that the response is not null
            $this->assertNotNull($tempResponse);

            $tempResponseData = json_decode($tempResponse->getBody()->getContents(), true);
            $this->assertArrayHasKey('message', $tempResponseData);
            $this->assertEquals('pair creation successful', $tempResponseData['message']);
            $this->assertArrayHasKey('data', $tempResponseData);



            // Get the id of the created pair
            $this->initialize("running test to show pair -- SHOWING NEWLY CREATED PAIR ID::" . $tempResponseData['data']['id']);
            $token = $this->authenticate(only_token: true);

            $pairId = $tempResponseData['data']['id'];
            $response = $this->makeRequestAndParse("GET", "pairs/{$pairId}", header: [
                "Authorization" => "Bearer " . $token
            ]);

            $responseData2 = json_encode($response);
            $this->initialize("running test show" . $responseData2);
            
            
            // $this->assertNotNull($response);

            $this->assertEquals(200, $response['status_code']);
            $this->assertArrayHasKey('data', $response['body']);
                        
            // $responseData = json_decode($response->getBody()->getContents(), true);

            $this->initialize("running test show" . $response['message']);
            $this->initialize("running test show" . $pairRandomName);
            $this->initialize("running test show" . $pairRandomDescription);
            $this->initialize("running test show" . $pairId);

            // $this->assertArrayHasKey('message', $response);
            
            $this->assertEquals('pair retrieved successfully', $response['body']['message']);
            $this->assertArrayHasKey('data', $response['body']);
            $this->assertEquals($pairRandomName, $response['body']['data']['name']);
            $this->assertEquals($pairRandomDescription, $response['body']['data']['description']);
        // } catch (\Exception $e) {
        //     // Handle any exceptions here
        //     $this->fail("An unexpected exception occurred: {$e->getMessage()}");
        // }
    }

}

