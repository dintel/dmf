<?php
namespace Application\Controller;

use Application\Json\Response as JsonResponse;
use Application\Json\Message as JsonMessage;

class Auth extends BaseController
{
    public function login()
    {
        $login = $this->getParameter('login');
        $password = $this->getParameter('password');

        if (!isset($login, $password)) {
            return JsonResponse::model(false, JsonMessage::error('Missing parameter(s)'));
        }

        $user = $this->mapper->findObjectByProp('User', 'login', $login);
        if ($user === null) {
            return JsonResponse::model(false, JsonMessage::error('User not found'));
        }

        if (!$user->checkPassword($password)) {
            return JsonResponse::model(false, JsonMessage::error('Password incorrect'));
        }

        if (!$user->active) {
            return JsonResponse::model(false, JsonMessage::error('User is inactive'));
        }

        $token = $this->mapper->newObject('Token', [
            'token' => hash('sha512', microtime() . $user->_id . $user->login),
            'user' => $user->getDBRef(),
        ]);
        $token->save();

        return JsonResponse::model($token);
    }

    public function logout()
    {
        $token = $this->getServiceLocator()->get('Application\Security\Authentication')->getToken();
        $token->delete();
        return JsonResponse::model(true);
    }

    public function whoami()
    {
        $token = $this->getServiceLocator()->get('Application\Security\Authentication')->getToken();
        return JsonResponse::model($token);
    }
}
