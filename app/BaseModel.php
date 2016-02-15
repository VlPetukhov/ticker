<?php
/**
 * BaseModel
 */

namespace app;


use PDO;
use ReflectionClass;

class BaseModel
{

    public $scenario;

    protected $safeProperties = [];
    protected $propertyErrors = [];

    protected static $dbProperties =[];


    /**
     * @throws \Exception
     * @returns string
     */
    public static function tableName()
    {
        throw new \Exception('Model must override tableName() function!');
    }

    /**
     * @param string $scenario
     */
    public function __construct( $scenario = null )
    {
        $this->scenario = $scenario;
    }

    /**
     * Mass assignment
     * @param array $data
     * @return bool
     */
    public function load(array $data)
    {
        try {
            foreach ($data as $paramName => $paramValue) {
                if (in_array($paramName, $this->safeProperties) && property_exists($this, $paramName)) {
                    $this->{$paramName} = $paramValue;
                }
            }

            return true;
        } catch (\Exception $e) {

            return false;
        }
    }

    /**
     * Helper method. Checks if model has any errors
     *
     * @param string $propName
     *
     * @throws \Exception
     * @return bool
     */
    public function hasErrors( $propName = '' )
    {
        if (empty($propName)) {

            return !empty($this->propertyErrors);
        }

        if ( property_exists($this, $propName)) {

            return isset($this->propertyErrors[$propName]);
        }

        throw new \Exception('Unknown property name.');
    }

    /**
     * Add error message to property
     * @param string $propName
     * @param string $msg
     */
    public function addErrorMsg($propName, $msg)
    {

        if (property_exists($this, $propName)) {
            $this->propertyErrors[$propName][] = $msg;
        }
    }

    /**
     * Returns property error messages
     * @param string $propName
     * @return array
     */
    public function getErrorMsg($propName)
    {

        if (property_exists($this,$propName) && isset($this->propertyErrors[$propName])) {

            return $this->propertyErrors[$propName];
        }

        return [];
    }

    /**
     * Getter
     * @param $varName
     *
     * @throws \Exception
     */
    public function __get( $varName )
    {
        if ( is_string($varName) ) {
            $methodName = 'get' . ucfirst($varName);
            if (method_exists( $this, $methodName )) {
                return $this->$methodName();
            }
        }

        throw new \Exception("Error! Property $varName not found.");
    }

    /**
     * Setter
     * @param $varName
     * @param $value
     * @throws \Exception
     */
    public function __set( $varName, $value )
    {
        if ( is_string($varName) ) {
            $methodName = 'set' . ucfirst($varName);
            if (method_exists( $this, $methodName )) {
                return $this->$methodName( $value );
            }
        }

        throw new \Exception("Error! Property $varName not found.");
    }

    /**
     * @param $id
     * @return null|static
     */
    public static function findByID($id)
    {
        $tableName = static::tableName();

        $properties = implode(',', static::$dbProperties );

        $sql = "SELECT $properties FROM {$tableName}
                WHERE id = :id
                LIMIT 1";

        $connection = App::instance()->getDB();
        $stmnt = $connection->prepare($sql);
        $stmnt->bindValue(':id', $id);
        $stmnt->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, get_called_class(),['DbSearch']);
        $stmnt->execute();

        $result = $stmnt->fetch();

        if ( $result ) {

            return $result;
        }

        return null;
    }

    /**
     * @param string $propName
     * @param string|number $propVal
     * @throws \Exception
     * @return null|static
     */
    public static function findByProp($propName, $propVal)
    {
        if ( ! is_string($propName) ) {
            throw new \Exception('PropName type should be string!');
        }

        if ( !property_exists(get_called_class(), $propName)) {
            throw new \Exception('Property "' . $propName . '" not found in ' . get_called_class() . '!');
        }

        if ( !in_array($propName, static::$dbProperties)) {
            throw new \Exception('Property "' . $propName . '" should be in "static::$dbProperties" array!');
        }

        $tableName = static::tableName();

        $properties = implode(',', static::$dbProperties );

        $sql = "SELECT $properties FROM {$tableName}
                WHERE {$propName} = :{$propName}
                LIMIT 1";

        $connection = App::instance()->getDB();
        $stmnt = $connection->prepare($sql);
        $stmnt->bindValue(":{$propName}", $propVal);
        $stmnt->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, get_called_class(), ['DbSearch']);
        $stmnt->execute();

        $result = $stmnt->fetch();

        if ( $result ) {

            return $result;
        }

        return null;
    }

    /**
     * @param string $propName
     * @param string|number $propVal
     * @throws \Exception
     * @return array|static[]
     */
    public static function findAllByProp($propName, $propVal)
    {
        if ( ! is_string($propName) ) {
            throw new \Exception('PropName type should be string!');
        }

        if ( !property_exists(get_called_class(), $propName)) {
            throw new \Exception('Property "' . $propName . '" not found in ' . get_called_class() . '!');
        }

        if ( !in_array($propName, static::$dbProperties)) {
            throw new \Exception('Property "' . $propName . '" should be in "static::$dbProperties" array!');
        }

        $tableName = static::tableName();

        $properties = implode(',', static::$dbProperties );

        $sql = "SELECT $properties FROM {$tableName}
                WHERE {$propName} = :{$propName}";

        $connection = App::instance()->getDB();
        $stmnt = $connection->prepare($sql);
        $stmnt->bindValue(":{$propName}", $propVal);
        $stmnt->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, get_called_class(),['DbSearch']);
        $stmnt->execute();

        $result = $stmnt->fetchAll();

        if ( $result ) {

            return $result;
        }

        return [];
    }

    public static function countByProp($propName, $propVal)
    {

        if ( ! is_string($propName) ) {
            throw new \Exception('PropName type should be string!');
        }

        if ( !property_exists(get_called_class(), $propName)) {
            throw new \Exception('Property "' . $propName . '" not found in ' . get_called_class() . '!');
        }

        if ( !in_array($propName, static::$dbProperties)) {
            throw new \Exception('Property "' . $propName . '" should be in "static::$dbProperties" array!');
        }

        $tableName = static::tableName();

        $sql = "SELECT COUNT(id) FROM {$tableName}
                WHERE {$propName} = :{$propName}
                LIMIT 1";

        $connection = App::instance()->getDB();
        $stmnt = $connection->prepare($sql);
        $stmnt->bindValue(":{$propName}", $propVal);
        $stmnt->setFetchMode(PDO::FETCH_NUM);
        $stmnt->execute();

        $result = $stmnt->fetch();

        if ( isset($result[0]) ) {

            return $result[0];
        }

        return null;
    }
} 