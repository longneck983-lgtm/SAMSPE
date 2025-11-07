<?php
session_start();
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['teacher', 'admin', 'superadmin'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Students - SAMS-PE</title>
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
                    <h1>Manage Students</h1>
                    <p>View, edit, or remove student records.</p>
                </div>
            </header>

            <div class="card">
                <div class="card-toolbar">
                    <input type="search" id="search-input" class="form-input" placeholder="Search by name, student ID, section...">
                    <div style="margin-left:auto;">
                        <!-- reserved for add student button in future -->
                    </div>
                </div>

                <div style="overflow:auto;">
                    <table class="data-table" id="students-table">
                        <thead>
                            <tr><th>ID</th><th>Student ID</th><th>Name</th><th>Email</th><th>Section</th><th>Dept</th><th>Year</th><th>Actions</th></tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="overlay"></div>
    </div>

    <!-- edit modal -->
    <div id="studentEditModal" style="display:none;">
        <form id="studentEditForm" style="max-width:640px;margin:1rem auto;background:#fff;padding:1rem;border-radius:8px;">
            <input type="hidden" name="id" id="student_edit_id">
            <div class="input-group"><label>Student ID</label><input type="text" name="student_id" id="student_edit_student_id" class="form-input" required></div>
            <div class="input-group"><label>First Name</label><input type="text" name="first_name" id="student_edit_first_name" class="form-input" required></div>
            <div class="input-group"><label>Last Name</label><input type="text" name="last_name" id="student_edit_last_name" class="form-input" required></div>
            <div class="input-group"><label>Email</label><input type="email" name="email" id="student_edit_email" class="form-input" required></div>
            <div class="input-group"><label>Department</label><input type="text" name="department" id="student_edit_department" class="form-input"></div>
            <div class="input-group"><label>Year Level</label><input type="text" name="year_level" id="student_edit_year_level" class="form-input"></div>
            <div class="input-group"><label>Section</label><input type="text" name="section" id="student_edit_section" class="form-input"></div>
            <div style="display:flex;gap:0.5rem;">
                <button type="submit" class="btn btn-primary">Save</button>
                <button type="button" id="cancelStudentEdit" class="btn btn-secondary">Cancel</button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        let studentsData = [];

        async function loadStudents() {
            const res = await fetch('manage_student_api.php?action=list_students');
            const json = await res.json();
            if (!json.success) { Swal.fire('Error', json.message || 'Failed to load students', 'error'); return; }
            studentsData = json.students || [];
            renderStudents(studentsData);
        }

        function renderStudents(list) {
            const tbody = document.querySelector('#students-table tbody');
            tbody.innerHTML = '';
            if (!list.length) {
                tbody.innerHTML = `<tr><td colspan="8" style="text-align:center; padding:2rem;">No students found.</td></tr>`;
                return;
            }
            list.forEach(s => {
                const tr = document.createElement('tr');
                tr.innerHTML = `<td>${s.id}</td>
                                <td>${s.student_id}</td>
                                <td>${s.last_name}, ${s.first_name}</td>
                                <td>${s.email}</td>
                                <td>${s.section || ''}</td>
                                <td>${s.department || ''}</td>
                                <td>${s.year_level || ''}</td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-secondary" onclick="openStudentEdit(${s.id})">Edit</button>
                                        <button class="btn btn-delete" onclick="deleteStudent(${s.id})">Delete</button>
                                    </div>
                                </td>`;
                tbody.appendChild(tr);
            });
        }

        function openStudentEdit(id) {
            const s = studentsData.find(x => x.id == id);
            if (!s) return;
            document.getElementById('student_edit_id').value = s.id;
            document.getElementById('student_edit_student_id').value = s.student_id;
            document.getElementById('student_edit_first_name').value = s.first_name;
            document.getElementById('student_edit_last_name').value = s.last_name;
            document.getElementById('student_edit_email').value = s.email;
            document.getElementById('student_edit_department').value = s.department || '';
            document.getElementById('student_edit_year_level').value = s.year_level || '';
            document.getElementById('student_edit_section').value = s.section || '';

            const modal = document.getElementById('studentEditModal');
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

        document.getElementById('cancelStudentEdit').addEventListener('click', () => {
            document.getElementById('studentEditModal').style.display = 'none';
        });

        document.getElementById('studentEditForm').addEventListener('submit', async function (e) {
            e.preventDefault();
            const form = new FormData(this);
            form.append('action', 'update_student');
            const btn = this.querySelector('.btn-primary');
            const original = btn.textContent;
            btn.textContent = 'Saving...'; btn.disabled = true;
            try {
                const res = await fetch('manage_student_api.php', { method: 'POST', body: form });
                const json = await res.json();
                if (json.success) {
                    Swal.fire('Saved', json.message, 'success');
                    document.getElementById('studentEditModal').style.display = 'none';
                    await loadStudents();
                } else {
                    throw new Error(json.message || 'Failed to update student');
                }
            } catch (err) {
                Swal.fire('Error', err.message, 'error');
            } finally {
                btn.textContent = original; btn.disabled = false;
            }
        });

        async function deleteStudent(id) {
            const result = await Swal.fire({ title: 'Delete student?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Delete' });
            if (!result.isConfirmed) return;
            const form = new FormData();
            form.append('action', 'delete_student');
            form.append('id', id);
            const res = await fetch('manage_student_api.php', { method: 'POST', body: form });
            const json = await res.json();
            if (json.success) {
                Swal.fire('Deleted', json.message, 'success');
                await loadStudents();
            } else {
                Swal.fire('Error', json.message || 'Failed to delete', 'error');
            }
        }

        document.getElementById('search-input').addEventListener('input', function () {
            const q = this.value.trim().toLowerCase();
            const filtered = studentsData.filter(s => {
                return (s.student_id || '').toLowerCase().includes(q) ||
                       (s.first_name || '').toLowerCase().includes(q) ||
                       (s.last_name || '').toLowerCase().includes(q) ||
                       (s.section || '').toLowerCase().includes(q);
            });
            renderStudents(filtered);
        });

        document.addEventListener('DOMContentLoaded', loadStudents);

        const menuToggle = document.getElementById('menu-toggle'), sidebar = document.querySelector('.sidebar'), overlay = document.querySelector('.overlay');
        if (menuToggle) { menuToggle.addEventListener('click', () => { sidebar.classList.add('show'); overlay.classList.add('show'); }); overlay.addEventListener('click', () => { sidebar.classList.remove('show'); overlay.classList.remove('show'); }); }
    </script>
</body>
</html>