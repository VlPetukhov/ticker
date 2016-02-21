<?php
/**
 * @class Processor
 * @namespace processor
 */

namespace processor;


use app\App;
use PDO;

include_once('../app/autoloader.php');

$processor = new Processor();
$processor->run();

class Processor {

    protected $_connection;

    protected $_periods = [];
    protected $_currencyPairs = [];

    /** Tables description */
    public static $currencyDbInfo = [
        'tableName' => 'currency_pair',
    ];
    public static $periodsDbInfo = [
        'tableName' => 'periods',
    ];
    public static $yahooDbInfo = [
        'rawDataTableName' => 'yahoo_raw_data',
        'rawDataFields' =>[
            'pair_id', 'ts', 'ask', 'bid'
        ],
        'statTableName' => 'yahoo_stat',
        'statDataFields' => [
            'pair_id', 'period_id', 'ts', 'ask', 'bid'
        ],
    ];
    public static $btceDbInfo = [
        'rawDataTableName' => 'btce_raw_data',
        'rawDataFields' =>[
            'pair_id', 'ts', 'ask', 'bid', 'high', 'low', 'avg_val', 'vol', 'vol_cur'
        ],
        'statTableName' => 'btce_stat',
        'statDataFields' => [
            'pair_id', 'period_id', 'ts', 'ask', 'bid', 'high', 'low', 'avg_val', 'vol', 'vol_cur'
        ],
    ];


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_connection = App::instance()->getDb();

        $tableName = static::$periodsDbInfo['tableName'];
        $sql = "SELECT id, value FROM {$tableName}";

        $stmnt = $this->_connection->query($sql);
        $results = $stmnt->fetchAll(PDO::FETCH_ASSOC);

        foreach ( $results as $result ) {
            $this->_periods[(int)$result['id']] = (int)$result['value'];
        }

        $currencyDbName = static::$currencyDbInfo['tableName'];
        $sql = "SELECT id ,pair FROM {$currencyDbName}";
        $stmnt = $this->_connection->query($sql);
        $results = $stmnt->fetchAll(PDO::FETCH_ASSOC);

