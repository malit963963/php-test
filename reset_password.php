<?php

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$conn = new mysqli("localhost", "root", "", "php_test"); // עדכן את פרטי החיבור ל-DB

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$form_visible = true; // משתנה לשליטה על הצגת הטופס

// מחיקת כל הרשומות הקודמות של המשתמש מטבלת password_resets
if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // מצא את המשתמש לפי הטוקן
    $stmt = $conn->prepare("SELECT user_id FROM password_resets WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id);
        $stmt->fetch();
        $stmt->close();

        // מחק את כל הרשומות של המשתמש מטבלת password_resets
        $stmt = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
    } else {
        echo "Invalid or expired token.";
        $form_visible = false; // הסתר את הטופס אם הטוקן אינו תקף
    }
} else {
    // אם אין טוקן, מחק את כל הרשומות הקודמות מטבלת password_resets
    $stmt = $conn->prepare("DELETE FROM password_resets WHERE expiry < NOW()");
    $stmt->execute();
    $stmt->close();
}

if (isset($_GET['token']) && isset($_POST['reset_password'])) {
    $token = $_GET['token'];
    $new_password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    // בדוק אם הטוקן תקף
    $stmt = $conn->prepare("SELECT user_id FROM password_resets WHERE token = ? AND expiry > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id);
        $stmt->fetch();
        $stmt->close();

        // עדכן את הסיסמה
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $new_password, $user_id);
        $stmt->execute();
        $stmt->close();

        // מחק את הטוקן
        $stmt = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $stmt->close();

        echo "Password has been reset successfully.";
        $form_visible = false; // הסתר את הטופס
    } else {
        echo "Invalid or expired token.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
</head>
<body>
    <h2>Reset Password</h2>
    <?php if ($form_visible): ?>
        <form method="POST">
            <input type="password" name="password" placeholder="New Password" required>
            <button type="submit" name="reset_password">Reset Password</button>
        </form>
    <?php endif; ?>
</body>
</html>