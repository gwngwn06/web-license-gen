<?php
ob_start();
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (isset($_COOKIE['remember_user']) || isset($_SESSION['current_user'])) {
    header('Location: ./index.php');
    exit;
}
?>

<?php
require "./accounts/account.php";

$account = new Account();
$errorMessage = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        !isset($_POST['csrf_token'], $_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        die("CSRF token validation failed.");
    }

    $account->initRegistrationFields();
    $response = $account->isValidRegistration();


    if ($response['status'] === 'error') {
        $errorMessage = $response['message'];
        // echo "<script>
        // const formError = document.getElementById('formError');
        // if (formError) {
        //     formError.textContent = '{$response['message']}';
        // }
        // console.log('{$response['message']}');
        // </script>";
        // exit;
    } else {
        $user = $account->registerNewUser();
        if ($user['status'] === "success") {
            unset($_SESSION['csrf_token']);

            $_SESSION['toast'] = [
                'status' => 'success',
                'header' => 'Registration Successful',
                'message' => 'Account created successfully! Please log in.'
            ];
            header("Location: ./login.php");
            exit;
        } else {
            $errorMessage = $user['message'];
            // echo "<script>
            // const formError = document.getElementById('formError');
            // if (formError) {
            //     formError.textContent = '{$user['message']}';
            // }
            // console.log('{$user['message']}');
            // </script>";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web Licence Generator - Nexas America</title>
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

            </div>
        </nav>
    </header>

    <main class="mx-auto my-5 bg-white border rounded-4 p-4 col-md-8 col-lg-5 col-sm-11 shadow-sm">
        <div class="fw-medium fs-2 text-center" style="color: #0071BC">Register for an account</div>
        <div class="text-center mb-3">Already registered? <a class="fw-bold" href="./login.php">Login</a> to your account now.</div>

        <form class="mx-3 mx-md-5" method="post" action="">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <div class="fw-medium mb-1">User Info</div>
            <div class="mb-2">
                <!-- <label for="username" class="form-label"><span class="text-danger">*</span>User name</label> -->
                <input name="username" type="text" class="form-control rounded-3 border-2" value="<?= htmlspecialchars($account->username ?? '') ?>" id="username" placeholder="Username" required>
            </div>
            <div class="mb-2">
                <input name="email" type="email" class="form-control rounded-3 border-2" value="<?= htmlspecialchars($account->email ?? '') ?>" id="email" placeholder="Email" required>
            </div>
            <div class="mb-2">
                <input name="password" type="password" class="form-control rounded-3 border-2" value="<?= htmlspecialchars($account->password ?? '') ?>" id="password" placeholder="Password" required>
            </div>
            <div class="mb-2">
                <input name="confirmPassword" type="password" class="form-control rounded-3 border-2" value="<?= htmlspecialchars($account->confirmPassword ?? '') ?>" id="confirmPassword" placeholder="Confirm password" required>
            </div>

            <div class="fw-medium mb-1">Account Type</div>
            <select class="form-select mb-3 rounded-3 border-2" aria-label="Select Account Type" id="accountType" name="accountType" required>
                <option value="0" selected>Reseller Account</option>
                <option value="1">Nexas Account</option>
            </select>

            <div id="resellerInfo" class="mb-2">
                <div class="fw-medium mb-1">Reseller Information</div>
                <div class="row">
                    <div class="mb-2 col">
                        <input name="resellerFirstname" type="text" class="form-control rounded-3 border-2" value="<?= htmlspecialchars($account->resellerFirstname ?? '') ?>" placeholder="First name" required>
                    </div>
                    <div class="mb-2 col">
                        <input name="resellerLastname" type="text" class="form-control rounded-3 border-2" value="<?= htmlspecialchars($account->resellerLastname ?? '') ?>" placeholder="Last name" required>
                    </div>
                </div>
                <div class="row">
                    <div class="mb-2 col">
                        <input name="companyName" type="text" class="form-control rounded-3 border-2" value="<?= htmlspecialchars($account->companyName ?? '') ?>" placeholder="Company Name" required>
                    </div>
                    <div class="mb-2 col">
                        <input name="resellerCode" type="text" class="form-control rounded-3 border-2" value="<?= htmlspecialchars($account->resellerCode ?? '') ?>" placeholder="Reseller Code" required>
                    </div>
                </div>
                <div>
                    <div class="mb-2 col">
                        <input name="mobileNumber" type="text" class="form-control rounded-3 border-2" value="<?= htmlspecialchars($account->mobileNumber ?? '') ?>" placeholder="Mobile/Phone Number" required>
                    </div>
                </div>
            </div>
            <div id="formError" class="text-danger">
                <?= htmlspecialchars($errorMessage) ?>
            </div>
            <div class="text-end mt-3">
                <button type="submit" class="btn btn-primary">Register</button>
            </div>
        </form>
    </main>

</body>



<script src="./assets/vendor/bootstrap-5.3.5-dist/js/bootstrap.bundle.min.js"></script>
<script type="module">
    const accountTypeSelect = document.getElementById('accountType');
    const resellerInfoDiv = document.getElementById('resellerInfo');

    accountTypeSelect.addEventListener('change', (e) => {
        if (e.target.value === "1") {
            resellerInfoDiv.style.display = "none";
            resellerInfoDiv.querySelectorAll('input').forEach(input => {
                input.type = 'hidden';
                input.required = false;
            });
        } else {
            resellerInfoDiv.style.display = "block";
            resellerInfoDiv.querySelectorAll('input').forEach(input => {
                input.type = 'text';
                input.required = true;
            });
        }
    });
</script>

</html>