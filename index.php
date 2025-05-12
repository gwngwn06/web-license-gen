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
                        <div class="text-secondary text-end fw-medium ms-3">
                            Web License Generator
                            <!-- <sup style="font-size: 9px; vertical-align: super">2025</sup> -->
                        </div>
                    </div>

                </div>

                <div class="d-flex flex-row-reverse align-items-center">
                    <a href="./accounts/logout.php" class="btn btn-outline-danger rounded-3 me-2 btn-sm">Logout</a>
                    <?php
                    $user = $_SESSION['current_user'];
                    $username = strtoupper($user['username']);
                    if ($user['account_type'] == 0) {
                        $badgeElement = "<span class='badge text-bg-success'>Reseller</span>";
                    } else {
                        $badgeElement = "<span class='badge text-bg-primary'>Admin</span>";
                    }
                    echo "<div class='text-secondary me-3 text-center fw-medium'> $badgeElement Welcome back, $username</div>";
                    ?>
                </div>
                <!-- <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false"
                    aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNavDropdown">
                    <ul class="navbar-nav"></ul>
                </div> -->
            </div>
        </nav>
    </header>

    <div class="mx-auto text-center col-11 col-md-7 col-sm-10" data-bs-toggle="modal" data-bs-target="#searchModal">
        <input type="text" id="searchInput" class="form-control rounded-4 shadow-sm border-2 my-3" placeholder="Search for a license...">
    </div>
    <main class="mx-auto mb-5 bg-white border rounded-4 p-4 col-md-8 col-lg-7 col-sm-11 shadow-sm">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="me-4"></div>
            <div class="ms-4 fw-medium fs-2 " style="color: #0071BC">License Generator</div>
            <div>
                <input type="file" accept=".json" id="licenseUpload" class="d-none">
                <label for="licenseUpload" class="btn btn-sm btn-outline-dark rounded-3">
                    Upload existing license
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
            echo "<input type='hidden' name='userId' value='$userId'>
                  <input id='licenseId' type='hidden' name='licenseId' value=''>";
            ?>
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
                    <label for="resellerName" class="form-label text-secondary fw-medium"><span
                            class="text-danger">*</span>Reseller Name</label>
                    <div class="">
                        <input name="resellerName" type="text" class="form-control rounded-3 border-2" id="resellerName"
                            required>
                    </div>
                </div>
                <div class="mb-2 col">
                    <label for="resellerCode" class="form-label text-secondary fw-medium">
                        <span class="text-danger">*</span>Reseller Code</label>
                    <div class="">
                        <input name="resellerCode" type="text" class="form-control rounded-3 border-2" id="resellerCode"
                            required>
                    </div>
                </div>
            </div>
            <div class="row">
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
            <div class="mb-2">
                <label for="companyName" class="form-label text-secondary fw-medium">
                    <span class="text-danger">*</span>
                    Company Name</label>
                <input id="companyName" name="companyName" type="text" class="form-control rounded-3 border-2" required>
            </div>
            <div class="row mb-2">
                <div class="col">
                    <label for="customerName" class="form-label text-secondary fw-medium">
                        <span class="text-danger">*</span>
                        Name</label>
                    <input name="customerName" type="text" class="form-control rounded-3 border-2" required>
                </div>
                <div class="col">
                    <label for="customerEmail" class="form-label text-secondary fw-medium">
                        <span class="text-danger">*</span>
                        Email Address</label>
                    <input name="customerEmail" type="email" class="form-control rounded-3 border-2" required>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <label for="customerAddress" class="form-label text-secondary fw-medium">
                        <span class="text-danger">*</span>
                        Address</label>
                    <input name="customerAddress" type="text" class="form-control rounded-3 border-2" required>
                </div>
                <div class="col">
                    <label for="customerContactNumber" class="form-label text-secondary fw-medium">
                        <span class="text-danger">*</span>
                        Contact Number</label>
                    <input name="customerContactNumber" type="text" class="form-control rounded-3 border-2" required>
                </div>
            </div>


            <div class="mt-3 mb-3"
                style="border-bottom: 1px dashed var(--bs-secondary); border-width: 3px;  opacity: 0.1;">
            </div>
            <div class="fw-medium fs-5 mt-4 mb-2">Machine License</div>

            <div id="machineLicenseContainer" class="container text-center mb-3">
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
                    <div class="col permanent-license">
                        <input name="mdcPermanentCount" value="0" type="number" class="form-control rounded-3 border-2"
                            id="mdcPermanentCount">
                    </div>
                    <div class="col">
                        <input name="mdcTrialCount" value="0" type="number" class="form-control rounded-3 border-2"
                            id="mdcTrialCount">
                    </div>
                    <div class="col">
                        <input name="mdcTrialDays" value="40" type="number" class="form-control rounded-3 border-2"
                            id="mdcTrialDays">
                    </div>
                </div>
                <div class="row mb-2 align-items-center">
                    <div class="col-2">
                        <span class="text-danger">*</span>DNC
                    </div>
                    <div class="col permanent-license">
                        <input name="dncPermanentCount" value="0" type="number" class="form-control rounded-3 border-2"
                            id="dncPermanentCount" required>
                    </div>
                    <div class="col">
                        <input name="dncTrialCount" value="0" type="number" class="form-control rounded-3 border-2"
                            id="dncTrialCount" required>
                    </div>
                    <div class="col">
                        <input name="dncTrialDays" value="40" type="number" class="form-control rounded-3 border-2"
                            id="dncTrialDays" required>
                    </div>
                </div>
                <div class="row mb-2 align-items-center">
                    <div class="col-2">
                        <span class="text-danger">*</span>HMI
                    </div>
                    <div class="col permanent-license">
                        <input name="hmiPermanentCount" value="0" type="number" class="form-control rounded-3 border-2"
                            id="hmiPermanentCount">
                    </div>
                    <div class="col">
                        <input name="hmiTrialCount" value="0" type="number" class="form-control rounded-3 border-2"
                            id="hmiTrialCount">
                    </div>
                    <div class="col">
                        <input name="hmiTrialDays" value="40" type="number" class="form-control rounded-3 border-2"
                            id="hmiTrialDays">
                    </div>
                </div>
            </div>

            <div class="text-end mt-4">
                <div class="mt-3 mb-3"
                    style="border-bottom: 1px solid var(--bs-secondary); border-width: 1px;  opacity: 0.2;">
                </div>
                <button type="submit" class="btn btn-primary rounded-3">
                    <span id="fileDownloadText">Generate & Download License File</span>
                </button>
            </div>
        </form>

    </main>

    <div class="modal fade" id="uploadSecretKeyModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="uploadSecretKeyModal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="secretkeyHeader">Enter the secret key to decrypt the license file
                    </h1>
                </div>
                <div class="modal-body">
                    <form id="uploadSecretKeyForm">
                        <div class="mb-2">
                            <label for="uploadSecretKeyInput" class="form-label text-secondary fw-medium">Secret
                                Key</label>
                            <input id="uploadSecretKeyInput" type="password" class="form-control rounded-3 border-2"
                                minlength="4" maxlength="30" required>
                        </div>
                        <div class="text-end">
                            <button id="uploadSecretKeySubmitBtn" type="submit" class="btn btn-primary">Enter</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="secretkeyModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="secretkeyModal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="secretkeyHeader">New secret key</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="secretkeyForm">
                        <div class="mb-2">
                            <label for="secretkeyInput" class="form-label text-secondary fw-medium">Secret Key</label>
                            <input id="secretkeyInput" type="password" class="form-control rounded-3 border-2"
                                minlength="4" maxlength="30" autofocus required>
                        </div>
                        <div class="text-end">
                            <button id="secretkeyCancelBtn" type="button" class="btn btn-secondary"
                                data-bs-dismiss="modal">Cancel</button>
                            <button id="secretkeySubmitBtn" type="submit" class="btn btn-primary">Download
                                License</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

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
                    <input type=" text" id="searchLicenseInput" class="form-control rounded-4 border-2 my-1" placeholder="Search for a license...">
                    <div class="mb-4"></div>
                    <!-- <div class="d-flex justify-content-end gap-2 mt-3 mb-2">
                        <div class="dropdown">
                            <button id="sortByDropdown" class="btn btn-outline-secondary btn-sm dropdown-toggle rounded-3" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Sort by
                            </button>
                            <div class="dropdown-menu shadow p-2" style="width: 250px;">
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="text-secondary mb-1">Date</div>
                                        <select id="sortByDateType" name="sortByDateType" class="form-select form-select-sm w-auto">
                                            <option value="updatedAt" selected>Updated</option>
                                            <option value="createdAt">Created</option>
                                            <option value="none">None</option>
                                        </select>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="dateSort" id="dateAscending" value="asc">
                                        <label class="form-check-label" for="dateAscending">
                                            Ascending
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="dateSort" id="dateDescending" value="desc" checked>
                                        <label class="form-check-label" for="dateDescending">
                                            Descending
                                        </label>
                                    </div>
                                </div>
                                <hr class="dropdown-divider">
                                </hr>
                                <div class="mb-2">
                                    <div class="text-secondary mb-1">Reseller</div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="resellerNameSort" id="resellerNameAscending" value="asc" checked>
                                        <label class="form-check-label" for="resellerNameAscending">
                                            A-Z
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="resellerNameSort" id="resellerNameDescending" value="desc">
                                        <label class="form-check-label" for="resellerNameDescending">
                                            Z-A
                                        </label>
                                    </div>
                                </div>
                                <hr class="dropdown-divider">
                                </hr>
                                <div class="mb-2">
                                    <div class="text-secondary mb-1">Company</div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="companyNameSort" id="companyNameAscending" value="asc" checked>
                                        <label class="form-check-label" for="companyNameAscending">
                                            A-Z
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="companyNameSort" id="companyNameDescending" value="desc">
                                        <label class="form-check-label" for="companyNameDescending">
                                            Z-A
                                        </label>
                                    </div>
                                </div>
                                <hr class="dropdown-divider">
                                </hr>
                                <div class="d-flex justify-content-between align-items-center">
                                    <button id="clearSortByBtn" class="btn btn-outline-secondary btn-sm rounded-3">Reset</button>
                                    <button id="sortByBtn" class="btn btn-primary btn-sm rounded-3">Apply</button>
                                </div>
                            </div>
                        </div>

                        <div class="dropdown">
                            <button id="filterByDropdown" class="btn btn-outline-secondary btn-sm dropdown-toggle rounded-3" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Filter
                            </button>
                            <div class="dropdown-menu shadow p-2">
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="mb-1 fw-medium">Date range</div>
                                        <select id="filterByDateType" name="filterByDateType" class="form-select form-select-sm w-auto">
                                            <option value="updatedAt" selected>Updated</option>
                                            <option value="createdAt">Created</option>
                                        </select>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <div>
                                            <label class="text-secondary" for="filterStartDate"><small>From</small></label>
                                            <input type="date" id="filterStartDate" class="form-control rounded-3 border-2">
                                        </div>
                                        <div>
                                            <label class="text-secondary" for="filterEndDate"><small>To</small></label>
                                            <input type="date" id="filterEndDate" class="form-control rounded-3 border-2">
                                        </div>
                                    </div>
                                </div>
                                <hr class="dropdown-divider">
                                </hr>
                                <div class="d-flex justify-content-between align-items-center">
                                    <button id="clearFilterBtn" class="btn btn-outline-secondary btn-sm rounded-3">Reset</button>
                                    <button id="filterBtn" class="btn btn-primary btn-sm rounded-3">Apply</button>
                                </div>
                            </div>
                        </div>
                    </div> -->

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
                    <div id="paginationDiv" class="d-flex justify-content-center align-items-center gap-2">
                        <button id="paginationPrevBtn" class="btn btn-outline-dark btn-sm rounded-3">Prev</button>
                        <div id="paginationPageCount">1/2</div>
                        <button id="paginationNextBtn" class="btn btn-outline-dark btn-sm rounded-3">Next</button>
                    </div>
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