<?php
namespace ApplicationTest\Blackbox;

use MongoClient;
use MongoId;

class UserControllerTest extends BaseHttpTestCase
{
    protected static $mongo;
    protected static $adminUser;
    protected static $token = "";
    protected static $errDbAlreadyExists;
    protected static $userSchema = [
        'id' => 'string',
        'login' => 'string',
        'type' => 'string',
        'name' => 'string',
        'email' => 'string',
        'active' => 'boolean'
    ];
    protected static $tokenSchema = [
        'id' => 'string',
        'token' => 'string',
        'expiration' => 'integer',
        'user' => [
            'id' => 'string',
            'login' => 'string',
            'type' => 'string',
            'name' => 'string',
            'email' => 'string',
            'active' => 'boolean'
        ],
    ];

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

    public function setUp()
    {
        if (self::$errDbAlreadyExists) {
            $this->fail("Can not run tests because test database already exists (".TEST_DB_NAME.")");
        }
        $this->setServer(WEB_SERVER_HOST.":".WEB_SERVER_PORT);
        parent::setUp();
    }

    public static function tearDownAfterClass()
    {
        if (!self::$errDbAlreadyExists) {
            self::$mongo->selectDB(TEST_DB_NAME)->drop();
        }
    }

    public function testBootstrap()
    {
        // Bootstrap and store admin password and token
        $result = $this->simpleCallSchema('/bootstrap', '', self::$userSchema);
        $this->assertEquals('admin', $result['result']['login']);
        $this->assertEquals(\Application\Controller\Bootstrap::DEFAULT_PASSWORD_LENGTH, strlen($result['result']['password']));
        self::$adminUser = [
            'type' => \Application\Model\User::TYPE_ADMIN,
            'login' => $result['result']['login'],
            'password' => $result['result']['password'],
            'active' => true,
        ];

        // Login
        $login = [
            'login' => self::$adminUser['login'],
            'password' => self::$adminUser['password'],
        ];
        $result = $this->simpleCallSchema('/auth/login', json_encode($login), self::$tokenSchema);
        self::$token = $this->getJsonHttpResult()['result']['token'];
    }

    /**
     * @depends testBootstrap
     */
    public function testBasicFlow()
    {
        // List users, there should only be one user (created in bootstrap
        // process)
        $result = $this->simpleCallArraySchema('/user/index', '', self::$userSchema);
        $this->assertEquals(1, count($result['result']));
        $this->assertSameUser(self::$adminUser, $result['result'][0]);

        // Save admin user details
        $adminUser = $result['result'][0];

        // Get admin user details and check they are correct
        $result = $this->simpleCallSchema('/user/get', json_encode(['id' => $adminUser['id']]), self::$userSchema);
        $this->assertSameUser($adminUser, $result['result']);
        self::$adminUser = $adminUser + self::$adminUser;


        // Create new admin user
        $newAdminUser = [
            'login' => 'admintest',
            'password' => 'adminpassword',
            'type' => \Application\Model\User::TYPE_ADMIN,
            'email' => 'admintest@test.com',
            'name' => 'admin test',
            'active' => true,
        ];
        $result = $this->simpleCallSchemaWithMessage('/user/save', json_encode(['data' => $newAdminUser]), self::$userSchema, 'success', "Successfully saved user {$newAdminUser['login']}");
        $this->assertSameUser($newAdminUser, $result['result']);
        self::$adminUser = $adminUser + self::$adminUser;

        // Save new user details
        $newAdminUser = $result['result'] + $newAdminUser;

        // Create new regular user
        $newRegularUser = [
            'login' => 'usertest',
            'password' => 'userpassword',
            'type' => \Application\Model\User::TYPE_USER,
            'email' => 'usertest@test.com',
            'name' => 'user test',
            'active' => true,
        ];
        $result = $this->simpleCallSchemaWithMessage('/user/save', json_encode(['data' => $newRegularUser]), self::$userSchema, 'success', "Successfully saved user {$newRegularUser['login']}");
        $this->assertSameUser($newRegularUser, $result['result']);
        self::$adminUser = $adminUser + self::$adminUser;

        // Save new user details
        $newRegularUser = $result['result'] + $newRegularUser;

        // Test login with new admin user
        $login = [
            'login' => $newAdminUser['login'],
            'password' => $newAdminUser['password'],
        ];
        $this->simpleCallSchema('/auth/login', json_encode($login), self::$tokenSchema);

        // Test login with new regular user
        $login = [
            'login' => $newRegularUser['login'],
            'password' => $newRegularUser['password'],
        ];
        $this->simpleCallSchema('/auth/login', json_encode($login), self::$tokenSchema);

        // Test that list now returns 3 objects
        $result = $this->simpleCallArraySchema('/user/index', '', self::$userSchema);
        $this->assertEquals(3, count($result['result']));
        $this->assertSameUser(self::$adminUser, $result['result'][0]);

        // Delete admin and regular user
        $this->simpleCallWithMessage('/user/delete', json_encode(['ids' => [$newAdminUser['id'], $newRegularUser['id']]]), 2, 'success', 'Successfully deleted 2 users');
    }

