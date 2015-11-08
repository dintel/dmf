<?php
namespace Application\Test;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\Http\Client;

abstract class HttpTestCase extends TestCase
{
    private $response = null;
    private $headers = [];
    private $server = null;

    public function send($method, $uri, $rawData, $encType = 'application/json;charset=UTF-8')
    {
        $client = new Client();
        $client->setHeaders($this->headers);
        $client->setUri("http://{$this->server}{$uri}");
        $client->setMethod($method);
        $client->setRawBody($rawData);
        $client->setEncType($encType);
        $this->response = $client->send();
    }

    public function setServer($server)
    {
        $this->server = $server;
        return $this;
    }

    public function clearHeaders()
    {
        $this->headers = [];
        return $this;
    }

    public function setHeader($name, $value)
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
        return $this;
    }

    public function removeHeader($name)
    {
        unset($this->headers[$name]);
        return $this;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function assertResponseSuccess()
    {
        $this->assertTrue($this->response->isSuccess(), 'Failed asserting HTTP request was successful');
    }

    public function assertResponseFail()
    {
        $this->assertFalse($this->response->isSuccess(), 'Failed asserting HTTP request failed');
    }

    public function assertResponseStatusCode($code)
    {
        $actualCode = $this->response->getStatusCode();
        $this->assertEquals($code, $actualCode, "Failed asserting that HTTP status code is {$code}. Actual code is {$actualCode}");
    }

    public function assertBodyEquals($body)
    {
        $actualBody = $this->response->getBody();
        $this->assertEquals($body, $actualBody, "Failed asserting HTTP body:\n{$actualBody}\n Expected body:\n{$body}");
    }
}
