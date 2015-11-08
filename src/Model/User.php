<?php
namespace Application\Model;

use MongoObject\Object;
use MongoObject\MapperObject;
use MongoCollection;

class User extends Object implements MapperObject
{
    const TYPE_ADMIN='admin';
    const TYPE_USER='user';

    protected $_id;
    protected $login;
    protected $type;
    protected $name;
    protected $email;
    protected $password;
    protected $active;

    public function __construct(array $data, MongoCollection $collection)
    {
        $schema = [
            '_id' => ['type' => Object::TYPE_ID, 'null' => false],
            'login' => ['type' => Object::TYPE_STRING, 'null' => false],
            'type' => ['type' => Object::TYPE_STRING, 'null' => false],
            'name' => ['type' => Object::TYPE_STRING, 'null' => false],
            'email' => ['type' => Object::TYPE_STRING, 'null' => false],
            'password' => ['type' => Object::TYPE_STRING, 'null' => false, 'hidden' => true],
            'active' => ['type' => Object::TYPE_BOOL, 'null' => false],
        ];
        $defaults = [
            'active' => true,
        ];
        parent::__construct($schema, $data + $defaults, $collection);
    }

    public static function getCollection()
    {
        return "users";
    }

    public function checkPassword($password)
    {
        return hash('sha512', $password) == $this->password;
    }

    public function setPassword($password)
    {
        $this->password = hash('sha512', $password);
    }

    public static function randomPassword($length = 8)
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-=+;:,.?";
        $password = substr(str_shuffle($chars), 0, $length);
        return $password;
    }

    public function mergeData(array $data)
    {
        if (isset($data['password'])) {
            $this->setPassword($data['password']);
            unset($data['password']);
        }
        parent::mergeData($data);
    }
}
