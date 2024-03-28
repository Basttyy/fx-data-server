<?php

namespace Test\Integration\Plan;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\StreamInterface; 
use Psr\Http\Message\ResponseInterface;
// use Exception;
use Test\Integration\TestCase;

final class ListPlanTest extends TestCase
{
    public function testListAllPlans()
    {
        $this->initialize("running test to list all plans");
        $token = $this->authenticate(only_token: true);
        $response = $this->makeRequestAndParse("GET", "plans/list", header: [
            "Authorization" => "Bearer " . $token
        ]);
        $this->assertEquals(200, $response['status_code']);
        $this->assertArrayHasKey('data', $response['body']);;
    }

    
    public function testListSinglePlan()
    {
        $this->initialize("running test to list one/single plan");
        $token = $this->authenticate(only_token: true);
        $pairId = 1;
        $response = $this->makeRequestAndParse("GET", "plans/" . $pairId, header: [
            "Authorization" => "Bearer " . $token
        ]);
        $this->assertEquals(200, $response['status_code']);
        $this->assertArrayHasKey('data', $response['body']);;
    }

}

