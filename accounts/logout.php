<?php
session_start();
session_unset();
session_destroy();

if (isset($_COOKIE['remember_user'])) {
    $token = $_COOKIE['remember_user'];

    $conn = new mysqli("localhost", "root", "", "testdb");
    if ($conn->connect_error) {
        return;;
    }

    $stmt = $conn->prepare("DELETE FROM users_tokens WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();

    setcookie("remember_user", "", time() - 3600, "/");
    $stmt->close();
    $conn->close();
}


header('Location: ../login.php');
exit;
