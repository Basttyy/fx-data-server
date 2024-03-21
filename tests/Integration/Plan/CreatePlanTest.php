<?php

namespace Test\Integration\Pair;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\StreamInterface; 
use Psr\Http\Message\ResponseInterface;
// use Exception;
use Test\Integration\TestCase;

final class CreatePlanTest extends TestCase
{


    public function testAdminCanCreatePlan()
    {
        $this->initialize("running test admin can create plan");

        // $token = $this->authenticate(true, env('TEST_USER'), env('TEST_PASS'));

        $token = $this->authenticate(only_token: true);
        $planRandomName = $this->faker->currencyCode . $this->faker->currencyCode . '  Test Plan ' . $this->faker->uuid;
        $planRandomDescription = "Description for " . $planRandomName;

        // $planRandomName = $this->faker->name;
        // $planRandomDescription = $this->faker->description; 

        $response = $this->makeRequest("POST", "plans", [
            'name' => $planRandomName,
            'description' => $planRandomDescription,
            'price' => 50,
            'status' => 'enabled',
            'features' => 'enabled',        
        ], header: ["Authorization" => "Bearer " . $token]);

        // Assert that the response is not null
        $this->assertNotNull($response);

        // Decode the response JSON content into an array. Assert that the response data matches the expected data
        $responseData = json_decode($response->getBody()->getContents(), true);

        // Assert that the response contains a 'message' key with value 'pair creation successful'
        $this->assertArrayHasKey('message', $responseData);
        $this->assertEquals('plan creation successful', $responseData['message']);

        // Assert that the response contains a 'pair' key with expected pair data
        $this->assertArrayHasKey('data', $responseData);
        $this->assertEquals($planRandomName, $responseData['data']['name']);
        $this->assertEquals($planRandomDescription, $responseData['data']['description']);
    
    }

    public function testUserCannotCreatePlan()
    {
        $this->initialize("running test user cannot create plan");

        try{
            $token = $this->authenticate(true, "user@fxtester.com.ng", "123456789");

            // $token = $this->authenticate(only_token: true);
            $planRandomName = $this->faker->currencyCode . $this->faker->currencyCode . '  Test Plan ' . $this->faker->uuid;
            $planRandomDescription = "Description for " . $planRandomName;

            $response = $this->makeRequest("POST", "plans", [
                'name' => $planRandomName,
                'description' => $planRandomDescription,
                'price' => '50',
                'status' => 'enabled',
                'features' => 'enabled', 
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

