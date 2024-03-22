<?php

namespace Test\Integration\Pair;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\StreamInterface; 
use Psr\Http\Message\ResponseInterface;
// use Exception;
use Test\Integration\TestCase;

final class ShowPlanTest extends TestCase
{
    
    public function testShowPlan()
{
    $this->initialize("running test to show a plan");
    $token = $this->authenticate(only_token: true);

    // Assuming $planId is the ID of a plan that exists in your database
    $planId = 1; // Update with the actual plan ID

    $response = $this->makeRequestAndParse("GET", "plans/$planId", header: [
        "Authorization" => "Bearer " . $token
    ]);

    // Assert the response status code
    $this->assertEquals(200, $response['status_code']);

    // Assert the response message
    $this->assertEquals("plan retrieved success", $response['body']['message']);

    // Assert the response data
    $this->assertArrayHasKey('data', $response['body']);
    $this->assertEquals($planId, $response['body']['data']['id']);
}


}

