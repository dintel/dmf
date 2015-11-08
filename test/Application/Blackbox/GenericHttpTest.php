<?php
namespace ApplicationTest\Blackbox;

use Application\Test\JsonHttpTestCase;
use MongoClient;

class GenericHttpTest extends BaseHttpTestCase
{
    protected static $mongo;
    protected static $adminUser;
    protected static $token;
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

    public function testUninitialized()
    {
        /* Test that logout is not accessible */
        $this->post('/auth/logout', '');
        $this->assertResponseSuccess();
        $result = $this->getJsonHttpResult();
        $this->assertMessage($result, 'error', 'No authentication token');
        $this->assertResultSame(null, $result);

        /* Test that whoami is not accessible */
        $this->post('/auth/whoami');
        $this->assertResponseSuccess();
        $result = $this->getJsonHttpResult();
        $this->assertMessage($result, 'error', 'No authentication token');
        $this->assertResultSame(null, $result);
    }

    /**
     * @depends testUninitialized
     */
    public function testBootstrap()
    {
        // Bootstrap and store admin password and token
        $this->post('/bootstrap');
        $this->assertResponseSuccess();
        $result = $this->getJsonHttpResult();
        $this->assertNoMessage($result);
        $this->assertResultSchema(self::$userSchema, $result);
        $this->assertEquals('admin', $result['result']['login']);
        $this->assertEquals(\Application\Controller\Bootstrap::DEFAULT_PASSWORD_LENGTH, strlen($result['result']['password']));
        self::$adminUser = [
            'type' => \Application\Model\User::TYPE_ADMIN,
            'login' => $result['result']['login'],
            'password' => $result['result']['password'],
            'active' => true,
        ];

        // Check that rebootstrap fails
        $this->post('/bootstrap');
        $this->assertResponseSuccess();
        $result = $this->getJsonHttpResult();
        $this->assertResultSame(null, $result);
        $this->assertMessage($result, 'error', 'System is already bootstrapped');

        // Login
        $this->post('/auth/login', json_encode([
            'login' => self::$adminUser['login'],
            'password' => self::$adminUser['password'],
        ]));
        $this->assertResponseSuccess();
        $result = $this->getJsonHttpResult();
        $this->assertNoMessage($result);
        $this->assertResultSchema(self::$tokenSchema, $result);
        $token = $result['result']['token'];

        // Check that rebootstrap fails
        $this->post('/bootstrap');
        $this->assertResponseSuccess();
        $result = $this->getJsonHttpResult();
        $this->assertResultSame(null, $result);
        $this->assertMessage($result, 'error', 'System is already bootstrapped');

        // Logout
        $this->setHeaders([
            "X-Auth-Token" => $token,
        ]);
        $this->post('/auth/logout', json_encode([
            'login' => self::$adminUser['login'],
            'password' => self::$adminUser['password'],
        ]));
        $this->assertResponseSuccess();
        $result = $this->getJsonHttpResult();
        $this->assertNoMessage($result);
        $this->assertResultSame(true, $result);

        // Login with admin credentials for later use
        $this->clearHeaders();
        $this->post('/auth/login', json_encode([
            'login' => self::$adminUser['login'],
            'password' => self::$adminUser['password'],
        ]));
        $this->assertResponseSuccess();
        $result = $this->getJsonHttpResult();
        $this->assertNoMessage($result);
        $this->assertResultSchema(self::$tokenSchema, $result);
        $this->assertSameUser(self::$adminUser, $result['result']['user']);
        $this->assertArrayNotHasKey('password', $result['result']['user']);
        self::$token = $result['result']['token'];
    }

    /**
     * @depends testBootstrap
     */
    public function testNotFound()
    {
        // Call unexisting method
        $this->setHeaders([
            "X-Auth-Token" => self::$token,
        ]);
        $this->post('/test-unknown');
        $this->assertResponseSuccess();
        $result = $this->getJsonHttpResult();
        $this->assertMessage($result, 'error', 'Path not found');
    }

