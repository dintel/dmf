<?php
namespace ApplicationTest\Blackbox;

use Application\Test\JsonHttpTestCase;

abstract class BaseHttpTestCase extends JsonHttpTestCase
{
    protected function assertResultSame($expectedResult, $result)
    {
        $this->assertArrayHasKey('result', $result);
        $this->assertSame($expectedResult, $result['result']);
    }

    protected function assertResult($result)
    {
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('result', $result);
    }

    protected function assertResultSchema($schema, $result)
    {
        $this->assertResult($result);
        $this->assertArraySchema($schema, $result['result']);
    }

    protected function assertResultArraySchema($schema, $result)
    {
        $this->assertResult($result);
        $this->assertInternalType('array', $result['result']);
        foreach ($result['result'] as $arr) {
            $this->assertArraySchema($schema, $arr);
        }
    }

    protected function assertArraySchema($schema, $array)
    {
        $this->assertInternalType('array', $array);
        foreach ($schema as $name => $type) {
            $this->assertArrayHasKey($name, $array);
            if (is_array($type)) {
                $this->assertInternalType('array', $array[$name]);
                $this->assertArraySchema($type, $array[$name]);
            } else {
                $this->assertInternalType($type, $array[$name]);
            }
        }
    }

    protected function assertMessage($result, $messageType, $messageText)
    {
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertInternalType('array', $result['message']);
        $this->assertArrayHasKey('type', $result['message']);
        $this->assertArrayHasKey('text', $result['message']);
        $this->assertSame($messageType, $result['message']['type']);
        $this->assertSame($messageText, $result['message']['text']);
    }

    protected function assertNoMessage($result)
    {
        $this->assertInternalType('array', $result);
        $this->assertArrayNotHasKey('message', $result);
    }

    protected function assertSameUser($expectedUser, $actualUser)
    {
        foreach ($expectedUser as $name => $value) {
            if ($name != "password") {
                $this->assertSame($value, $actualUser[$name]);
            }
        }
    }
}
