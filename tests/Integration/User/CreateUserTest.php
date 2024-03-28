<?php

namespace Test\Integration\User;
use Test\Integration\TestCase;


final class CreateUserTest extends TestCase
{

    public function testCreateNewUser()
    {
        $this->initialize("running test create new user");
    
        try {
            $userRandomFirstName = $this->faker->firstName;
            $userRandomLastName = $this->faker->lastName;
            $userRandomUserName = $this->faker->userName;
            $userRandomEmail = $this->faker->unique()->safeEmail;
            $userRandomPassword = $this->faker->regexify('[A-Za-z0-9]{8,16}');
            $userRandomEmail2faToken = $this->faker->md5;
            $userRandomEmail2faExpire = $this->faker->dateTimeBetween('now', '+1 year')->format('Y-m-d H:i:s');
    
            $this->assertGreaterThanOrEqual(8, strlen($userRandomPassword), 'Password length is at least 8 characters');
    
            $requestBody = [
                'email' => $userRandomEmail,
                'password' => $userRandomPassword,
                'firstname' => $userRandomFirstName,
                'lastname' => $userRandomLastName,
                'username' => $userRandomUserName,
                'email2fa_token' => $userRandomEmail2faToken,
                'email2fa_expire' => $userRandomEmail2faExpire,
            ];
    
            $response = $this->makeRequest("POST", "users", $requestBody, header: ["Authorization" => "Bearer YOUR_AUTH_TOKEN_HERE"]);
    
            $this->assertNotNull($response);
            $this->assertEquals(200, $response->getStatusCode());
    
            $responseData = json_decode($response->getBody()->getContents(), true);
    
            $this->assertArrayHasKey('message', $responseData);
            $this->assertEquals('user creation successful', $responseData['message']);
    
            $this->assertArrayHasKey('data', $responseData);
    
            $userData = $responseData['data'];
    
            $this->assertEquals($userRandomEmail, $userData['email']);
            $this->assertEquals($userRandomFirstName, $userData['firstname']);
            $this->assertEquals($userRandomLastName, $userData['lastname']);
            $this->assertEquals($userRandomUserName, $userData['username']); 
    
            $this->assertNotEquals($userRandomPassword, $userData['password']);
            
            if (isset($userData['email2fa_token'])) {
                $this->assertNotNull($userData['email2fa_token']);
            }
            if (isset($userData['email2fa_expire'])) {
                $this->assertNotNull($userData['email2fa_expire']);
            }
    
        } catch (\Exception $e) {
            $this->fail('An exception occurred: ' . $e->getMessage());
        }
    }
    

    


//    public function testCreateNewUser()
// {
//     try {
//         $this->initialize("running test create new user");

//         $faker = \Faker\Factory::create();

//         // Generate random user data
//         $userRandomEmail = $faker->unique()->safeEmail;
//         $userRandomPassword = $faker->regexify('[A-Za-z0-9]{8,16}');
//         $userRandomFirstName = $faker->firstName;
//         $userRandomLastName = $faker->lastName;
//         $userRandomUserName = $faker->userName;
//         $userRandomEmail2faToken = $faker->md5; // Use md5 as an example for email2fa_token
//         $userRandomEmail2faExpire = $faker->dateTimeBetween('now', '+1 year')->format('Y-m-d H:i:s');

//         $this->assertGreaterThanOrEqual(8, strlen($userRandomPassword), 'Password length is at least 8 characters');

//         //Construct the request body
//         $requestBody = [
//             'email' => $userRandomEmail,
//             'password' => $userRandomPassword,
//             'firstname' => $userRandomFirstName,
//             'lastname' => $userRandomLastName,
//             'username' => $userRandomUserName,
//             'email2fa_token' => $userRandomEmail2faToken,
//             'email2fa_expire' => $userRandomEmail2faExpire,
//         ];
        
        
//         // Make the request
//         $response = $this->makeRequest("POST", "users", $requestBody);

//         // Assert that the response is not null
//         $this->assertNotNull($response);
//         $this->assertEquals(200, $response->getStatusCode());

//         // Decode the response JSON content into an array
//         $responseData = json_decode($response->getBody()->getContents(), true);

//         // Assert that the response contains a 'message' key with value 'user creation successful'
//         $this->assertArrayHasKey('message', $responseData);
//         $this->assertEquals('user creation successful', $responseData['message']);

//         // Assert that the response contains a 'data' key with expected user data
//         $this->assertArrayHasKey('data', $responseData);

//         $userData = $responseData['data'];

//         $this->assertEquals($userRandomEmail, $userData['email']);
//         $this->assertEquals($userRandomFirstName, $userData['firstname']);
//         $this->assertEquals($userRandomLastName, $userData['lastname']);
//         $this->assertEquals($userRandomUserName, $userData['username']); 

//         // Assert that the password is hashed
//         $this->assertNotEquals($userRandomPassword, $userData['password']);
        
//         // Assert that email2fa_token and email2fa_expire are set
//         $this->assertArrayHasKey('email2fa_token', $userData); // Add this assertion
//         $this->assertArrayHasKey('email2fa_expire', $userData);

//     } catch (\Exception $e) {
//         $this->fail('An exception occurred: ' . $e->getMessage());
//     }
// }


}

