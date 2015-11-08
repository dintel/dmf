<?php
namespace Application\Test;

abstract class JsonHttpTestCase extends HttpTestCase
{
    public function post($uri, $rawData = "")
    {
        $result = parent::send('POST', $uri, $rawData, 'application/json;charset=UTF-8');
        $this->assertJsonResponse();
        return $result;
    }

    public function get($uri)
    {
        $result = parent::send('GET', $uri, "", 'application/json;charset=UTF-8');
        $this->assertJsonResponse();
        return $result;
    }

    public function assertJsonResponse()
    {
        $jsonData = json_decode($this->getResponse()->getBody(), true);
        if ($jsonData === null) {
            $this->fail("Failed parsing response JSON: " . json_last_error_msg() . " - " . $this->getResponse()->getBody());
        }
    }

    public function getJsonHttpResult()
    {
        $this->assertJsonResponse();
        return json_decode($this->getResponse()->getBody(), true);
    }
}
