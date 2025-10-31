<?php
    define("__ROOT__", realpath(__DIR__."/../.."));
    define("VIEWS", __ROOT__."/Views");
    define("CONTROLLERS", __ROOT__."/App/Controllers");
    define("MODELS", __ROOT__."/App/Models");
    define("CONFIG", __ROOT__."/App/config");
    define("APP_ROOT", "/");
    define('APP_URL', str_replace("index.php", "", (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? "https" : 'http').'://'.$_SERVER["HTTP_HOST"]).APP_ROOT);
    define("__PUBLIC__", __ROOT__."/public");
    define("DATA", __ROOT__."/App/data");
?>