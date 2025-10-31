<?php
    /*
        Code By Bauguelson
        -> Telegram: NoHelloPlz
        -> Discord: Bauguelson
    */
    require_once("../App/helpers/defines.php");
    require_once("../App/helpers/functions.php");
    require_once("../App/helpers/Router.php");
    require_once("../vendor/autoload.php");
    spl_autoload_register('Router::loader');
    if(DEBUG) { ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL); }
    Session::start();
    include_once("../App/routes.php");
    Router::routeReq();
?>