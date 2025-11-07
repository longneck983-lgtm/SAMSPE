<?php
session_start();
header('Content-Type: application/json');
include 'db_connection.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'list':
        echo json_encode(listUsers($conn));
        break;
    case 'update_user':
        updateUser($conn);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}
$conn->close();

function listUsers($conn) {
    $students = [];
    $teachers = [];
    $admins = [];

    $res = $conn->query("SELECT id, student_id, first_name, last_name, email, department, year_level, section FROM students ORDER BY last_name, first_name");
    if ($res) { $students = $res->fetch_all(MYSQLI_ASSOC); }

    $res2 = $conn->query("SELECT id, teacher_id, first_name, last_name, email, department FROM teachers ORDER BY last_name, first_name");
    if ($res2) { $teachers = $res2->fetch_all(MYSQLI_ASSOC); }

    // gather admin accounts (role 'admin' or 'superadmin') and try to attach a display name from teachers table (if present)
    $sqlAdmins = "SELECT a.id as account_id, a.username, a.role, t.first_name, t.last_name, t.email AS teacher_email
                  FROM accounts a
                  LEFT JOIN teachers t ON a.username = t.teacher_id
                  WHERE a.role IN ('admin','superadmin')
                  ORDER BY a.username";
    $res3 = $conn->query($sqlAdmins);
    if ($res3) { $admins = $res3->fetch_all(MYSQLI_ASSOC); }

    return ['students' => $students, 'teachers' => $teachers, 'admins' => $admins];
}

function updateUser($conn) {
    $role = $_POST['role'] ?? '';
    $id = $_POST['id'] ?? '';
    $new_username = trim($_POST['new_username'] ?? '');
    $new_password = $_POST['new_password'] ?? '';

    if ($role === '') {
        echo json_encode(['success' => false, 'message' => 'Missing role.']);
        return;
    }

    try {
        $conn->begin_transaction();

        if ($role === 'student') {
            $student_id = intval($id);
            if ($student_id <= 0) throw new Exception('Invalid student id.');

            $stmt = $conn->prepare("SELECT student_id FROM students WHERE id = ?");
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            $orig = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$orig) throw new Exception('Student not found.');

            $original_username = $orig['student_id'];

            // Update students table
            $stmt = $conn->prepare("UPDATE students SET student_id = ? WHERE id = ?");
            $stmt->bind_param("si", $new_username, $student_id);
            $stmt->execute();
            $stmt->close();

            if ($original_username !== $new_username) {
                $stmt = $conn->prepare("UPDATE accounts SET username = ? WHERE username = ? AND role = 'student'");
                $stmt->bind_param("ss", $new_username, $original_username);
                $stmt->execute();
                $stmt->close();
            }

            if (!empty($new_password)) {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE accounts SET password = ? WHERE username = ? AND role = 'student'");
                $stmt->bind_param("ss", $hashed, $new_username);
                $stmt->execute();
                $stmt->close();
            }
        } elseif ($role === 'teacher') {
            $teacher_id = intval($id);
            if ($teacher_id <= 0) throw new Exception('Invalid teacher id.');

            $stmt = $conn->prepare("SELECT teacher_id FROM teachers WHERE id = ?");
            $stmt->bind_param("i", $teacher_id);
            $stmt->execute();
            $orig = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$orig) throw new Exception('Teacher not found.');

            $original_username = $orig['teacher_id'];

            $stmt = $conn->prepare("UPDATE teachers SET teacher_id = ? WHERE id = ?");
            $stmt->bind_param("si", $new_username, $teacher_id);
            $stmt->execute();
            $stmt->close();

            if ($original_username !== $new_username) {
                $stmt = $conn->prepare("UPDATE accounts SET username = ? WHERE username = ? AND role = 'teacher'");
                $stmt->bind_param("ss", $new_username, $original_username);
                $stmt->execute();
                $stmt->close();
            }

            if (!empty($new_password)) {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE accounts SET password = ? WHERE username = ? AND role = 'teacher'");
                $stmt->bind_param("ss", $hashed, $new_username);
                $stmt->execute();
                $stmt->close();
            }
        } elseif ($role === 'admin') {
            // For admins, id represents accounts.id
            $account_id = intval($id);
            if ($account_id <= 0) throw new Exception('Invalid account id.');

            // Fetch original username
            $stmt = $conn->prepare("SELECT username FROM accounts WHERE id = ?");
            $stmt->bind_param("i", $account_id);
            $stmt->execute();
            $orig = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$orig) throw new Exception('Admin account not found.');

            $original_username = $orig['username'];

            // Update accounts.username if changed
            if ($original_username !== $new_username) {
                $stmt = $conn->prepare("UPDATE accounts SET username = ? WHERE id = ?");
                $stmt->bind_param("si", $new_username, $account_id);
                $stmt->execute();
                $stmt->close();

                // If this admin also has a teacher profile, update teachers.teacher_id
                $stmt = $conn->prepare("UPDATE teachers SET teacher_id = ? WHERE teacher_id = ?");
                $stmt->bind_param("ss", $new_username, $original_username);
                $stmt->execute();
                $stmt->close();
            }

            if (!empty($new_password)) {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE accounts SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $hashed, $account_id);
                $stmt->execute();
                $stmt->close();
            }
        } else {
            throw new Exception('Unsupported role.');
        }

        // Optional logging
        if ($conn->query("SHOW TABLES LIKE 'admin_actions'")->num_rows > 0) {
            $action_text = "Superadmin {$_SESSION['username']} updated $role id={$id}, new_username={$new_username}";
            $stmt = $conn->prepare("INSERT INTO admin_actions (admin_username, action, details) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $_SESSION['username'], $action_text, $action_text);
            $stmt->execute();
            $stmt->close();
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'User updated.']);
    } catch (Exception $e) {
        $conn->rollback();
        if ($conn->errno == 1062) {
            echo json_encode(['success' => false, 'message' => 'The chosen username already exists.']);
        } else {
            error_log("manage_users_api.php error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to update user.']);
        }
    }
}
?>