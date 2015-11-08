<?php
namespace Application\Dispatcher;

use Zend\ServiceManager\ServiceLocatorAwareInterface;

class LogRequest implements ServiceLocatorAwareInterface
{
    use \Zend\ServiceManager\ServiceLocatorAwareTrait;

    public function __invoke()
    {
        $log = $this->getServiceLocator()->get('log');
        $dispatcher = $this->getServiceLocator()->get('dispatcher');
        $log->debug($dispatcher->getMethod() . " " . $dispatcher->getPath());
    }
}