    /**
     * @depends testNotFound
     */
    public function testLogin()
    {
        // Login with admin credentials
        $this->clearHeaders();
        $this->post('/auth/login', json_encode([
            'login' => self::$adminUser['login'],
            'password' => self::$adminUser['password'],
        ]));
        $this->assertResponseSuccess();
        $result = $this->getJsonHttpResult();
        $this->assertNoMessage($result);
        $this->assertResultSchema(self::$tokenSchema, $result);
        $this->assertSameUser(self::$adminUser, $result['result']['user']);
        $this->assertArrayNotHasKey('password', $result['result']['user']);
        $token = $result['result']['token'];

        // Check that new token works
        $this->setHeaders([
            "X-Auth-Token" => $token,
        ]);
        $this->post('/auth/whoami');
        $this->assertResponseSuccess();
        $result = $this->getJsonHttpResult();
        $this->assertNoMessage($result);
        $this->assertResultSchema(self::$tokenSchema, $result);
        $this->assertSameUser(self::$adminUser, $result['result']['user']);

        // Login again
        $this->clearHeaders();
        $this->post('/auth/login', json_encode([
            'login' => self::$adminUser['login'],
            'password' => self::$adminUser['password'],
        ]));
        $this->assertResponseSuccess();
        $result = $this->getJsonHttpResult();
        $this->assertNoMessage($result);
        $this->assertResultSchema(self::$tokenSchema, $result);
        $this->assertSameUser(self::$adminUser, $result['result']['user']);
        $this->assertArrayNotHasKey('password', $result['result']['user']);
        $token2 = $result['result']['token'];

        // Test that new token is different
        $this->assertNotEquals($token, $token2);

        // Check login with wrong password
        $this->clearHeaders();
        $this->post('/auth/login', json_encode([
            'login' => self::$adminUser['login'],
            'password' => 'adcdefghij',
        ]));
        $this->assertResponseSuccess();
        $result = $this->getJsonHttpResult();
        $this->assertMessage($result, "error", "Password incorrect");
        $this->assertResultSame(false, $result);

        // Check login with wrong login
        $this->clearHeaders();
        $this->post('/auth/login', json_encode([
            'login' => 'someone',
            'password' => self::$adminUser['password'],
        ]));
        $this->assertResponseSuccess();
        $result = $this->getJsonHttpResult();
        $this->assertMessage($result, "error", "User not found");
        $this->assertResultSame(false, $result);

        // Check login with wrong login and password
        $this->clearHeaders();
        $this->post('/auth/login', json_encode([
            'login' => 'someone',
            'password' => 'adcdefghij',
        ]));
        $this->assertResponseSuccess();
        $result = $this->getJsonHttpResult();
        $this->assertMessage($result, "error", "User not found");
        $this->assertResultSame(false, $result);

        // Check login with missing login
        $this->clearHeaders();
        $this->post('/auth/login', json_encode([
            'password' => 'adcdefghij',
        ]));
        $this->assertResponseSuccess();
        $result = $this->getJsonHttpResult();
        $this->assertMessage($result, "error", "Missing parameter(s)");
        $this->assertResultSame(false, $result);

        // Check login with missing password
        $this->clearHeaders();
        $this->post('/auth/login', json_encode([
            'login' => self::$adminUser['login'],
        ]));
        $this->assertResponseSuccess();
        $result = $this->getJsonHttpResult();
        $this->assertMessage($result, "error", "Missing parameter(s)");
        $this->assertResultSame(false, $result);

        // Check login with missing login and password
        $this->clearHeaders();
        $this->post('/auth/login', json_encode([
        ]));
        $this->assertResponseSuccess();
        $result = $this->getJsonHttpResult();
        $this->assertMessage($result, "error", "Missing parameter(s)");
        $this->assertResultSame(false, $result);

        // Check login with with password2 instead of password
        $this->clearHeaders();
        $this->post('/auth/login', json_encode([
            'login' => self::$adminUser['login'],
            'password2' => self::$adminUser['password'],
        ]));
        $this->assertResponseSuccess();
        $result = $this->getJsonHttpResult();
        $this->assertMessage($result, "error", "Missing parameter(s)");
        $this->assertResultSame(false, $result);

        // Check login with non JSON POST data
        $this->clearHeaders();
        $this->post('/auth/login', "adcd");
        $this->assertResponseSuccess();
        $result = $this->getJsonHttpResult();
        $this->assertMessage($result, "error", "Failed parsing JSON request");
        $this->assertResultSame(null, $result);

        // Check login with empty POST data
        $this->clearHeaders();
        $this->post('/auth/login', "");
        $this->assertResponseSuccess();
        $result = $this->getJsonHttpResult();
        $this->assertMessage($result, "error", "Missing parameter(s)");
        $this->assertResultSame(false, $result);

        // Check login with inactive user
        $db = self::$mongo->selectDB(TEST_DB_NAME);
        $db->users->update([], ['$set' => ['active' => false]]);
        $this->clearHeaders();
        $this->post('/auth/login', json_encode([
                'login' => self::$adminUser['login'],
                'password' => self::$adminUser['password'],
        ]));
        $this->assertResponseSuccess();
        $result = $this->getJsonHttpResult();
        $this->assertMessage($result, "error", "User is inactive");
        $this->assertResultSame(false, $result);
        $db->users->update([], ['$set' => ['active' => true]]);

        $result = ['token1' => $token, 'token2' => $token2];
        return $result;
    }

