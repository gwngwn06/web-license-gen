<?php
require_once '../utils/constants.php';

session_start();
$currentUser = $_SESSION['current_user'] ?? null;

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($currentUser)) {
    $conn = new mysqli("localhost", "root", "", DB_NAME);
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save license. Please try again later.']);
        exit;
    }

    $userId = $currentUser['id'];
    if (!ctype_digit(strval($userId))) {
        echo json_encode(['error' => 'Invalid user id']);
        exit;
    }

    try {
        if ($currentUser['account_type'] == 0) {
            $sql = "
            SELECT u.email, u.account_type, u.mobile_number, u.company_name, u.created_at, r.first_name, r.last_name, r.reseller_code
            FROM users AS u
            JOIN resellers AS r ON u.reseller_id = r.id
            WHERE u.id = ?
        ";
        } else {
            $sql = "SELECT username, email, account_type, created_at FROM users WHERE id = ?";
        }
        // $stmt = $conn->prepare("SELECT email, account_type, reseller_name, mobile_number, company_name, reseller_code, created_at FROM users WHERE id = ?");
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        echo json_encode(['success' => 'User found', 'user' => [
            'email' => $row['email'],
            'accountType' => $row['account_type'],
            'firstName' => $row['first_name'] ?? '',
            'lastName' => $row['last_name'] ?? '',
            'mobileNumber' => $row['mobile_number'] ?? '',
            'companyName' => $row['company_name'] ?? '',
            'resellerCode' => $row['reseller_code'] ?? '',
            'createdAt' => $row['created_at'],
        ]]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'User not found']);
    } finally {
        $conn->close();
    }
}
