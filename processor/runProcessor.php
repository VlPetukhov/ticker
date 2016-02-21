<?php
/**
 * Processor runner
 */
namespace processor;

include_once('../app/autoloader.php');

$processor = new Processor();
$processor->run();