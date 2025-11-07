<?php
include 'db_connection.php';
$departments = $conn->query("SELECT DISTINCT name FROM departments WHERE name != 'Administration' ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Create an Account - SAMS-PE</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <style>
        /* small inline styles for password eye button */
        .pw-wrapper { position: relative; }
        .pw-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 4px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
        }
        .pw-toggle:focus { outline: none; }
    </style>
</head>

<body>
    <div class="auth-container">
        <div class="auth-card" id="form-card">
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const formCard = document.getElementById('form-card');
        const departmentsJson = <?= json_encode($departments) ?>;

        const choiceHTML = `
            <h1 class="auth-title">Join SAMS-PE</h1>
            <p class="auth-subtitle">Please select your role to begin registration.</p>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <button class="auth-btn btn-primary" onclick="showForm('student')">I am a Student</button>
                <button class="auth-btn btn-secondary" onclick="showForm('teacher')">I am a Teacher</button>
            </div>
            <div class="auth-footer">Already have an account? <a href="login.php">Log In</a></div>`;

        function generateOptions(items, key, placeholder) {
            let optionsHTML = `<option value="" disabled selected>${placeholder}</option>`;
            optionsHTML += items.map(item => `<option value="${item[key]}">${item[key]}</option>`).join('');
            return optionsHTML;
        }

        const studentFormHTML = `
            <h1 class="auth-title">Student Registration</h1>
            <p class="auth-subtitle">Please fill out the form below.</p>
            <form id="student-reg-form" novalidate>
                <div class="input-group"><label>Student ID</label><input type="text" name="student_id" class="form-input" placeholder="e.g., 240000001593" pattern="[0-9]{12}" maxlength="12" title="ID must be exactly 12 digits." required></div>
                <div class="input-group"><label>First Name</label><input type="text" name="first_name" class="form-input" placeholder="e.g., Juan" required></div>
                <div class="input-group"><label>Last Name</label><input type="text" name="last_name" class="form-input" placeholder="e.g., Dela Cruz" required></div>
                <div class="input-group">
                    <label>Gender</label>
                    <select name="gender" class="form-input" required><option value="" disabled selected>Select Gender</option><option value="Male">Male</option><option value="Female">Female</option><option value="Other">Other</option></select>
                </div>
                <div class="input-group"><label>Email</label><input type="email" name="email" class="form-input" placeholder="e.g., juan.delacruz@uic.edu.ph" required></div>
                <div class="input-group"><label>Department</label><select name="department" class="form-input" required>${generateOptions(departmentsJson, 'name', 'Select Department')}</select></div>
                <div class="input-group">
                    <label>Year Level</label>
                    <select name="year_level" class="form-input" required><option value="" disabled selected>Select Year Level</option><option value="1st Year">1st Year</option><option value="2nd Year">2nd Year</option></select>
                </div>
                <div class="input-group"><label>Section</label><input type="text" name="section" class="form-input" placeholder="e.g., BSIT-2A" required style="text-transform:uppercase;"></div>

                <div class="input-group pw-wrapper"><label>Password</label><input type="password" name="password" class="form-input" placeholder="6+ characters required" minlength="6" required>
                    <button type="button" class="pw-toggle" aria-label="Show password" onclick="toggleEye(this)"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg></button>
                </div>

                <div class="input-group pw-wrapper"><label>Confirm Password</label><input type="password" name="password_confirm" class="form-input" placeholder="Re-type password" minlength="6" required>
                    <button type="button" class="pw-toggle" aria-label="Show password" onclick="toggleEye(this)"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg></button>
                </div>

                <button type="submit" class="auth-btn btn-primary">Create Student Account</button>
            </form>
            <div class="auth-footer"><a href="#" onclick="showForm('choice')">Back to role selection</a></div>`;

        const teacherFormHTML = `
            <h1 class="auth-title">Teacher Registration</h1>
            <p class="auth-subtitle">An invitation code is required for teacher accounts.</p>
            <form id="teacher-reg-form" novalidate>
                <div class="input-group"><label>Invitation Code</label><input type="text" name="invitation_code" class="form-input" placeholder="Enter 16-character code" required></div>
                <div class="input-group"><label>Teacher ID</label><input type="text" name="teacher_id" class="form-input" placeholder="e.g., 202400001234" pattern="[0-9]{12}" maxlength="12" title="ID must be exactly 12 digits." required></div>
                <div class="input-group"><label>First Name</label><input type="text" name="first_name" class="form-input" placeholder="e.g., Maria" required></div>
                <div class="input-group"><label>Last Name</label><input type="text" name="last_name" class="form-input" placeholder="e.g., Santos" required></div>
                <div class="input-group">
                    <label>Gender</label>
                    <select name="gender" class="form-input" required><option value="" disabled selected>Select Gender</option><option value="Male">Male</option><option value="Female">Female</option></select>
                </div>
                <div class="input-group"><label>Email</label><input type="email" name="email" class="form-input" placeholder="e.g., maria.santos@uic.edu.ph" required></div>

                <div class="input-group pw-wrapper"><label>Password</label><input type="password" name="password" class="form-input" placeholder="6+ characters required" minlength="6" required>
                    <button type="button" class="pw-toggle" aria-label="Show password" onclick="toggleEye(this)"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg></button>
                </div>

                <div class="input-group pw-wrapper"><label>Confirm Password</label><input type="password" name="password_confirm" class="form-input" placeholder="Re-type password" minlength="6" required>
                    <button type="button" class="pw-toggle" aria-label="Show password" onclick="toggleEye(this)"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg></button>
                </div>

                <button type="submit" class="auth-btn btn-primary">Create Teacher Account</button>
            </form>
            <div class="auth-footer"><a href="#" onclick="showForm('choice')">Back to role selection</a></div>`;

        // --- NEW & IMPROVED VALIDATION LOGIC ---
        function attachValidationListeners() {
            const idInput = document.querySelector('input[name="student_id"], input[name="teacher_id"]');
            const emailInput = document.querySelector('input[name="email"]');
            const passwordInput = document.querySelector('input[name="password"]');
            const passwordConfirm = document.querySelector('input[name="password_confirm"]');

            if (idInput) idInput.addEventListener('blur', validateInput);
            if (emailInput) emailInput.addEventListener('blur', validateInput);
            if (passwordInput) passwordInput.addEventListener('blur', validateInput);
            if (passwordConfirm) passwordConfirm.addEventListener('blur', validateInput);
        }

        function toggleEye(button) {
            // find sibling input[type=password] within same pw-wrapper
            const wrapper = button.closest('.pw-wrapper');
            if (!wrapper) return;
            const input = wrapper.querySelector('input.form-input');
            if (!input) return;
            if (input.type === 'password') {
                input.type = 'text';
                // change icon (simple approach: rotate)
                button.style.color = 'var(--primary)';
            } else {
                input.type = 'password';
                button.style.color = 'var(--text-secondary)';
            }
        }

        // Shows an error message under an input field
        function showFieldError(input, message) {
            const parentGroup = input.parentElement;
            const existingError = parentGroup.querySelector('.error-message');
            if (existingError) existingError.remove();

            input.classList.add('error');
            const errorMessage = document.createElement('div');
            errorMessage.className = 'error-message';
            errorMessage.textContent = message;
            parentGroup.append(errorMessage);
        }

        function clearFieldError(input) {
            const parentGroup = input.parentElement;
            const existingError = parentGroup.querySelector('.error-message');
            if (existingError) existingError.remove();
            input.classList.remove('error');
        }

        async function validateInput(event) {
            const input = event.target;
            const value = input.value.trim();
            const name = input.name;
            clearFieldError(input);
            if (!value) return;

            if (name.includes('id') && value.length !== 12) {
                showFieldError(input, 'ID must be exactly 12 digits.');
                return;
            }
            if (name === 'password' && value.length < 6) {
                showFieldError(input, 'Password must be at least 6 characters.');
                return;
            }

            if (name === 'password_confirm') {
                const form = input.closest('form');
                const pw = form.querySelector('input[name="password"]').value;
                if (pw !== value) {
                    showFieldError(input, 'Passwords do not match.');
                    return;
                } else {
                    clearFieldError(input);
                }
            }

            if (name.includes('id') || name.includes('email')) {
                const formData = new FormData();
                formData.append(name.includes('id') ? 'idNumber' : 'email', value);
                try {
                    const response = await fetch('check_student.php', { method: 'POST', body: formData });
                    const data = await response.json();
                    if (data.exists) {
                        showFieldError(input, `${name.replace(/_/g, ' ')} is already taken.`);
                    }
                } catch (err) { console.error("Validation check failed:", err); }
            }
        }

        function validateFormOnSubmit(form) {
            let isValid = true;
            form.querySelectorAll('.error').forEach(el => el.classList.remove('error'));
            form.querySelectorAll('.error-message').forEach(el => el.remove());

            form.querySelectorAll('input[required], select[required]').forEach(input => {
                if (!input.value.trim()) {
                    showFieldError(input, 'This field is required.');
                    isValid = false;
                }
            });
            const idInput = form.querySelector('input[name="student_id"], input[name="teacher_id"]');
            if (idInput && idInput.value.length !== 12) {
                showFieldError(idInput, 'ID must be exactly 12 digits.');
                isValid = false;
            }
            const passwordInput = form.querySelector('input[name="password"]');
            const passwordConfirm = form.querySelector('input[name="password_confirm"]');
            if (passwordInput && passwordInput.value.length < 6) {
                showFieldError(passwordInput, 'Password must be at least 6 characters.');
                isValid = false;
            }
            if (passwordInput && passwordConfirm && passwordInput.value !== passwordConfirm.value) {
                showFieldError(passwordConfirm, 'Passwords do not match.');
                isValid = false;
            }
            return isValid;
        }

        function showForm(type) {
            formCard.style.opacity = 0;
            setTimeout(() => {
                if (type === 'student') formCard.innerHTML = studentFormHTML;
                else if (type === 'teacher') formCard.innerHTML = teacherFormHTML;
                else formCard.innerHTML = choiceHTML;
                formCard.style.opacity = 1;
                attachValidationListeners();
            }, 200);
        }

        formCard.addEventListener('submit', async function (e) {
            if (e.target.tagName !== 'FORM') return;
            e.preventDefault();
            const form = e.target;
            if (!validateFormOnSubmit(form)) return;

            const action = form.id.includes('student') ? 'register_student' : 'register_teacher';
            const btn = form.querySelector('.auth-btn');
            const originalText = btn.textContent;
            btn.textContent = 'Creating...';
            btn.disabled = true;

            try {
                const res = await fetch(`account.php?action=${action}`, { method: 'POST', body: new FormData(form) });
                const data = await res.json();
                if (data.success) {
                    await Swal.fire({ icon: 'success', title: 'Account Created!', text: 'You can now log in.' });
                    window.location.href = 'login.php';
                } else { throw new Error(data.message); }
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'Registration Failed', text: error.message });
                const errorMessage = error.message.toLowerCase();
                if (errorMessage.includes('id')) {
                    const idInput = form.querySelector('input[name="student_id"], input[name="teacher_id"]');
                    showFieldError(idInput, error.message);
                }
                if (errorMessage.includes('email')) {
                    showFieldError(form.querySelector('input[name="email"]'), error.message);
                }
                if (errorMessage.includes('password')) {
                    showFieldError(form.querySelector('input[name="password_confirm"]') || form.querySelector('input[name="password"]'), error.message);
                }
            } finally {
                btn.textContent = originalText;
                btn.disabled = false;
            }
        });

        formCard.style.transition = 'opacity 0.2s ease-in-out';
        showForm('choice');
    </script>
</body>

</html>