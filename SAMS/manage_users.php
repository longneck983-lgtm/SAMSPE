<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'superadmin') {
    header('Location: login.php');
    exit;
}
include 'db_connection.php';
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users - Superadmin - SAMS-PE</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="page-container">
        <?php include 'sidebar.php'; ?>
        <div class="main-content">
            <header class="main-header">
                <button id="menu-toggle" class="menu-toggle">&#9776;</button>
                <div class="header-text">
                    <h1>Manage Users</h1>
                    <p>Create and manage student, teacher, and admin accounts.</p>
                </div>
            </header>

            <div class="card">
                <h2 class="card-title">Students</h2>
                <div style="overflow:auto;">
                    <table class="data-table" id="students-table">
                        <thead><tr><th>ID</th><th>Student ID</th><th>Name</th><th>Email</th><th>Section</th><th>Actions</th></tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            <div class="card" style="margin-top:1rem;">
                <h2 class="card-title">Teachers</h2>
                <div style="overflow:auto;">
                    <table class="data-table" id="teachers-table">
                        <thead><tr><th>ID</th><th>Teacher ID</th><th>Name</th><th>Email</th><th>Dept</th><th>Actions</th></tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            <div class="card" style="margin-top:1rem;">
                <h2 class="card-title">Admins</h2>
                <div style="overflow:auto;">
                    <table class="data-table" id="admins-table">
                        <thead><tr><th>Account ID</th><th>Username</th><th>Role</th><th>Name/Email</th><th>Actions</th></tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="overlay"></div>
    </div>

    <!-- edit modal -->
    <div id="editModal" style="display:none;">
        <form id="editForm" style="max-width:520px;margin:1rem auto;background:#fff;padding:1rem;border-radius:8px;">
            <input type="hidden" name="role" id="edit_role">
            <input type="hidden" name="id" id="edit_id">
            <div class="input-group">
                <label>New Username (ID)</label>
                <input type="text" name="new_username" id="edit_new_username" class="form-input" required>
            </div>
            <div class="input-group">
                <label>New Password (leave blank to keep current)</label>
                <input type="password" name="new_password" id="edit_new_password" class="form-input">
            </div>
            <div style="display:flex;gap:0.5rem;">
                <button type="submit" class="btn btn-primary">Save</button>
                <button type="button" id="cancelEdit" class="btn btn-secondary">Cancel</button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        async function loadUsers(){
            const res = await fetch('manage_users_api.php?action=list');
            const data = await res.json();
            if (!data) return;
            const stBody = document.querySelector('#students-table tbody');
            const tBody = document.querySelector('#teachers-table tbody');
            const aBody = document.querySelector('#admins-table tbody');
            stBody.innerHTML = '';
            tBody.innerHTML = '';
            aBody.innerHTML = '';

            (data.students || []).forEach(s => {
                const tr = document.createElement('tr');
                tr.innerHTML = `<td>${s.id}</td><td>${s.student_id}</td><td>${s.last_name}, ${s.first_name}</td><td>${s.email}</td><td>${s.section || ''}</td>
                <td><button class="btn btn-secondary" onclick="openEdit('student', ${s.id}, '${s.student_id.replace(/'/g,"\\'")}')">Edit</button></td>`;
                stBody.appendChild(tr);
            });

            (data.teachers || []).forEach(t => {
                const tr = document.createElement('tr');
                tr.innerHTML = `<td>${t.id}</td><td>${t.teacher_id}</td><td>${t.last_name}, ${t.first_name}</td><td>${t.email}</td><td>${t.department || ''}</td>
                <td><button class="btn btn-secondary" onclick="openEdit('teacher', ${t.id}, '${t.teacher_id.replace(/'/g,"\\'")}')">Edit</button></td>`;
                tBody.appendChild(tr);
            });

            (data.admins || []).forEach(a => {
                const display = (a.first_name && a.last_name) ? `${a.last_name}, ${a.first_name}` : (a.teacher_email || '');
                const tr = document.createElement('tr');
                tr.innerHTML = `<td>${a.account_id}</td><td>${a.username}</td><td>${a.role}</td><td>${display}</td>
                <td><button class="btn btn-secondary" onclick="openEdit('admin', ${a.account_id}, '${a.username.replace(/'/g,"\\'")}')">Edit</button></td>`;
                aBody.appendChild(tr);
            });
        }

        function openEdit(role, id, currentUsername){
            document.getElementById('edit_role').value = role;
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_new_username').value = currentUsername;
            document.getElementById('edit_new_password').value = '';
            // show modal
            const modal = document.getElementById('editModal');
            modal.style.position = 'fixed';
            modal.style.left = '0';
            modal.style.top = '0';
            modal.style.width = '100%';
            modal.style.height = '100%';
            modal.style.display = 'flex';
            modal.style.alignItems = 'center';
            modal.style.justifyContent = 'center';
            modal.style.zIndex = '2000';
        }

        document.getElementById('cancelEdit').addEventListener('click', () => {
            document.getElementById('editModal').style.display = 'none';
        });

        document.getElementById('editForm').addEventListener('submit', async function(e){
            e.preventDefault();
            const form = new FormData(this);
            form.append('action','update_user');
            const btn = this.querySelector('.btn-primary');
            const originalText = btn.textContent;
            btn.textContent = 'Saving...'; btn.disabled = true;
            try {
                const res = await fetch('manage_users_api.php', { method:'POST', body: form });
                const data = await res.json();
                if (data.success) {
                    Swal.fire('Saved', data.message, 'success');
                    document.getElementById('editModal').style.display = 'none';
                    await loadUsers();
                } else {
                    throw new Error(data.message || 'Failed to save');
                }
            } catch(err) { Swal.fire('Error', err.message, 'error'); }
            finally { btn.textContent = originalText; btn.disabled = false; }
        });

        document.addEventListener('DOMContentLoaded', loadUsers);

        const menuToggle = document.getElementById('menu-toggle'), sidebar = document.querySelector('.sidebar'), overlay = document.querySelector('.overlay');
        if(menuToggle) { menuToggle.addEventListener('click', () => { sidebar.classList.add('show'); overlay.classList.add('show'); }); overlay.addEventListener('click', () => { sidebar.classList.remove('show'); overlay.classList.remove('show'); }); }
    </script>
</body>
</html>