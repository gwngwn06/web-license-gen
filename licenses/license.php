<?php
session_start();
header('Content-Type: application/json');

function isValidDate($date)
{
    $parsedDate = DateTime::createFromFormat('Y-m-d H:i:s', $date);
    return $parsedDate && $parsedDate->format('Y-m-d H:i:s') === $date;
}

function getIdByName($conn, $table, $firstname, $lastname)
{
    $id = null;
    $stmt = $conn->prepare("SELECT id FROM $table WHERE first_name = ? AND last_name = ?");
    $stmt->bind_param("ss", $firstname, $lastname);
    $stmt->execute();
    $stmt->bind_result($id);
    $stmt->fetch();
    $stmt->close();
    return $id;
}

function updateReseller($conn, $resellerId, $resellerCode, $technician)
{
    $stmt = $conn->prepare("UPDATE resellers SET reseller_code = ?, technician = ? WHERE id = ?");
    $stmt->bind_param("ssi", $resellerCode, $technician, $resellerId);
    $stmt->execute();
    $stmt->close();
}

function insertReseller($conn, $firstname, $lastname, $resellerCode, $technician)
{
    $stmt = $conn->prepare("INSERT INTO resellers (first_name, last_name, reseller_code, technician) VALUES(?, ?, ?, ?)");
    $stmt->bind_param("ssss", $firstname, $lastname, $resellerCode, $technician);
    $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();
    return $id;
}