    /**
     * @depends testBasicFlow
     */
    public function testGetIllegalParameters()
    {
        // Empty request
        $this->parameterErrorTest('/user/get', '', "Missing ID parameter");

        // Invalid JSON
        $this->parameterErrorTest('/user/get', "abc[", 'Failed parsing JSON request');

        // Not array parameters
        $this->parameterErrorTest('/user/get', json_encode(true), 'Request must be valid JSON object or array');

        // No id parameter
        $this->parameterErrorTest('/user/get', json_encode([]), 'Missing ID parameter');

        // Unexisting ID parameter
        $id = new MongoId();
        $this->parameterErrorTest('/user/get', json_encode(['id' => (string)$id]), 'Object not found');

        // Invalid ID parameter
        $this->parameterErrorTest('/user/get', json_encode(['id' => '!!!']), 'Object not found');
    }

    /**
     * @depends testBasicFlow
     */
    public function testDeleteIllegalParameters()
    {
        // Create test users
        $testUsers[] = $this->createUser('test1', 'password1', 'test1@test.com', 'test user 1');
        $testUsers[] = $this->createUser('test2', 'password2', 'test2@test.com', 'test user 2');
        $testUsers[] = $this->createUser('test3', 'password3', 'test3@test.com', 'test user 3');

        // Empty request
        $this->parameterErrorTest('/user/delete', json_encode([]), "Missing IDs parameter");

        // Invalid JSON
        $this->parameterErrorTest('/user/delete', 'abc]', "Failed parsing JSON request");

        // Not array parameters
        $this->parameterErrorTest('/user/delete', json_encode("aaa"), "Request must be valid JSON object or array");

        // Excessive parameter
        $this->simpleCallWithMessage('/user/delete', json_encode(['excessive' => 'param', 'ids' => [$testUsers[2]['id']]]), 1, 'success', 'Successfully deleted 1 users');
        unset($testUsers[2]);

        // ids not an array
        $this->parameterErrorTest('/user/delete', json_encode(['ids' => true]), "Parameter ids must be an array");

        // Not existing ID
        $id = new MongoId();
        $this->parameterErrorTest('/user/delete', json_encode(['ids' => [(string)$id]]), "Object ID {$id} not found", 0);

        // Mixed existing and not existing IDs
        $this->parameterErrorTest('/user/delete', json_encode(['ids' => [(string)$testUsers[0]['id'], (string)$testUsers[1]['id'], (string)$id]]), "Object ID {$id} not found", 0);

        // Mixed existing and invalid IDs
        $this->parameterErrorTest('/user/delete', json_encode(['ids' => [(string)$testUsers[0]['id'], (string)$testUsers[1]['id'], ['aaa']]]), "Object ID array(1) not found", 0);

        // Delete correct users
        $this->simpleCallWithMessage('/user/delete', json_encode(['ids' => [$testUsers[0]['id'], $testUsers[1]['id']]]), 2, 'success', "Successfully deleted 2 users");

        // Delete all users should fail
        $this->simpleCallWithMessage('/user/delete', json_encode(['ids' => [(string)self::$adminUser['id']]]), 0, 'error', "Can not delete all users in system");
    }

