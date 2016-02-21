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
        if ( isset ($_GET['periodId'])) {
            $periodId = (int)$_GET['periodId'];
        } else {
            $periodId = 2; //hourly
        }

        $dataSource = new DataSource();
        $this->render(
            'index',
            [
                'periodName' => $dataSource->getPeriodName($periodId),
                'yahooData' => $dataSource->getYahooAvgData($periodId),
                'btceData' => $dataSource->getBtceAvgData($periodId),
            ]);
    }
}