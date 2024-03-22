<?php

namespace Test\Integration\Pair;
use GuzzleHttp\Exception\RequestException;
use Test\Integration\TestCase;

final class UpdatePlanTest extends TestCase
{
    public function testAdminCanUpdatePlan()
    {
        try{
            $this->initialize("running test admin can update plan");
        
            $token = $this->authenticate(only_token: true);
        
            // Create a new plan to update
            $planRandomName = $this->faker->currencyCode . $this->faker->currencyCode . '  Test Plan ' . $this->faker->uuid;
            $planRandomDescription = "Description for " . $planRandomName;
        
            $response = $this->makeRequest("POST", "plans", [
                'name' => $planRandomName,
                'description' => $planRandomDescription,
                'price' => 5.7,
                'status' => 'enabled',
                'features' => 'two pairs, three years of data, two indicators per session',
            ], header: ["Authorization" => "Bearer " . $token]);
        
            $this->assertNotNull($response);
    
            $responseData = json_decode($response->getBody()->getContents(), true);
        
            $this->assertArrayHasKey('message', $responseData);
            $this->assertEquals('plan creation successful', $responseData['message']);
        
            $this->assertArrayHasKey('data', $responseData);
            $this->assertEquals($planRandomName, $responseData['data']['name']);
            $this->assertEquals($planRandomDescription, $responseData['data']['description']);
        
            // Get the ID of the created plan
            $planId = $responseData['data']['id'];
        
            // Generate new random data for the update
            $planRandomNameUpdate = $this->faker->currencyCode . $this->faker->currencyCode . ' Updated Test Plan ' . $this->faker->uuid;
            $planRandomDescriptionUpdate = "Updated description for " . $planRandomNameUpdate;
        
            // Make the update request
            $response = $this->makeRequest("PUT", "plans/{$planId}", [
                'name' => $planRandomNameUpdate,
                'description' => $planRandomDescriptionUpdate,
            ], header: ["Authorization" => "Bearer " . $token]);
    
        // $this->assertNotNull($response);
    
    } catch (\Exception $e) {
        if ($e instanceof RequestException) {
            $this->assertSame(404, $e->getCode());
            $response = $e->getResponse();
            $body = json_decode($response->getBody()->getContents(), true);

            $this->assertEquals(404, $response->getStatusCode());
            $this->assertArrayHasKey("message", $body);
            $this->assertEquals("Plan not found or unable to update", $body["message"]);        
        } else {
            throw $e;
        }
    }
    }
    




    public function testUserCannotUpdatePlan()
{
    $this->initialize("running test user cannot update plan");

    try {
        // Authenticate as a regular user
        $token = $this->authenticate(true, "user@fxtester.com.ng", "123456789");

        // Create a plan using the admin user
        $adminToken = $this->authenticate(true, env('TEST_USER'), env('TEST_PASS'));
        $planRandomName = $this->faker->currencyCode . $this->faker->currencyCode . '  Test Plan ' . $this->faker->uuid;
        $planRandomDescription = "Description for " . $planRandomName;

        $response = $this->makeRequest("POST", "plans", [
            'name' => $planRandomName,
            'description' => $planRandomDescription,
            'price' => 5.7,
            'status' => 'enabled',
            'features' => 'two pairs, three years of data, two indicators per session',
        ], header: ["Authorization" => "Bearer " . $adminToken]);

        $responseData = json_decode($response->getBody()->getContents(), true);
        $planId = $responseData['data']['id'];

        // Attempt to update the plan with the user token
        $response = $this->makeRequest("PUT", "plans/{$planId}", [
            'name' => 'New Plan Name',
            'description' => 'New Plan Description',
        ], header: ["Authorization" => "Bearer " . $token]);

    } catch (\Exception $e) {
        if ($e instanceof RequestException) {
            $this->assertSame(401, $e->getCode());
            $response = $e->getResponse();
            $body = json_decode($response->getBody()->getContents(), true);

            $this->assertEquals(401, $response->getStatusCode());
            $this->assertArrayHasKey("message", $body);
            $this->assertEquals("you can't update a plan", $body["message"]);        
        } else {
            throw $e;
        }
    }
}



    
}

