<?php

namespace Test\Integration\Pair;

use GuzzleHttp\Exception\RequestException;
use Test\Integration\TestCase;

final class CreatePlanTest extends TestCase
{
    public function testAdminCanCreatePlan()
    {
        $this->initialize("running test admin can create plan");

        $token = $this->authenticate(only_token: true);
        
        
        $planRandomName = $this->faker->currencyCode . $this->faker->currencyCode . '  Test Plan ' . $this->faker->uuid;
        $planRandomDescription = "Description for " . $planRandomName;
        

        $response = $this->makeRequest("POST", "plans", [
            'name' => $planRandomName,
            'description' => $planRandomDescription,
            'price' => 5.0,
            'status' => 'enabled',
            'features' => 'four pairs, three years of data, three indicators per session',        
        ], header: ["Authorization" => "Bearer " . $token]);

        $this->assertNotNull($response);

        $responseData = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayHasKey('message', $responseData);
        $this->assertEquals('plan creation successful', $responseData['message']);

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
            $pairRandomName = $this->faker->currencyCode . $this->faker->currencyCode . '  Test plan ' . $this->faker->uuid;
            $pairRandomDescription = "Description for " . $pairRandomName;

            $response = $this->makeRequest("POST", "plans", [
                'name' => $pairRandomName,
                'description' => $pairRandomDescription,
                'price' => 5.0,
                'status' => 'enabled',
                'features' => 'user cannot create plan ', 
            ], header: ["Authorization" => "Bearer " . $token]);

        } catch (\Exception $e) {
            if ($e instanceof RequestException) {
                $this->assertSame(401, $e->getCode());
                $response = $e->getResponse();
                $body = json_decode($response->getBody()->getContents(), true);

                $this->assertEquals(401, $response->getStatusCode());
                $this->assertArrayHasKey("message", $body);
                $this->assertEquals("you can't create a plan", $body["message"]);        
            } else {
                throw $e;
            }
        }
    }
}
