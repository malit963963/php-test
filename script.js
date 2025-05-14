function showLogin() {
    document.getElementById('choiceSection').style.display = 'none';
    document.getElementById('loginForm').style.display = 'block';
}

function showSignUp() {
    document.getElementById('choiceSection').style.display = 'none';
    document.getElementById('signupForm').style.display = 'block';
}

// הצג טופס הגדרת סיסמה חדשה בלחיצה על "שכחתי סיסמה"
function showForgotPassword() {
    document.getElementById('loginForm').style.display = 'none';
    document.getElementById('signupForm').style.display = 'none';
    document.getElementById('resetPasswordSection').style.display = 'block';
    document.getElementById('resetEmail').value = '';
    document.getElementById('newPassword').value = '';
    document.getElementById('confirmPassword').value = '';
    document.getElementById('savePasswordBtn').style.display = 'none';
}

// פונקציה להצגת הודעה
function showMessage(type, text) {
    const messageSection = document.getElementById('messageSection');
    const messageDiv = document.getElementById('message');
    messageDiv.className = `message ${type}`;
    messageDiv.textContent = text;
    messageSection.style.display = 'block';
}

function submitForm(action) {
    const form = document.getElementById(action + 'Form');
    if (!form) {
        showPopup('error', `Form with ID '${action}Form' not found.`);
        return;
    }

    const formData = new FormData(form);
    formData.append('action', action);

    fetch('auth.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        // הצג תמיד ב-Popup
        if (data.type === 'success') {
            showPopup('success', data.message);

            // מעבר אוטומטי לטופס התחברות אחרי הרשמה מוצלחת
            if (action === 'signup') {
                setTimeout(() => {
                    document.getElementById('signupForm').style.display = 'none';
                    document.getElementById('loginForm').style.display = 'block';
                }, 2000);
            }
        } else {
            showPopup('error', data.message);
        }
    })
    .catch(error => {
        showPopup('error', 'שגיאה בתקשורת עם השרת.');
    });
}

// דוגמה לפונקציית showPopup
function showPopup(type, message) {
    const popupTitle = document.getElementById('popupTitle');
    const popupMessage = document.getElementById('popupMessage');
    const popup = document.getElementById('popup');
    const popupContent = document.getElementById('popupContent');
    popupContent.className = `popup-content ${type}`;
    popupTitle.textContent = type === 'success' ? 'הצלחה!' : 'שגיאה!';
    popupMessage.textContent = message;
    popup.style.display = 'flex';
}

function closePopup() {
    document.getElementById('popup').style.display = 'none';
}

// בדוק אם המשתמש מחובר לפני הצגת כפתור שכחתי סיסמה
document.addEventListener('DOMContentLoaded', function () {
    fetch('auth.php', {
        method: 'POST',
        body: new URLSearchParams({ action: 'check_login' }) // פעולה לבדיקה אם המשתמש מחובר
    })
    .then(response => response.json())
    .then(data => {
        if (data.type === 'success') {
            // הצג את כפתור שכחתי סיסמה
            document.getElementById('forgotPasswordButton').style.display = 'block';
        } else {
            // הסתר את כפתור שכחתי סיסמה
            document.getElementById('forgotPasswordButton').style.display = 'none';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('forgotPasswordButton').style.display = 'none';
    });
});

// הצגת טופס להזנת סיסמה חדשה בלבד
document.getElementById('forgotPasswordButton').addEventListener('click', function () {
    document.getElementById('forgotPasswordSection').style.display = 'none';
    document.getElementById('loginForm').style.display = 'none';
    document.getElementById('signupForm').style.display = 'none';
    document.getElementById('resetPasswordSection').style.display = 'block';
    document.getElementById('newPassword').value = '';
    document.getElementById('confirmPassword').value = '';
    document.getElementById('savePasswordBtn').style.display = 'none';
});

// הצג כפתור "שמור" רק כשהסיסמאות זהות ולא ריקות
document.getElementById('newPassword').addEventListener('input', checkPasswordsMatch);
document.getElementById('confirmPassword').addEventListener('input', checkPasswordsMatch);

function checkPasswordsMatch() {
    const pass = document.getElementById('newPassword').value;
    const confirm = document.getElementById('confirmPassword').value;
    const btn = document.getElementById('savePasswordBtn');
    if (pass && confirm && pass === confirm) {
        btn.style.display = 'block';
    } else {
        btn.style.display = 'none';
    }
}

// שליחת סיסמה חדשה
document.getElementById('resetPasswordForm').addEventListener('submit', function (e) {
    e.preventDefault();
    const email = document.getElementById('resetEmail').value;
    const password = document.getElementById('newPassword').value;

    fetch('auth.php', {
        method: 'POST',
        body: new URLSearchParams({
            action: 'resetPassword',
            email: email,
            password: password
        })
    })
    .then(response => response.json())
    .then(data => {
        showPopup(data.type, data.message);
        if (data.type === 'success') {
            setTimeout(() => {
                document.getElementById('resetPasswordSection').style.display = 'none';
            }, 1500);
        }
    })
    .catch(() => {
        showPopup('error', 'שגיאה בתקשורת עם השרת.');
    });
});

function getUsers() {
    fetch('auth.php', {
        method: 'POST',
        body: new URLSearchParams({ action: 'get_users' })
    })
    .then(response => response.json())
    .then(data => {
        if (data.type === 'success') {
            let usersTable = `
                <h2>רשימת משתמשים</h2>
                <table>
                    <thead>
                        <tr>
                            <th>שם משתמש</th>
                            <th>אימייל</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            data.users.forEach(user => {
                usersTable += `
                    <tr>
                        <td>${user.username}</td>
                        <td>${user.email}</td>
                    </tr>
                `;
            });
            usersTable += `
                    </tbody>
                </table>
                <button onclick="showLogin()" style="margin-top: 20px; padding: 10px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">חזור</button>
            `;
            const usersSection = document.getElementById('usersSection');
            usersSection.innerHTML = usersTable;
            usersSection.style.display = 'block'; // הצג את הטבלה

            // הסתר את כל הטפסים
            document.getElementById('loginForm').style.display = 'none';
            document.getElementById('signupForm').style.display = 'none';
            document.getElementById('forgotPasswordForm').style.display = 'none';
            document.getElementById('choiceSection').style.display = 'none';
        } else {
            showPopup('error', data.message);
        }
    })
    .catch(error => {
        console.error(error); // הדפס את השגיאה לקונסול
        showPopup('error', 'אירעה שגיאה. נסה שוב מאוחר יותר.');
    });
}
