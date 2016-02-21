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

    protected $_connection;

    protected $_periods = [];

    public function __construct()
    {

        $this->_connection = App::instance()->getDb();

        $tableName = Processor::$periodsDbInfo['tableName'];
        $sql = "SELECT id, value, name FROM {$tableName}";

        $stmnt = $this->_connection->query($sql);
        $results = $stmnt->fetchAll(PDO::FETCH_ASSOC);

        foreach ( $results as $result ) {
            $this->_periods[(int)$result['id']] = [
                'value' => (int)$result['value'],
                'name' => $result['name'],
            ];
        }
    }

    public function getYahooAvgData( $periodId, $dateStart = null, $dateEnd = null )
    {
        $periodId = (int)$periodId;

        if ( !in_array($periodId, array_keys($this->_periods))) {
            throw new \Exception('Parameter error. Wrong period ID.');
        }

        $connection = App::instance()->getDb();
        $tableName = Processor::$yahooDbInfo['statTableName'];
        $currencytableName = Processor::$currencyDbInfo['tableName'];
        $tsQuery = '';
        $tsQuery .= ($dateStart) ? ' AND ts >= ' . (int)$dateStart : '';
        $tsQuery .= ($dateEnd) ? ' AND ts <= ' . (int)$dateEnd : '';

        $sql = "SELECT cp.name AS name, ts, ask, bid FROM {$tableName} AS st
                INNER JOIN {$currencytableName} AS cp ON cp.id = st.pair_id
                WHERE st.period_id = {$periodId} {$tsQuery}";
        $stmnt = $connection->query($sql);

        if ( !$stmnt ) {

            return [];
        }

        $result = $stmnt->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }


    public function getBtceAvgData( $periodId, $dateStart = null, $dateEnd = null )
    {
        $periodId = (int)$periodId;

        if ( !in_array($periodId, array_keys($this->_periods))) {
            throw new \Exception('Parameter error. Wrong period ID.');
        }

        $connection = App::instance()->getDb();
        $tableName = Processor::$btceDbInfo['statTableName'];
        $currencytableName = Processor::$currencyDbInfo['tableName'];
        $tsQuery = '';
        $tsQuery .= ($dateStart) ? ' AND ts >= ' . (int)$dateStart : '';
        $tsQuery .= ($dateEnd) ? ' AND ts <= ' . (int)$dateEnd : '';

        $sql = "SELECT cp.name AS name, ts, ask, bid, high, low, avg_val, vol, vol_cur FROM {$tableName} AS st
                LEFT JOIN {$currencytableName} AS cp ON cp.id = st.pair_id
                WHERE st.period_id = {$periodId} {$tsQuery}";
        $stmnt = $connection->query($sql);

        if ( !$stmnt ) {

            return [];
        }

        $result = $stmnt->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }

    public function getPeriodName($periodId)
    {
        $periodId = (int)$periodId;

        if ( !in_array($periodId, array_keys($this->_periods))) {

            return 'Unknown period ID.';
        }

        return $this->_periods[$periodId]['name'];
    }
} 