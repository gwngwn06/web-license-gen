<?php
session_start();
header('Content-Type: application/json');

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

    // TODO: fix logic
    $conn->begin_transaction();
    try {
        // Make sure the licenseId exists in the database and belongs to the user
        // At this point, admin account can update all licenses, but reseller account can only update their own licenses
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


            // Only admin account can update the permanent count of licenses
            if ($currentUser['account_type'] == 1) {
                $resellerId = null;
                $stmt1 = $conn->prepare("SELECT id FROM resellers WHERE first_name = ? AND last_name = ?");
                $stmt1->bind_param("ss", $resellerFirstname, $resellerLastname);
                $stmt1->execute();
                $stmt1->bind_result($resellerId);
                $stmt1->fetch();
                $stmt1->close();

                $customerId = null;
                if ($resellerId !== null) {
                    $stmt2 = $conn->prepare("UPDATE resellers SET reseller_code = ?, technician = ? WHERE id = ?");
                    $stmt2->bind_param("ssi", $resellerCode, $technician, $resellerId);
                    $stmt2->execute();
                    $stmt2->close();

                    $stmt3 = $conn->prepare("SELECT id from customers WHERE first_name = ? AND last_name = ?");
                    $stmt3->bind_param("ss", $customerFirstname, $customerLastname);
                    $stmt3->execute();
                    $stmt3->bind_result($customerId);
                    $stmt3->fetch();
                    $stmt3->close();

                    if ($customerId !== null) {
                        $stmt5 = $conn->prepare("UPDATE customers SET company_name = ?, address = ?, contact_number = ?, email = ?");
                        $stmt5->bind_param("ssss", $companyName, $customerAddress, $customerContactNumber, $customerEmailAddress);
                        $stmt5->execute();
                        $stmt5->close();
                    } else {
                        $stmt6 = $conn->prepare("INSERT INTO customers (reseller_id, company_name, first_name, last_name, address, contact_number, email) VALUES(?, ?, ?, ?, ?, ?, ?)");
                        $stmt6->bind_param("issssss", $resellerId, $companyName, $customerFirstname, $customerLastname, $customerAddress, $customerContactNumber, $customerEmailAddress);
                        $stmt6->execute();
                        $customerId = $stmt6->insert_id;
                        $stmt6->close();
                    }
                } else {
                    $stmt4 = $conn->prepare("INSERT INTO resellers (first_name, last_name, reseller_code, technician) VALUES(?, ?, ?, ?)");
                    $stmt4->bind_param("ssss", $resellerFirstname, $resellerLastname, $resellerCode, $technician);
                    $stmt4->execute();
                    $resellerId = $stmt4->insert_id;
                    $stmt4->close();
                }


                $stmt = $conn->prepare("UPDATE licenses 
                SET reseller_id = ?, customer_id  = ?, code_verifier = ?,  
                mdc_permanent_count = ?, mdc_trial_count = ?, mdc_trial_days = ?, dnc_permanent_count = ?, 
                dnc_trial_count = ?, dnc_trial_days = ?, hmi_permanent_count = ?, hmi_trial_count = ?, 
                hmi_trial_days = ?, annual_maintenance_expiration_date = ? 
                WHERE id = ? AND user_id = ?");
                $stmt->bind_param("iisiiiiiiiiisii", $resellerId, $customerId, $codeVerifier, $mdcPermanentCount, $mdcTrialCount, $mdcTrialDays, $dncPermanentCount, $dncTrialCount, $dncTrialDays, $hmiPermanentCount, $hmiTrialCount, $hmiTrialDays, $annualMaintenanceExpDate, $licenseId, $userId);
                $stmt->execute();
            } else {
                $resellerId = null;
                $stmt1 = $conn->prepare("SELECT id FROM resellers WHERE first_name = ? AND last_name = ?");
                $stmt1->bind_param("ss", $resellerFirstname, $resellerLastname);
                $stmt1->execute();
                $stmt1->bind_result($resellerId);
                $stmt1->fetch();
                $stmt1->close();

                $customerId = null;
                if ($resellerId !== null) {
                    $stmt2 = $conn->prepare("UPDATE resellers SET reseller_code = ?, technician = ? WHERE id = ?");
                    $stmt2->bind_param("ssi", $resellerCode, $technician, $resellerId);
                    $stmt2->execute();
                    $stmt2->close();

                    $stmt3 = $conn->prepare("SELECT id from customers WHERE first_name = ? AND last_name = ?");
                    $stmt3->bind_param("ss", $customerFirstname, $customerLastname);
                    $stmt3->execute();
                    $stmt3->bind_result($customerId);
                    $stmt3->fetch();
                    $stmt3->close();

                    if ($customerId !== null) {
                        $stmt5 = $conn->prepare("UPDATE customers SET company_name = ?, address = ?, contact_number = ?, email = ?");
                        $stmt5->bind_param("ssss", $companyName, $customerAddress, $customerContactNumber, $customerEmailAddress);
                        $stmt5->execute();
                        $stmt5->close();
                    } else {
                        $stmt6 = $conn->prepare("INSERT INTO customers (reseller_id, company_name, first_name, last_name, address, contact_number, email) VALUES(?, ?, ?, ?, ?, ?, ?)");
                        $stmt6->bind_param("issssss", $resellerId, $companyName, $customerFirstname, $customerLastname, $customerAddress, $customerContactNumber, $customerEmailAddress);
                        $stmt6->execute();
                        $customerId = $stmt6->insert_id;
                        $stmt6->close();
                    }
                } else {
                    $stmt4 = $conn->prepare("INSERT INTO resellers (first_name, last_name, reseller_code, technician) VALUES(?, ?, ?, ?)");
                    $stmt4->bind_param("ssss", $resellerFirstname, $resellerLastname, $resellerCode, $technician);
                    $stmt4->execute();
                    $resellerId = $stmt4->insert_id;
                    $stmt4->close();
                }


                $stmt = $conn->prepare("UPDATE licenses 
                SET reseller_id = ?, customer_id  = ?, code_verifier = ?,
                mdc_trial_count = ?, mdc_trial_days = ?, dnc_trial_count = ?, dnc_trial_days = ?,  hmi_trial_count = ?, 
                hmi_trial_days = ?, annual_maintenance_expiration_date = ? 
                WHERE id = ? AND user_id = ?");
                $stmt->bind_param("iisiiiiiisii", $resellerId, $customerId, $codeVerifier, $mdcTrialCount, $mdcTrialDays, $dncTrialCount, $dncTrialDays, $hmiTrialCount, $hmiTrialDays, $annualMaintenanceExpDate, $licenseId, $userId);
                $stmt->execute();
            }
            $lastId = $licenseId;
            $isUpdated = true;
        } else {
            $resellerId = null;
            $stmt1 = $conn->prepare("SELECT id FROM resellers WHERE first_name = ? AND last_name = ?");
            $stmt1->bind_param("ss", $resellerFirstname, $resellerLastname);
            $stmt1->execute();
            $stmt1->bind_result($resellerId);
            $stmt1->fetch();
            $stmt1->close();

            $customerId = null;
            if ($resellerId !== null) {
                $stmt2 = $conn->prepare("UPDATE resellers SET reseller_code = ?, technician = ? WHERE id = ?");
                $stmt2->bind_param("ssi", $resellerCode, $technician, $resellerId);
                $stmt2->execute();
                $stmt2->close();

                $stmt3 = $conn->prepare("SELECT id from customers WHERE first_name = ? AND last_name = ?");
                $stmt3->bind_param("ss", $customerFirstname, $customerLastname);
                $stmt3->execute();
                $stmt3->bind_result($customerId);
                $stmt3->fetch();
                $stmt3->close();

                if ($customerId !== null) {
                    $stmt5 = $conn->prepare("UPDATE customers SET company_name = ?, address = ?, contact_number = ?, email = ?");
                    $stmt5->bind_param("ssss", $companyName, $customerAddress, $customerContactNumber, $customerEmailAddress);
                    $stmt5->execute();
                    $stmt5->close();
                } else {
                    $stmt6 = $conn->prepare("INSERT INTO customers (reseller_id, company_name, first_name, last_name, address, contact_number, email) VALUES(?, ?, ?, ?, ?, ?, ?)");
                    $stmt6->bind_param("issssss", $resellerId, $companyName, $customerFirstname, $customerLastname, $customerAddress, $customerContactNumber, $customerEmailAddress);
                    $stmt6->execute();
                    $customerId = $stmt6->insert_id;
                    $stmt6->close();
                }
            } else {
                $stmt4 = $conn->prepare("INSERT INTO resellers (first_name, last_name, reseller_code, technician) VALUES(?, ?, ?, ?)");
                $stmt4->bind_param("ssss", $resellerFirstname, $resellerLastname, $resellerCode, $technician);
                $stmt4->execute();
                $resellerId = $stmt4->insert_id;
                $stmt4->close();

                $stmt6 = $conn->prepare("INSERT INTO customers (reseller_id, company_name, first_name, last_name, address, contact_number, email) VALUES(?, ?, ?, ?, ?, ?, ?)");
                $stmt6->bind_param("issssss", $resellerId, $companyName, $customerFirstname, $customerLastname, $customerAddress, $customerContactNumber, $customerEmailAddress);
                $stmt6->execute();
                $customerId = $stmt6->insert_id;
                $stmt6->close();
            }

            $stmt7 = $conn->prepare("INSERT INTO licenses (user_id, reseller_id, customer_id, code_verifier, mdc_permanent_count, mdc_trial_count, mdc_trial_days, dnc_permanent_count, dnc_trial_count, dnc_trial_days, hmi_permanent_count, hmi_trial_count, hmi_trial_days, annual_maintenance_expiration_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt7->bind_param("iiisiiiiiiiiis", $userId, $resellerId, $customerId, $codeVerifier, $mdcPermanentCount, $mdcTrialCount, $mdcTrialDays, $dncPermanentCount, $dncTrialCount, $dncTrialDays, $hmiPermanentCount, $hmiTrialCount, $hmiTrialDays, $annualMaintenanceExpDate);
            $stmt7->execute();
            $lastId = $stmt7->insert_id;
            $isUpdated = false;
            $stmt7->close();
        }

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

// function isValidDate($date)
// {
//     $parsedDate = DateTime::createFromFormat('Y-m-d', $date);
//     return $parsedDate && $parsedDate->format('Y-m-d') === $date;
// }

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
            $sql = "SELECT l.id, l.user_id, l.code_verifier, r.first_name AS reseller_first_name, r.last_name AS reseller_last_name, r.reseller_code, r.technician,
            c.company_name, c.first_name AS customer_first_name, c.last_name AS customer_last_name, c.email AS customer_email, c.address AS customer_address, c.contact_number as customer_contact_number, 
            l.mdc_permanent_count, l.mdc_trial_count, l.mdc_trial_days,
            l.dnc_permanent_count, l.dnc_trial_count, l.dnc_trial_days,
            l.hmi_permanent_count, l.hmi_trial_count, l.hmi_trial_days,
            l.license_created_at, l.license_updated_at
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
        $licenseId = $_GET['id'];
        $userId = $currentUser['id'];

        $conn = new mysqli("localhost", "root", "", "testdb");
        if ($conn->connect_error) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to save license. Please try again later.']);
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
    }
}