        foreach ( $results as $result ) {
            $this->_currencyPairs[$result['pair']] =(int)$result['id'];
        }
    }

    /**
     * Main method for data processing
     */
    public function run()
    {
        foreach ( $this->_periods as $perId => $perVal ) {
            $this->getAvgYahoo($perId);
            $this->getAvgBtce($perId);
        }
    }

    /**
     * Tries to get last processed time
     *
     * @param int    $destPerId
     * @param string $tableName
     * @param string $fieldName
     *
     * @return bool|int
     */
    protected function getLastTsForPeriod( $destPerId, $tableName, $fieldName = 'ts' )
    {
        $sql = "SELECT {$fieldName} FROM {$tableName} WHERE period_id = {$destPerId} ORDER BY {$fieldName} DESC LIMIT 1";

        $stmnt = $this->_connection->query($sql);

        if ( $stmnt ) {
            $result = $stmnt->fetch(PDO::FETCH_ASSOC)[$fieldName];
            if ( $result ) {

                return (int)$result;
            }
        }

        return false;
    }

    /**
     * Tries to get last processed time from raw data table
     * @param string $tableName
     * @param string $fieldName
     *
     * @return bool|int
     */
    protected function getLastTsForRawData( $tableName, $fieldName = 'ts' )
    {
        $sql = "SELECT {$fieldName} FROM {$tableName} ORDER BY {$fieldName} DESC LIMIT 1";
        $stmnt = $this->_connection->query($sql);

        if ( $stmnt ) {
            $result = $stmnt->fetch(PDO::FETCH_ASSOC)[$fieldName];
            if ( $result ) {

                return (int)$result;
            }
        }

        return false;
    }

    /**
     * Calculates average period values for currency pairs
     * @param $destPerId
     *
     * @return bool
     */
    protected function getAvgYahoo( $destPerId )
    {
        if ( !in_array($destPerId, array_keys($this->_periods))) {
            return false;
        }

        $rawTableName = static::$yahooDbInfo['rawDataTableName'];
        $avgTableName = static::$yahooDbInfo['statTableName'];

        $srcTable = ($destPerId === array_keys($this->_periods)[0]) ? $rawTableName : $avgTableName;
        $lastTs = 0;

        //process from the stat table (raw data)
        if ( $srcTable === static::$yahooDbInfo['rawDataTableName'] ) {

             $lastTs = $this->getLastTsForPeriod($destPerId, $avgTableName);

            if (false === $lastTs) {
                //no avg data was made - method should start from the very beginning
                $lastTs = $this->getLastTsForRawData($rawTableName);

                if ( false === $lastTs) {
                    //no record in stat table - method should return
                    return false;
                }
            }

            // get data from the stat table
            $periodsEnd = $lastTs + $this->_periods[$destPerId];
            $currTime = time();

            $this->_connection->beginTransaction();

            while ( $periodsEnd <= $currTime ) {
                //only finished period should get to the avg table!
                $sql = "SELECT pair_id, AVG(ask) AS ask, AVG(bid) AS bid FROM {$rawTableName}
                        WHERE ts > {$lastTs} AND ts <= {$periodsEnd} GROUP BY pair_id";
                $stmnt = $this->_connection->query($sql);

                if ( false === $stmnt ) {
                    $this->_connection->rollBack();

                    break;
                }

                $result = $stmnt->fetchAll(PDO::FETCH_ASSOC);

                if ( !empty($result) ) {
                    $sql = "INSERT INTO {$avgTableName} ( pair_id, period_id, ts, ask, bid )
                            VALUES ( :pair_id, :period_id, :ts, :ask, :bid )";
                    $stmnt = $this->_connection->prepare($sql);

                    foreach ( $result as $data ){
                        $stmnt->execute([
                            ':pair_id'    => (int)$data['pair_id'],
                            ':period_id'  => (int)$destPerId,
                            ':ts'      => $periodsEnd,
                            ':ask' => (float)$data['ask'],
                            ':bid' => (float)$data['bid']
                        ]);
                    }
                }
                //set next period
                $lastTs = $periodsEnd;
                $periodsEnd = $lastTs + $this->_periods[$destPerId];
            }

            $this->_connection->commit();

            return true;
        }

        //process from the avg table
        //get previous array key from $this->_periods
        $subPeriodId = array_keys($this->_periods)[array_flip(array_keys($this->_periods))[$destPerId] - 1];

        //get last TS from average table
        $lastTs = $this->getLastTsForPeriod($destPerId, $avgTableName);

        if (false === $lastTs) {
            //no avg data was made - method should start from the very beginning
            $lastTs = $this->getLastTsForPeriod($subPeriodId, $avgTableName);

            if ( false === $lastTs) {
                //no record in avg table - method should return
                return false;
            }
        }

        // get data from the stat table
        $periodsEnd = $lastTs + $this->_periods[$destPerId];
        $currTime = time();

        $this->_connection->beginTransaction();

        while ( $periodsEnd <= $currTime ) {
            //only finished period should get to the avg table!
            $sql = "SELECT pair_id, AVG(ask) AS ask, AVG(bid) AS bid FROM {$avgTableName}
                        WHERE ts > {$lastTs} AND ts <= {$periodsEnd} AND period_id = '{$subPeriodId}' GROUP BY pair_id";
            $stmnt = $this->_connection->query($sql);

            if ( false === $stmnt ) {
                $this->_connection->rollBack();

                break;
            }

            $result = $stmnt->fetchAll(PDO::FETCH_ASSOC);

            if ( !empty($result) ) {
                $sql   = "INSERT INTO {$avgTableName} ( pair_id, period_id, ts, ask, bid )
                            VALUES ( :pair_id, :period_id, :ts, :ask, :bid )";
                $stmnt = $this->_connection->prepare($sql);

                foreach ($result as $data) {
                    $stmnt->execute([
                        ':pair_id'   => (int)$data['pair_id'],
                        ':period_id' => $destPerId,
                        ':ts'        => $periodsEnd,
                        ':ask'       => (float)$data['ask'],
                        ':bid'       => (float)$data['bid']
                    ]);
                }
            }


            //set next period
            $lastTs = $periodsEnd;
            $periodsEnd = $lastTs + $this->_periods[$destPerId];
        }

        $this->_connection->commit();

        return true;
    }

    /**
     * Calculates average period values for currency pairs
     * @param $destPerId
     *
     * @return bool
     */
    protected function getAvgBtce( $destPerId )
    {
        if ( !in_array($destPerId, array_keys($this->_periods))) {
            return false;
        }

        $rawTableName = static::$btceDbInfo['rawDataTableName'];
        $avgTableName = static::$btceDbInfo['statTableName'];

        $srcTable = ($destPerId === array_keys($this->_periods)[0]) ? $rawTableName : $avgTableName;
        $lastTs = 0;

        //process from the stat table (raw data)
        if ( $srcTable === static::$btceDbInfo['rawDataTableName'] ) {

             $lastTs = $this->getLastTsForPeriod($destPerId, $avgTableName);

            if (false === $lastTs) {
                //no avg data was made - method should start from the very beginning
                $lastTs = $this->getLastTsForRawData($rawTableName);

                if ( false === $lastTs) {
                    //no record in stat table - method should return
                    return false;
                }
            }

            // get data from the stat table
            $periodsEnd = $lastTs + $this->_periods[$destPerId];
            $currTime = time();

            $this->_connection->beginTransaction();

            while ( $periodsEnd <= $currTime ) {
            //only finished period should get to the avg table!
            $sql = "SELECT
                      pair_id,
                      AVG(ask) AS ask,
                      AVG(bid) AS bid,
                      AVG(high) AS high,
                      AVG(low) AS low,
                      AVG(avg_val) AS avg_val,
                      AVG(vol) AS vol,
                      AVG(vol_cur) AS vol_cur
                    FROM {$rawTableName}
                    WHERE ts > {$lastTs} AND ts <= {$periodsEnd} GROUP BY pair_id";
            $stmnt = $this->_connection->query($sql);

            if ( false === $stmnt ) {
                $this->_connection->rollBack();

                break;
            }

            $result = $stmnt->fetchAll(PDO::FETCH_ASSOC);

            if ( !empty($result) ) {
                $sql = "INSERT INTO {$avgTableName} (
                              pair_id,
                              period_id,
                              ts,
                              ask,
                              bid,
                              high,
                              low,
                              avg_val,
                              vol,
                              vol_cur
                            )
                            VALUES (
                              :pair_id,
                              :period_id,
                              :ts,
                              :ask,
                              :bid,
                              :high,
                              :low,
                              :avg_val,
                              :vol,
                              :vol_cur
                            )";
                $stmnt = $this->_connection->prepare($sql);

                foreach ($result as $data) {
                    $stmnt->execute([
                        ':pair_id'   => (int)$data['pair_id'],
                        ':period_id' => (int)$destPerId,
                        ':ts'        => $periodsEnd,
                        ':ask'       => (float)$data['ask'],
                        ':bid'       => (float)$data['bid'],
                        ':high'      => (float)$data['high'],
                        ':low'       => (float)$data['low'],
                        ':avg_val'   => (float)$data['avg_val'],
                        ':vol'       => (float)$data['vol'],
                        ':vol_cur'   => (float)$data['vol_cur']
                    ]);
                }
            }
                //set next period
                $lastTs = $periodsEnd;
                $periodsEnd = $lastTs + $this->_periods[$destPerId];
            }

            $this->_connection->commit();

            return true;
        }

        //process from the avg table
        //get previous array key from $this->_periods
        $subPeriodId = array_keys($this->_periods)[array_flip(array_keys($this->_periods))[$destPerId] - 1];

        //get last TS from average table
        $lastTs = $this->getLastTsForPeriod($destPerId, $avgTableName);

        if (false === $lastTs) {
            //no avg data was made - method should start from the very beginning
            $lastTs = $this->getLastTsForPeriod($subPeriodId, $avgTableName);

            if ( false === $lastTs) {
                //no record in avg table - method should return
                return false;
            }
        }

        // get data from the stat table
        $periodsEnd = $lastTs + $this->_periods[$destPerId];
        $currTime = time();

        $this->_connection->beginTransaction();

        while ( $periodsEnd <= $currTime ) {
            //only finished period should get to the avg table!
            $sql = "SELECT
                      pair_id,
                      AVG(ask) AS ask,
                      AVG(bid) AS bid,
                      AVG(high) AS high,
                      AVG(low) AS low,
                      AVG(avg_val) AS avg_val,
                      AVG(vol) AS vol,
                      AVG(vol_cur) AS vol_cur
                    FROM {$avgTableName}
                    WHERE ts > {$lastTs} AND ts <= {$periodsEnd} AND period_id = {$subPeriodId} GROUP BY pair_id";
            $stmnt = $this->_connection->query($sql);

            if ( false === $stmnt ) {
                $this->_connection->rollBack();

                break;
            }

            $result = $stmnt->fetchAll(PDO::FETCH_ASSOC);

            if ( !empty($result) ) {
                $sql = "INSERT INTO {$avgTableName} (
                              pair_id,
                              period_id,
                              ts,
                              ask,
                              bid,
                              high,
                              low,
                              avg_val,
                              vol,
                              vol_cur
                            )
                            VALUES (
                              :pair_id,
                              :period_id,
                              :ts,
                              :ask,
                              :bid,
                              :high,
                              :low,
                              :avg_val,
                              :vol,
                              :vol_cur
                            )";
                $stmnt = $this->_connection->prepare($sql);

                foreach ( $result as $data ){
                    $stmnt->execute([
                        ':pair_id'    => (int)$data['pair_id'],
                        ':period_id'  => (int)$destPerId,
                        ':ts'      => $periodsEnd,
                        ':ask' => (float)$data['ask'],
                        ':bid' => (float)$data['bid'],
                        ':high' => (float)$data['high'],
                        ':low' => (float)$data['low'],
                        ':avg_val' => (float)$data['avg_val'],
                        ':vol' => (float)$data['vol'],
                        ':vol_cur' => (float)$data['vol_cur']
                    ]);
                }
            }

            //set next period
            $lastTs = $periodsEnd;
            $periodsEnd = $lastTs + $this->_periods[$destPerId];
        }

        $this->_connection->commit();

        return true;
    }

} 