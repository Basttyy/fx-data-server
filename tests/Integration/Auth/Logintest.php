<?php
namespace Test\Integration\Auth;
use Test\Integration\TestCase;

final class LoginTest extends TestCase
{
    public function testUserCanLogin()
    {
        $this->displayTestInfo("\nrunning test user can login");
        $response = $this->authenticate();
        $this->assertEquals(200, $response['status_code']);
        $this->assertArrayHasKey('data', $response['body']);
        $this->assertArrayHasKey('auth_token', $response['body']['data']);
        $this->assertIsString($response['body']['data']['auth_token']);
    }

    public function testBadCredentialsGives401()
    {
        $this->displayTestInfo("LoginTest: running test bad credentials give 401");
        $response = $this->authenticate(false, 'anything', 'anypass');
        $this->assertIsArray($response);
        $this->assertStringContainsString('401', $response['status_code']);
    }
}