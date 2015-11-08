<?php
namespace Application\Json;

use ArrayIterator;
use JsonSerializable;

class Message implements JsonSerializable
{
    const TYPE_SUCCESS="success";
    const TYPE_INFO="info";
    const TYPE_WARNING="warning";
    const TYPE_ERROR="error";

    private $data;

    public function __construct($type, $text)
    {
        $this->data['type'] = $type;
        $this->data['text'] = $text;
    }

    public function __toString()
    {
        return "{$this->data['type']}: {$this->data['text']}";
    }

    public function getType()
    {
        return $this->data['type'];
    }

    public function getText()
    {
        return $this->data['text'];
    }
    public static function success($text)
    {
        return new Message(Message::TYPE_SUCCESS, $text);
    }

    public static function info($text)
    {
        return new Message(Message::TYPE_INFO, $text);
    }

    public static function warning($text)
    {
        return new Message(Message::TYPE_WARNING, $text);
    }

    public static function error($text)
    {
        return new Message(Message::TYPE_ERROR, $text);
    }

    public function jsonSerialize()
    {
        return $this->data;
    }
}
