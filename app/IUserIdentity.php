<?php
/**
 * Created by PhpStorm.
 * User: Vladimir
 * Date: 10.02.2016
 * Time: 22:00
 */

namespace app;


interface IUserIdentity
{
    public function login();
    public function getId();
} 