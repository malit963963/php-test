<?php
session_start();
$conn = new mysqli("localhost", "root", "", "php_test"); // עדכן את פרטי החיבור ל-DB

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$response = []; // משתנה לאחסון הודעות

// פונקציית הרשמה
if (isset($_POST['signup'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $password);

    if ($stmt->execute()) {
        $response = ["type" => "success", "message" => "הרשמה בוצעה בהצלחה!"];
    } else {
        $response = ["type" => "error", "message" => "שגיאה בהרשמה: " . $stmt->error];
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
            $response = ["type" => "success", "message" => "התחברות בוצעה בהצלחה!"];
        } else {
            $response = ["type" => "error", "message" => "סיסמה שגויה."];
        }
    } else {
        $response = ["type" => "error", "message" => "לא נמצא משתמש עם האימייל הזה."];
    }
    $stmt->close();
}

// פונקציית שחזור סיסמה ללא שימוש במייל
if (isset($_POST['forgot_password'])) {
    $email = $_POST['email'];

    // בדוק אם המייל קיים במערכת
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $token = bin2hex(random_bytes(16)); // צור טוקן ייחודי
        $expiry = date("Y-m-d H:i:s", strtotime("+1 hour")); // תוקף של שעה

        // שמור את הטוקן במסד הנתונים
        $stmt->bind_result($user_id);
        $stmt->fetch();
        $stmt->close();

        $stmt = $conn->prepare("INSERT INTO password_resets (user_id, token, expiry) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $token, $expiry);
        $stmt->execute();
        $stmt->close();

        // הצג את הקישור לאיפוס הסיסמה
        $reset_link = "http://localhost/php-test/reset_password.php?token=$token";
        $response = ["type" => "success", "message" => "קישור לאיפוס סיסמה: <a href='$reset_link'>$reset_link</a>"];
    } else {
        $response = ["type" => "error", "message" => "לא נמצא משתמש עם האימייל הזה."];
    }
}

// שמירת ההודעה ב-SESSION
$_SESSION['response'] = $response;

// חזרה לדף הראשי
header("Location: index.php");
exit();
?>