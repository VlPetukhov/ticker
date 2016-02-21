<?php
/**
 * Created by PhpStorm.
 * User: Vladimir
 * Date: 09.02.2016
 * Time: 23:35
 */

namespace controllers;


use app\BaseController;
use models\DataSource;

class SiteController extends BaseController
{

    public function actionIndex()
    {
        $dataSource = new DataSource();
        $this->render('index', ['data' => $dataSource->getYahooAvgData(2)]);
    }
}