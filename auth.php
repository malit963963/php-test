<?php
header('Content-Type: application/json');

session_start();
$conn = new mysqli("localhost", "root", "", "php_test");
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
                echo json_encode([
                    "type" => "success",
                    "message" => "הרשמה בוצעה בהצלחה! כעת תוכל להתחבר."
                ]);
            } else {
                echo json_encode(["type" => "error", "message" => "שגיאה בהרשמה."]);
            }
        }
        $stmt->close();
    } elseif ($_POST['action'] === 'login') {
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
                $_SESSION['logged_in'] = true;
                $_SESSION['logged_in_user_id'] = $id;
                echo json_encode(["type" => "success", "message" => "התחברת בהצלחה!"]);
            } else {
                echo json_encode(["type" => "error", "message" => "סיסמה שגויה."]);
            }
        } else {
            echo json_encode(["type" => "error", "message" => "לא נמצא משתמש עם האימייל הזה."]);
        }
        $stmt->close();
    } elseif ($_POST['action'] === 'forgotPassword') {
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            echo json_encode(["type" => "success", "message" => "משתמש מחובר."]);
        } else {
            echo json_encode(["type" => "error", "message" => "עליך להתחבר כדי להשתמש באפשרות שכחתי סיסמה."]);
            exit();
        }

        $email = $_POST['email'];

        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($user_id);
            $stmt->fetch();
            $stmt->close();

            $token = bin2hex(random_bytes(16));
            $expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));

            $stmt = $conn->prepare("INSERT INTO password_resets (user_id, token, expiry) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $user_id, $token, $expiry);
            $stmt->execute();
            $stmt->close();

            $_SESSION['logged_in'] = true;
            $_SESSION['logged_in_user_id'] = $user_id;
            echo json_encode(["type" => "success", "message" => "הכנס סיסמה חדשה.", "token" => $token]);
        } else {
            echo json_encode(["type" => "error", "message" => "לא נמצא משתמש עם האימייל הזה."]);
        }
    } elseif ($_POST['action'] === 'get_users') {
        $result = $conn->query("SELECT username, email FROM users");
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        echo json_encode(["type" => "success", "users" => $users]);
        exit();
    } elseif ($_POST['action'] === 'resetPassword') {
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $password, $email);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            echo json_encode(["type" => "success", "message" => "הסיסמה עודכנה בהצלחה!"]);
        } else {
            echo json_encode(["type" => "error", "message" => "האימייל לא נמצא או שגיאה בעדכון הסיסמה."]);
        }
        $stmt->close();
        exit();
    }
} else {
    echo json_encode(["type" => "error", "message" => "לא נשלחה פעולה."]);
}

$conn->close();
?>