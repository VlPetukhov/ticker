<?php
/**
 * Site index file
 */
namespace web;


use app\App;

include('../app/autoloader.php');

App::instance()->init();

$requestedPath = isset($_GET['requestedPath']) ? $_GET['requestedPath'] : '';

App::instance()->route($requestedPath);

