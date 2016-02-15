<?php
/**
 * Validators lib
 */

namespace app;


use PDO;

class Validator
{
    /**
     * Constructor
     */
    protected function __construct()
    {

    }

    /**
     * Clone
     */
    protected function __clone()
    {

    }

    /**
     * @param BaseModel $model
     * @param $propName
     */
    public static function required(BaseModel $model, $propName, $scenario = [])
    {
        if (property_exists($model, $propName) && (null === $model->scenario || in_array($model->scenario, $scenario))) {
            $value = $model->{$propName};
            if (empty($value)) {
                $model->addErrorMsg($propName, 'Required property.');
            }
        }
    }

    /**
     * @param BaseModel $model
     * @param $propName
     */
    public static function isString(BaseModel $model, $propName, $scenario = [])
    {
        if (isset($model->{$propName}) && (null === $model->scenario || in_array($model->scenario, $scenario))) {
            if (!is_string($model->{$propName})) {
                $model->addErrorMsg($propName, 'Should be string.');
            }
        }
    }

    /**
     * @param BaseModel $model
     * @param $propName
     */
    public static function isUnique(BaseModel $model, $propName, $scenario = [])
    {
        if (isset($model->{$propName}) && ( empty($scenario) || in_array($model->scenario, $scenario))) {

            /** @var PDO $connection */
            $connection = App::instance()->getDB();
            $modelTable = $model::tableName();

            $sql = "SELECT COUNT(id) AS cnt FROM {$modelTable} WHERE {$propName} = :value";

            $stmnt  = $connection->prepare($sql);
            $stmnt->execute([':value' => $model->{$propName}]);
            $result = $stmnt->fetch(PDO::FETCH_ASSOC);

            if (false !== $result && 0 == $result['cnt']) {
                return;
            }

            $model->addErrorMsg($propName, 'Already exists.');
        }
    }

    public static function strMaxLength(BaseModel $model, $propName, $maxLength, $scenario = [])
    {
        if (isset($model->{$propName}) && ( empty($scenario) || in_array($model->scenario, $scenario))) {

            if ( !is_string($model->{$propName}) ) {
                $model->addErrorMsg($propName, 'Should be string!');
                return;
            }

            if ( mb_strlen('$model->{$propName}') < $maxLength ) {
                return;
            }

            $model->addErrorMsg($propName, 'Max length should be not greater then ' . $maxLength );
        }
    }
} 