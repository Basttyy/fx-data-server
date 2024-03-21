<?php

namespace Test\Integration\Pair;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\StreamInterface; 
use Psr\Http\Message\ResponseInterface;
// use Exception;
use Test\Integration\TestCase;

final class ListPlaanTest extends TestCase
{
    // public function testListAllPairs()
    // {
    //     $this->initialize("running test to list all pairs");
    //     $token = $this->authenticate(only_token: true);
    //     $response = $this->makeRequestAndParse("GET", "pairs/list/onlypair", header: [
    //         "Authorization" => "Bearer " . $token
    //     ]);
    //     $this->assertEquals(200, $response['status_code']);
    //     $this->assertArrayHasKey('data', $response['body']);;
    // }

    // public function testListSinglePair()
    // {
    //     $this->initialize("running test to list one/single pair");
    //     $token = $this->authenticate(only_token: true);
    //     $pairId = 1;
    //     $response = $this->makeRequestAndParse("get", "pairs/" . $pairId, header: [
    //         "Authorization" => "Bearer " . $token
    //     ]);
    //     $this->assertEquals(200, $response['status_code']);
    //     $this->assertArrayHasKey('data', $response['body']);;
    // }

}