function updateCustomer($conn, $companyName, $address, $contactNumber, $email, $customerId)
{
    $stmt = $conn->prepare("UPDATE customers SET company_name = ?, address = ?, contact_number = ?, email = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $companyName, $address, $contactNumber, $email, $customerId);
    $stmt->execute();
    $stmt->close();
}

function insertCustomer($conn, $resellerId, $firstname, $lastname, $companyName, $address, $contactNumber, $email)
{
    $stmt = $conn->prepare("INSERT INTO customers (reseller_id, first_name, last_name, company_name, address, contact_number, email) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssss", $resellerId, $firstname, $lastname, $companyName, $address, $contactNumber, $email);
    $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();
    return $id;
}

$currentUser = $_SESSION['current_user'] ?? null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($currentUser)) {
    if ($currentUser['id'] != $_POST['userId']  && $currentUser['account_type'] == 0) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid user']);
        exit;
    }

    $userId = $_POST['userId'];
    $licenseId = trim($_POST['licenseId'] ?? '');
    $codeVerifier = trim($_POST['codeVerifier'] ?? '');
    $resellerFirstname = trim($_POST['resellerFirstName'] ?? '');
    $resellerLastname = trim($_POST['resellerLastName'] ?? '');
    $resellerCode = trim($_POST['resellerCode'] ?? '');
    $technician = trim($_POST['technician'] ?? '');
    $companyName = trim($_POST['companyName'] ?? '');
    $customerFirstname = trim($_POST['customerFirstName'] ?? '');
    $customerLastname = trim($_POST['customerLastName'] ?? '');
    $customerEmailAddress = trim($_POST['customerEmail'] ?? '');
    $customerAddress = trim($_POST['customerAddress'] ?? '');
    $customerContactNumber = trim($_POST['customerContactNumber'] ?? '');
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
    $dateLicenseUsed = isValidDate($_POST['dateLicenseUsed']) ? $_POST['dateLicenseUsed'] : null;

    if (
        empty($codeVerifier) || empty($resellerFirstname) || empty($resellerLastname) ||
        empty($resellerCode) || empty($companyName) || empty($customerFirstname) ||
        empty($customerLastname) || empty($customerEmailAddress) || empty($customerAddress) ||
        empty($customerContactNumber)
    ) {
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

    $conn->begin_transaction();
    try {
        // Update License
        // Check if licenseId exists in the database and belongs to the user for an update
        // At this point, admin account can update all licenses, but reseller account can only update their own licenses
        if (!empty($licenseId) && ctype_digit($licenseId)) {
            $checkStmt = $conn->prepare("SELECT id, reseller_id, customer_id FROM licenses WHERE id = ? AND user_id = ?");
            $checkStmt->bind_param("ii", $licenseId, $userId);
            $checkStmt->execute();
            $checkStmt->store_result();
            if ($checkStmt->num_rows === 0) {
                http_response_code(404);
                echo json_encode(['error' => 'License not found']);
                exit;
            }

            // Only admin account can update the permanent count of licenses
            if ($currentUser['account_type'] == 1) {
                $resellerId = getIdByName($conn, "resellers", $resellerFirstname, $resellerLastname);
                if ($resellerId !== null) {
                    updateReseller($conn, $resellerId, $resellerCode, $technician);
                } else {
                    $resellerId = insertReseller($conn, $resellerFirstname, $resellerLastname, $resellerCode, $technician);
                }

                $customerId = getIdByName($conn, "customers", $customerFirstname, $customerLastname);
                if ($customerId !== null) {
                    updateCustomer($conn, $companyName, $customerAddress, $customerContactNumber, $customerEmailAddress, $customerId);
                } else {
                    $customerId = insertCustomer($conn, $resellerId, $customerFirstname, $customerLastname, $companyName, $customerAddress, $customerContactNumber, $customerEmailAddress);
                }

                $stmt = $conn->prepare("UPDATE licenses 
                SET reseller_id = ?, customer_id  = ?, code_verifier = ?,  
                mdc_permanent_count = ?, mdc_trial_count = ?, mdc_trial_days = ?, dnc_permanent_count = ?, 
                dnc_trial_count = ?, dnc_trial_days = ?, hmi_permanent_count = ?, hmi_trial_count = ?, 
                hmi_trial_days = ?, annual_maintenance_expiration_date = ?, service_license_updated_at = ?
                WHERE id = ? AND user_id = ?");
                $stmt->bind_param(
                    "iisiiiiiiiiissii",
                    $resellerId,
                    $customerId,
                    $codeVerifier,
                    $mdcPermanentCount,
                    $mdcTrialCount,
                    $mdcTrialDays,
                    $dncPermanentCount,
                    $dncTrialCount,
                    $dncTrialDays,
                    $hmiPermanentCount,
                    $hmiTrialCount,
                    $hmiTrialDays,
                    $annualMaintenanceExpDate,
                    $dateLicenseUsed,
                    $licenseId,
                    $userId
                );
                $stmt->execute();
            } else {
                // Reseller license update
                // permanent licenses are not included here
                $resellerId = getIdByName($conn, "resellers", $resellerFirstname, $resellerLastname);
                if ($resellerId !== null) {
                    updateReseller($conn, $resellerId, $resellerCode, $technician);
                } else {
                    $resellerId = insertReseller($conn, $resellerFirstname, $resellerLastname, $resellerCode, $technician);
                }

                $customerId = getIdByName($conn, "customers", $customerFirstname, $customerLastname);
                if ($customerId !== null) {
                    updateCustomer($conn, $companyName, $customerAddress, $customerContactNumber, $customerEmailAddress, $customerId);
                } else {
                    $customerId = insertCustomer($conn, $resellerId, $customerFirstname, $customerLastname, $companyName, $customerAddress, $customerContactNumber, $customerEmailAddress);
                }

                $stmt = $conn->prepare("UPDATE licenses 
                SET reseller_id = ?, customer_id  = ?, code_verifier = ?,
                mdc_trial_count = ?, mdc_trial_days = ?, dnc_trial_count = ?, dnc_trial_days = ?,  hmi_trial_count = ?, 
                hmi_trial_days = ?, annual_maintenance_expiration_date = ?, service_license_updated_at = ?
                WHERE id = ? AND user_id = ?");
                $stmt->bind_param(
                    "iisiiiiiissii",
                    $resellerId,
                    $customerId,
                    $codeVerifier,
                    $mdcTrialCount,
                    $mdcTrialDays,
                    $dncTrialCount,
                    $dncTrialDays,
                    $hmiTrialCount,
                    $hmiTrialDays,
                    $annualMaintenanceExpDate,
                    $dateLicenseUsed,
                    $licenseId,
                    $userId
                );
                $stmt->execute();
            }
            $lastId = $licenseId;
            $isUpdated = true;
        } else {

            // Generate License
            $resellerId = getIdByName($conn, "resellers", $resellerFirstname, $resellerLastname);
            if ($resellerId !== null) {
                updateReseller($conn, $resellerId, $resellerCode, $technician);
            } else {
                $resellerId = insertReseller($conn, $resellerFirstname, $resellerLastname, $resellerCode, $technician);
            }

            $customerId = getIdByName($conn, "customers", $customerFirstname, $customerLastname);
            if ($customerId !== null) {
                updateCustomer($conn, $companyName, $customerAddress, $customerContactNumber, $customerEmailAddress, $customerId);
            } else {
                $customerId = insertCustomer($conn, $resellerId, $customerFirstname, $customerLastname, $companyName, $customerAddress, $customerContactNumber, $customerEmailAddress);
            }

            $stmt = $conn->prepare("INSERT INTO licenses (user_id, reseller_id, customer_id, code_verifier, mdc_permanent_count, mdc_trial_count,
             mdc_trial_days, dnc_permanent_count, dnc_trial_count, dnc_trial_days, hmi_permanent_count, hmi_trial_count, 
             hmi_trial_days, annual_maintenance_expiration_date, service_license_updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param(
                "iiisiiiiiiiiiss",
                $userId,
                $resellerId,
                $customerId,
                $codeVerifier,
                $mdcPermanentCount,
                $mdcTrialCount,
                $mdcTrialDays,
                $dncPermanentCount,
                $dncTrialCount,
                $dncTrialDays,
                $hmiPermanentCount,
                $hmiTrialCount,
                $hmiTrialDays,
                $annualMaintenanceExpDate,
                $dateLicenseUsed
            );
            $stmt->execute();
            $lastId = $stmt->insert_id;
            $isUpdated = false;
        }
        $stmt->close();

        $createdAt = null;
        $updatedAt = null;
        $resMDCPermanentCount = null;
        $resDNCPermanentCount = null;
        $resHMIPermanentCount = null;

        $fetchStmt = $conn->prepare("SELECT license_created_at, license_updated_at, mdc_permanent_count, dnc_permanent_count, hmi_permanent_count FROM licenses WHERE id = ?");
        $fetchStmt->bind_param("i", $lastId);
        $fetchStmt->execute();
        $fetchStmt->bind_result($createdAt, $updatedAt, $resMDCPermanentCount, $resDNCPermanentCount, $resHMIPermanentCount);
        $fetchStmt->fetch();
        $fetchStmt->close();

        $conn->commit();

        echo json_encode([
            'license' =>
            [
                'id' => $lastId,
                'createdAt' => $createdAt,
                'updatedAt' => $updatedAt,
                'isUpdated' => $isUpdated,
                'mdc' => $resMDCPermanentCount,
                'dnc' => $resDNCPermanentCount,
                'hmi' => $resHMIPermanentCount
            ],
            'message' => 'License saved successfully'
        ]);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => 'Failed to save license. Please try again later.', 'message' => $e->getMessage()]);
    } finally {
        $conn->close();
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($currentUser)) {
    if (isset($_GET['search'])) {
        $searchQuery = $_GET['search'] ?? '';

        $selectedColumnHeader = in_array($_GET['selectedColumnHeader'] ?? '', ['reseller', 'company', 'created', 'updated']) ? $_GET['selectedColumnHeader'] : 'reseller';
        $selectedOrder = in_array($_GET['selectedOrder'] ?? '', ['asc', 'desc']) ? $_GET['selectedOrder'] : 'asc';

        $limit = 10;
        $page = isset($_GET['currentPage']) ? (int)$_GET['currentPage'] : 1;
        $page = max($page, 1);
        $offset = ($page - 1) * $limit;

        $searchQuery = trim($searchQuery);

        $conn = new mysqli("localhost", "root", "", "testdb");
        if ($conn->connect_error) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to save license. Please try again later.']);
            exit;
        }

        try {
            $searchParam = '%' . $searchQuery . '%';
            $sql = "SELECT l.id, l.user_id, l.reseller_id, l.code_verifier, r.first_name AS reseller_first_name, r.last_name AS reseller_last_name, r.reseller_code, r.technician,
            c.company_name, c.first_name AS customer_first_name, c.last_name AS customer_last_name, c.email AS customer_email, c.address AS customer_address, c.contact_number as customer_contact_number, 
            l.mdc_permanent_count, l.mdc_trial_count, l.mdc_trial_days,
            l.dnc_permanent_count, l.dnc_trial_count, l.dnc_trial_days,
            l.hmi_permanent_count, l.hmi_trial_count, l.hmi_trial_days,
            l.license_created_at, l.license_updated_at, l.service_license_updated_at
            FROM licenses AS l
            JOIN resellers AS r ON l.reseller_id = r.id
            JOIN customers AS c ON l.customer_id = c.id
            ";
            // $sql = "SELECT id, user_id, code_verifier, reseller_name, reseller_code, technician, 
            //     company_name, customer_name, customer_email, customer_address, customer_contact_number, 
            //     mdc_permanent_count, mdc_trial_count, mdc_trial_days, dnc_permanent_count, 
            //     dnc_trial_count, dnc_trial_days, hmi_permanent_count, hmi_trial_count, hmi_trial_days, 
            //     license_created_at, license_updated_at 
            //     FROM licenses";

            if ($currentUser['account_type'] == '1') {
                $sql .= " WHERE (r.first_name LIKE ?
                    OR r.last_name LIKE ?
                    OR c.company_name LIKE ?
                    OR c.email LIKE ?
                    OR l.code_verifier LIKE ?
                    )";

                $params = [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam];
                $types = "sssss";
            } else {
                $sql .= " WHERE l.user_id = ?
                    AND (r.first_name LIKE ?
                    OR r.last_name LIKE ?
                    OR c.company_name LIKE ?
                    OR c.email LIKE ?
                    OR l.code_verifier LIKE ?
                    )";

                $params = [$currentUser['id'], $searchParam, $searchParam, $searchParam, $searchParam, $searchParam];
                $types = "isssss";
            }


            if ($selectedColumnHeader == "reseller") {
                $sql .= " ORDER BY r.first_name $selectedOrder LIMIT $limit OFFSET $offset";
            } else if ($selectedColumnHeader == "company") {
                $sql .= " ORDER BY c.company_name $selectedOrder LIMIT $limit OFFSET $offset";
            } else if ($selectedColumnHeader == "updated") {
                $sql .= " ORDER BY l.license_updated_at $selectedOrder LIMIT $limit OFFSET $offset";
            } else if ($selectedColumnHeader == "created") {
                $sql .= " ORDER BY l.license_created_at $selectedOrder LIMIT $limit OFFSET $offset";
            }


            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();

            $result = $stmt->get_result();
            $licenses = [];

            while ($row = $result->fetch_assoc()) {
                $licenses[] = $row;
            }

            if ($currentUser['account_type'] == '1') {
                $totalResult = $conn->query("SELECT COUNT(*) as total FROM licenses");
            } else {
                $totalResult = $conn->query("SELECT COUNT(*) as total FROM licenses WHERE user_id = " . $currentUser['id']);
            }

            $totalRow = $totalResult->fetch_assoc();
            $totalPages = ceil($totalRow['total'] / $limit);

            echo json_encode(['result' => $licenses, 'metadata' => ['totalPage' => $totalPages, 'currentPage' => $page]]);
        } catch (Exception $e) {
            http_response_code(400);
            // echo json_encode(['error' => 'Failed to fetch licenses. Please try again later.', 'err' => $e->getMessage()]);
            echo json_encode(['error' => 'Failed to fetch licenses. Please try again later.']);
            exit;
        } finally {
            $stmt->close();
            $conn->close();
        }
    } else if (isset($_GET['id'])) {
        // check if license exists
        $licenseId = $_GET['id'];
        $userId = $currentUser['id'];

        $conn = new mysqli("localhost", "root", "", "testdb");
        if ($conn->connect_error) {
            http_response_code(500);
            echo json_encode(['error' => 'Something went wrong. Please try again later.']);
            exit;
        }

        try {
            if (ctype_digit($licenseId)) {
                if ($currentUser['account_type'] == 1) {
                    $sql = "SELECT user_id FROM licenses WHERE id = ?";
                    $params = [$licenseId];
                    $types = "i";
                } else {
                    $sql = "SELECT user_id FROM licenses WHERE id = ? AND user_id = ?";
                    $params = [$licenseId, $userId];
                    $types = "ii";
                }
                $stmt = $conn->prepare($sql);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $stmt->store_result();
                if ($stmt->num_rows === 0) {
                    http_response_code(404);
                    echo json_encode(['error' => 'License not found']);
                    exit;
                }
                $fetchedUserId = null;
                $stmt->bind_result($fetchedUserId);
                $stmt->fetch();
                echo json_encode(['success' => 'License found', 'userId' => $fetchedUserId]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'License not found']);
                exit;
            }
        } catch (Exception $e) {
            http_response_code(404);
            echo json_encode(['error' => 'License not found']);
        } finally {
            $conn->close();
        }
    } else if (isset($_GET['action']) && $_GET['action'] == "searchDropdown") {
        $resellerId = $_GET['resellerId'] ?? '';
        $query = trim($_GET['query'] ?? '');
        $dataSearch = in_array($_GET['dataSearch'] ?? '', ['resellers', 'customers']) ? $_GET['dataSearch'] : 'resellers';
        $searchParam = '%' . $query . '%';

        $conn = new mysqli("localhost", "root", "", "testdb");
        if ($conn->connect_error) {
            http_response_code(500);
            echo json_encode(['error' => 'Something went wrong. Please try again later.']);
            exit;
        }

        if ($dataSearch == "resellers") {
            $sql = "SELECT id, first_name, last_name, reseller_code, technician FROM $dataSearch WHERE (first_name LIKE ? OR last_name LIKE ?) ORDER BY first_name LIMIT 10";
            $params = [$searchParam, $searchParam];
            $types = "ss";
        } else if ($dataSearch == "customers" && ctype_digit($resellerId)) {
            $sql = "SELECT id, company_name, first_name, last_name, address, contact_number, email FROM $dataSearch WHERE reseller_id = ? AND (first_name LIKE ? OR last_name LIKE ?) ORDER BY first_name LIMIT 10";
            $params = [$resellerId, $searchParam, $searchParam];
            $types = "iss";
        } else {
            echo json_encode(['success' => 'No customers found']);
            exit;
        }
        try {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();

            $result = $stmt->get_result();
            $rows = [];

            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
            $stmt->close();
            echo json_encode(['success' => $dataSearch . ' found', 'result' => $rows]);
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        } finally {
            $conn->close();
        }
    }
}
