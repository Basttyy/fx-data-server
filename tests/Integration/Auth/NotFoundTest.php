<?php
namespace Test\Integration\Auth;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\StreamInterface;
use Test\Integration\TestCase;

final class NotFoundTest extends TestCase
{
    public function testNotFound()
    {
        $this->initialize("testing wrong route returns 404");

        $response = $this->makeRequest("GET", "/some/wrong/endpoint");
        $this->assertSame(404, $e->getCode());
        $response = $e->getResponse();
        $body = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertArrayHasKey("message", $body);
        $this->assertEquals("the requested resource is not found", $body["message"]);
    }
    
}