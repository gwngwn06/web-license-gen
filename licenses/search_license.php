<?php
session_start();
$currentUser = $_SESSION['current_user'] ?? null;
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($currentUser)) {
}
