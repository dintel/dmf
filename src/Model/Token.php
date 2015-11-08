<?php
namespace Application\Model;

use MongoObject\Object;
use MongoObject\MapperObject;
use MongoCollection;

class Token extends Object implements MapperObject
{
    const DEFAULT_TTL=14*24*60*60;

    protected $_id;
    protected $token;
    protected $user;
    protected $expiration;

    public function __construct(array $data, MongoCollection $collection)
    {
        $schema = [
            '_id' => ['type' => Object::TYPE_ID, 'null' => false],
            'token' => ['type' => Object::TYPE_STRING, 'null' => false],
            'user' => ['type' => Object::TYPE_REFERENCE, 'null' => false],
            'expiration' => ['type' => Object::TYPE_INT, 'null' => false],
        ];
        $defaults = [
            'token' => null,
            'expiration' => time() + self::DEFAULT_TTL,
        ];
        parent::__construct($schema, $data + $defaults, $collection);
        $user = $this->fetchDBRef('User', $data['user']);
        if ($this->token === null) {
            $this->token = $user === null ? null : hash('sha512', microtime() . $user->_id . $user->login);
        }

    }

    public static function getCollection()
    {
        return "tokens";
    }

    public function getUser()
    {
        return $this->fetchDBRef('User', $this->user);
    }

    public function jsonSerialize()
    {
        $result = parent::jsonSerialize();
        $result['user'] = $this->getUser();
        return $result;
    }
}
