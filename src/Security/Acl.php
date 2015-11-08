<?php
namespace Application\Security;

use MongoObject\Mapper;
use Zend\Log\Logger;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Application\Json\Response as JsonResponse;
use Application\Json\Message as JsonMessage;

class Acl implements ServiceLocatorAwareInterface
{
    use \Zend\ServiceManager\ServiceLocatorAwareTrait;

    const ERR_ACCESS_DENIED="Access dinied";

    protected $public;

    /**
     * Check ACL
     * @return null on success, Application\Json\Response on failure
     */
    public function __invoke()
    {
        $log = $this->getServiceLocator()->get('log');
        $dispatcher = $this->getServiceLocator()->get('dispatcher');
        $auth = $this->getServiceLocator()->get('Application\Security\Authentication');

        if ($auth->isPublic()) {
            return null;
        }

        $acl = $this->getServiceLocator()->get('config')['acl'];
        $user = $auth->getToken()->getUser();
        if (!isset($acl[$user->type])) {
            return JsonResponse::model(null, JsonMessage::error(self::ERR_ACCESS_DENIED));
        }
        $service = $this->getServiceLocator()->get('Dispatcher')->getRequestParameter("service");
        $method = $this->getServiceLocator()->get('Dispatcher')->getRequestParameter("method");

        $resolution = "deny";
        foreach ($acl[$user->type] as $rule) {
            if (preg_match($rule['service'], $service) && preg_match($rule['method'], $method)) {
                $resolution = $rule['type'];
            }
        }
        if ($resolution !== 'allow') {
            return JsonResponse::model(null, JsonMessage::error(self::ERR_ACCESS_DENIED));
        }
        return null;
    }

    /**
     * Check if path is public (does not require authentication)
     */
    protected function isPublicPath($path)
    {
        $log = $this->getServiceLocator()->get('log');
        $publicRoutes = $this->getServiceLocator()->get('config')["publicPaths"];
        foreach ($publicRoutes as $pattern) {
            if (preg_match($pattern, $path)) {
                $log->debug("Path '{$path}' matched by pattern '{$pattern}'");
                return true;
            }
        }
        return false;
    }

    /**
     * Whether current route is public
     *
     * @return null|bool null if route was not yet checked, boolean indicating
     * whether current request is to a 'public' path.
     */
    public function isPublic()
    {
        return $this->public;
    }
}
