<?php
session_start();

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
    // $licenseId = 9;
    $codeVerifier = $_POST['codeVerifier'] ?? null;
    $resellerName = $_POST['resellerName'] ?? null;
    $resellerCode = $_POST['resellerCode'] ?? null;
    $technician = $_POST['technician'] ?? null;
    $companyName = $_POST['companyName'] ?? null;
    $customerName = $_POST['customerName'] ?? null;
    $customerEmailAddress = $_POST['customerEmailAddress'] ?? null;
    $customerAddress = $_POST['customerAddress'] ?? null;
    $customerContactNumber = $_POST['customerContactNumber'] ?? null;
    $mdcPermanentCount = $_POST['MDCPermanentCount'] ?? 0;
    $mdcTrialCount = $_POST['MDCTrialCount'] ?? 0;
    $mdcTrialDays = $_POST['MDCTrialDays'] ?? 40;
    $dncPermanentCount = $_POST['DNCPermanentCount'] ?? 0;
    $dncTrialCount = $_POST['DNCTrialCount'] ?? 0;
    $dncTrialDays = $_POST['DNCTrialDays'] ?? 40;
    $hmiPermanentCount = $_POST['HMIPermanentCount'] ?? 0;
    $hmiTrialCount = $_POST['HMITrialCount'] ?? 0;
    $hmiTrialDays = $_POST['HMITrialDays'] ?? 40;
    $annualMaintenanceExpDate = $_POST['annualMaintenanceExpDate'] ?? null;

    if ($currentUser['account_type'] == '0' && (!empty($mdcPermanentCount) || !empty($dncPermanentCount) || !empty($hmiPermanentCount))) {
        $mdcPermanentCount = 0;
        $dncPermanentCount = 0;
        $hmiPermanentCount = 0;
    }

    $conn = new mysqli("localhost", "root", "", "testdb");
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save license. Please try again later.']);
        exit;
    }
    try {
        if (!empty($licenseId)) {
            $stmt = $conn->prepare("UPDATE licenses SET code_verifier = ?, reseller_name = ?, reseller_code = ?, technician = ?, company_name = ?, customer_name = ?, customer_email = ?, customer_address = ?, customer_contact_number = ?, mdc_permanent_count = ?, mdc_trial_count = ?, mdc_trial_days = ?, dnc_permanent_count = ?, dnc_trial_count = ?, dnc_trial_days = ?, hmi_permanent_count = ?, hmi_trial_count = ?, hmi_trial_days = ?, annual_maintenance_expiration_date = ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("sssssssssiiiiiiiiisii", $codeVerifier, $resellerName, $resellerCode, $technician, $companyName, $customerName, $customerEmailAddress, $customerAddress, $customerContactNumber, $mdcPermanentCount, $mdcTrialCount, $mdcTrialDays, $dncPermanentCount, $dncTrialCount, $dncTrialDays, $hmiPermanentCount, $hmiTrialCount, $hmiTrialDays, $annualMaintenanceExpDate, $licenseId, $userId);
            $stmt->execute();
            $lastId = $licenseId;
        } else {
            $stmt = $conn->prepare("INSERT INTO licenses (user_id, code_verifier, reseller_name, reseller_code, technician, company_name, customer_name, customer_email, customer_address, customer_contact_number, mdc_permanent_count, mdc_trial_count, mdc_trial_days, dnc_permanent_count, dnc_trial_count, dnc_trial_days, hmi_permanent_count, hmi_trial_count, hmi_trial_days, annual_maintenance_expiration_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssssssssiiiiiiiiis", $userId, $codeVerifier, $resellerName, $resellerCode, $technician, $companyName, $customerName, $customerEmailAddress, $customerAddress, $customerContactNumber, $mdcPermanentCount, $mdcTrialCount, $mdcTrialDays, $dncPermanentCount, $dncTrialCount, $dncTrialDays, $hmiPermanentCount, $hmiTrialCount, $hmiTrialDays, $annualMaintenanceExpDate);
            $stmt->execute();
            $lastId = $stmt->insert_id;
        }

        $createdAt = null;
        $fetchStmt = $conn->prepare("SELECT license_created_at FROM licenses WHERE id = ?");
        $fetchStmt->bind_param("i", $lastId);
        $fetchStmt->execute();
        $fetchStmt->bind_result($createdAt);
        $fetchStmt->fetch();
        $fetchStmt->close();

        echo json_encode(['id' => $lastId, 'created_at' => $createdAt, 'message' => 'License saved successfully']);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => 'Failed to save license. Please try again later.', 'err' => $e->getMessage()]);
    } finally {
        $conn->close();
    }
}
