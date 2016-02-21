<?php
/**
 * TickerRunner
 */
namespace ticker;

include_once('../app/autoloader.php');

$ticker = new Ticker();
$ticker->getData();