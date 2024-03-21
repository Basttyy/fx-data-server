<?php

namespace Test\Integration\Pair;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\StreamInterface; 
use Psr\Http\Message\ResponseInterface;
// use Exception;
use Test\Integration\TestCase;

final class DeletePlanTest extends TestCase
{
    // public function testDeletePair()
    // {
    //     // CREATING PAIR TO BE DELETED
    //     $this->initialize("running test to delete one/single pair -- CREATING PAIR TO BE DELETED");

    //     $token = $this->authenticate(only_token: true);
    //     $pairRandomName = $this->faker->currencyCode . $this->faker->currencyCode . '  Test Pair to be deleted ' . $this->faker->uuid;
    //     $pairRandomDescription = "Description for " . $pairRandomName;

    //     $tempResponse = $this->makeRequest("POST", "pairs", [
    //         'name' => $pairRandomName,
    //         'description' => $pairRandomDescription,
    //         'status' => 'enabled',
    //         'dollar_per_pip' => 0.1,
    //         'history_start' => '2024-01-01',
    //         'history_end' => '2024-12-31',
    //         'exchange' => 'Test Exchange',
    //         'market' => 'fx',
    //         'short_name' => 'TP',
    //         'ticker' => 'TEST',
    //         'price_precision' => 2,
    //         'volume_precision' => 2,
    //         'price_currency' => 'USD',
    //         'type' => 'test',
    //         'logo' => 'test_logo_url'
    //     ], header: ["Authorization" => "Bearer " . $token]);

    //     // Assert that the response is not null
    //     $this->assertNotNull($tempResponse);

    //     // Decode the response JSON content into an array. Assert that the response data matches the expected data
    //     $tempResponseData = json_decode($tempResponse->getBody()->getContents(), true);

    //     // Assert that the response contains a 'message' key with value 'pair creation successful'
    //     $this->assertArrayHasKey('message', $tempResponseData);
    //     $this->assertEquals('pair creation successful', $tempResponseData['message']);

    //     // Assert that the response contains a 'pair' key with expected pair data
    //     $this->assertArrayHasKey('data', $tempResponseData);
    //     $this->assertEquals($pairRandomName, $tempResponseData['data']['name']);
    //     $this->assertEquals($pairRandomDescription, $tempResponseData['data']['description']);




    //     // DELETING NEWLY CREATED PAIR 
    //     $this->initialize("running test to delete one/single pair -- DELETING NEWLY CREATED PAIR ID::" . $tempResponseData['data']['id']);
    //     $token = $this->authenticate(only_token: true);
    //     $pairId = $tempResponseData['data']['id'];
    //     $response = $this->makeRequestAndParse("DELETE", "pairs/" . $pairId, header: [
    //         "Authorization" => "Bearer " . $token
    //     ]);

    //     $this->assertEquals(200, $response['status_code']);
    //     $this->assertArrayHasKey('data', $response['body']);
    // }

}

