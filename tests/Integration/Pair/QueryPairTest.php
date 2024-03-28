<?php

namespace Test\Integration\Pair;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\StreamInterface; 
use Psr\Http\Message\ResponseInterface;
// use Exception;
use Test\Integration\TestCase;

final class QueryPairTest extends TestCase
{

    public function testQuery()
    {
        $this->initialize("running test query");

        try {
        $token = $this->authenticate(only_token: true);

        $id = 1;
        $searchParams = "searchstring";
        $searchTerm = "GBPUSD";

        $response = $this->makeRequest("GET", "pairs/list/pairorsym/" . $id . "/query/?" . $searchParams . "=" . $searchTerm, header: [
            "Authorization" => "Bearer " . $token
            ]);


        $this->assertNotNull($response);

        $responseData = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayHasKey('message', $responseData);
        $this->assertEquals('pairs retrieved success', $responseData['message']);
        $this->assertArrayHasKey('data', $responseData);

        } catch (\Exception $e) {
            $this->fail("An unexpected exception occurred: {$e->getMessage()}");
        }
    }

}

