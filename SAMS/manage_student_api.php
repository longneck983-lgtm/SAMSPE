<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
include 'db_connection.php';

// Permissions: teacher, admin, superadmin allowed
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['teacher', 'admin', 'superadmin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized: insufficient role']);
    exit;
}

$action = $_REQUEST['action'] ?? '';
try {
    switch ($action) {
        case 'list_students':
            listStudents($conn);
            break;
        case 'update_student':
            updateStudent($conn);
            break;
        case 'delete_student':
            deleteStudent($conn);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    }
} catch (Exception $e) {
    error_log("manage_student_api.php exception: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error.']);
}
$conn->close();

function listStudents($conn)
{
    $res = $conn->query("SELECT id, student_id, first_name, last_name, gender, email, department, year_level, section FROM students ORDER BY last_name, first_name");
    if ($res === false) {
        error_log("manage_student_api.php listStudents SQL error: " . $conn->error);
        echo json_encode(['success' => false, 'message' => 'Database error while fetching students.']);
        return;
    }
    $students = $res->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['success' => true, 'count' => count($students), 'students' => $students]);
}

function updateStudent($conn)
{
    $id = intval($_POST['id'] ?? 0);
    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'Student ID is missing.']);
        return;
    }
    $stmt_orig = $conn->prepare("SELECT student_id FROM students WHERE id = ?");
    $stmt_orig->bind_param("i", $id);
    $stmt_orig->execute();
    $orig_row = $stmt_orig->get_result()->fetch_assoc();
    $original_student_id = $orig_row['student_id'] ?? null;
    $stmt_orig->close();

    $new_student_id = $_POST['student_id'] ?? '';
    $section = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $_POST['section'] ?? ''));

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE students SET student_id=?, first_name=?, last_name=?, gender=?, email=?, department=?, year_level=?, section=? WHERE id=?");
        $stmt->bind_param("ssssssssi", $new_student_id, $_POST['first_name'], $_POST['last_name'], $_POST['gender'], $_POST['email'], $_POST['department'], $_POST['year_level'], $section, $id);
        $stmt->execute();
        $stmt->close();

        if ($original_student_id !== $new_student_id) {
            $stmt_acc = $conn->prepare("UPDATE accounts SET username = ? WHERE username = ? AND role = 'student'");
            $stmt_acc->bind_param("ss", $new_student_id, $original_student_id);
            $stmt_acc->execute();
            $stmt_acc->close();
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Student record updated.']);
    } catch (Exception $e) {
        $conn->rollback();
        error_log("manage_student_api.php updateStudent error: " . $e->getMessage());
        if ($conn->errno == 1062) {
            echo json_encode(['success' => false, 'message' => 'The new Student ID or Email already exists for another student.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Update failed.']);
        }
    }
}

function deleteStudent($conn)
{
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID is required.']);
        return;
    }
    $conn->begin_transaction();
    try {
        $stmt_get = $conn->prepare("SELECT student_id FROM students WHERE id = ?");
        $stmt_get->bind_param("i", $id);
        $stmt_get->execute();
        $row = $stmt_get->get_result()->fetch_assoc();
        $student_id_to_delete = $row['student_id'] ?? null;
        $stmt_get->close();

        if ($student_id_to_delete) {
            $stmt1 = $conn->prepare("DELETE FROM students WHERE id = ?");
            $stmt1->bind_param("i", $id);
            $stmt1->execute();
            $stmt1->close();

            $stmt2 = $conn->prepare("DELETE FROM accounts WHERE username = ? AND role = 'student'");
            $stmt2->bind_param("s", $student_id_to_delete);
            $stmt2->execute();
            $stmt2->close();
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Student deleted successfully.']);
    } catch (Exception $e) {
        $conn->rollback();
        error_log("manage_student_api.php deleteStudent error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to delete student.']);
    }
}
?>