    /**
     * @depends testBasicFlow
     */
    public function testListIllegalParameters()
    {
        // Empty request
        $result = $this->simpleCallArraySchema('/user/index', "", self::$userSchema);
        $this->assertUser(self::$adminUser, $result['result'][0]);

        // Invalid JSON
        $this->parameterErrorTest('/user/index', "}}}", 'Failed parsing JSON request');

        // Not array parameters
        $this->parameterErrorTest('/user/index', json_encode(1), 'Request must be valid JSON object or array');

        // Unknown parameters should be ignored silently
        $result = $this->simpleCallArraySchema('/user/index', json_encode(['unknown' => 'parameter']), self::$userSchema);
        $this->assertEquals(1, count($result['result']));
        $this->assertUser(self::$adminUser, $result['result'][0]);

        // Invalid filter
        $this->parameterErrorTest('/user/index', json_encode(['filter' => 'unknown']), 'Illegal filter parameter - must be array or undefined');

        // Invalid order
        $this->parameterErrorTest('/user/index', json_encode(['order' => 'unknown']), 'Illegal order parameter - must be array, null or undefined');
    }

    /**
     * @depends testBasicFlow
     */
    public function testSaveIllegalParameters()
    {
        // Empty request
        $this->parameterErrorTest('/user/save', '', 'Missing data parameter');

        // Invalid JSON
        $this->parameterErrorTest('/user/save', '}}}', 'Failed parsing JSON request');

        // Not array parameters
        $this->parameterErrorTest('/user/save', json_encode(1), 'Request must be valid JSON object or array');

        // Unknown parameters should be ignored silently
        $this->parameterErrorTest('/user/save', json_encode(['unknown' => 'parameter']), 'Missing data parameter');

        // User data is null
        $this->parameterErrorTest('/user/save', json_encode(['data' => 1]), 'Wrong data parameter');

        // User data is empty array
        $this->parameterErrorTest('/user/save', json_encode(['data' => []]), 'Missing one of data fields');

        // Partial user data
        $this->parameterErrorTest('/user/save', json_encode(['data' => ['login' => 'usertest', 'password' => 'password']]), 'Missing one of data fields');

        // Test user
        $user = [
            'login' => 'usertest',
            'type' => \Application\Model\User::TYPE_ADMIN,
            'email' => "test@test.com",
            'name' => "Test user",
            'active' => true,
        ];

        // Invalid login 1
        $user['login'] = false;
        $this->parameterErrorTest('/user/save', json_encode(['data' => $user]), 'Invalid user login specified');

        // Invalid login 2
        $user['login'] = [];
        $this->parameterErrorTest('/user/save', json_encode(['data' => $user]), 'Invalid user login specified');

        // Invalid login 3
        $user['login'] = "user test";
        $this->parameterErrorTest('/user/save', json_encode(['data' => $user]), 'Invalid user login specified');

        // Invalid type 1
        $user['login'] = "usertest";
        $user['type'] = true;
        $this->parameterErrorTest('/user/save', json_encode(['data' => $user]), 'Invalid user type specified');

        // Invalid type 2
        $user['type'] = "";
        $this->parameterErrorTest('/user/save', json_encode(['data' => $user]), 'Invalid user type specified');

        // Invalid type 3
        $user['type'] = "poweruser";
        $this->parameterErrorTest('/user/save', json_encode(['data' => $user]), 'Invalid user type specified');

        // Invalid email 1
        $user['type'] = \Application\Model\User::TYPE_ADMIN;
        $user['email'] = true;
        $this->parameterErrorTest('/user/save', json_encode(['data' => $user]), 'Invalid user email specified');

        // Invalid email 2
        $user['email'] = '';
        $this->parameterErrorTest('/user/save', json_encode(['data' => $user]), 'Invalid user email specified');

        // Invalid email 3
        $user['email'] = 'a@';
        $this->parameterErrorTest('/user/save', json_encode(['data' => $user]), 'Invalid user email specified');

        // Invalid email 4
        $user['email'] = 'dima';
        $this->parameterErrorTest('/user/save', json_encode(['data' => $user]), 'Invalid user email specified');

        // Invalid email 5
        $user['email'] = 'd ima@test.com';
        $this->parameterErrorTest('/user/save', json_encode(['data' => $user]), 'Invalid user email specified');

        // Invalid email 6
        $user['email'] = 'dima@test.unknown';
        $this->parameterErrorTest('/user/save', json_encode(['data' => $user]), 'Invalid user email specified');

        // Invalid name 1
        $user['email'] = 'test@test.com';
        $user['name'] = true;
        $this->parameterErrorTest('/user/save', json_encode(['data' => $user]), 'Invalid user name specified');

        // Invalid name 2
        $user['name'] = '';
        $this->parameterErrorTest('/user/save', json_encode(['data' => $user]), 'Invalid user name specified');

        // Invalid name 3
        $user['name'] = '   ';
        $this->parameterErrorTest('/user/save', json_encode(['data' => $user]), 'Invalid user name specified');

        // Invalid name 4
        $user['name'] = '!!!';
        $this->parameterErrorTest('/user/save', json_encode(['data' => $user]), 'Invalid user name specified');

        // Invalid active 1
        $user['name'] = 'Test user';
        $user['active'] = 'true';
        $this->parameterErrorTest('/user/save', json_encode(['data' => $user]), 'Invalid user active specified');

        // Invalid active 2
        $user['active'] = 0;
        $this->parameterErrorTest('/user/save', json_encode(['data' => $user]), 'Invalid user active specified');

        // Invalid password 1
        $user['active'] = true;
        $user['password'] = "123";
        $this->parameterErrorTest('/user/save', json_encode(['data' => $user]), 'Invalid user password specified');

        // New user without password
        $this->parameterErrorTest('/user/save', json_encode(['data' => $user]), 'Invalid user password specified');

        // Duplicate login
        $user['login'] = self::$adminUser['login'];
        $user['password'] = "12341234";
        $this->parameterErrorTest('/user/save', json_encode(['data' => $user]), "User with login '{$user['login']}' already exists");

        // Unexisting user update
        $user['id'] = (string) new MongoId();
        $this->parameterErrorTest('/user/save', json_encode(['data' => $user]), "User with ID {$user['id']} not found");
    }

