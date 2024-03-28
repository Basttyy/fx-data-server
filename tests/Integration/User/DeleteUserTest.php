<?php

namespace Test\Integration\User;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\StreamInterface; 
use Psr\Http\Message\ResponseInterface;
// use Exception;
use Test\Integration\TestCase;

final class DeleteUserTest extends TestCase
{
    // public function testDeleteUser()
    // {
    //     $this->initialize("running test to delete user -- CREATING USER TO BE DELETED");
    //     try {
    //         $token = $this->authenticate(only_token: true);

    //         $userRandomEmail = $this->faker->unique()->safeEmail;
    //         $userRandomFirstName = $this->faker->firstName;
    //         $userRandomLastName = $this->faker->lastName;
    //         $userRandomUserName = $this->faker->userName;
    //         $userRandomPassword = $this->faker->regexify('[A-Za-z0-9]{8,16}');

    //         // Assert that the password meets certain criteria, such as minimum length
    //         $this->assertGreaterThanOrEqual(8, strlen($userRandomPassword), 'Password length is at least 8 characters');

    //         $response = $this->makeRequest("POST", "users", [
    //             'email' => $userRandomEmail,
    //             'password' => $userRandomPassword,
    //             'firstname' => $userRandomFirstName,
    //             'lastname' => $userRandomLastName,
    //             'username' => $userRandomUserName,
    //         ], ["Authorization" => "Bearer " . $token]);

    //         $this->assertNotNull($response);

    //         $responseData = json_decode($response->getBody()->getContents(), true);

    //         $this->assertArrayHasKey('message', $responseData);
    //         $this->assertEquals('user creation successful', $responseData['message']);

    //         $this->assertArrayHasKey('data', $responseData);

    //         $userId = $responseData['data']['id'];

    //         $this->initialize("running test to delete user -- DELETING NEWLY CREATED USER ID::" . $userId);
    //         $token = $this->authenticate(only_token: true);

    //         $response = $this->makeRequest("DELETE", "users/{$userId}", header: [
    //             "Authorization" => "Bearer " . $token
    //         ]);

    //         $this->assertEquals(200, $response['status_code']);
    //         $this->assertArrayHasKey('data', $response['body']);

    //     } catch (\Exception $e) {
    //         $this->fail('An exception occurred: ' . $e->getMessage());
    //     }
    // }

    
    public function testDeleteUser()
    {
        // CREATING USER TO BE DELETED
        $this->initialize("running test to delete user -- CREATING USER TO BE DELETED");
        // try {
            $token = $this->authenticate(only_token: true);

            $userRandomEmail = $this->faker->unique()->safeEmail;
            $userRandomFirstName = $this->faker->firstName;
            $userRandomLastName = $this->faker->lastName;
            $userRandomUserName = $this->faker->userName;
            $userRandomPassword = $this->faker->regexify('[A-Za-z0-9]{8,16}');

            // Assert that the password meets certain criteria, such as minimum length
            $this->assertGreaterThanOrEqual(8, strlen($userRandomPassword), 'Password length is at least 8 characters');

            $tempResponse = $this->makeRequest("POST", "users", [
                'email' => $userRandomEmail,
                'password' => $userRandomPassword,
                'firstname' => $userRandomFirstName,
                'lastname' => $userRandomLastName,
                'username' => $userRandomUserName,
            ], ["Authorization" => "Bearer " . $token]);

            // Assert that the response is not null
            $this->assertNotNull($tempResponse);

            // Decode the response JSON content into an array. Assert that the response data matches the expected data
            $responseData = json_decode($tempResponse->getBody()->getContents(), true);

            // Assert that the response contains a 'message' key with value 'user creation successful'
            $this->assertArrayHasKey('message', $responseData);
            $this->assertEquals('user creation successful', $responseData['message']);

            // Assert that the response contains a 'user' key with expected user data
            $this->assertArrayHasKey('data', $responseData);
            $this->assertEquals($userRandomEmail, $responseData['data']['email']);
            $this->assertEquals($userRandomFirstName, $responseData['data']['firstname']);
            $this->assertEquals($userRandomLastName, $responseData['data']['lastname']);
            $this->assertEquals($userRandomUserName, $responseData['data']['username']);

            // DELETING NEWLY CREATED USER
            $this->initialize("running test to delete user -- DELETING NEWLY CREATED USER ID::" . $responseData['data']['id']);
            $token = $this->authenticate(only_token: true);
            $userId = $responseData['data']['id'];
            $response = $this->makeRequestAndParse("DELETE", "users/{$userId}", header: [
                "Authorization" => "Bearer " . $token
            ]);

            // Check if the deletion was successful
            $this->assertEquals(401, $response['status_code']);
            // $this->assertArrayHasKey('data', $response['body']);
            $this->assertEquals("you can't delete this user", $response['body']['message']);
            // $this->assertEquals('user deleted successfull', $response['body']['message']);
            
            // $this->assertEquals(200, $response['status_code']);
            // $this->assertArrayHasKey('data', $response['body']);
            // $this->assertEquals('user deleted successfull', $response['body']['message']);

        // } catch (\Exception $e) {
        //     $this->fail($e->getMessage());
        // }
    }


   

}

