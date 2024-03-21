<?php

namespace Test\Integration\Pair;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\StreamInterface; 
use Psr\Http\Message\ResponseInterface;
// use Exception;
use Test\Integration\TestCase;

final class ListUserTest extends TestCase
{
    public function testListAllUsers()
    {
        $this->initialize("running test to list all users");
        $token = $this->authenticate(only_token: true);
        $response = $this->makeRequestAndParse("GET", "users/list", header: [
            "Authorization" => "Bearer " . $token
        ]);
        $this->assertEquals(200, $response['status_code']);
        $this->assertArrayHasKey('data', $response['body']);;
    }

    public function testListSingleUser()
    {
        $this->initialize("running test to list one/single user");
        $token = $this->authenticate(only_token: true);
        $userId = 1;
        $response = $this->makeRequestAndParse("GET", "users/" . $userId, header: [
            "Authorization" => "Bearer " . $token
        ]);
        $this->assertEquals(200, $response['status_code']);
        $this->assertArrayHasKey('data', $response['body']);
    }

}

