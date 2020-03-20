<?php

(function ($mainFile) {
    $rootDir = dirname($mainFile, 2);
    error_reporting(E_ALL | E_STRICT);

    require_once "$rootDir/vendor/autoload.php";

    define('ROOT_DIR', $rootDir);
})(__FILE__);
