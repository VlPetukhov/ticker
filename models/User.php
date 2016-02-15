<?php
/**
 * User model
 * @class User
 * @namespace models
 */

namespace models;


use app\App;
use app\BaseModel;
use app\IUserIdentity;
use app\Validator;
use PDO;

class User extends BaseModel implements IUserIdentity
{
    /** @var  int */
    protected $id;
    /** @var  string */
    protected $passwordHash;

    //Properties
    public $name;
    public $surname;
    public $email;
    public $password;

    protected static $dbProperties =[
        'id',
        'name',
        'surname',
        'email',
        'password_hash'
    ];

    /**
     * List of properties available for mass assignment
     * @var array
     */
    protected $safeProperties = [
        'name',
        'surname',
        'email',
        'password'
    ];

    /**
     * @return string|void
     */
    public static function tableName()
    {
        return 'user';
    }

    /**
     * @param string|null $scenario
     */
    public function __constructor ( $scenario = null )
    {
        parent::__construct( $scenario );
    }

    /**
     * Model validator
     * @return bool
     */
    public function validate()
    {
        $this->propertyErrors = [];

        Validator::required($this, 'name', ['create']);
        Validator::required($this, 'email', ['create', 'login']);
        Validator::required($this, 'password', ['create', 'login']);

        Validator::isUnique($this, 'email', ['create']);


        Validator::strMaxLength($this, 'name', 30, ['create']);
        Validator::strMaxLength($this, 'surname', 45, ['create']);
        Validator::strMaxLength($this, 'email', 255, ['create']);

        return empty($this->propertyErrors);
    }

    /**
     * Saves model
     * @param bool $validate
     * @return bool
     */
    public function save($validate = true)
    {
        if ($validate && !$this->validate()) {

            return false;
        }

        $tableName = static::tableName();

        $sql = "INSERT INTO {$tableName} (name, surname, email, password_hash)
                VALUES (:name, :surname, :email, :passwordHash)";

        $values = [
            ':name' => $this->name,
            ':surname' => $this->surname,
            ':email' => $this->email,
            ':passwordHash' => $this->generatePasswordHash($this->password)
        ];

        $connection = App::instance()->getDB();
        $smnt = $connection->prepare($sql);

        if ( $smnt->execute($values) ) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function login()
    {
        $dbUser = User::findByProp('email', $this->email);

        if ( isset($dbUser) && $this->generatePasswordHash($this->password) === $dbUser->passwordHash) {
            //change user App for dbUser
            App::instance()->setUser( $dbUser );

            return true;
        }

        $this->addErrorMsg('email', 'Unknown email or password. Try again.');

        return false;
    }

    /**
     * @param string $password
     * @return string
     */
    protected function generatePasswordHash( $password )
    {
        return sha1($password);
    }

    /**
     * Getters and setters
     */

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $value
     */
    public function setId( $value ){
        if ('DbSearch' === $this->scenario) {
            $this->id = (int)$value;
        }
    }

    /**
     * @param string $value
     */
    public function setPassword_hash( $value ){
        if ('DbSearch' === $this->scenario) {
            $this->passwordHash = $value;
        }
    }
} 