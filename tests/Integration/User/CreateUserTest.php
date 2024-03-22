<?php

namespace Test\Integration\Pair;
use \Exception;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface; 
use Test\Integration\TestCase;

final class CreateUserTest extends TestCase
{
    public function testCreateNewUser()
{
    try {
        $this->initialize("running test create new user");

        // $token = $this->authenticate(only_token: true);

        // Generate random user data
        $userRandomEmail = $this->faker->unique()->safeEmail;
        $userRandomFirstName = $this->faker->firstName;
        $userRandomLastName = $this->faker->lastName;
        $userRandomPassword = $this->faker->regexify('[A-Za-z0-9]{8,16}');

        $this->assertGreaterThanOrEqual(8, strlen($userRandomPassword), 'Password length is at least 8 characters');

        //Construct the request body
        $requestBody = [
            'email' => $userRandomEmail,
            'password' => $userRandomPassword,
            'firstname' => $userRandomFirstName,
            'lastname' => $userRandomLastName
        ];
        

        // Make the request
        $response = $this->makeRequest("POST", "users", $requestBody);

        // Assert that the response is not null
        $this->assertNotNull($response);
        $this->assertEquals(200, $response->getStatusCode());

        // Decode the response JSON content into an array
        $responseData = json_decode($response->getBody()->getContents(), true);

        // Assert that the response contains a 'message' key with value 'user creation successful'
        $this->assertArrayHasKey('message', $responseData);
        $this->assertEquals('user creation successful', $responseData['message']);

        // Assert that the response contains a 'data' key with expected user data
        $this->assertArrayHasKey('data', $responseData);

        $userData = $responseData['data'];

        $this->assertEquals($userRandomEmail, $userData['email']);
        $this->assertEquals($userRandomFirstName, $userData['firstname']);
        $this->assertEquals($userRandomLastName, $userData['lastname']);
        $this->assertTrue(isset($userData['username'])); 

        // Assert that the password is hashed
        $this->assertNotEquals($userRandomPassword, $userData['password']);
        
        // Assert that email2fa_token and email2fa_expire are set
        $this->assertArrayHasKey('email2fa_token', $userData); // Add this assertion
        $this->assertArrayHasKey('email2fa_expire', $userData);

    } catch (\Exception $e) {
        $this->fail('An exception occurred: ' . $e->getMessage());
    }
}


}

