<?php

/**
* Base Controller for BTPost
*/

namespace Controllers;

use \Utility\FieldValidator;
class Base
{
    protected $model;
    protected $fields = [];
    /**
     * Function to instantiate controller
     *
     * @param mixed $request Request Data
     *
     * @throws Exception in case the ID passed in the request is invalid
     *
     * @return void
     */
    public function __construct($request)
    {
        #TODO change the request data type
        $id = isset($request[0]) ? $request[0] : null;
        $modelClass = $this->getModelClass();
        $this->model = new $modelClass($id);
    }

/**
     * Function to save the data in the model object into the DB
     *
     * @param bool $validate Flag to disable validation while saving the data
     * @param array $fields List of parameters to be selectively updated into DB
     *
     * @throws Exception if the validation fails
     * @throws Exception if the model has not been instantiated yet
     *
     * @return string id primary identifier of the record
     */
    public function save($validate = true, $fields = null)
    {
        $this->model->save();
    }

    /**
     * Function to get the Model Clas for the given controller
     *
     * @return string $className Name of the model class
     */
    protected static function getModelClass()
    {
        return str_replace("Controllers\\", "Model\\", get_called_class());
    }

    protected function checkFields($data)
    {
        $checkedData = FieldValidator::checkFields($data, $this->fields);
        return $checkedData;
    }
}
