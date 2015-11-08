<?php
namespace Application\Controller;

use Application\Json\Response as JsonResponse;
use Application\Json\Message as JsonMessage;
use Application\Model\User;

class Bootstrap extends BaseController
{
    const DEFAULT_PASSWORD_LENGTH=12;

    public function bootstrap()
    {
        $numOfUsers = $this->mapper->countObjects(User::getCollection());
        if ($numOfUsers != 0) {
            $this->log->notice("Can not bootstrap, there are {$numOfUsers} users");
            return JsonResponse::model(null, JsonMessage::error("System is already bootstrapped"));
        }

        $password = User::randomPassword(self::DEFAULT_PASSWORD_LENGTH);
        $user = $this->mapper->newObject('User');
        $user->login = 'admin';
        $user->type = User::TYPE_ADMIN;
        $user->name = 'Admin';
        $user->email = '';
        $user->active = true;
        $user->setPassword($password);
        $user->save();
        $user = $user->jsonSerialize();
        $user['password'] = $password;

        return JsonResponse::model($user);
    }
}
