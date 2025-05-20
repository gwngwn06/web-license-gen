<?php
class Account
{
    public $username;
    public $email;
    public $password;
    public $confirmPassword;
    public $accountType;
    public $resellerFirstname;
    public $resellerLastname;
    public $mobileNumber;
    public $companyName;
    public $resellerCode;

    public function initLoginFields()
    {
        $this->username = trim($_POST['username'] ?? '');
        $this->password = trim($_POST['password'] ?? '');
    }

    public function loginUser()
    {
        if (empty($this->username) || empty($this->password)) {
            return ["status" => "error", "message" => "Email and password are required"];
        }

        $conn = new mysqli("localhost", "root", "", "testdb");
        if ($conn->connect_error) {
            return ["status" => "error", "message" => "Something went wrong. Please try again later."];
        }

        try {
            $stmt = $conn->prepare("SELECT * FROM users WHERE LOWER(username) = LOWER(?)");
            $stmt->bind_param("s", $this->username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                if (password_verify($this->password, $user['hashed_password'])) {
                    return ["status" => "success", "message" => "Login successful", "user" => $user];
                } else {
                    return ["status" => "error", "message" => "Incorrect password"];
                }
            } else {
                return ["status" => "error", "message" => "Username not found"];
            }
        } catch (Exception $e) {
            return ["status" => "error", "message" => "Something went wrong. Please try again later."];
        } finally {
            $conn->close();
        }
    }

    public function generateUserToken($userId, $token)
    {
        $conn = new mysqli("localhost", "root", "", "testdb");
        if ($conn->connect_error) {
            return ["status" => "error", "message" => "Something went wrong. Please try again later."];
        }

        try {
            $stmt = $conn->prepare("INSERT INTO users_tokens (user_id, token) VALUES (?, ?)");
            $stmt->bind_param("is", $userId, $token);
            $stmt->execute();
            return ["status" => "success"];
        } catch (Exception $e) {
            return ["status" => "error", "message" => "Unable to save user token"];
        } finally {
            $stmt->close();
            $conn->close();
        }
    }

    public function getUserByToken($token)
    {
        $days = 30;
        $conn = new mysqli("localhost", "root", "", "testdb");
        if ($conn->connect_error) {
            return ["status" => "error", "message" => "Something went wrong. Please try again later."];
        }

        try {
            $stmt = $conn->prepare("SELECT user_id FROM users_tokens WHERE token = ? AND created_at > NOW() - INTERVAL ? DAY");
            $stmt->bind_param("si", $token, $days);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                $userId = $row['user_id'];
                $stmtUser = $conn->prepare("SELECT * FROM users WHERE id = ?");
                $stmtUser->bind_param("i", $userId);
                $stmtUser->execute();

                $userResult = $stmtUser->get_result();
                if ($userResult->num_rows > 0) {
                    $user = $userResult->fetch_assoc();
                    return ["status" => "success", "message" => "User found", "user" => $user];
                }
            } else {
                return ["status" => "error", "message" => "User not found"];
            }
        } catch (Exception $e) {
            return ["status" => "error", "message" => $e->getMessage()];
        } finally {
            $stmt->close();
            $conn->close();
        }
    }

    public function initRegistrationFields()
    {
        $this->username = trim($_POST['username'] ?? '');
        $this->email = trim($_POST['email'] ?? '');
        $this->password = trim($_POST['password'] ?? '');
        $this->confirmPassword = trim($_POST['confirmPassword'] ?? '');
        $this->accountType = trim($_POST['accountType'] ?? '');
        $this->resellerFirstname = trim($_POST['resellerFirstname'] ?? '');
        $this->resellerLastname = trim($_POST['resellerLastname'] ?? '');
        $this->mobileNumber = trim($_POST['mobileNumber'] ?? '');
        $this->companyName = trim($_POST['companyName'] ?? '');
        $this->resellerCode = trim($_POST['resellerCode'] ?? '');
    }


    public function isValidRegistration()
    {
        if (empty($this->username) || empty($this->email) || empty($this->password) || empty($this->confirmPassword)) {
            return ["status" => "error", "message" => "All fields are required"];
        }
        if ($this->password !== $this->confirmPassword) {
            return ["status" => "error", "message" => "Passwords do not match"];
        }
        if (!preg_match("/^[a-zA-Z0-9_]+$/", $this->username)) {
            return ["status" => "error", "message" => "Username can only contain letters, numbers, and underscores"];
        }
        if (strlen($this->username) < 3 || strlen($this->username) > 20) {
            return ["status" => "error", "message" => "Username must be between 3 and 20 characters long"];
        }
        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            return ["status" => "error", "message" => "Invalid email format"];
        }
        if (strlen($this->password) <= 5) {
            return ["status" => "error", "message" => "Password must be at least 6 characters long"];
        }
        if ($this->accountType == 0 && (empty($this->resellerFirstname) || empty($this->resellerLastname) || empty($this->mobileNumber) || empty($this->companyName) || empty($this->resellerCode))) {
            return ["status" => "error", "message" => "Reseller fields are required"];
        }
        if ($this->accountType == 1) {
            $this->resellerFirstname = null;
            $this->resellerLastname = null;
            $this->mobileNumber = null;
            $this->companyName = null;
            $this->resellerCode = null;
        } else {
            // if (!preg_match("/^[0-9]{10}$/", $this->mobileNumber)) {
            //     return ["status" => "error", "message" => "Mobile number must be 10 digits long"];
            // }
        }
        if ($this->isEmailAlreadyExists()) {
            return ["status" => "error", "message" => "Email already exists"];
        }
        if ($this->isUsernameAlreadyExists()) {
            return ["status" => "error", "message" => "Username already exists"];
        }

        return ["status" => "success", "message" => "Registration is valid"];
    }

    private function isEmailAlreadyExists()
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

    private function isUsernameAlreadyExists()
    {
        $conn = new mysqli("localhost", "root", "", "testdb");
        if ($conn->connect_error) {
            return true;
        }

        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        if (!$stmt) {
            return true;
        }
        $stmt->bind_param("s", $this->username);
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
        $conn->begin_transaction();

        try {
            $resellerId = null;
            if ($this->accountType == 0) {
                $stmt1 = $conn->prepare("INSERT INTO resellers (first_name, last_name, reseller_code) VALUES (?, ?, ?)");
                $stmt1->bind_param("sss", $this->resellerFirstname, $this->resellerLastname, $this->resellerCode);
                $stmt1->execute();
                $resellerId = $conn->insert_id;
            }


            $stmt2 = $conn->prepare("INSERT INTO users (reseller_id, username, email, hashed_password, account_type, mobile_number, company_name) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt2->bind_param("issssss", $resellerId, $this->username, $this->email, $hashedPassword, $this->accountType,  $this->mobileNumber, $this->companyName);
            $stmt2->execute();

            $conn->commit();
            return ["status" => "success", "message" => "Registration successful"];
        } catch (Exception $e) {
            $conn->rollback();
            return ["status" => "error", "message" => $e->getMessage()];
        } finally {
            $conn->close();
        }
    }
}
