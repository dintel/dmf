<?php
namespace Application\Controller;

use Zend\Validator\Date as DateValidator;
use Zend\Validator\Regex as RegexValidator;
use Zend\Validator\NotEmpty as NotEmptyValidator;
use Zend\Validator\InArray as InArrayValidator;
use Zend\Validator\EmailAddress as EmailValidator;
use Application\Json\Response as JsonResponse;
use Application\Json\Message as JsonMessage;
use Application\Model\User as UserModel;

class User extends AbstractCollectionController
{
    protected $minPasswordLength = 8;

    public function __construct()
    {
        parent::__construct("User");
    }

    protected function validate(array $data)
    {
        $loginValidator = new RegexValidator(['pattern' => '/^\S+$/']);
        $typeValidator = new InArrayValidator(['haystack' => [UserModel::TYPE_ADMIN, UserModel::TYPE_USER], 'strict' => InArrayValidator::COMPARE_STRICT]);
        $nameValidator = new RegexValidator(['pattern' => '/\w+/']);
        $emailValidator = new EmailValidator();
        $passwordValidator = new RegexValidator(['pattern' => "/^\S{{$this->minPasswordLength},}$/"]);

        if (!isset($data['login'], $data['type'], $data['name'], $data['email'], $data['active'])) {
            return JsonMessage::error("Missing one of data fields");
        }

        if (!$loginValidator->isValid($data['login'])) {
            return JsonMessage::error("Invalid user login specified");
        }
        if (!$typeValidator->isValid($data['type'])) {
            return JsonMessage::error("Invalid user type specified");
        }
        if (!$nameValidator->isValid($data['name'])) {
            return JsonMessage::error("Invalid user name specified");
        }
        if (!$emailValidator->isValid($data['email'])) {
            return JsonMessage::error("Invalid user email specified");
        }
        if (!is_bool($data['active'])) {
            return JsonMessage::error("Invalid user active specified");
        }
        if (isset($data['password']) && !$passwordValidator->isValid($data['password'])) {
            return JsonMessage::error("Invalid user password specified");
        }

        if (!isset($data['id'])) {
            if (!isset($data['password'])) {
                return JsonMessage::error("New user must have valid password");
            }
            $user = $this->mapper->findObjectByProp('User', 'login', $data['login']);
            if ($user !== null) {
                return JsonMessage::error("User with login '{$data['login']}' already exists");
            }
        } else {
            $user = $this->mapper->findObject('User', $data['id']);
            if ($user === null) {
                return JsonMessage::error("User with ID {$data['id']} not found");
            }
        }

        return true;
    }

    protected function savePostHook($obj)
    {
        return JsonMessage::success("Successfully saved user {$obj->login}");
    }

    protected function deletePreHook(array $objs)
    {
        $totalUsers = $this->mapper->countObjects(UserModel::getCollection());
        $deleteUsers = count($objs);
        if ($totalUsers == $deleteUsers) {
            return JsonMessage::error("Can not delete all users in system");
        }
        return true;
    }

    protected function deletePostHook(array $users)
    {
        foreach ($users as $user) {
            $tokens = $this->mapper->fetchObjects('Token', ['user' => $user->getDBRef()]);
            foreach ($tokens as $token) {
                $token->delete();
            }
        }

        $count = count($users);
        return JsonMessage::success("Successfully deleted {$count} users");
    }
}
