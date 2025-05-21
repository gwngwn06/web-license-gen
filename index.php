<?php
session_start();

if (isset($_COOKIE['remember_user']) && !isset($_SESSION['current_user'])) {
    require './accounts/account.php';
    $token = $_COOKIE['remember_user'];
    $account = new Account();
    $result = $account->getUserByToken($token);
    if ($result['status'] === 'success') {
        $user = $result['user'];
        $_SESSION['current_user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'account_type' => $user['account_type']
        ];
    } else {
        header('Location: ./accounts/logout.php');
    }
}

if (!isset($_SESSION['current_user'])) {
    header('Location: ./login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web Licence Generator - Nexas America</title>
    <link rel="stylesheet" href="./assets/css/global.css">
    <link rel="stylesheet" href="./assets/vendor/bootstrap-5.3.5-dist/css/bootstrap.min.css">
</head>

<body class="bg-light">
    <header>
        <nav class="navbar navbar-expand-sm border-bottom border-secondary bg-white">
            <div class="container-fluid ">
                <div class="d-flex align-items-center">
                    <img id="backToHomePage" src="./assets/icons/nexas-america.png" width="110" height="55" />
                    <div class="navbar-brand d-flex flex-column">
                        <div class="text-end fw-medium ms-3" style="color: #0071BC">
                            Web License Generator
                            <!-- <sup style="font-size: 9px; vertical-align: super">2025</sup> -->
                        </div>
                    </div>

                </div>

                <div class="d-flex flex-row align-items-center">
                    <?php
                    $user = $_SESSION['current_user'];
                    // $username = strtoupper($user['username']);
                    if ($user['account_type'] == 0) {
                        $badgeElement = "<span class='badge text-bg-success'>Reseller</span>";
                    } else {
                        $badgeElement = "<span class='badge text-bg-primary'>Admin</span>";
                    }
                    echo "<div class='text-secondary me-3 text-center fw-medium'> $badgeElement </div>";
                    ?>
                    <div class="dropdown-center">
                        <button class="dropdown-toggle d-flex align-items-center" type="button" style="all: unset;" data-bs-toggle="dropdown" aria-expanded="false">

                            <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center fw-bold"
                                style="width: 40px; height: 40px;">
                                <?php
                                $user = strtoupper($_SESSION['current_user']['username'])[0];
                                echo "$user";
                                ?>
                            </div>
                            <div class="ms-2 text-start">
                                <?php
                                $user = $_SESSION['current_user'];
                                $username = ($user['username']);
                                $email = $user['email'];
                                echo "
                                <div class='fw-medium d-flex'>
                                    $username
                                </div>
                                <div class='text-secondary text-truncate' style='width: 125px;'>$email</div>";
                                ?>
                            </div>
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" data-bs-toggle="modal" data-bs-target="#profileModal" href="#">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person" viewBox="0 0 16 16">
                                        <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4m-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10s-3.516.68-4.168 1.332c-.678.678-.83 1.418-.832 1.664z" />
                                    </svg>
                                    Profile
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item text-danger" href="./accounts/logout.php">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-box-arrow-right" viewBox="0 0 16 16">
                                        <path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0z" />
                                        <path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708z" />
                                    </svg>
                                    Sign out
                                </a>
                            </li>
                        </ul>
                    </div>
                    <!-- <a href="./accounts/logout.php" class="btn btn-outline-danger rounded-3 me-2 btn-sm">Logout</a> -->
                </div>
            </div>
        </nav>
    </header>

    <div class="mx-auto mx-3 text-center col-12 col-md-8 col-sm-11 col-lg-7 d-flex align-items-center position-relative" data-bs-toggle="modal" data-bs-target="#searchModal">
        <svg style="left: 16px; top: 50%; transform: translateY(-50%);" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-search position-absolute" viewBox="0 0 16 16">
            <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0" />
        </svg>
        <input type="text" id="searchInput" class="form-control rounded-3 border-2 ps-5 p-2 mx-1 my-2 my-md-3" style="background-color: #F6F6F6" placeholder="Search for a license...">
    </div>
    <main class="mx-auto mb-5 bg-white border rounded-4 p-4 p-lg-5 col-md-8 col-lg-7 col-sm-11 shadow-sm">
        <div class="d-flex justify-content-between align-items-center mb-3 position-relative">
            <div class="d-none d-md-block" style="visibility: hidden;">
            </div>
            <div class="fw-medium fs-2 d-block d-md-none" style="color: #0071BC">
                License Generator
            </div>

            <div class="fw-medium fs-2 
                position-absolute start-50 translate-middle-x 
                d-none d-md-block" style="color: #0071BC">License Generator</div>
            <div>
                <input type="file" accept=".json" id="licenseUpload" class="d-none">
                <label for="licenseUpload" class="btn btn-sm btn-outline-success rounded-3">
                    Import existing license
                </label>
            </div>
        </div>
        <?php
        $userId = $_SESSION['current_user']['id'];
        $userType = $_SESSION['current_user']['account_type'];

        echo "<div id='cid' data-cid='$userId'></div>
              <div id='utype' data-utype='$userType'></div>"
        ?>
        <form id="generateLicenseForm">
            <?php
            $userId = $_SESSION['current_user']['id'];
            echo "<input id='userId' type='hidden' name='userId' value='$userId'>
                  <input id='licenseId' type='hidden' name='licenseId' value=''>";
            ?>
            <input id='dateLicenseUsed' name="dateLicenseUsed" type="hidden" value="" />
            <div class="">
                <label for="codeVerifier" class="form-label text-secondary fw-medium"><span class="text-danger">*</span>
                    Verifier Code
                </label>
                <div class="">
                    <input name="codeVerifier" type="text" class="form-control rounded-3 border-2" id="codeVerifier"
                        required>
                </div>
            </div>

            <div class="fw-medium fs-5 mt-4 mb-2">Reseller's Information</div>
            <div class="row">
                <div class="mb-2 col">
                    <label for="resellerFirstName" class="form-label text-secondary fw-medium"><span
                            class="text-danger">*</span>First name</label>
                    <!-- <div class="">
                        <input name="resellerFirstName" type="text" class="form-control rounded-3 border-2" id="resellerFirstName"
                            required>
                    </div> -->

                    <div class="" style="position: relative; display: flex;">
                        <input name="resellerFirstName" id="resellerFirstName" data-search="resellers" placeholder="" class="select-button form-control rounded-3 border-2" />
                        <ul id="" class="select-dropdown hidden">
                            <!-- dynamic data -->
                        </ul>
                    </div>
                </div>
                <div class="mb-2 col">
                    <label for="resellerLastName" class="form-label text-secondary fw-medium"><span
                            class="text-danger">*</span>Last name</label>
                    <div class="" style="position: relative; display: flex;">
                        <input name="resellerLastName" type="text" data-search="resellers" class="select-button form-control rounded-3 border-2" id="resellerLastName"
                            required>
                        <ul id="" class="select-dropdown hidden">
                            <!-- dynamic data -->
                        </ul>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="mb-2 col">
                    <label for="resellerCode" class="form-label text-secondary fw-medium">
                        <span class="text-danger">*</span>Reseller Code</label>
                    <div class="">
                        <input name="resellerCode" type="text" class="form-control rounded-3 border-2" id="resellerCode"
                            required>
                    </div>
                </div>
                <div class="col mb-2">
                    <label for="technician" class="form-label text-secondary fw-medium">Technician</label>
                    <div class="">
                        <input name="technician" type="text" class="form-control rounded-3 border-2" id="technician">
                    </div>
                </div>
            </div>
            <div class="mt-3 mb-3"
                style="border-bottom: 1px dashed var(--bs-secondary); border-width: 3px;  opacity: 0.1;">
            </div>


            <div class="fw-medium fs-5 mt-4 mb-2">Customer Information</div>
            <div class="row mb-2">
                <div class="col">
                    <label for="customerFirstName" class="form-label text-secondary fw-medium">
                        <span class="text-danger">*</span>
                        First name</label>
                    <div class="" style="position: relative; display: flex;">
                        <input id="customerFirstName" name="customerFirstName" type="text" data-search="customers" class="select-button form-control rounded-3 border-2" required>
                        <ul id="" class="select-dropdown hidden">
                            <!-- dynamic data -->
                        </ul>
                    </div>
                </div>
                <div class="col">
                    <label for="customerLastName" class="form-label text-secondary fw-medium">
                        <span class="text-danger">*</span>
                        Last name</label>
                    <div class="" style="position: relative; display: flex;">
                        <input id="customerLastName" name="customerLastName" type="text" data-search="customers" class="select-button form-control rounded-3 border-2" required>
                        <ul id="" class="select-dropdown hidden">
                            <!-- dynamic data -->
                        </ul>
                    </div>
                </div>
            </div>
            <div class="row mb-2">
                <div class="col">
                    <label for="companyName" class="form-label text-secondary fw-medium">
                        <span class="text-danger">*</span>
                        Company Name</label>
                    <input id="companyName" name="companyName" type="text" class="form-control rounded-3 border-2" required>
                </div>
                <div class="col">
                    <label for="customerAddress" class="form-label text-secondary fw-medium">
                        <span class="text-danger">*</span>
                        Address</label>
                    <input id="customerAddress" name="customerAddress" type="text" class="form-control rounded-3 border-2" required>
                </div>
            </div>
            <div class="row mb-2">
                <div class="col">
                    <label for="customerEmail" class="form-label text-secondary fw-medium">
                        <span class="text-danger">*</span>
                        Email Address</label>
                    <input id="customerEmail" name="customerEmail" type="email" class="form-control rounded-3 border-2" required>
                </div>
                <div class="col">
                    <label for="customerContactNumber" class="form-label text-secondary fw-medium">
                        <span class="text-danger">*</span>
                        Contact Number</label>
                    <input id="customerContactNumber" name="customerContactNumber" type="text" class="form-control rounded-3 border-2" required>
                </div>
            </div>
            <div class="my-3"
                style="border-bottom: 1px dashed var(--bs-secondary); border-width: 3px;  opacity: 0.1;">
            </div>
            <div class="fw-medium fs-5 mt-4 mb-2">Machine License
                <span class="fw-normal fs-6 ms-2 d-none" id="licenseTagInfo">
                    <code>
                        <small>
                            <img src='./assets/icons/check.svg' /> Available <img src='./assets/icons/arrow-repeat.svg' /> In use <img src='./assets/icons/clock.svg' /> Remaining days
                        </small>
                    </code>
                </span>
            </div>

            <div id="machineLicenseContainer" class="text-center mb-3">
                <div class="row fw-medium mb-2">
                    <div class="col-2">
                        Type
                    </div>
                    <div class="col permanent-license">
                        No. Permanent License
                    </div>
                    <div class="col">
                        No. Trial License
                    </div>
                    <div class="col">
                        No. Trial Days (default 40)
                    </div>
                </div>

                <div class="row mb-2 align-items-center">
                    <div class="col-2">
                        <span class="text-danger">*</span>MDC
                    </div>
                    <div class="col permanent-license input-group">
                        <input name="mdcPermanentCount" value="0" type="number" class="form-control rounded-3 border-2"
                            id="mdcPermanentCount">
                        <span class="license-info input-group-text d-flex flex-column px-1 py-0 d-none" id="">
                            <div class="">
                                <img src="./assets/icons/check.svg" />
                                <span class="available-license"><small>0</small></span>
                            </div>
                            <div class="">
                                <img src="./assets/icons/arrow-repeat.svg" />
                                <span class="in-use-license"><small>0</small></span>
                            </div>
                        </span>
                    </div>
                    <div class="col input-group input-group">
                        <input name="mdcTrialCount" value="0" type="number" class="form-control rounded-3 border-2"
                            id="mdcTrialCount">
                        <span class="license-info input-group-text d-flex flex-column px-1 py-0 d-none" id="">
                            <div class="">
                                <img src="./assets/icons/check.svg" />
                                <span><small>0</small></span>
                            </div>
                            <div class="">
                                <img src="./assets/icons/arrow-repeat.svg" />
                                <span class=""><small>0</small></span>
                            </div>
                        </span>
                    </div>
                    <div class="col input-group input-group">
                        <input name="mdcTrialDays" value="40" type="number" class="form-control rounded-3 border-2"
                            id="mdcTrialDays">
                        <span class="license-info input-group-text px-1 d-none" id="" style="padding-top: 10px; padding-bottom: 10px;">
                            <div class="">
                                <img src="./assets/icons/clock.svg" />
                                <span><small>0</small></span>
                            </div>
                        </span>
                    </div>
                </div>
                <div class="row mb-2 align-items-center">
                    <div class="col-2">
                        <span class="text-danger">*</span>DNC
                    </div>
                    <div class="col permanent-license input-group">
                        <input name="dncPermanentCount" value="0" type="number" class="form-control rounded-3 border-2"
                            id="dncPermanentCount" required>
                        <span class="license-info input-group-text d-flex flex-column px-1 py-0 d-none" id="">
                            <div class="">
                                <img src="./assets/icons/check.svg" />
                                <span><small>0</small></span>
                            </div>
                            <div class="">
                                <img src="./assets/icons/arrow-repeat.svg" />
                                <span class=""><small>0</small></span>
                            </div>
                        </span>
                    </div>
                    <div class="col input-group">
                        <input name="dncTrialCount" value="0" type="number" class="form-control rounded-3 border-2"
                            id="dncTrialCount" required>
                        <span class="license-info input-group-text d-flex flex-column px-1 py-0 d-none" id="">
                            <div class="">
                                <img src="./assets/icons/check.svg" />
                                <span><small>0</small></span>
                            </div>
                            <div class="">
                                <img src="./assets/icons/arrow-repeat.svg" />
                                <span class=""><small>0</small></span>
                            </div>
                        </span>
                    </div>
                    <div class="col input-group">
                        <input name="dncTrialDays" value="40" type="number" class="form-control rounded-3 border-2"
                            id="dncTrialDays" required>
                        <span class="license-info input-group-text px-1 d-none" id="" style="padding-top: 10px; padding-bottom: 10px;">
                            <div class="">
                                <img src="./assets/icons/clock.svg" />
                                <span><small>0</small></span>
                            </div>
                        </span>
                    </div>
                </div>
                <div class="row mb-2 align-items-center">
                    <div class="col-2">
                        <span class="text-danger">*</span>HMI
                    </div>
                    <div class="col permanent-license input-group">
                        <input name="hmiPermanentCount" value="0" type="number" class="form-control rounded-3 border-2"
                            id="hmiPermanentCount">
                        <span class="license-info input-group-text d-flex flex-column px-1 py-0 d-none" id="">
                            <div class="">
                                <img src="./assets/icons/check.svg" />
                                <span><small>0</small></span>
                            </div>
                            <div class="">
                                <img src="./assets/icons/arrow-repeat.svg" />
                                <span class=""><small>0</small></span>
                            </div>
                        </span>
                    </div>
                    <div class="col input-group">
                        <input name="hmiTrialCount" value="0" type="number" class="form-control rounded-3 border-2"
                            id="hmiTrialCount">
                        <span class="license-info input-group-text d-flex flex-column px-1 py-0 d-none" id="">
                            <div class="">
                                <img src="./assets/icons/check.svg" />
                                <span><small>0</small></span>
                            </div>
                            <div class="">
                                <img src="./assets/icons/arrow-repeat.svg" />
                                <span class=""><small>0</small></span>
                            </div>
                        </span>
                    </div>
                    <div class="col input-group">
                        <input name="hmiTrialDays" value="40" type="number" class="form-control rounded-3 border-2"
                            id="hmiTrialDays">
                        <span class="license-info input-group-text px-1 d-none" id="" style="padding-top: 10px; padding-bottom: 10px;">
                            <div class="">
                                <img src="./assets/icons/clock.svg" />
                                <span><small>0</small></span>
                            </div>
                        </span>
                    </div>
                </div>
            </div>

            <div class="text-center text-lg-end mt-4">
                <div class="mt-3 mb-3"
                    style="border-bottom: 1px solid var(--bs-secondary); border-width: 1px;  opacity: 0.2;">
                </div>
                <div class="d-flex gap-2 justify-content-end">
                    <button id="cancelFormBtn" type="button" disabled class="btn btn-danger rounded-3 mt-2 mt-lg-4">
                        <span id="">Cancel</span>
                    </button>
                    <button type="submit" class="btn btn-primary rounded-3 mt-2 mt-lg-4">
                        <span id="fileDownloadText">Generate & Download License File</span>
                    </button>

                </div>
            </div>
        </form>

    </main>



    <div class="toast-container  position-fixed bottom-0 end-0 p-3">
        <div id="liveToast" class="toast text-bg-primary" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <img src="./assets/icons/nexas-america.png" width="50" height="25" class="rounded me-2" alt="...">
                <strong class="me-auto toast-header-text">License Generated</strong>
                <!-- <small>11 mins ago</small> -->
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                Your license has been generated
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal" id="searchModal" tabindex="-1" aria-labelledby="searchModal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-fullscreen-lg-down">
            <div class="modal-content" style="height: 80vh; overflow-y: auto;">

                <div class="modal-body">
                    <div class="d-flex align-items-center position-relative">
                        <svg style="left: 16px; top: 50%; transform: translateY(-50%);" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-search position-absolute" viewBox="0 0 16 16">
                            <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0" />
                        </svg>
                        <input type=" text" id="searchLicenseInput" class="form-control rounded-3 border-2 p-2 ps-5 my-1" placeholder="Search for a license...">
                    </div>
                    <div class="mb-4"></div>

                    <table id="licenseTable" class="table table-hover">
                        <thead style="cursor: pointer;">
                            <tr>
                                <th data-order="asc" data-sort="reseller" scope="col">Reseller Name <img class="sorting-order" src="./assets/icons/caret-up-fill.svg" /></th>
                                <th data-order="asc" data-sort="company" scope="col">Company name <img class="sorting-order" src="./assets/icons/caret-up-fill.svg" /></th>
                                <th data-order="desc" data-sort="created" scope="col">Date created <img class="sorting-order" src="./assets/icons/caret-down-fill.svg" /></th>
                                <th data-order="desc" data-sort="updated" scope="col">Date updated <img class="sorting-order" src="./assets/icons/caret-down-fill.svg" /></th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody id="searchResultsTableBody">
                        </tbody>
                    </table>
                    <div id="noLicenseMessage" class="text-center text-secondary fs-4 my-5" hidden>No available license</div>
                    <div id="paginationDiv" class="d-flex justify-content-center align-items-center gap-2">
                        <button id="paginationPrevBtn" class="btn btn-outline-dark btn-sm rounded-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-chevron-left" viewBox="0 0 16 16">
                                <path fill-rule="evenodd" d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0" />
                            </svg>
                        </button>
                        <div id="paginationPageCount">1/2</div>
                        <button id="paginationNextBtn" class="btn btn-outline-dark btn-sm rounded-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-chevron-right" viewBox="0 0 16 16">
                                <path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal" id="profileModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center fw-bold"
                                style="width: 40px; height: 40px;">
                                <?php
                                $user = strtoupper($_SESSION['current_user']['username'])[0];
                                echo "$user";
                                ?>
                            </div>
                            <div class="ms-2 text-start">
                                <?php
                                $user = $_SESSION['current_user'];
                                $username = ($user['username']);
                                $email = $user['email'];
                                echo "
                                <div class='fw-medium '>
                                    $username
                                </div>";
                                ?>
                            </div>
                        </div>
                    </h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div id="profileModalBody" class="modal-body">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <!-- <button type="button" class="btn btn-primary">Save changes</button> -->
                </div>
            </div>
        </div>
    </div>

</body>

<script src="./assets/vendor/bootstrap-5.3.5-dist/js/bootstrap.bundle.min.js"></script>
<script type="module" src="./assets/js/index.js"></script>

<?php
$toast = null;
if (isset($_SESSION['toast'])) {
    $toast = $_SESSION['toast'];
    unset($_SESSION['toast']);
    echo "<script>
        const toastMessage = document.getElementById('liveToast');
        const toastBody = toastMessage.querySelector('.toast-body');
        const toastHeader = toastMessage.querySelector('.toast-header-text');
        if ('$toast[status]' === 'success') {
            toastHeader.innerHTML = '$toast[header]';
            toastBody.innerHTML = '$toast[message]';
            toastMessage.classList.remove('text-bg-success');
            toastMessage.classList.remove('text-bg-danger');
            toastMessage.classList.add('text-bg-primary');
        } else {
            toastHeader.innerHTML = '$toast[header]';
            toastBody.innerHTML = '$toast[message]';
            toastMessage.classList.remove('text-bg-primary');
            toastMessage.classList.remove('text-bg-success');
            toastMessage.classList.add('text-bg-danger');
         }
        const toastBootstrap = bootstrap.Toast.getOrCreateInstance(toastMessage);
        toastBootstrap.show();
    </script>";
}

$user = $_SESSION['current_user'];
$accountType = $user['account_type'];

if ($accountType == 0) {
    echo "<script>
    const mdcPermanentCount = document.getElementById('mdcPermanentCount');
    const dncPermanentCount = document.getElementById('dncPermanentCount');
    const hmiPermanentCount = document.getElementById('hmiPermanentCount');
    if (mdcPermanentCount.value > 0 || dncPermanentCount.value > 0 || hmiPermanentCount > 0) {
        document.querySelectorAll('.permanent-license').forEach(function (element) {
            element.classList.remove('d-none');
        });

        mdcPermanentCount.disabled = true;
        dncPermanentCount.disabled = true;
        hmiPermanentCount.disabled = true;

    } else {
        document.querySelectorAll('.permanent-license').forEach(function (element) {
            element.classList.add('d-none');
        });
    }
    </script>";
}

?>


</html>