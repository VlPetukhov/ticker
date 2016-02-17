<?php
/**
 * Created by PhpStorm.
 * User: Vladimir
 * Date: 09.02.2016
 * Time: 23:35
 */

namespace controllers;


use app\BaseController;

class SiteController extends BaseController
{

    public function actionIndex()
    {
        $this->render('index');
    }
}