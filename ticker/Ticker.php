<?php
/**
 * @class Ticker
 *@namespace ticker
 */
namespace ticker;

use app\App;
use PDO;
use processor\Processor;

include_once('../app/autoloader.php');

$ticker = new Ticker();
$ticker->getData();

class Ticker {

    const MAX_PERIOD = 15;

    protected $_connection;
    protected $_currencyPairs = [];

    /**
     * Constructor
     */
    public function __construct(){
        //create new App instance if no one
        $this->_connection = App::instance()->getDb();

        $currencyDbName = Processor::$currencyDbInfo['tableName'];
        $sql = "SELECT id ,pair FROM {$currencyDbName}";
        $stmnt = $this->_connection->query($sql);
        $results = $stmnt->fetchAll(PDO::FETCH_ASSOC);

        foreach ( $results as $result ) {
            $this->_currencyPairs[$result['pair']] =(int)$result['id'];
        }
    }

    /**
     * Main function that gets data from sources
     */
    public function getData()
    {
        $this->getDataFromSource('yahoo');
        $this->getDataFromSource('btce');
    }

    /**
     * @param string $tableName
     * @param string $columnName
     *
     * @return integer|boolean
     */
    protected function getLastProcessedTime( $tableName, $columnName = 'ts' )
    {
        $sql = "SELECT {$columnName} FROM {$tableName} ORDER BY ts DESC LIMIT 1";

        $stmnt = $this->_connection->query($sql);

        if ( $stmnt ) {

            $result = $stmnt->fetch(PDO::FETCH_ASSOC);

            if (false !== $result ) {

                return (int)$result[$columnName];
            }
        }

        return false;
    }

    protected function getDataFromSource( $souceName )
    {
        switch($souceName){
            case 'yahoo':
                $requestStr = "https://query.yahooapis.com/v1/public/yql?q=select+*+from+yahoo.finance.xchange+where+pair+=+%22USDUAH,USDRUB,EURRUB,USDCNY,USDBTC%22&format=json&env=store%3A%2F%2Fdatatables.org%2Falltableswithkeys&callback=";
                break;
            case 'btce':
                $requestStr = "https://btc-e.com/api/3/ticker/btc_usd-btc_rur-btc_eur";
                break;

            default: return false;
        }

        $time = time();

        $dbVarName = strtolower($souceName) . 'DbInfo';
        $tableName = Processor::${$dbVarName}['rawDataTableName'];

        $lastProcTime = $this->getLastProcessedTime($tableName);

        if ( false !== $lastProcTime && ($time - static::MAX_PERIOD) < $lastProcTime) {

            return false;
        }

        $response = file_get_contents( $requestStr );

        if ( false === $response ) {

            return false;
        }

        $result = json_decode($response);

        return $this->saveResults( $result , $souceName );
    }

    /**
     * Saving procedure router
     * @param $result
     * @param $souceName
     * @return bool
     */
    protected function saveResults( $result , $souceName )
    {

        switch($souceName){
            case 'yahoo': return $this->saveYahoo($result);
            case 'btce': return $this->saveBtce($result);
        }
        return false;
    }

    /**
     * @param Object $result
     * @return array
     */
    protected function saveYahoo( $result )
    {
        $tableName = Processor::$yahooDbInfo['rawDataTableName'];

        $sql = "INSERT INTO {$tableName} (pair_id, ts, ask, bid) VALUES (:pair_id, :ts, :ask, :bid)";

        $stmnt = $this->_connection->prepare($sql);

        if ( !$stmnt ) {

            return false;
        }

        $time = time();

        $this->_connection->beginTransaction();

        foreach ($result->query->results->rate as $curRes ) {

            if ( !array_key_exists($curRes->id, $this->_currencyPairs)) {
                continue;
            }

            $stmnt->bindValue(':pair_id', $this->_currencyPairs[$curRes->id], PDO::PARAM_INT);
            $stmnt->bindValue(':ts', $time, PDO::PARAM_INT);
            $stmnt->bindValue(':ask', $curRes->Ask);
            $stmnt->bindValue(':bid', $curRes->Bid);

            $stmnt->execute();
            $stmnt->closeCursor();
        }

        $this->_connection->commit();

        return true;
    }

    /**
     * @param Object $result
     * @return array
     */
    protected function saveBtce( $result )
    {
        $tableName = Processor::$btceDbInfo['rawDataTableName'];

        $sql = "INSERT INTO {$tableName} (pair_id, ts, ask, bid, high, low, avg_val, vol, vol_cur)
                VALUES (:pair_id, :ts, :ask, :bid, :high, :low, :avg_val, :vol, :vol_cur)";

        $stmnt = $this->_connection->prepare($sql);

        if ( !$stmnt ) {

            return false;
        }

        $time = time();

        $this->_connection->beginTransaction();

        foreach ($result as $objName=>$obj ) {

            $name = strtoupper(str_replace('_', '', $objName));

            if ( !array_key_exists($name, $this->_currencyPairs)) {
                continue;
            }

            $stmnt->bindValue(':pair_id', $this->_currencyPairs[$name], PDO::PARAM_INT);
            $stmnt->bindValue(':ts', $time, PDO::PARAM_INT);
            $stmnt->bindValue(':ask', $obj->buy);
            $stmnt->bindValue(':bid', $obj->sell);
            $stmnt->bindValue(':high', $obj->high);
            $stmnt->bindValue(':low', $obj->low);
            $stmnt->bindValue(':avg_val', $obj->avg);
            $stmnt->bindValue(':vol', $obj->vol);
            $stmnt->bindValue(':vol_cur', $obj->vol_cur);

            $stmnt->execute();
            $stmnt->closeCursor();
        }

        $this->_connection->commit();

        return true;
    }
}