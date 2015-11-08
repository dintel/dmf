<?php
namespace Application\Json;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Application\Json\Response as JsonResponse;
use Application\Json\Message as JsonMessage;

class Request implements ServiceLocatorAwareInterface
{
    use \Zend\ServiceManager\ServiceLocatorAwareTrait;

    const ERR_INVALID_BODY="Request must be valid JSON object or array";
    const ERR_PARSE_ERROR="Failed parsing JSON request";

    private $parameters = [];

    public function setParameters(array $params)
    {
        $this->parameters = $params;
    }

    public function setParameter($name, $value)
    {
        $this->parameters[$name] = $value;
    }

    public function getParameter($name, $default = null)
    {
        return isset($this->parameters[$name]) ? $this->parameters[$name] : $default;
    }

    public function __invoke()
    {
        $log = $this->getServiceLocator()->get('log');

        $body = file_get_contents('php://input');
        if ($body !== "") {
            $this->parameters = json_decode($body, true);
            if ($this->parameters === null) {
                $this->parameters = [];
                $log->err(self::ERR_PARSE_ERROR);
                $log->err(json_last_error_msg());
                return JsonResponse::model(null, JsonMessage::error(self::ERR_PARSE_ERROR));
            }
            if (!is_array($this->parameters)) {
                $this->parameters = [];
                $log->err(self::ERR_INVALID_BODY);
                return JsonResponse::model(null, JsonMessage::error(self::ERR_INVALID_BODY));
            }
        }
        return null;
    }
}
