<?php
require_once '../utils/constants.php';

session_start();

if (isset($_COOKIE['remember_user'])) {
    $token = $_COOKIE['remember_user'];

    $conn = new mysqli("localhost", "root", "", DB_NAME);
    if ($conn->connect_error) {
        return;;
    }

    $stmt = $conn->prepare("DELETE FROM users_tokens WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    setcookie("remember_user", "", time() - 3600, "/");
}

session_unset();
session_destroy();

header('Location: ../login.php');
exit;
