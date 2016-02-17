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

    protected $connection;

    protected $periods = [
        '5minutes' => 300, //5min
        '1hour' => 3600, //1hr
        '4hours' => 14400, // 4hr
        '24hours' => 86400, // 24hr
        '1week' => 604800, //1 week
        '1month' => 2629745, // 30,43686 days - average tropical month length in seconds
        '1year' => 315556941, // 1year - average tropical year length in seconds
    ];

    protected $yahooStatTbl = 'yahoo_statistic';
    protected $yahooAvgTbl = 'yahoo_avg';

    protected $btceStatTbl = 'btce_statistic';
    protected $btceAvgTbl = 'btce_avg';

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->connection = App::instance()->getDb();
    }

    public function run()
    {
        foreach ( $this->periods as $perName => $perVal ) {
            $this->getAvgYahoo($perName);
            $this->getAvgBtce($perName);
        }
    }

    protected function getAvgYahoo( $destPerName )
    {
        if ( !in_array($destPerName, array_keys($this->periods))) {
            return false;
        }

        $srcTable = ($destPerName === array_keys($this->periods)[0]) ? $this->yahooStatTbl : $this->yahooAvgTbl;
        $lastTs = 0;

        //process from the stat table (raw data)
        if ( $srcTable === $this->yahooStatTbl ) {

            //get last TS from statistic table
            $sql = "SELECT ts FROM {$this->yahooAvgTbl} WHERE period = '{$destPerName}' ORDER BY id DESC LIMIT 1";
            $stmnt = $this->connection->query($sql);

            if ( $stmnt ) {
                $lastTs = ($stmnt->fetch(PDO::FETCH_ASSOC)['ts']) ?: 0;
            }

            if (0 === $lastTs) {
                //no avg data was made - method should start from the very beginning
                $sql = "SELECT ts FROM {$this->yahooStatTbl} ORDER BY id ASC LIMIT 1";
                $stmnt = $this->connection->query($sql);

                if ( $stmnt ) {
                    $lastTs = ($stmnt->fetch(PDO::FETCH_ASSOC)['ts']) ?: 0;
                }

                if ( 0 === $lastTs) {
                    //no record in stat table - method should return
                    return false;
                }
            }

            // get data from the stat table
            $periodsEnd = $lastTs + $this->periods[$destPerName];
            $currTime = time();

            $this->connection->beginTransaction();

            while ( $periodsEnd <= $currTime ) {
                //only finished period should get to the avg table!
                $sql = "SELECT pair, name, AVG(ask) AS avg_ask, AVG(bid) AS avg_bid FROM {$this->yahooStatTbl}
                        WHERE ts > {$lastTs} AND ts <= {$periodsEnd} GROUP BY pair";
                $stmnt = $this->connection->query($sql);

                if ( false === $stmnt ) {
                    $this->connection->rollBack();

                    break;
                }

                $result = $stmnt->fetchAll(PDO::FETCH_ASSOC);


                if ( !empty($result) ) {
                    $sql = "INSERT INTO {$this->yahooAvgTbl} ( pair, name, period, ts, avg_ask, avg_bid )
                            VALUES ( :pair, :name, :period, :ts, :avg_ask, :avg_bid )";
                    $stmnt = $this->connection->prepare($sql);

                    foreach ( $result as $data ){
                        $stmnt->execute([
                            ':pair'    => $data['pair'],
                            ':name'    => $data['name'],
                            ':period'  => $destPerName,
                            ':ts'      => $periodsEnd,
                            ':avg_ask' => $data['avg_ask'],
                            ':avg_bid' => $data['avg_bid']
                        ]);
                    }
                }


                //set next period
                $lastTs = $periodsEnd;
                $periodsEnd = $lastTs + $this->periods[$destPerName];
            }

            $this->connection->commit();

            return true;
        }


        //process from the avg table
        //get previous array key from $this->periods
        $subPeriodName = array_keys($this->periods)[array_flip(array_keys($this->periods))[$destPerName] - 1];
        //get last TS from average table
        $sql = "SELECT ts FROM {$this->yahooAvgTbl} WHERE name = {$destPerName} ORDER BY id DESC LIMIT 1";
        $stmnt = $this->connection->query($sql);

        if ( $stmnt ) {
            $lastTs = ($stmnt->fetch(PDO::FETCH_ASSOC)['ts']) ?: 0;
        }

        if (0 === $lastTs) {
            //no avg data was made - method should start from the very beginning
            $sql = "SELECT ts FROM {$this->yahooAvgTbl} WHERE period = '{$subPeriodName}' ORDER BY id ASC LIMIT 1";
            $stmnt = $this->connection->query($sql);

            if ( $stmnt ) {
                $lastTs = ($stmnt->fetch(PDO::FETCH_ASSOC)['ts']) ?: 0;
            }

            if ( 0 === $lastTs) {
                //no record in avg table - method should return
                return false;
            }
        }

        // get data from the stat table
        $periodsEnd = $lastTs + $this->periods[$destPerName];
        $currTime = time();

        $this->connection->beginTransaction();

        while ( $periodsEnd <= $currTime ) {
            //only finished period should get to the avg table!
            $sql = "SELECT pair,name,AVG(avg_ask) AS avg_ask, AVG(avg_bid) AS avg_bid FROM {$this->yahooAvgTbl}
                        WHERE ts > {$lastTs} AND ts <= {$periodsEnd} AND period = '{$subPeriodName}' GROUP BY pair";
            $stmnt = $this->connection->query($sql);

            if ( false === $stmnt ) {
                $this->connection->rollBack();

                break;
            }

            $result = $stmnt->fetchAll(PDO::FETCH_ASSOC);

            if ( !empty($result) ) {
                $sql   = "INSERT INTO {$this->yahooAvgTbl} ( pair, name, period, ts, avg_ask, avg_bid )
                            VALUES ( :pair, :name, :period, :ts, :avg_ask, :avg_bid )";
                $stmnt = $this->connection->prepare($sql);

                foreach ($result as $data) {
                    $stmnt->execute([
                        ':pair'    => $data['pair'],
                        ':name'    => $data['name'],
                        ':period'  => $destPerName,
                        ':ts'      => $periodsEnd,
                        ':avg_ask' => $data['avg_ask'],
                        ':avg_bid' => $data['avg_bid']
                    ]);
                }
            }


            //set next period
            $lastTs = $periodsEnd;
            $periodsEnd = $lastTs + $this->periods[$destPerName];
        }

        $this->connection->commit();

        return true;
    }

    protected function getAvgBtce( $destPerName )
    {
        if ( !in_array($destPerName, array_keys($this->periods))) {
            return false;
        }

        $srcTable = ($destPerName === array_keys($this->periods)[0]) ? $this->btceStatTbl : $this->btceAvgTbl;
        $lastTs = 0;

        //process from the stat table (raw data)
        if ( $srcTable === $this->btceStatTbl ) {

            //get last TS from statistic table
            $sql = "SELECT ts FROM {$this->btceAvgTbl} WHERE period = '{$destPerName}' ORDER BY id DESC LIMIT 1";
            $stmnt = $this->connection->query($sql);

            if ( $stmnt ) {
                $lastTs = ($stmnt->fetch(PDO::FETCH_ASSOC)['ts']) ?: 0;
            }

            if (0 === $lastTs) {
                //no avg data was made - method should start from the very beginning
                $sql = "SELECT ts FROM {$this->btceStatTbl} ORDER BY id ASC LIMIT 1";
                $stmnt = $this->connection->query($sql);

                if ( $stmnt ) {
                    $lastTs = ($stmnt->fetch(PDO::FETCH_ASSOC)['ts']) ?: 0;
                }

                if ( 0 === $lastTs) {
                    //no record in stat table - method should return
                    return false;
                }
            }

            // get data from the stat table
            $periodsEnd = $lastTs + $this->periods[$destPerName];
            $currTime = time();

            $this->connection->beginTransaction();

            while ( $periodsEnd <= $currTime ) {
                //only finished period should get to the avg table!
                $sql = "SELECT
                          pair,
                          name,
                          AVG(ask) AS avg_ask,
                          AVG(bid) AS avg_bid,
                          AVG(high) AS avg_high,
                          AVG(low) AS avg_low,
                          AVG(avg_val) AS period_avg_val,
                          AVG(vol) AS avg_vol,
                          AVG(vol_cur) AS avg_vol_cur
                        FROM {$this->btceStatTbl}
                        WHERE ts > {$lastTs} AND ts <= {$periodsEnd} GROUP BY pair";
                $stmnt = $this->connection->query($sql);

                if ( false === $stmnt ) {
                    $this->connection->rollBack();

                    break;
                }

                $result = $stmnt->fetchAll(PDO::FETCH_ASSOC);


                if ( !empty($result) ) {
                    $sql = "INSERT INTO {$this->btceAvgTbl} (
                              pair,
                              name,
                              period,
                              ts,
                              avg_ask,
                              avg_bid,
                              avg_high,
                              avg_low,
                              period_avg_val,
                              avg_vol,
                              avg_vol_cur
                            )
                            VALUES (
                              :pair,
                              :name,
                              :period,
                              :ts,
                              :avg_ask,
                              :avg_bid,
                              :avg_high,
                              :avg_low,
                              :period_avg_val,
                              :avg_vol,
                              :avg_vol_cur
                            )";
                    $stmnt = $this->connection->prepare($sql);

                    foreach ( $result as $data ){
                        $stmnt->execute([
                            ':pair'    => $data['pair'],
                            ':name'    => $data['name'],
                            ':period'  => $destPerName,
                            ':ts'      => $periodsEnd,
                            ':avg_ask' => $data['avg_ask'],
                            ':avg_bid' => $data['avg_bid'],
                            ':avg_high' => $data['avg_high'],
                            ':avg_low' => $data['avg_low'],
                            ':period_avg_val' => $data['period_avg_val'],
                            ':avg_vol' => $data['avg_vol'],
                            ':avg_vol_cur' => $data['avg_vol_cur']
                        ]);
                    }
                }


                //set next period
                $lastTs = $periodsEnd;
                $periodsEnd = $lastTs + $this->periods[$destPerName];
            }

            $this->connection->commit();

            return true;
        }


        //process from the avg table
        //get previous array key from $this->periods
        $subPeriodName = array_keys($this->periods)[array_flip(array_keys($this->periods))[$destPerName] - 1];
        //get last TS from average table
        $sql = "SELECT ts FROM {$this->btceAvgTbl} WHERE period = {$destPerName} ORDER BY id DESC LIMIT 1";
        $stmnt = $this->connection->query($sql);

        if ( $stmnt ) {
            $lastTs = ($stmnt->fetch(PDO::FETCH_ASSOC)['ts']) ?: 0;
        }

        if (0 === $lastTs) {
            //no avg data was made - method should start from the very beginning
            $sql = "SELECT ts FROM {$this->btceAvgTbl} WHERE period = '{$subPeriodName}' ORDER BY id ASC LIMIT 1";
            $stmnt = $this->connection->query($sql);

            if ( $stmnt ) {
                $lastTs = ($stmnt->fetch(PDO::FETCH_ASSOC)['ts']) ?: 0;
            }

            if ( 0 === $lastTs) {
                //no record in avg table - method should return
                return false;
            }
        }

        // get data from the stat table
        $periodsEnd = $lastTs + $this->periods[$destPerName];
        $currTime = time();

        $this->connection->beginTransaction();

        while ( $periodsEnd <= $currTime ) {
            //only finished period should get to the avg table!
            $sql = "SELECT
                      pair,
                      name,
                      AVG(avg_ask) AS avg_ask,
                      AVG(avg_bid) AS avg_bid,
                      AVG(avg_high) AS avg_high,
                      AVG(avg_low) AS avg_low,
                      AVG(period_avg_val) AS period_avg_val,
                      AVG(avg_vol) AS avg_vol,
                      AVG(avg_vol_cur) AS avg_vol_cur
                    FROM {$this->btceAvgTbl}
                    WHERE ts > {$lastTs} AND ts <= {$periodsEnd} AND period = '{$subPeriodName}' GROUP BY pair";
            $stmnt = $this->connection->query($sql);

            if ( false === $stmnt ) {
                $this->connection->rollBack();

                break;
            }

            $result = $stmnt->fetchAll(PDO::FETCH_ASSOC);

            if ( !empty($result) ) {
                $sql = "INSERT INTO {$this->btceAvgTbl} (
                              pair,
                              name,
                              period,
                              ts,
                              avg_ask,
                              avg_bid,
                              avg_high,
                              avg_low,
                              period_avg_val,
                              avg_vol,
                              avg_vol_cur
                            )
                            VALUES (
                              :pair,
                              :name,
                              :period,
                              :ts,
                              :avg_ask,
                              :avg_bid,
                              :avg_high,
                              :avg_low,
                              :period_avg_val,
                              :avg_vol,
                              :avg_vol_cur
                            )";
                $stmnt = $this->connection->prepare($sql);

                foreach ( $result as $data ){
                    $stmnt->execute([
                        ':pair'    => $data['pair'],
                        ':name'    => $data['name'],
                        ':period'  => $destPerName,
                        ':ts'      => $periodsEnd,
                        ':avg_ask' => $data['avg_ask'],
                        ':avg_bid' => $data['avg_bid'],
                        ':avg_high' => $data['avg_high'],
                        ':avg_low' => $data['avg_low'],
                        ':period_avg_val' => $data['period_avg_val'],
                        ':avg_vol' => $data['avg_vol'],
                        ':avg_vol_cur' => $data['avg_vol_cur']
                    ]);
                }
            }


            //set next period
            $lastTs = $periodsEnd;
            $periodsEnd = $lastTs + $this->periods[$destPerName];
        }

        $this->connection->commit();

        return true;
    }
} 