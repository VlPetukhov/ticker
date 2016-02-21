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
        $tsQuery = '';
        $tsQuery .= ($dateStart) ? ' AND ts >= ' . (int)$dateStart : '';
        $tsQuery .= ($dateEnd) ? ' AND ts <= ' . (int)$dateEnd : '';

        $sql = "SELECT name, ts, avg_ask, avg_bid FROM {$tableName} WHERE period_id = {$periodId} {$tsQuery}";
        $stmnt = $connection->query($sql);

        if ( !$stmnt ) {

            return [];
        }

        $result = $stmnt->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }
} 