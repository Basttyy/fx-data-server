<?php

namespace Test\Integration\Pair;
use GuzzleHttp\Exceptions\RequestException;
use Psr\Http\Message\StreamInterface; 
use Psr\Http\Message\ResponseInterface;
use Exception;
use Test\Integration\TestCase;
// use PHPUnit\Framework\Testcase;

final class CreateTest extends TestCase
{
    // public function testAdminCanCreate()
    // {
    //     // Initialize the test case with a description
    //     $this->initialize("Testing pair creation");

    //     try {
    //         // Make a POST request to create a pair
    //         // $response = $this->makeRequest("POST", "/api/pairs", [
    //         //     'name' => 'Test Pair',
    //         //     'description' => 'Test Pair Description'
    //         // ]);
    //         $response = $this->makeRequest("POST", "pairs");
            

    //         //Assert that the response is not null
    //         $this->assertNotNull($response);

    //         // Decode the response JSON content into an array. Assert that the response data matches the expected data
    //         $responseData = json_decode($response->getBody()->getContents(), true);

    //         // Assert that the response contains a 'message' key with value 'pair creation successful'
    //         $this->assertArrayHasKey('message', $responseData);
    //         $this->assertEquals('pair creation successful', $responseData['message']);

    //         // Assert that the response contains a 'pair' key with expected pair data
    //         $this->assertArrayHasKey('pair', $responseData);
    //         $this->assertEquals('Test Pair', $responseData['pair']['name']);
    //         $this->assertEquals('Test Pair Description', $responseData['pair']['description']);
    //     } catch (Exception $e) {
    //         // If an exception occurs, fail the test with the exception message
    //         $this->fail($e->getMessage());
    //     }
    // }

    public function testAdminCanCreatePair()
    {
        $this->initialize("running test admin can create pair");
        $response = $this->makeRequest("POST", "/admin/create/pair");

        $this->assertEquals(200, $response['status_code']);
        $this->assertArrayHasKey('data', $response['body']);
        $this->assertEquals('Test Pair', $response['pair']['name']);
        $this->assertEquals('Test Pair Description', $response['pair']['description']);       
    }

    public function testUserCanCreate()
    {
        $this->initialize("Running test for user creating pair");

        // Assume you have a regular user authenticated
        // $userToken = $this->authenticateUser();
        $UserToken = $this->authenticate(true);
            // Make a POST request to create a pair as a regular user
            $response = $this->makeRequest("POST", "/user/create/pairs");

            // Assert that the response status code is 200 (OK) or any other success status code
            $this->assertEquals(200, $response->getStatusCode());

            $responseData = json_decode($response->getBody()->getContents(), true);
            $this->assertEquals('pair creation successful', $responseData['message']);
            $this->assertEquals('Test Pair', $responseData['pair']['name']);
            $this->assertEquals('Test Pair Description', $responseData['pair']['description']);
    }


    public function testBadCredentialsGives401()
    {
        $this->initialize("Running test for bad credentials giving 401");

        $response = $this->makeRequest("POST", "/some/bad/credentials");
        $this->assertSame(401, $e->getCode());
        $response = $e->getResponse();
        $body = json_decode($response->getBody()->getContents(), true);

        $this->assertIsArray($response);
        $this->assertArrayHasKey("message", $body);
        $this->assertArrayHasKey('status_code', $response);
        $this->assertEquals(401, $response['status_code']);
    }

    public function testWrongBodyParameters()
    {
        $this->initialize("Running test for wrong body parameters return 422");

        $response = $this->makeRequest("GET", "/wrong/body/parameters");
        $this->assertEquals(422, $response->getStatusCode());
        $response = $e->getResponse();
        $body = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayHasKey('message', $body);
        $this->assertEquals('Invalid request body', $body['message']);
    }



}

