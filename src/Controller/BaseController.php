<?php
namespace Application\Controller;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Application\Json\Response as JsonResponse;
use Application\Json\Message as JsonMessage;

class BaseController implements ServiceLocatorAwareInterface
{
    use \Zend\ServiceManager\ServiceLocatorAwareTrait;

    protected $log;
    protected $mapper;

    protected function getParameter($name, $defaultValue = null)
    {
        return $this->getServiceLocator()->get('request')->getParameter($name, $defaultValue);
    }

    protected function setParameter($name, $value)
    {
        return $this->getServiceLocator()->get('request')->setParameter($name, $value);
    }

    public function __invoke()
    {
        $this->mapper = $this->getServiceLocator()->get('mapper');
        $this->log = $this->getServiceLocator()->get('log');
    }
}
