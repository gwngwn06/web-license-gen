<?php

class Account
{
    public $username;
    public $email;
    public $password;
    public $confirmPassword;
    public $accountType;
    public $resellerName;
    public $mobileNumber;
    public $companyInformation;
    public $resellerCode;


    public function __construct()
    {
        $this->username = $_POST['username'] ?? null;
        $this->email = $_POST['email'] ?? null;
        $this->password = $_POST['password'] ?? null;
        $this->confirmPassword = $_POST['confirmPassword'] ?? null;
        $this->accountType = $_POST['accountType'] ?? null;
        $this->resellerName = $_POST['resellerName'] ?? null;
        $this->mobileNumber = $_POST['mobileNumber'] ?? null;
        $this->companyInformation = $_POST['companyInformation'] ?? null;
        $this->resellerCode = $_POST['resellerCode'] ?? null;
    }

    public function isValidRegistration()
    {
        if (empty($this->username) || empty($this->email) || empty($this->password) || empty($this->confirmPassword)) {
            return ["status" => "error", "message" => "All fields are required"];
        }
        if ($this->password !== $this->confirmPassword) {
            return ["status" => "error", "message" => "Passwords do not match"];
        }
        if ($this->accountType == 0 && (empty($this->resellerName) || empty($this->mobileNumber) || empty($this->companyInformation) || empty($this->resellerCode))) {
            return ["status" => "error", "message" => "All reseller fields are required"];
        }
        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            return ["status" => "error", "message" => "Invalid email format"];
        }
        if (strlen($this->password) <= 5) {
            return ["status" => "error", "message" => "Password must be at least 6 characters long"];
        }
        if ($this->emailAlreadyExists()) {
            return ["status" => "error", "message" => "Email already exists"];
        }

        return ["status" => "success", "message" => "Registration is valid"];
    }

    private function emailAlreadyExists()
    {
        $conn = new mysqli("localhost", "root", "", "testdb");
        if ($conn->connect_error) {
            return true;
        }

        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        if (!$stmt) {
            return true;
        }
        $stmt->bind_param("s", $this->email);
        try {
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                return true;
            } else {
                return false;
            }
        } finally {
            $stmt->close();
            $conn->close();
        }
    }

    public function registerNewUser()
    {
        $conn = new mysqli("localhost", "root", "", "testdb");
        if ($conn->connect_error) {
            return ["status" => "error", "message" => "Something went wrong. Please try again later."];
        }

        $hashedPassword = password_hash($this->password, PASSWORD_BCRYPT);

        $stmt = $conn->prepare("INSERT INTO users (username, email, hashed_password, account_type, reseller_name, mobile_number, company_information, reseller_code) VALUES (?, ?, ?, ?, ?, ?, ? ,?)");

        if (!$stmt) {
            return ["status" => "error", "message" => "Failed to prepare statement."];
        }

        $stmt->bind_param("ssssssss", $this->username, $this->email, $hashedPassword, $this->accountType, $this->resellerName, $this->mobileNumber, $this->companyInformation, $this->resellerCode);
        try {
            if ($stmt->execute()) {
                return ["status" => "success", "message" => "Registration successful"];
            } else {
                return ["status" => "error", "message" => "Failed to register user. Please try again."];
            }
        } finally {
            $stmt->close();
            $conn->close();
        }
    }
}
