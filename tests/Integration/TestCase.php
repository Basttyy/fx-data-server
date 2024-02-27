<?php
namespace Test\Integration;

use Dotenv\Dotenv;
use Faker\Factory as Faker;
use GuzzleHttp\Exception\BadResponseException;
// use PHPUnit\Framework\TestCase as BaseTestCase;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    private \GuzzleHttp\Client $client;
    private $base_username;
    private $base_password;
    private $base_url;
    public \Faker\Generator $faker;
    public function initialize($infostr = '')
    {
        echo PHP_EOL.$infostr.' ';
        $dotenv = strtolower(PHP_OS_FAMILY) === 'windows' ? Dotenv::createImmutable(__DIR__ . "\\..\\..\\") : Dotenv::createImmutable(__DIR__ . '/../../');
        $dotenv->load();
        $dotenv->required(['TEST_USER', 'TEST_PASS', 'SERVER_APP_URI'])->notEmpty();
        $this->base_username = env('TEST_USER');
        $this->base_password = env('TEST_PASS');
        $this->base_url = env('SERVER_APP_URI');
        $this->faker = Faker::create('en_US');
        $this->client = new \GuzzleHttp\Client();
    }

    /**
     * Make a request and get response
     * @param string $method
     * @param string $endpoint
     * @param array $body
     * @param array $header
     * 
     * @return ResponseInterface
     */
    protected function makeRequest($method, $endpoint, $body = null, $header = null)
    {

        //echo $method.'<>'.$endpoint.'<>'.serialize($body).'<>'.serialize($header).PHP_EOL;
        $header = !is_null($header) ? $header : [
            'Content-Type' => 'application/json'
        ];
        $data = $this->client->request($method, "$this->base_url/$endpoint", [
            'headers' => $header,
            'body'=> json_encode($body)
        ]);
        return $data;
    }

    /**
     * Make a Request and parse response into an array
     * @param string $method
     * @param string $endpoint
     * @param array $body
     * @param array $header
     * @param boolean $only_token
     * 
     * @return array | string
     * @throws \Exception
     */
    protected function makeRequestAndParse ($method, $endpoint, $body = null, $header = null, $only_token = false)
    {
        try {
            $response = $this->makeRequest($method, $endpoint, $body, $header);
            if ($only_token && $response->getStatusCode() < 300) {
                return json_decode($response->getBody(), true)['data']['auth_token'];
            }
            // echo ('body is : '.$response->getBody()).PHP_EOL;
            return [
                'body' => json_decode($response->getBody(), true),
                'headers' => $response->getHeaders(),
                'status_code' => $response->getStatusCode()
            ];
        } catch (\Exception $e) {
            if ($e instanceof RequestException) {
                $response = $e->getResponse();            
                // echo 'body is now: '. $response->getBody()->getContents().PHP_EOL;
                // echo 'status_code is now: '. $response->getStatusCode().PHP_EOL;
                
                return [
                    'body' => json_decode($response->getBody(), true),
                    'headers' => $response->getHeaders(),
                    'status_code' => $response->getStatusCode()
                ];
            } else {
                throw $e;
            }
        }
    }

    protected function authenticate(bool $only_token = false, string $username = '', string $password = ''): array|string
    {
        return $this->makeRequestAndParse('post', 'auth/login', [
            'email' => $username !== '' ? $username : $this->base_username,
            'password' => $password !== '' ? $password : $this->base_password
        ], null, $only_token);
    }
}
