<?php
namespace Application\Dispatcher;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Application\Json\Response as JsonResponse;
use Application\Json\Message as JsonMessage;
use Aura\Router\RouterFactory;

class Dispatcher implements ServiceLocatorAwareInterface
{
    use \Zend\ServiceManager\ServiceLocatorAwareTrait;

    protected $params = [];
    protected $path = '';
    protected $result = null;
    protected $jsonOptions = 0;
    protected $headers = null;

    public function getRequestParameters()
    {
        return $this->params;
    }

    public function getRequestParameter($name)
    {
        return isset($this->params[$name]) ? $this->params[$name] : null;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getMethod()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    public function getResult()
    {
        return json_encode($this->result, $this->jsonOptions);
    }

    public function getHeader($name)
    {
        if ($this->headers === null) {
            $this->headers = $this->getHeaders();
        }
        return isset($this->headers[$name]) ? $this->headers[$name] : null;
    }

    protected function getHeaders()
    {
        if(function_exists("apache_request_headers"))
        {
            if($headers = apache_request_headers())
            {
                return $headers;
            }
        }
        $headers = array();
        foreach($_SERVER as $skey => $svalue)
        {
            if(substr($skey, 0, 5) == "HTTP_")
            {
                $name = ucwords(strtolower(str_replace("_", "-", substr($skey, 5))), "-");
                $headers[$name] = $svalue;
            }
        }
        return $headers;
    }

    public function dispatch()
    {
        $config = $this->getServiceLocator()->get('config')['dispatcher'];
        $log = $this->getServiceLocator()->get('log');
        if (isset($config['exception'])) {
            $log->debug("Regitering exception handler {$config['exception']}");
            $exceptionHandler = $this->getServiceLocator()->get($config['exception']);
        }

        try {
            if (!headers_sent()) {
                header('Content-Type: application/json');
            }
            $this->path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $realPath = $this->path;
            $basePath = trim(@$config['basePath'], '/');
            if ($basePath && substr($this->path, 0, strlen($basePath)) == $basePath) {
                $this->path = substr($this->path, strlen($basePath));
            }

            $routerFactory = new RouterFactory($basePath);
            $router = $routerFactory->newInstance();
            if (isset($config['secure'])) {
                $router->setSecure($config['secure']);
            }
            foreach ($config['routes'] as $route) {
                $tokens = isset($route['tokens']) ? $route['tokens'] : array();
                $values = isset($route['values']) ? $route['values'] : array();
                $router->add(null, $route['path'])->addTokens($tokens)->addValues($values);
            }

            foreach ($config['pre'] as $name) {
                $service = $this->getServiceLocator()->get($name);
                $this->result = $service();
                if ($this->result !== null) {
                    return;
                }
            }

            $route = $router->match($realPath, $_SERVER);
            if (!empty($route)) {
                $this->params = $route->params;
                $log->debug("Matched patch parameters");
                $log->debug($route->params);
                $service = $this->getServiceLocator()->get($route->params['service']);
                if (is_callable($service)) {
                    $service();
                }
                $callback = [$service, $route->params['method']];
                $this->result = is_callable($callback) ? call_user_func($callback) : JsonResponse::model(null, JsonMessage::error("Path not found"));
            } else {
                $this->result = JsonResponse::model(null, JsonMessage::error("Path not found"));
            }
        } catch (\Exception $e) {
            if ($exceptionHandler) {
                $this->jsonOptions = JSON_PRETTY_PRINT;
                $this->result = $exceptionHandler($e);
            }
        } finally {
            return $this->getResult();
        }
    }
}
