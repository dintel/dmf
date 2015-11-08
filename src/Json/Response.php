<?php
namespace Application\Json;

use ArrayIterator;
use JsonSerializable;

class Response implements JsonSerializable
{
    private $data;

    public function __construct($result, Message $msg = null)
    {
        $this->data = [
            'result' => $result,
        ];

        if ($msg !== null) {
            $this->data['message'] = $msg;
        }
    }

    public function jsonSerialize()
    {
        return $this->data;
    }

    public static function model($result, Message $msg = null)
    {
        return new Response($result, $msg);
    }
}