    /**
     * @depends testBasicFlow
     */
    public function testList()
    {
        // Create some test users
        $testUsers[] = self::$adminUser;
        $testUsers[] = $this->createUser('test2', 'password2', 'test2@test.com', 'test user 2');
        $testUsers[] = $this->createUser('test1', 'password1', 'test1@test.com', 'test user 1');
        $testUsers[] = $this->createUser('test3', 'password3', 'test3@test.com', 'test user 3');
        unset($testUsers[0]['password']);
        unset($testUsers[1]['password']);
        unset($testUsers[2]['password']);
        unset($testUsers[3]['password']);

        // List users, order should be the same as they were created
        $result = $this->simpleCallArraySchema('/user/index', "", self::$userSchema);
        $this->assertEquals(4, count($result['result']));
        $this->assertUser($testUsers[0], $result['result'][0]);
        $this->assertUser($testUsers[1], $result['result'][1]);
        $this->assertUser($testUsers[2], $result['result'][2]);
        $this->assertUser($testUsers[3], $result['result'][3]);

        // Now sort users by name
        array_splice($testUsers, 1, 2, array_reverse(array_slice($testUsers, 1, 2)));
        $result = $this->simpleCallArraySchema('/user/index', json_encode(['order' => ['name' => 1]]), self::$userSchema);
        $this->assertEquals(4, count($result['result']));
        $this->assertUser($testUsers[0], $result['result'][0]);
        $this->assertUser($testUsers[1], $result['result'][1]);
        $this->assertUser($testUsers[2], $result['result'][2]);
        $this->assertUser($testUsers[3], $result['result'][3]);
    }

    /**
     * @depends testBasicFlow
     */
    public function testAcl()
    {
        // Create regular user and login with it
        $testUser = $this->createUser('test', 'password', 'test@test.com', 'test user', \Application\Model\User::TYPE_USER);

        // Login with regular user
        $login = [
            'login' => $testUser['login'],
            'password' => $testUser['password'],
        ];
        $result = $this->simpleCallSchema('/auth/login', json_encode($login), self::$tokenSchema);
        $this->assertUser($testUser, $result['result']['user']);
        $token = $result['result']['token'];

        // Check that save is denied
        $newUser = [
            'login' => 'user',
            'password' => 'password',
            'type' => \Application\Model\User::TYPE_USER,
            'email' => 'test@test.com',
            'name' => 'Someone',
            'active' => true,
        ];
        $this->aclErrorTest($token, '/user/save', json_encode(['data' => $newUser]));

        // Check that delete is denied
        $this->aclErrorTest($token, '/user/delete', json_encode(['ids' => [$testUser['id']]]));

        // Delete regular user
        $this->simpleCallWithMessage('/user/delete', json_encode(['ids' => [$testUser['id']]]), 1, 'success', 'Successfully deleted 1 users');
    }

