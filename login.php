<?php
session_start();
require 'db_connect.php'; // Your PDO connection setup

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pdo = Database::connect();
    $action = $_POST['action']; // 'login' or 'register'
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        if ($action === 'login') {
            $stmt = $pdo->prepare("SELECT * FROM iss_persons WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && md5($password . $user['pwd_salt']) === $user['pwd_hash']) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['admin'] = $user['admin'];
                header("Location: issues_list.php");
                exit();
            } else {
                $error = "Invalid email or password.";
                session_destroy();
            }
        } elseif ($action === 'register') {
            $fname = trim($_POST['fname']);
            $lname = trim($_POST['lname']);

            if (empty($fname) || empty($lname)) {
                $error = "Please enter your first and last name.";
            } else {
                // Check if email already exists
                $checkStmt = $pdo->prepare("SELECT id FROM iss_persons WHERE email = ?");
                $checkStmt->execute([$email]);
                if ($checkStmt->fetch()) {
                    $error = "Email already registered.";
                } else {
                    $salt = bin2hex(random_bytes(8));
                    $hashedPassword = md5($password . $salt);

                    $insert = $pdo->prepare("INSERT INTO iss_persons (email, pwd_salt, pwd_hash, fname, lname, admin) VALUES (?, ?, ?, ?, ?, 0)");
                    $insert->execute([$email, $salt, $hashedPassword, $fname, $lname]);

                    $success = "Registration successful. You can now log in.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login or Register - DSR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .form-box {
            background-color: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        .toggle-btns {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .toggle-btns button {
            width: 48%;
        }
        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <div class="form-box">
        <div class="toggle-btns">
            <button class="btn btn-outline-primary" onclick="showForm('login')">Login</button>
            <button class="btn btn-outline-secondary" onclick="showForm('register')">Register</button>
        </div>

        <?php if (!empty($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
        <?php if (!empty($success)) echo "<div class='alert alert-success'>$success</div>"; ?>

        <!-- Login Form -->
        <form method="post" id="loginForm">
            <input type="hidden" name="action" value="login">
            <div class="mb-3">
                <label>Email</label>
                <input type="email" class="form-control" name="email" required>
            </div>
            <div class="mb-3">
                <label>Password</label>
                <input type="password" class="form-control" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>

        <!-- Registration Form -->
        <form method="post" id="registerForm" class="hidden">
            <input type="hidden" name="action" value="register">
            <div class="mb-3">
                <label>First Name</label>
                <input type="text" class="form-control" name="fname" required>
            </div>
            <div class="mb-3">
                <label>Last Name</label>
                <input type="text" class="form-control" name="lname" required>
            </div>
            <div class="mb-3">
                <label>Email</label>
                <input type="email" class="form-control" name="email" required>
            </div>
            <div class="mb-3">
                <label>Password</label>
                <input type="password" class="form-control" name="password" required>
            </div>
            <button type="submit" class="btn btn-success w-100">Register</button>
        </form>
    </div>

    <script>
        function showForm(type) {
            document.getElementById('loginForm').classList.add('hidden');
            document.getElementById('registerForm').classList.add('hidden');
            document.getElementById(type + 'Form').classList.remove('hidden');
        }

        // Show login form by default
        showForm('login');
    </script>
</body>
</html>
