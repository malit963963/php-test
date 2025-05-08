<?php
// filepath: c:\xampp\htdocs\php-test\auth.php
session_start();
$conn = new mysqli("localhost", "root", "", "php_test"); // עדכן את פרטי החיבור ל-DB

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// פונקציית הרשמה
if (isset($_POST['signup'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $password);

    if ($stmt->execute()) {
        echo "Signup successful!";
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}

// פונקציית לוגין
if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $hashed_password);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            $_SESSION['user_id'] = $id;
            echo "Login successful!";
        } else {
            echo "Invalid password.";
        }
    } else {
        echo "No user found with this email.";
    }
    $stmt->close();
}

// פונקציית שחזור סיסמה
if (isset($_POST['forgot_password'])) {
    $email = $_POST['email'];
    $new_password = bin2hex(random_bytes(4)); // סיסמה חדשה אקראית
    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt->bind_param("ss", $hashed_password, $email);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo "Your new password is: $new_password";
    } else {
        echo "No user found with this email.";
    }
    $stmt->close();
}
?>