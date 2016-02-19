<?php
/**
 * Created by PhpStorm.
 * User: Владимир
 * Date: 17.02.2016
 * Time: 14:17
 */

namespace models;


use app\App;
use PDO;
use processor\Processor;

class DataSource{

    public static function getYahooAvgData( $period, $dateStart = null, $dateEnd = null )
    {
        if ( !in_array($period, Processor::getPeriodsNames())) {
            throw new \Exception('Parameter error. Wrong period.');
        }

        $connection = App::instance()->getDb();
        $tableName = Processor::$yahooAvgTblName;
        $tsQuery = '';
        $tsQuery .= ($dateStart) ? ' AND ts >= ' . (int)$dateStart : '';
        $tsQuery .= ($dateEnd) ? ' AND ts <= ' . (int)$dateEnd : '';

        $sql = "SELECT name, ts, avg_ask, avg_bid FROM {$tableName} WHERE period = '{$period}' {$tsQuery}";
        $stmnt = $connection->query($sql);

        if ( !$stmnt ) {

            return [];
        }

        $result = $stmnt->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }
} 