<?php
namespace ApplicationTest\Unit;

use Application\Test\UnitTest;

class UserUnitTest extends UnitTest
{
    protected static $userSchema = [
        'id' => 'string',
        'login' => 'string',
        'type' => 'string',
        'name' => 'string',
        'email' => 'string',
        'active' => 'boolean'
    ];

    protected static $testUser = [
        'login' => 'testSaveUser',
        'password' => 'testSavePassword',
        'type' => \Application\Model\User::TYPE_ADMIN,
        'email' => 'testSaveUser@test.com',
        'name' => 'Test save user',
        'active' => true,
    ];

    public function testUserIndex()
    {
        $this->setUri('/user/index');
        $this->setAuthToken('admin');
        $result = $this->dispatch();
        $this->assertResultArraySchema(self::$userSchema, $result);
    }

    public function testUserSave()
    {
        $this->setUri('/user/save');
        $this->setAuthToken('admin');
        $this->setParameters([
            'data' => self::$testUser,
        ]);
        $result = $this->dispatch();
        $this->assertMessage('success', "Successfully saved user ".self::$testUser['login'], $result);
        $this->assertResultSchema(self::$userSchema, $result);
        return $result['result'];
    }

    /**
     * @depends testUserSave
     */
    public function testUserGet($user)
    {
        $this->setUri('/user/get');
        $this->setAuthToken('admin');
        $this->setParameters([
            'id' => $user['id'],
        ]);
        $result = $this->dispatch();
        $this->assertNoMessage($result);
        $this->assertResultSchema(self::$userSchema, $result);
        return $user;
    }

    /**
     * @depends testUserGet
     */
    public function testUserDelete($user)
    {
        $this->setUri('/user/delete');
        $this->setAuthToken('admin');
        $this->setParameters([
            'ids' => [$user['id']],
        ]);
        $result = $this->dispatch();
        $this->assertResultSame(1, $result);
        $this->assertMessage('success', 'Successfully deleted 1 users', $result);
    }
}