    /**
     * @depends testLogin
     */
    public function testLogout(array $tokens)
    {
        // Logout with token1
        $this->setHeaders([
            "X-Auth-Token" => $tokens['token1'],
        ]);
        $this->post('/auth/logout');
        $this->assertResponseSuccess();
        $result = $this->getJsonHttpResult();
        $this->assertNoMessage($result);
        $this->assertResultSame(true, $result);

        // Check that token1 does not work
        $this->setHeaders([
            "X-Auth-Token" => $tokens['token1'],
        ]);
        $this->post('/auth/whoami');
        $this->assertResponseSuccess();
        $result = $this->getJsonHttpResult();
        $this->assertMessage($result, 'error', 'Invalid authentication token');
        $this->assertResultSame(null, $result);

        // Check that logout using that token returns correct error
        $this->setHeaders([
            "X-Auth-Token" => $tokens['token1'],
        ]);
        $this->post('/auth/logout');
        $this->assertResponseSuccess();
        $result = $this->getJsonHttpResult();
        $this->assertMessage($result, 'error', 'Invalid authentication token');
        $this->assertResultSame(null, $result);

        // Logout with random token
        $this->setHeaders([
            "X-Auth-Token" => hash('sha512', microtime()),
        ]);
        $this->post('/auth/logout');
        $this->assertResponseSuccess();
        $result = $this->getJsonHttpResult();
        $this->assertMessage($result, 'error', 'Invalid authentication token');
        $this->assertResultSame(null, $result);

        // Logout with invalid token
        $this->setHeaders([
            "X-Auth-Token" => "123",
        ]);
        $this->post('/auth/logout');
        $this->assertResponseSuccess();
        $result = $this->getJsonHttpResult();
        $this->assertMessage($result, 'error', 'Invalid authentication token');
        $this->assertResultSame(null, $result);

        // Logout without token
        $this->clearHeaders();
        $this->post('/auth/logout');
        $this->assertResponseSuccess();
        $result = $this->getJsonHttpResult();
        $this->assertMessage($result, 'error', 'No authentication token');
        $this->assertResultSame(null, $result);

        // Logout with token2
        $this->setHeaders([
            "X-Auth-Token" => $tokens['token2'],
        ]);
        $this->post('/auth/logout');
        $this->assertResponseSuccess();
        $result = $this->getJsonHttpResult();
        $this->assertNoMessage($result);
        $this->assertResultSame(true, $result);

        // Check that self::$token still works
        $this->setHeaders([
            "X-Auth-Token" => self::$token,
        ]);
        $this->post('/auth/whoami');
        $this->assertResponseSuccess();
        $result = $this->getJsonHttpResult();
        $this->assertNoMessage($result);
        $this->assertResultSchema(self::$tokenSchema, $result);
        $this->assertSameUser(self::$adminUser, $result['result']['user']);
    }

    /**
     * @depends testBootstrap
     */
    public function testWhoami()
    {
        // Call whoami without token
        $this->clearHeaders();
        $this->post('/auth/whoami');
        $this->assertResponseSuccess();
        $result = $this->getJsonHttpResult();
        $this->assertMessage($result, 'error', 'No authentication token');
        $this->assertResultSame(null, $result);

        // Call whoami with random token
        $this->setHeaders([
            "X-Auth-Token" => hash('sha512', microtime()),
        ]);
        $this->post('/auth/whoami');
        $this->assertResponseSuccess();
        $result = $this->getJsonHttpResult();
        $this->assertMessage($result, 'error', 'Invalid authentication token');
        $this->assertResultSame(null, $result);

        // Call whoami with illegal token
        $this->setHeaders([
            "X-Auth-Token" => "abcdz",
        ]);
        $this->post('/auth/whoami');
        $this->assertResponseSuccess();
        $result = $this->getJsonHttpResult();
        $this->assertMessage($result, 'error', 'Invalid authentication token');
        $this->assertResultSame(null, $result);

        // Call whoami with existing token
        $this->setHeaders([
            "X-Auth-Token" => self::$token,
        ]);
        $this->post('/auth/whoami');
        $this->assertResponseSuccess();
        $result = $this->getJsonHttpResult();
        $this->assertNoMessage($result);
        $this->assertResultSchema(self::$tokenSchema, $result);
        $this->assertSameUser(self::$adminUser, $result['result']['user']);
    }
}
