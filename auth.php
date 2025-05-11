<?php
header('Content-Type: application/json');
session_start();
$conn = new mysqli("localhost", "root", "", "php_test");

if ($conn->connect_error) {
    echo json_encode(["type" => "error", "message" => "שגיאה בחיבור למסד הנתונים."]);
    exit();
}

if (isset($_POST['action'])) {
    if ($_POST['action'] === 'signup') {
        $username = $_POST['username'];
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            echo json_encode(["type" => "error", "message" => "האימייל כבר קיים במערכת."]);
        } else {
            $stmt->close();
            $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $email, $password);

            if ($stmt->execute()) {
                echo json_encode(["type" => "success", "message" => "הרשמה בוצעה בהצלחה!"]);
            } else {
                echo json_encode(["type" => "error", "message" => "שגיאה בהרשמה."]);
            }
        }
        $stmt->close();
    } elseif ($_POST['action'] === 'login') {
        $email = $_POST['email'];
        $password = $_POST['password'];

        $stmt = $conn->prepare("SELECT password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($hashed_password);
            $stmt->fetch();

            if (password_verify($password, $hashed_password)) {
                $_SESSION['logged_in'] = true; // שמור את מצב ההתחברות
                echo json_encode(["type" => "success", "message" => "התחברות בוצעה בהצלחה!"]);
            } else {
                echo json_encode(["type" => "error", "message" => "סיסמה שגויה."]);
            }
        } else {
            echo json_encode(["type" => "error", "message" => "לא נמצא משתמש עם האימייל הזה."]);
        }
        $stmt->close();
    } elseif ($_POST['action'] === 'forgot_password') {
        $email = $_POST['email'];

        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            echo json_encode(["type" => "success", "message" => "קישור לאיפוס סיסמה נשלח למייל שלך (לא באמת)."]);
        } else {
            echo json_encode(["type" => "error", "message" => "לא נמצא משתמש עם האימייל הזה."]);
        }
        $stmt->close();
    } elseif ($_POST['action'] === 'get_users') {
        // בדוק אם המשתמש מחובר
        if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
            echo json_encode(["type" => "error", "message" => "עליך להתחבר כדי לצxxx ברשימת המשתמשים."]);
            exit();
        }

        // שלוף את כל המשתמשים
        $result = $conn->query("SELECT username, email FROM users");
        $users = [];

        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }

        echo json_encode(["type" => "success", "users" => $users]);
    }
} else {
    echo json_encode(["type" => "error", "message" => "לא נשלחה פעולה."]);
}

$conn->close();
?>