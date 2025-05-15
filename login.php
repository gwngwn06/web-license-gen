<?php
session_start();

if (isset($_COOKIE['remember_user']) || isset($_SESSION['current_user'])) {
    header('Location: ./index.php');
    exit;
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
        <div class="fw-medium fs-2 text-center" style="color: #0071BC">Login</div>
        <div class="text-center mb-3">Don't have an account? <a class="fw-bold" href="./register.php">Sign up</a> for an account now.</div>
        <form class="mx-5" method="post" action="">
            <div class="mb-2">
                <input name="username" type="text" class="form-control  rounded-3 border-2" id="username" placeholder="Username" required>
            </div>
            <div class="mb-1">
                <input name="password" type="password" class="form-control rounded-3 border-2" id="password" placeholder="Password" required>
            </div>
            <div class="mb-2 form-check">
                <input name="rememberMe" type="checkbox" class="form-check-input border-2" id="rememberMe">
                <label class="form-check-label" for="rememberMe">Remember me</label>
            </div>
            <div class="text-end">
                <button type="submit" class="btn btn-primary">Login</button>
            </div>
        </form>

    </main>

    <div class="toast-container  position-fixed bottom-0 end-0 p-3">
        <div id="liveToast" class="toast text-bg-primary" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <img src="./assets/icons/nexas-america.png" width="50" height="25" class="rounded me-2" alt="...">
                <strong class="me-auto toast-header-text"></strong>
                <!-- <small>11 mins ago</small> -->
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">

            </div>
        </div>
    </div>
</body>

<script src="./assets/vendor/bootstrap-5.3.5-dist/js/bootstrap.bundle.min.js"></script>
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once './accounts/account.php';
    $account = new Account();
    $account->initLoginFields();
    $result = $account->loginUser();
    // echo "<script>console.log('Login result: " . json_encode($result) . "');</script>";
    if ($result['status'] === 'success') {
        $user = $result['user'];

        $_SESSION['toast'] = [
            'status' => 'success',
            'header' => 'Login successful',
            'message' => 'Welcome back, ' . strtoupper($user['username']) . '!'
        ];

        if (isset($_POST['rememberMe'])) {
            $token = bin2hex(random_bytes(32));

            $account->generateUserToken($user['id'], $token);
            setcookie("remember_user", $token, time() + (30 * 24 * 60 * 60), "/"); // 30 days
        }

        $_SESSION['current_user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'account_type' => $user['account_type']
        ];

        header('Location: ./index.php');
        exit;
    } else {
        $_SESSION['toast'] = [
            'status' => 'error',
            'header' => 'Login failed',
            'message' => $result['message']
        ];
        header('Location: ./login.php');
        exit;
    }
}
?>

</html>