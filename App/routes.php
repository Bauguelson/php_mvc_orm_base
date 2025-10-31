<?php

    # Landing page routes
    Router::get("", [AppController::class, "home"]);
    
    Router::cond([Session::class, "requireLogin"], function() {
        Router::get("dashboard", [AppController::class, "dashboard"]);
    });
    
?>