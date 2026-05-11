<?php

/*
========================================
CROIN — Entry Point
========================================
*/

if (session_status() === PHP_SESSION_NONE) {

    // compatível com PHP antigo
    ini_set('session.cookie_httponly', 1);

    session_start();
}

require_once '../config/config.php';
require_once '../routes/web.php';