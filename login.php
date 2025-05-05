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
                <img id="backToHomePage" src="./assets/icons/nexas-america.png" width="110" height="55" />
                <div class="navbar-brand d-flex flex-column">
                    <div class="text-secondary text-end fw-medium ms-3">
                        Web License Generator
                        <!-- <sup style="font-size: 9px; vertical-align: super">2025</sup> -->
                    </div>
                </div>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false"
                    aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNavDropdown">
                    <ul class="navbar-nav"></ul>
                </div>
            </div>
        </nav>
    </header>

    <main class="mx-auto my-5 bg-white border rounded-4 p-4 col-md-8 col-lg-5 col-sm-11 shadow-sm">
        <div class="ms-4 fw-medium fs-2 mb-3 text-center" style="color: #0071BC">Login Account</div>
        <form class="mx-3">
            <div class="mb-2">
                <input type="email" class="form-control  rounded-3 border-2" id="email" placeholder="Email" required>
            </div>
            <div class="mb-1">
                <input type="password" class="form-control rounded-3 border-2" id="password" placeholder="Password" required>
            </div>
            <div class="mb-2 form-check">
                <input type="checkbox" class="form-check-input" id="rememberMe">
                <label class="form-check-label" for="rememberMe">Remember me</label>
            </div>
            <div class="text-end">
                <button type="submit" class="btn btn-primary">Login</button>
            </div>
        </form>

    </main>

</body>

<script src="./assets/vendor/bootstrap-5.3.5-dist/js/bootstrap.bundle.min.js"></script>

</html>