    protected function assertUser(array $expectedUser, array $user)
    {
        $this->assertInternalType('array', $user);
        $this->assertArrayHasKey('id', $user);
        $this->assertArrayHasKey('login', $user);
        $this->assertArrayHasKey('type', $user);
        $this->assertArrayHasKey('name', $user);
        $this->assertArrayHasKey('email', $user);
        $this->assertArrayHasKey('active', $user);
        $this->assertArrayNotHasKey('password', $user);

        foreach ($expectedUser as $name => $value) {
            if ($name != "password") {
                $this->assertEquals($value, $user[$name]);
            }
        }
    }

    protected function createUser($login, $password, $email, $name, $type = \Application\Model\User::TYPE_ADMIN, $active = true)
    {
        $newUser = [
            'login' => $login,
            'password' => $password,
            'type' => $type,
            'email' => $email,
            'name' => $name,
            'active' => $active,
        ];
        $this->setHeaders([
            "X-Auth-Token" => self::$token,
        ]);
        $this->post('/user/save', json_encode(['data' => $newUser]));
        $this->assertResponseSuccess();
        $r = $this->getJsonHttpResult();
        $this->assertMessage($r, 'success', "Successfully saved user {$login}");
        $this->assertResultSchema(self::$userSchema, $r);

        return $r['result'] + $newUser;
    }

    protected function parameterErrorTest($url, $data, $message, $result = null)
    {
        $this->setHeaders([
            "X-Auth-Token" => self::$token,
        ]);
        $this->post($url, $data);
        $this->assertResponseSuccess();
        $r = $this->getJsonHttpResult();
        $this->assertMessage($r, 'error', $message);
        $this->assertResultSame($result, $r);
    }

    protected function aclErrorTest($token, $url, $data, $result = null)
    {
        $this->setHeaders([
            "X-Auth-Token" => $token,
        ]);
        $this->post($url, $data);
        $this->assertResponseSuccess();
        $r = $this->getJsonHttpResult();
        $this->assertMessage($r, 'error', 'Access dinied');
        $this->assertResultSame($result, $r);
    }

    protected function simpleCall($url, $data, $result)
    {
        $this->setHeaders([
            "X-Auth-Token" => self::$token,
        ]);
        $this->post($url, $data);
        $this->assertResponseSuccess();
        $r = $this->getJsonHttpResult();
        $this->assertNoMessage($r);
        $this->assertResultSame($result, $r);
        return $r;
    }

    protected function simpleCallWithMessage($url, $data, $result, $type, $text)
    {
        $this->setHeaders([
            "X-Auth-Token" => self::$token,
        ]);
        $this->post($url, $data);
        $this->assertResponseSuccess();
        $r = $this->getJsonHttpResult();
        $this->assertMessage($r, $type, $text);
        $this->assertResultSame($result, $r);
        return $r;
    }

    protected function simpleCallSchema($url, $data, $schema)
    {
        $this->setHeaders([
            "X-Auth-Token" => self::$token,
        ]);
        $this->post($url, $data);
        $this->assertResponseSuccess();
        $r = $this->getJsonHttpResult();
        $this->assertNoMessage($r);
        $this->assertResultSchema($schema, $r);
        return $r;
    }

    protected function simpleCallSchemaWithMessage($url, $data, $schema, $type, $text)
    {
        $this->setHeaders([
            "X-Auth-Token" => self::$token,
        ]);
        $this->post($url, $data);
        $this->assertResponseSuccess();
        $r = $this->getJsonHttpResult();
        $this->assertMessage($r, $type, $text);
        $this->assertResultSchema($schema, $r);
        return $r;
    }

    protected function simpleCallArraySchema($url, $data, $schema)
    {
        $this->setHeaders([
            "X-Auth-Token" => self::$token,
        ]);
        $this->post($url, $data);
        $this->assertResponseSuccess();
        $r = $this->getJsonHttpResult();
        $this->assertNoMessage($r);
        $this->assertResultArraySchema($schema, $r);
        return $r;
    }
}
