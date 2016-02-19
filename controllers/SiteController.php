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
        $this->render('index', ['data' => DataSource::getYahooAvgData('1hour')]);
    }

    public function actionTicker()
    {
        include '../ticker/Ticker.php';
    }
}