<?php

namespace Test\Integration\Pair;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\StreamInterface; 
use Psr\Http\Message\ResponseInterface;
// use Exception;
use Test\Integration\TestCase;

final class CreateUserTest extends TestCase
{
    public function testCreateNewUser()
    {
        try {
            $this->initialize("running test create new user");
    
            $token = $this->authenticate(only_token: true);
    
            $userRandomEmail = $this->faker->unique()->safeEmail;
            $userRandomFirstName = $this->faker->firstName;
            $userRandomLastName = $this->faker->lastName;
            $userRandomUserName = $this->faker->userName;
            $userRandomPassword = $this->faker->password;
    
            // Assert that the password meets certain criteria, such as minimum length
            $this->assertGreaterThanOrEqual(8, strlen($userRandomPassword), 'Password length is at least 8 characters');
    
            $response = $this->makeRequest("POST", "users", [
                'email' => $userRandomEmail,
                'password' => $userRandomPassword,
                'firstname' => $userRandomFirstName,
                'lastname' => $userRandomLastName,
                'username' => $userRandomUserName,
            ], ["Authorization" => "Bearer " . $token]);
    
            // Assert that the response is not null
            $this->assertNotNull($response);
    
            // Decode the response JSON content into an array. Assert that the response data matches the expected data
            $responseData = json_decode($response->getBody()->getContents(), true);
    
            // Assert that the response contains a 'message' key with value 'user creation successful'
            $this->assertArrayHasKey('message', $responseData);
            $this->assertEquals('user creation successful', $responseData['message']);
    
            // Assert that the response contains a 'user' key with expected user data
            $this->assertArrayHasKey('data', $responseData);
            $this->assertEquals($userRandomFirstName, $responseData['data']['firstname']);
            $this->assertEquals($userRandomLastName, $responseData['data']['lastname']);
            $this->assertEquals($userRandomEmail, $responseData['data']['email']);
            // $this->assertEquals($userRandomPassword, $responseData['data']['password']);
    
            // Optionally, you can also assert that the user was actually created in the database
            // $this->assertDatabaseHas('users', [
            //     'email' => $userRandomEmail,
            //     'name' => $userRandomName
            // ]);
        } catch (\Exception $e) {
            $this->fail('An exception occurred: ' . $e->getMessage());
        }
    }
    




}

