<?php

namespace Test\Integration\Pair;
use Basttyy\FxDataServer\Models\User;
use GuzzleHttp\Exception\RequestException;
use Exception;
use Test\Integration\TestCase;

final class UpdateUserTest extends TestCase
{
    // public function testUpdateUser()
    // {
    //     try {
    //         $this->initialize("running test create user to be updated");
    
    //         // Generate random user data
    //         $userRandomEmail = $this->faker->unique()->safeEmail;
    //         $userRandomFirstName = $this->faker->firstName;
    //         $userRandomLastName = $this->faker->lastName;
    //         $userRandomPassword = $this->faker->password;
    
    //         // Construct the request body
    //         $requestBody = [
    //             'email' => $userRandomEmail,
    //             'password' => $userRandomPassword,
    //             'firstname' => $userRandomFirstName,
    //             'lastname' => $userRandomLastName,
    //         ];
    
    //         // Make the request
    //         $response = $this->makeRequest("POST", "users", $requestBody);
    
    //         // Assert that the response is not null
    //         $this->assertNotNull($response);
    
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
    //         $this->assertTrue(isset($userData['username'])); // Ensure username is set
    
    //         // Assert that the password is hashed
    //         $this->assertNotEquals($userRandomPassword, $userData['password']);
            
    //         // Assert that email2fa_token and email2fa_expire are set
    //         $this->assertArrayHasKey('email2fa_token', $userData);
    //         $this->assertArrayHasKey('email2fa_expire', $userData);
    








    //         $this->initialize("running test update user");

    //         // Authenticate the user and get the token
    //         $token = $this->authenticate(only_token: true);

    //         // Construct the request body with updated user data
    //         $updatedUserData = [
    //             'email' => $this->faker->unique()->safeEmail,
    //             'firstname' => $this->faker->firstName,
    //             'lastname' => $this->faker->lastName,
    //             'username' => $this->faker->userName,
    //             'phone' => $this->faker->phoneNumber,
    //             'level' => 'user',
    //             'country' => $this->faker->country,
    //             'city' => $this->faker->city,
    //             'address' => $this->faker->address,
    //             'postal_code' => $this->faker->postcode,
    //             'avatar' => 'path/to/avatar.jpg'
    //         ];

    //         // Make the request to update the user
    //         $response = $this->makeRequest("PUT", "users/{$user->id}", $updatedUserData, ["Authorization" => "Bearer " . $token]);

    //         // Assert that the response is not null
    //         $this->assertNotNull($response);

    //         // Decode the response JSON content into an array
    //         $responseData = json_decode($response->getBody()->getContents(), true);

    //         // Assert that the response contains a 'message' key with value 'user update successful'
    //         $this->assertArrayHasKey('message', $responseData);
    //         $this->assertEquals('user update successful', $responseData['message']);

    //         // Assert that the response contains a 'data' key with updated user data
    //         $this->assertArrayHasKey('data', $responseData);
    //         $updatedUserData['id'] = $user->id; // Add the user ID to the updated data for comparison
    //         $this->assertEquals($updatedUserData, $responseData['data']);

    //     } catch (\Exception $e) {
    //         $this->fail('An exception occurred: ' . $e->getMessage());
    //     }
    // }

//     public function testUpdateUser()
// {
//     $this->initialize("running test create user to be updated");
//     try {

//         // Generate random user data
//         $userRandomEmail = $this->faker->unique()->safeEmail;
//         $userRandomFirstName = $this->faker->firstName;
//         $userRandomLastName = $this->faker->lastName;
//         $userRandomPassword = $this->faker->password;

//         // Construct the request body
//         $requestBody = [
//             'email' => $userRandomEmail,
//             'password' => $userRandomPassword,
//             'firstname' => $userRandomFirstName,
//             'lastname' => $userRandomLastName,
//             'phone' => $this->faker->phoneNumber,
//             'level' => '1',
//             'country' => $this->faker->country,
//             'city' => $this->faker->city,
//             'address' => $this->faker->address,
//             'postal_code' => "10010305",
//             // 'postal_code' => $this->faker->postcode,
//             'avatar' => 'path/to/avatar.jpg'
//         ];

//         // Make the request to create a user
//         $response = $this->makeRequest("POST", "users", $requestBody);

//         // Assert that the response is not null
//         $this->assertNotNull($response);

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
//         $this->assertTrue(isset($userData['username'])); // Ensure username is set

//         // Assert that the password is hashed
//         $this->assertNotEquals($userRandomPassword, $userData['password']);

//         $this->initialize("running test updated user -- UPDATING NEWLY CREATED USER ID::" . $userData['id']);
//         // Get the ID of the newly created user 
//         $userId = $userData['data']['id'];
//         // Authenticate the user and get the token
//         $token = $this->authenticate(only_token: true);

//         // Construct the request body with updated user data
//         $updatedUserData = [
//             'email' => $this->faker->unique()->safeEmail,
//             'firstname' => $this->faker->firstName,
//             'lastname' => $this->faker->lastName,
//             'username' => $this->faker->userName,
//             'phone' => $this->faker->phoneNumber,
//             'level' => '1',
//             'country' => $this->faker->country,
//             'city' => $this->faker->city,
//             'address' => $this->faker->address,
//             'postal_code' => "5910387",
//             // 'postal_code' => $this->faker->postcode,
//             'avatar' => 'path/to/avatar.jpg'
//         ];

//         // Make the request to update the user with the obtained user ID
//         $response = $this->makeRequest("PUT", "users/{$userId}", $updatedUserData, [
//             "Authorization" => "Bearer " . $token
//          ]);

//         // Assert that the response is not null
//         $this->assertNotNull($response);

//         // Decode the response JSON content into an array
//         $responseData = json_decode($response->getBody()->getContents(), true);

//         // Assert that the response contains a 'message' key with value 'user update successful'
//         $this->assertArrayHasKey('message', $responseData);
//         $this->assertEquals('user update successful', $responseData['message']);

//         // Assert that the response contains a 'data' key with updated user data
//         $this->assertArrayHasKey('data', $responseData);
//         $updatedUserData['id'] = $userId; // Add the user ID to the updated data for comparison
//         $this->assertEquals($updatedUserData, $responseData['data']);

//     } catch (\Exception $e) {
//         if ($e instanceof RequestException) {
//             $response = $e->getResponse();
//             if ($response) {
//                 $statusCode = $response->getStatusCode();
//                 if ($statusCode === 500) {
//                     $this->fail('Server error: ' . $response->getBody()->getContents());
//                 } else {
//                     $this->assertEquals(404, $statusCode);
//                     $body = json_decode($response->getBody()->getContents(), true);
//                     $this->assertArrayHasKey("message", $body);
//                     $this->assertEquals("you don't have this permission", $body["message"]);
//                 }
//             } else {
//                 $this->fail('Request failed: ' . $e->getMessage());
//             }
//         } else {
//             throw $e;
//         }
//     }
// }


    
    
}

