<?php
namespace Application\Security;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Application\Json\Response as JsonResponse;
use Application\Json\Message as JsonMessage;

class Authentication implements ServiceLocatorAwareInterface
{
    use \Zend\ServiceManager\ServiceLocatorAwareTrait;

    protected $log;
    protected $mapper;
    protected $token;
    protected $public;

    /**
     * Check authentication
     * @return null on success, Application\Json\Response on failure
     */
    public function __invoke()
    {
        $this->token = null;
        $this->public = null;

        $dispatcher = $this->getServiceLocator()->get('dispatcher');
        $this->log = $this->getServiceLocator()->get('log');
        $this->mapper = $this->getServiceLocator()->get('mapper');
        $this->public = $this->isPublicPath($dispatcher->getPath());

        if (!$this->isPublic()) {
            $this->log->debug("Path '".$dispatcher->getPath()."' is private, authenticating");
            $token = $dispatcher->getHeader("X-Auth-Token");
            if ($token === null) {
                $error = "No authentication token";
                $this->log->debug($error);
                return JsonResponse::model(null, JsonMessage::error($error));
            } else {
                $this->log->debug("Checking token '{$token}'");
                if (!$this->check($token)) {
                    $this->log->debug("Token '{$token}' is invalid");
                    return JsonResponse::model(null, JsonMessage::error('Invalid authentication token'));
                } else {
                    $this->log->debug("Token '{$token}' is valid");
                }
            }
        } else {
            $this->log->debug("Path '".$dispatcher->getPath()."' is public");
        }
    }

    /**
     * Check if path is public (does not require authentication)
     */
    protected function isPublicPath($path)
    {
        $publicRoutes = $this->getServiceLocator()->get('config')["publicPaths"];
        foreach ($publicRoutes as $pattern) {
            if (preg_match($pattern, $path)) {
                $this->log->debug("Path '{$path}' matched by pattern '{$pattern}'");
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

    /**
     * Getter for token
     *
     * @return null|Token return null if authentication did not pass or
     * authentication token
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Check authentication token
     *
     * @param string $token token to check
     * @return mixed null if authentication token is invalid, token object otherwise
     */
    protected function check($token)
    {
        $mongoToken = $this->mapper->findObjectByProp('Token', 'token', $token);
        if ($mongoToken === null) {
            return null;
        }
        $this->token = $mongoToken;
        return true;
    }
}
