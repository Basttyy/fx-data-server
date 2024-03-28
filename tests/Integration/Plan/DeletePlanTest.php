<?php

namespace Test\Integration\Plan;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\StreamInterface; 
use Psr\Http\Message\ResponseInterface;
// use Exception;
use Test\Integration\TestCase;

final class DeletePlanTest extends TestCase
{
    public function testDeletePair()
    {
        $this->initialize("running test to delete one/single pair");

        $token = $this->authenticate(only_token: true);

        // Create a new plan to be deleted
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

        // DELETING NEWLY CREATED PLAN
        $this->initialize("running test to delete one/single plan ID:: " . $responseData['data']['id']);
        $pairId = $responseData['data']['id'];
        $response = $this->makeRequestAndParse("DELETE", "plans/" . $pairId, header: [
            "Authorization" => "Bearer " . $token
        ]);

        // Check if the deletion was successful
        $this->assertEquals(200, $response['status_code']);
        $this->assertArrayHasKey('message', $response['body']);
        $this->assertEquals('plan deleted successfull', $response['body']['message']);
    }


}

