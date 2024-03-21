<?php

namespace Test\Integration\Pair;
use Basttyy\FxDataServer\Models\User;
use GuzzleHttp\Exception\RequestException;
use Exception;

use Test\Integration\TestCase;

final class UpdateUserTest extends TestCase
{
    public function testUpdateUser()
    {
        $this->initialize("running test update user");
    
        // Authenticate the user and get the token
        $token = $this->authenticate(only_token: true);
    
        // Create a new user to update
        $userRandomName = $this->faker->firstName . ' ' . $this->faker->lastName;
        $userRandomEmail = $this->faker->unique()->safeEmail;
    
        $response = $this->makeRequest("POST", "user", [
            'email' => $userRandomEmail,
            'password' => '12345678',
            'firstname' => $userRandomName,
            // 'firstname' => $this->faker->firstName,
            // 'lastname' => $this->faker->lastName,
            'lastname' => $userRandomName,
            'username' => $this->faker->userName
        ], header: ["Authorization" => "Bearer " . $token]);
    
        // Assert that the response is not null
        $this->assertNotNull($response);
    
        // Decode the response JSON content into an array
        $responseData = json_decode($response->getBody()->getContents(), true);
    
        // Get the ID of the created user
        $userId = $responseData['data']['id'];
    
        // Generate new random data for the update
        $userRandomNameUpdate = $this->faker->firstName . ' ' . $this->faker->lastName;
        $userRandomEmailUpdate = $this->faker->unique()->safeEmail;
    
        // Make the update request
        $response = $this->makeRequest("PUT", "user/{$userId}", [
            'email' => $userRandomEmailUpdate,
            'firstname' => $userRandomNameUpdate,
            'lastname' => $userRandomNameUpdate,
            // 'firstname' => $this->faker->firstName,
            // 'lastname' => $this->faker->lastName,
            'username' => $this->faker->userName
        ], header: ["Authorization" => "Bearer " . $token]);
    
        // Assert that the response is not null
        $this->assertNotNull($response);
    
        // Decode the response JSON content into an array
        $responseData = json_decode($response->getBody()->getContents(), true);
    
        // Assert that the response contains a 'message' key with value 'user update successful'
        $this->assertArrayHasKey('message', $responseData);
        $this->assertEquals('user updated successfully', $responseData['message']);
    
        // Assert that the response contains a 'user' key with updated user data
        $this->assertArrayHasKey('data', $responseData);
        $this->assertEquals($userRandomNameUpdate, $responseData['data']['name']);
        $this->assertEquals($userRandomEmailUpdate, $responseData['data']['email']);
    }
    
    // public function testUpdateUser()
    // {
    //     $this->initialize("running test update user");

    //     try{
    //             // Authenticate the user and get the token
    //     $token = $this->authenticate(only_token: true);
    
    //     // Create a new user to update
    //     $userRandomEmail = $this->faker->unique()->safeEmail;
    //     $userRandomFirstName = $this->faker->firstName;
    //     $userRandomLastName = $this->faker->lastName;
    //     $userRandomUserName = $this->faker->userName;
    //     $userRandomPassword = $this->faker->password;
    
    //     // Assert that the password meets certain criteria, such as minimum length
    //     // try {
    //         // $this->assertGreaterThanOrEqual(8, strlen($userRandomPassword), 'Password length is at least 8 characters');
    //         $userRandomPassword = $this->faker->regexify('[A-Za-z0-9]{8,16}');
    //     // } catch (\PHPUnit\Framework\AssertionFailedError $e) {
    //     //     $this->fail($e->getMessage());
    //     // }
    
    //     $response = $this->makeRequest("POST", "users", [
    //         'email' => $userRandomEmail,
    //         'password' => $userRandomPassword,
    //         'firstname' => $userRandomFirstName,
    //         'lastname' => $userRandomLastName,
    //         'username' => $userRandomUserName,
    //     ], ["Authorization" => "Bearer " . $token]);
    
    //     // Assert that the response is not null
    //     $this->assertNotNull($response);
    
    //     // Decode the response JSON content into an array
    //     $responseData = json_decode($response->getBody()->getContents(), true);
    
    //     // Get the ID of the created user
    //     $userId = $responseData['data']['id'];
    
    //     // Generate new random data for the update
    //     $userRandomEmailUpdate = $this->faker->unique()->safeEmail;
    //     $userRandomFirstNameUpdate = $this->faker->firstName;
    //     $userRandomLastNameUpdate = $this->faker->lastName;
    //     $userRandomUserNameUpdate = $this->faker->userName;
    //     $userRandomPasswordUpdate = $this->faker->password;
    
    //     // Assert that the password meets certain criteria, such as minimum length
    //     // try {
    //         // $this->assertGreaterThanOrEqual(8, strlen($userRandomPasswordUpdate), 'Password length is at least 8 characters');
    //         $userRandomPasswordUpdate = $this->faker->regexify('[A-Za-z0-9]{8,16}');
    //     // } catch (\PHPUnit\Framework\AssertionFailedError $e) {
    //     //     $this->fail($e->getMessage());
    //     // }
    
    //     // Make the update request
    //     $response = $this->makeRequest("PUT", "users/{$userId}", [
    //         'email' => $userRandomEmailUpdate,
    //         'email' => $userRandomPasswordUpdate,
    //         'firstname' => $userRandomFirstNameUpdate,
    //         'lastname' => $userRandomLastNameUpdate,
    //         'username' => $userRandomUserNameUpdate,
    //     ], header: ["Authorization" => "Bearer " . $token]);
    
    //     // Assert that the response is not null
    //     $this->assertNotNull($response);
    
    //     // Decode the response JSON content into an array
    //     $responseData = json_decode($response->getBody()->getContents(), true);
    
    //     // Assert that the response contains a 'message' key with value 'user update successful'
    //     $this->assertArrayHasKey('message', $responseData);
    //     $this->assertEquals('user update successful', $responseData['message']);
    
    //     // Assert that the response contains a 'user' key with updated user data
    //     $this->assertArrayHasKey('data', $responseData);
    //     $this->assertEquals($userRandomFirstNameUpdate, $responseData['data']['firstname']);
    //     $this->assertEquals($userRandomLastNameUpdate, $responseData['data']['lastname']);
    //     $this->assertEquals($userRandomEmailUpdate, $responseData['data']['email']);
    //     }catch (\Exception $e) {
    //         $this->fail('An exception occurred: ' . $e->getMessage());
    //     }
    
    
    // }
    
    




    
}

