<?php
/**
 * @var app\View $this
 */
use app\App;

?>
<!DOCTYPE html>
<html>
<head lang="en">
    <meta charset="UTF-8">
    <title><?= (isset($this->title) ? $this->title : App::instance()->appName)?></title>
    <link rel="stylesheet" href="/css/bootstrap.min.css">
    <link rel="stylesheet" href="/css/site.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>
    <script type="text/javascript" src="/js/bootstrap.min.js"></script>
</head>
<body>
    <div id="content-container">
        <?=$this->content;?>
        <div class="clearfix"></div>
    </div>
    <div class="footer">
        <span><?=date('Y')?> All rights reserved</span>
    </div>
</body>
</html>