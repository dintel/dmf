<?php
namespace Application\Dispatcher;

use Zend\ServiceManager\ServiceLocatorAwareInterface;

class Error implements ServiceLocatorAwareInterface
{
    use \Zend\ServiceManager\ServiceLocatorAwareTrait;

    public function __invoke($error)
    {
        $this->getServiceLocator()->get('log')->emerg($error);
        if ($error instanceof \Exception) {
            $e = $error;
            $result = [];
            while ($e) {
                $result[] = [
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTrace(),
                ];
                $e = $e->getPrevious();
            }
            return $result;
        }
    }
}
