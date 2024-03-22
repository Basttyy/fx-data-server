<?php

namespace Test\Integration\Pair;
use GuzzleHttp\Exception\RequestException;
use Test\Integration\TestCase;

final class UpdatePairTest extends TestCase
{
    public function testAdminCanUpdatePair()
    {
        $this->initialize("running test admin can update pair -- CREATING PAIR TO BE UPDATED");

        // Authenticate the user and get the token
        $token = $this->authenticate(only_token: true);

        // Create a new pair to update
        $pairRandomName = $this->faker->currencyCode . $this->faker->currencyCode . '  Test Pair to be updated ' . $this->faker->uuid;
        $pairRandomDescription = "Description for Test Pair to be Updated " . $pairRandomName; 

        $response = $this->makeRequest("POST", "pairs", [
            'name' => $pairRandomName,
            'description' => $pairRandomDescription,
            'status' => 'enabled',
            'dollar_per_pip' => 0.2,
            'history_start' => '2024-03-01',
            'history_end' => '2024-12-31',
            'exchange' => 'Test Exchange',
            'market' => 'fx',
            'short_name' => 'TP',
            'ticker' => 'TEST',
            'price_precision' => 4,
            'volume_precision' => 3,
            'price_currency' => 'USD',
            'type' => 'test',
            'logo' => 'test_logo_url'
        ], header: ["Authorization" => "Bearer " . $token]);
  
        // Assert that the response is not null
        $this->assertNotNull($response);

        // Decode the response JSON content into an array
        $responseData = json_decode($response->getBody()->getContents(), true);

        
        
        // Get the ID of the created pair
        $pairId = $responseData['data']['id'];

        // Generate new random data for the update
        $pairRandomNameUpdate = $this->faker->currencyCode . $this->faker->currencyCode . ' Updated Test Pair ' . $this->faker->uuid;
        $pairRandomDescriptionUpdate = "Updated description for Test Pair " . $pairRandomNameUpdate;


        // Make the update request
        $response = $this->makeRequest("PUT", "pairs/{$pairId}", [
            'name' => $pairRandomNameUpdate,
            'description' => $pairRandomDescriptionUpdate,
        ], header: ["Authorization" => "Bearer " . $token]);

        // Assert that the response is not null
        $this->assertNotNull($response);

        // Decode the response JSON content into an array
        $responseData = json_decode($response->getBody()->getContents(), true);

        // Assert that the response contains a 'message' key with value 'pair update successful'
        $this->assertArrayHasKey('message', $responseData);
        $this->assertEquals('pair updated successfully', $responseData['message']);

        // Assert that the response contains a 'pair' key with updated pair data
        $this->assertArrayHasKey('data', $responseData);
        $this->assertEquals($pairRandomNameUpdate, $responseData['data']['name']);
        $this->assertEquals($pairRandomDescriptionUpdate, $responseData['data']['description']);
    }


    public function testUserCannotUpdatePair()
    {
        $this->initialize("running test user cannot update pair");

        try {
            $token = $this->authenticate(true, "user@fxtester.com.ng", "123456789");

            // Create a pair using the admin user
            $adminToken = $this->authenticate(true, env('TEST_USER'), env('TEST_PASS'));
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
                'market' => 'fx',
                'short_name' => 'TP',
                'ticker' => 'TEST',
                'price_precision' => 2,
                'volume_precision' => 2,
                'price_currency' => 'USD',
                'type' => 'test',
                'logo' => 'test_logo_url'
            ], header: ["Authorization" => "Bearer " . $adminToken]);

            $responseData = json_decode($response->getBody()->getContents(), true);
            $pairId = $responseData['data']['id'];

            // Attempt to update the pair with the user token
            $response = $this->makeRequest("PUT", "pairs/{$pairId}", [
                'name' => 'New Name',
                'description' => 'New Description',
            ], header: ["Authorization" => "Bearer " . $token]);

        } catch (\Exception $e) {
            if ($e instanceof RequestException) {
                $this->assertSame(401, $e->getCode());
                $response = $e->getResponse();
                $body = json_decode($response->getBody()->getContents(), true);

                $this->assertEquals(401, $response->getStatusCode());
                $this->assertArrayHasKey("message", $body);
                $this->assertEquals("you don't have this permission", $body["message"]);        
            } else {
                throw $e;
            }
        }
    }


    
}

