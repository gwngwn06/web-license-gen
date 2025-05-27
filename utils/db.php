<?php
define("DB_SERVER", "localhost");
define("DB_USERNAME", "root");
define("DB_PASSWORD", "");
define("DB_NAME", "licensedb");

try {
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
} catch (Exception $e) {
    http_response_code(500);
    header('Location: ./error/500.php');
    exit;
}
