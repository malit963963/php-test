function showLogin() {
    document.getElementById('choiceSection').style.display = 'none';
    document.getElementById('loginForm').style.display = 'block';
}

function showSignUp() {
    document.getElementById('choiceSection').style.display = 'none';
    document.getElementById('signupForm').style.display = 'block';
}

function showForgotPassword() {
    document.getElementById('loginForm').style.display = 'none';
    document.getElementById('signupForm').style.display = 'none';
    document.getElementById('forgotPasswordForm').style.display = 'block';
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
    const formData = new FormData(form);
    formData.append('action', action);

    fetch('auth.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.type === 'success') {
            showPopup('success', data.message);
            getUsers(); // הצגת רשימת המשתמשים
        }
    })
    .catch(error => {
        showPopup('error', 'אירעה שגיאה. נסה שוב מאוחר יותר.');
    });
}

function showPopup(type, message) {
    const popup = document.getElementById('popup');
    const popupContent = document.getElementById('popupContent');
    const popupTitle = document.getElementById('popupTitle');
    const popupMessage = document.getElementById('popupMessage');

    popupContent.className = `popup-content ${type}`;
    popupTitle.textContent = type === 'success' ? 'הצלחה!' : 'שגיאה!';
    popupMessage.textContent = message;
    popup.style.display = 'flex';
}

function closePopup() {
    document.getElementById('popup').style.display = 'none';
}

function getUsers() {
    fetch('auth.php', {
        method: 'POST',
        body: new URLSearchParams({ action: 'get_users' })
    })
    .then(response => response.json())
    .then(data => {
        if (data.type === 'success') {
            console.log(data.users); // בדוק את הנתונים שמתקבלים
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
