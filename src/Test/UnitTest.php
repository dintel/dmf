<?php
namespace Application\Test;

use PHPUnit_Framework_TestCase as TestCase;
use Application\Model\User;
use Application\Model\Token;
use MongoClient;

class UnitTest extends TestCase
{
    private static $errDbAlreadyExists;
    private static $mongo;

    private $zsm;

    private $regularUser;
    private $adminUser;

    private $regularToken;
    private $adminToken;

    public static function setUpBeforeClass()
    {
        self::$errDbAlreadyExists = false;
        self::$mongo = new MongoClient();
        $dbs = self::$mongo->listDBs();
        foreach ($dbs['databases'] as $db) {
            if ($db['name'] == TEST_DB_NAME) {
                self::$errDbAlreadyExists = true;
            }
        }

        parent::setUpBeforeClass();
    }

    public static function tearDownAfterClass()
    {
        if (!self::$errDbAlreadyExists) {
            self::$mongo->selectDB(TEST_DB_NAME)->drop();
        }
    }

    public function setUp()
    {
        if (self::$errDbAlreadyExists) {
            $this->fail("Can not run tests because test database already exists (".TEST_DB_NAME.")");
        }

        $this->zsm = include 'config/services.php';
        $this->mapper = $this->zsm->get("mapper");

        $this->adminUser = $this->mapper->newObject('User', [
            'login' => 'testAdmin',
            'password' => 'testPassword',
            'type' => User::TYPE_ADMIN,
            'email' => 'testAdmin@test.com',
            'name' => 'Admin user',
            'active' => true,
        ]);
        $this->regularUser = $this->mapper->newObject('User', [
            'login' => 'testUser',
            'password' => 'testPassword',
            'type' => User::TYPE_USER,
            'email' => 'testUser@test.com',
            'name' => 'Regular user',
            'active' => true,
        ]);
        $this->adminUser->save();
        $this->regularUser->save();

        $this->adminToken = $this->mapper->newObject('Token', [
            'token' => hash('sha512', microtime() . $this->adminUser->_id . $this->adminUser->login),
            'user' => $this->adminUser->getDBRef(),
        ]);
        $this->regularToken = $this->mapper->newObject('Token', [
            'token' => hash('sha512', microtime() . $this->regularUser->_id . $this->regularUser->login),
            'user' => $this->regularUser->getDBRef(),
        ]);
        $this->adminToken->save();
        $this->regularToken->save();

        parent::setUp();
    }

    public function tearDown()
    {
        $this->adminToken->delete();
        $this->regularToken->delete();
        $this->adminUser->delete();
        $this->regularUser->delete();
        unset($this->zsm);
        unset($this->mapper);
    }

    public function setAuthToken($type)
    {
        if ($type === 'admin') {
            $_SERVER['HTTP_X_AUTH_TOKEN'] = $this->adminToken->token;
            return;
        }
        if ($type === 'regular') {
            $_SERVER['HTTP_X_AUTH_TOKEN'] = $this->regularToken->token;
            return;
        }
        $this->fail("Unknown type of token requested - {$type}");
    }

    public function setUri($uri)
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = $uri;
    }

    public function setParameters(array $params)
    {
        $this->zsm->get('request')->setParameters($params);
    }

    public function dispatch()
    {
        $dispatcher = $this->zsm->get('dispatcher');
        $dispatcher->dispatch();
        $result = json_decode($dispatcher->getResult(), true);
        $this->assertResult($result);
        return $result;
    }

    protected function assertMessage($messageType, $messageText, $result)
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
}
