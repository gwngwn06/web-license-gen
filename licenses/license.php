<?php
session_start();
header('Content-Type: application/json');

$currentUser = $_SESSION['current_user'] ?? null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($currentUser)) {
    // print_r($_POST);
    if ($currentUser['id'] != $_POST['userId']) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid user']);
        exit;
    }

    $userId = $_POST['userId'];
    $licenseId = $_POST['licenseId'] ?? null;
    $codeVerifier = $_POST['codeVerifier'] ?? null;
    $resellerName = $_POST['resellerName'] ?? null;
    $resellerCode = $_POST['resellerCode'] ?? null;
    $technician = $_POST['technician'] ?? null;
    $companyName = $_POST['companyName'] ?? null;
    $customerName = $_POST['customerName'] ?? null;
    $customerEmailAddress = $_POST['customerEmail'] ?? null;
    $customerAddress = $_POST['customerAddress'] ?? null;
    $customerContactNumber = $_POST['customerContactNumber'] ?? null;
    $mdcPermanentCount = $_POST['mdcPermanentCount'] ?? 0;
    $mdcTrialCount = $_POST['mdcTrialCount'] ?? 0;
    $mdcTrialDays = $_POST['mdcTrialDays'] ?? 40;
    $dncPermanentCount = $_POST['dncPermanentCount'] ?? 0;
    $dncTrialCount = $_POST['dncTrialCount'] ?? 0;
    $dncTrialDays = $_POST['dncTrialDays'] ?? 40;
    $hmiPermanentCount = $_POST['hmiPermanentCount'] ?? 0;
    $hmiTrialCount = $_POST['hmiTrialCount'] ?? 0;
    $hmiTrialDays = $_POST['hmiTrialDays'] ?? 40;
    $annualMaintenanceExpDate = $_POST['annualMaintenanceExpDate'] ?? null;

    if ($currentUser['account_type'] == '0' && (!empty($mdcPermanentCount) || !empty($dncPermanentCount) || !empty($hmiPermanentCount))) {
        $mdcPermanentCount = 0;
        $dncPermanentCount = 0;
        $hmiPermanentCount = 0;
    }
    if (empty($codeVerifier) || empty($resellerName) || empty($resellerCode) || empty($companyName) || empty($customerName) || empty($customerEmailAddress) || empty($customerAddress) || empty($customerContactNumber)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }
    if (!filter_var($customerEmailAddress, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email format']);
        exit;
    }

    $conn = new mysqli("localhost", "root", "", "testdb");
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save license. Please try again later.']);
        exit;
    }
    try {
        if (!empty($licenseId) && ctype_digit($licenseId)) {
            $checkStmt = $conn->prepare("SELECT id FROM licenses WHERE id = ? AND user_id = ?");
            $checkStmt->bind_param("ii", $licenseId, $userId);
            $checkStmt->execute();
            $checkStmt->store_result();
            if ($checkStmt->num_rows === 0) {
                http_response_code(404);
                echo json_encode(['error' => 'License not found']);
                exit;
            }

            $stmt = $conn->prepare("UPDATE licenses SET code_verifier = ?, reseller_name = ?, reseller_code = ?, technician = ?, company_name = ?, customer_name = ?, customer_email = ?, customer_address = ?, customer_contact_number = ?, mdc_permanent_count = ?, mdc_trial_count = ?, mdc_trial_days = ?, dnc_permanent_count = ?, dnc_trial_count = ?, dnc_trial_days = ?, hmi_permanent_count = ?, hmi_trial_count = ?, hmi_trial_days = ?, annual_maintenance_expiration_date = ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("sssssssssiiiiiiiiisii", $codeVerifier, $resellerName, $resellerCode, $technician, $companyName, $customerName, $customerEmailAddress, $customerAddress, $customerContactNumber, $mdcPermanentCount, $mdcTrialCount, $mdcTrialDays, $dncPermanentCount, $dncTrialCount, $dncTrialDays, $hmiPermanentCount, $hmiTrialCount, $hmiTrialDays, $annualMaintenanceExpDate, $licenseId, $userId);
            $stmt->execute();
            $lastId = $licenseId;
            $isUpdated = true;
        } else {
            $stmt = $conn->prepare("INSERT INTO licenses (user_id, code_verifier, reseller_name, reseller_code, technician, company_name, customer_name, customer_email, customer_address, customer_contact_number, mdc_permanent_count, mdc_trial_count, mdc_trial_days, dnc_permanent_count, dnc_trial_count, dnc_trial_days, hmi_permanent_count, hmi_trial_count, hmi_trial_days, annual_maintenance_expiration_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssssssssiiiiiiiiis", $userId, $codeVerifier, $resellerName, $resellerCode, $technician, $companyName, $customerName, $customerEmailAddress, $customerAddress, $customerContactNumber, $mdcPermanentCount, $mdcTrialCount, $mdcTrialDays, $dncPermanentCount, $dncTrialCount, $dncTrialDays, $hmiPermanentCount, $hmiTrialCount, $hmiTrialDays, $annualMaintenanceExpDate);
            $stmt->execute();
            $lastId = $stmt->insert_id;
            $isUpdated = false;
        }

        $createdAt = null;
        $updatedAt = null;

        $fetchStmt = $conn->prepare("SELECT license_created_at, license_updated_at FROM licenses WHERE id = ?");
        $fetchStmt->bind_param("i", $lastId);
        $fetchStmt->execute();
        $fetchStmt->bind_result($createdAt, $updatedAt);
        $fetchStmt->fetch();

        echo json_encode(['id' => $lastId, 'createdAt' => $createdAt, 'updatedAt' => $updatedAt, 'isUpdated' => $isUpdated, 'message' => 'License saved successfully']);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => 'Failed to save license. Please try again later.', 'err' => $e->getMessage()]);
    } finally {
        $fetchStmt->close();
        $conn->close();
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($currentUser)) {
    $searchQuery = $_GET['search'] ?? '';

    $conn = new mysqli("localhost", "root", "", "testdb");
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save license. Please try again later.']);
        exit;
    }

    $searchParam = '%' . $searchQuery . '%';
    if ($currentUser['account_type'] == '1') {
        $stmt = $conn->prepare("
        SELECT id, user_id, code_verifier, reseller_name, reseller_code, technician, company_name, customer_name, customer_email, customer_address, customer_contact_number, mdc_permanent_count, mdc_trial_count, mdc_trial_days, dnc_permanent_count, dnc_trial_count, dnc_trial_days, hmi_permanent_count, hmi_trial_count, hmi_trial_days, license_created_at, license_updated_at 
        FROM licenses 
        WHERE reseller_name LIKE ?
            OR company_name LIKE ?
            OR customer_name LIKE ?
            OR customer_email LIKE ?
            OR code_verifier LIKE ?
        ORDER BY id DESC 
        LIMIT 10");
        $stmt->bind_param("sssss", $searchParam, $searchParam, $searchParam, $searchParam, $searchParam);
    } else {
        $stmt = $conn->prepare("
        SELECT id, user_id, code_verifier, reseller_name, reseller_code, technician, company_name, customer_name, customer_email, customer_address, customer_contact_number, mdc_permanent_count, mdc_trial_count, mdc_trial_days, dnc_permanent_count, dnc_trial_count, dnc_trial_days, hmi_permanent_count, hmi_trial_count, hmi_trial_days, license_created_at, license_updated_at 
        FROM licenses
        WHERE user_id = ? 
        AND (reseller_name LIKE ? 
            OR company_name LIKE ? 
            OR customer_name LIKE ? 
            OR customer_email LIKE ? 
            OR code_verifier LIKE ?) 
        ORDER BY id DESC 
        LIMIT 10");
        $stmt->bind_param("isssss", $currentUser['id'], $searchParam, $searchParam, $searchParam, $searchParam, $searchParam);
    }
    $stmt->execute();

    $result = $stmt->get_result();
    $licenses = [];

    while ($row = $result->fetch_assoc()) {
        $licenses[] = $row;
    }
    $stmt->close();

    echo json_encode(['result' => $licenses]);
}
