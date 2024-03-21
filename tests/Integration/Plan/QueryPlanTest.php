<?php

namespace Test\Integration\Pair;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\StreamInterface; 
use Psr\Http\Message\ResponseInterface;
// use Exception;
use Test\Integration\TestCase;

final class QueryPlanTest extends TestCase
{
    // public function testQuery()
    // {
    //     $this->initialize("running test query");

    //     try {
    //         // Set up test data in $_GET
    //         $_GET['searchstring'] = 'USD';
    //         $_GET['exchange'] = 'Test Exchange';
    //         $_GET['market'] = 'fx';

    //         // Make the request to the query endpoint
    //         $response = $this->makeRequest("GET", "query/1", $_GET);

    //         $this->assertNotNull($response);
    //         $responseData = json_decode($response->getBody()->getContents(), true);
    //         $this->assertArrayHasKey('message', $responseData);
    //         $this->assertEquals('no pair found in list1', $responseData['message']);

    //         $this->assertArrayHasKey('data', $responseData);
    //         $this->assertEmpty($responseData['data']);

    //     } catch (\Exception $e) {
    //         $this->fail("An unexpected exception occurred: {$e->getMessage()}");
    //     }
    // }

    

    

}

