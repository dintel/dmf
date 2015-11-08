<?php
namespace Application\Controller;

use Application\Json\Response as JsonResponse;
use Application\Json\Message as JsonMessage;

abstract class AbstractCollectionController extends BaseController
{
    private $objectType;
    protected $mapper;

    protected function __construct($objectType)
    {
        $this->objectType = $objectType;
    }

    public function index()
    {
        $filter = $this->getParameter('filter', []);
        $order = $this->getParameter('order');
        if (!is_array($filter)) {
            $this->log->err("Illegal filter parameter - must be array or undefined");
            return JsonResponse::model(null, JsonMessage::error("Illegal filter parameter - must be array or undefined"));
        }
        if ($order !== null && !is_array($order)) {
            $this->log->err("Illegal order parameter - must be array, null or undefined");
            return JsonResponse::model(null, JsonMessage::error("Illegal order parameter - must be array, null or undefined"));
        }
        return JsonResponse::model($this->mapper->fetchObjects($this->objectType, $filter, $order));
    }

    public function get()
    {
        $id = $this->getParameter('id');

        if (!isset($id)) {
            $this->log->err("Missing ID parameter");
            return JsonResponse::model(null, JsonMessage::error("Missing ID parameter"));
        }

        $obj = $this->mapper->findObject($this->objectType, $id);
        if ($obj === null) {
            $this->log->err("Object not found");
            return JsonResponse::model(null, JsonMessage::error("Object not found"));
        }

        return JsonResponse::model($obj);
    }

    public function save()
    {
        $data = $this->getParameter("data");

        if ($data === null) {
            $this->log->err("Missing data parameter");
            return JsonResponse::model(null, JsonMessage::error("Missing data parameter"));
        }

        if (!is_array($data)) {
            $this->log->err("Wrong data parameter");
            return JsonResponse::model(null, JsonMessage::error("Wrong data parameter"));
        }

        $this->log->debug('Validating object');
        $validation = $this->validate($data);
        if ($validation !== true) {
            $this->log->err($validation->getText());
            return JsonResponse::model(null, $validation);
        }

        $this->log->debug('Validation passed successfully');
        $obj = isset($data['id']) ? $this->mapper->findObject($this->objectType, $data['id']) : $this->mapper->newObject($this->objectType, $data);

        unset($data['id']);
        $obj->mergeData($data);
        $obj->save();
        return JsonResponse::model($obj, $this->savePostHook($obj));
    }

    public function delete()
    {
        $ids = $this->getParameter("ids");

        if (!isset($ids)) {
            $this->log->err("Missing IDs parameter");
            return JsonResponse::model(null, JsonMessage::error("Missing IDs parameter"));
        }

        if (!is_array($ids)) {
            $this->log->err("Parameter ids must be an array");
            return JsonResponse::model(null, JsonMessage::error("Parameter ids must be an array"));
        }

        $objs = [];
        foreach ($ids as $id) {
            $obj = $this->mapper->findObject($this->objectType, $id);
            if ($obj === null) {
                $id = self::convertToString($id);
                $this->log->err("Object ID {$id} not found");
                return JsonResponse::model(0, JsonMessage::error("Object ID {$id} not found"));
            }
            $objs[] = $obj;
        }

        $hookResult = $this->deletePreHook($objs);
        if ($hookResult !== true) {
            $this->log->err($hookResult);
            return JsonResponse::model(0, $hookResult);
        }
        foreach ($objs as $obj) {
            $obj->delete();
        }

        $count = count($objs);
        return JsonResponse::model($count, $this->deletePostHook($objs));
    }

    protected function convertToString($id)
    {
        return is_array($id) ? "array(".count($id).")" : (string)$id;
    }

    /**
     * Function called to validate data before saving it
     * @param array $data data to save into collection
     * @return bool|Application\JsonServer\Message true on valid data or Message
     * to return to user on failure
     */
    abstract protected function validate(array $data);

    /**
     * Hook called after object is saved
     * @param mixed $obj object that was saved to DB
     * @return Application\JsonServer\Message message that will be returned to
     * user
     */
    abstract protected function savePostHook($obj);

    /**
     * Hook called before deleting object to determine if delete may be
     * performed
     * @param array $objs array of objects to delete
     * @return bool whether objects can be deleted
     */
    abstract protected function deletePreHook(array $objs);

    /**
     * Hook called after delete was successful
     * @param array $objs array of deleted objects
     * @return Application\JsonServer\Message message that will be returned to
     * user
     */
    abstract protected function deletePostHook(array $objs);
}
