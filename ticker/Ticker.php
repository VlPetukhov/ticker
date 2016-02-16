<?php
/**
 * @class Ticker
 *@namespace ticker
 */
namespace ticker;

use app\App;
use PDO;

include_once('../app/autoloader.php');

$ticker = new Ticker();
$ticker->getData();

class Ticker {

    const MAX_PERIOD = 15;

    public function __construct(){
        //create new App instance if no one
        App::instance();
    }

    public function getData()
    {
        $this->getFromYahoo();
        $this->getFromBtcE();
    }

    /**
     * @return array
     */
    protected function getFromYahoo()
    {
        $time = time();

        $sql = "SELECT ts FROM yahoo_statistic ORDER BY ts DESC LIMIT 1";

        $connection = App::instance()->getDB();
        $result = $connection->query($sql)->fetch();

        if ( $result && ($time - static::MAX_PERIOD) < $result['ts']) {

            return false;
        }

        $request = "https://query.yahooapis.com/v1/public/yql?q=select+*+from+yahoo.finance.xchange+where+pair+=+%22USDUAH,USDRUB,EURRUB,USDCNY,USDBTC%22&format=json&env=store%3A%2F%2Fdatatables.org%2Falltableswithkeys&callback=";

        $response = file_get_contents( $request );

        if ( false === $response ) {

            return false;
        }

        $result = json_decode($response);

        $sql = "INSERT INTO yahoo_statistic (pair, name, ts, ask, bid) VALUES (:pair, :name, :ts, :ask, :bid)";
        $stmnt = $connection->prepare($sql);

        foreach ($result->query->results->rate as $curRes ) {
            $stmnt->bindValue(':pair', $curRes->id, PDO::PARAM_STR);
            $stmnt->bindValue(':name', $curRes->Name, PDO::PARAM_STR);
            $stmnt->bindValue(':ts', $time, PDO::PARAM_INT);
            $stmnt->bindValue(':ask', $curRes->Ask, PDO::PARAM_STR);
            $stmnt->bindValue(':bid', $curRes->Bid, PDO::PARAM_STR);

            $stmnt->execute();

            $stmnt->closeCursor();
        }

        return true;
    }

    /**
     * @return array
     */
    protected function getFromBtcE()
    {
        $time = time();

        $sql = "SELECT ts FROM btce_statistic ORDER BY ts DESC LIMIT 1";

        $connection = App::instance()->getDB();
        $result = $connection->query($sql)->fetch();

        if ( $result && ($time - static::MAX_PERIOD) < $result['ts']) {

            return false;
        }

        $request = "https://btc-e.com/api/3/ticker/btc_usd-btc_rur-btc_eur";

        $response = file_get_contents( $request );

        if ( false === $response ) {

            return false;
        }

        $result = json_decode($response);

        $sql = "INSERT INTO btce_statistic (pair, name, ts, ask, bid, high, low, avg_val, vol, vol_cur)
                VALUES (:pair, :name, :ts, :ask, :bid, :high, :low, :avg_val, :vol, :vol_cur)";
        $stmnt = $connection->prepare($sql);

        foreach ($result as $objName=>$obj ) {

            $name = strtoupper(str_replace('_', '', $objName));
            $pairName = strtoupper(str_replace('_', '/', $objName));

            $stmnt->bindValue(':pair', $name, PDO::PARAM_STR);
            $stmnt->bindValue(':name', $pairName, PDO::PARAM_STR);
            $stmnt->bindValue(':ts', $time, PDO::PARAM_INT);
            $stmnt->bindValue(':ask', $obj->buy, PDO::PARAM_STR);
            $stmnt->bindValue(':bid', $obj->sell, PDO::PARAM_STR);
            $stmnt->bindValue(':high', $obj->sell, PDO::PARAM_STR);
            $stmnt->bindValue(':low', $obj->sell, PDO::PARAM_STR);
            $stmnt->bindValue(':avg_val', $obj->sell, PDO::PARAM_STR);
            $stmnt->bindValue(':vol', $obj->sell, PDO::PARAM_STR);
            $stmnt->bindValue(':vol_cur', $obj->sell, PDO::PARAM_STR);

            $stmnt->execute();

            $stmnt->closeCursor();
        }

        return true;
    }
}