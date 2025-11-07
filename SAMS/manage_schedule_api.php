<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
include 'db_connection.php';

// Ensure session role is present
if (!isset($_SESSION['role'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized: not logged in.']);
    exit;
}

// Allow teacher, admin, and superadmin to modify schedules/rules
$is_admin_or_teacher = in_array($_SESSION['role'], ['teacher', 'admin', 'superadmin']);

$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {
        case 'get_data':
            getData($conn);
            break;
        case 'save_schedule':
            if (!$is_admin_or_teacher) {
                echo json_encode(['success' => false, 'message' => 'Unauthorized: insufficient role to save schedule.']);
                break;
            }
            saveSchedule($conn);
            break;
        case 'delete_schedule':
            if (!$is_admin_or_teacher) {
                echo json_encode(['success' => false, 'message' => 'Unauthorized: insufficient role to delete schedule.']);
                break;
            }
            deleteSchedule($conn);
            break;
        case 'save_rules':
            if (!$is_admin_or_teacher) {
                echo json_encode(['success' => false, 'message' => 'Unauthorized: insufficient role to update rules.']);
                break;
            }
            saveRules($conn);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action.']);
            break;
    }
} catch (Throwable $e) {
    // Catch fatal errors and exceptions, never return an empty response
    error_log("manage_schedule_api.php error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

$conn->close();
exit;


// -------------------- Functions --------------------

function getData($conn)
{
    // Fetch schedules
    $schedules_sql = "
        SELECT 
            id, course_name, section, instructor, department, day_of_week, 
            TIME_FORMAT(start_time, '%H:%i') as start_time, 
            TIME_FORMAT(end_time, '%H:%i') as end_time,
            announcement
        FROM schedules 
        ORDER BY course_name, day_of_week, start_time
    ";
    $schedules_res = $conn->query($schedules_sql);
    if ($schedules_res === false) {
        echo json_encode(['success' => false, 'message' => 'Failed to fetch schedules.']);
        return;
    }
    $schedules = $schedules_res->fetch_all(MYSQLI_ASSOC);

    // Fetch rules
    $rules_result = $conn->query("SELECT name, content FROM rules");
    $rules = [];
    if ($rules_result) {
        while ($row = $rules_result->fetch_assoc()) {
            $rules[$row['name']] = $row['content'];
        }
    }

    // Fetch teachers (with department)
    $teachers_result = $conn->query("SELECT teacher_id, first_name, last_name, department FROM teachers ORDER BY last_name");
    $teachers = $teachers_result ? $teachers_result->fetch_all(MYSQLI_ASSOC) : [];

    // Fetch departments (exclude Administration if desired)
    $departments_result = $conn->query("SELECT name FROM departments WHERE name != 'Administration' ORDER BY name");
    $departments = $departments_result ? $departments_result->fetch_all(MYSQLI_ASSOC) : [];

    echo json_encode([
        'success' => true,
        'schedules' => $schedules,
        'rules' => $rules,
        'teachers' => $teachers,
        'departments' => $departments
    ]);
}

function saveSchedule($conn)
{
    // Required fields
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $course_name = trim($_POST['course_name'] ?? '');
    $section = trim($_POST['section'] ?? '');
    $instructor = trim($_POST['instructor'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $day_of_week = trim($_POST['day_of_week'] ?? '');
    $start_time = trim($_POST['start_time'] ?? '');
    $end_time = trim($_POST['end_time'] ?? '');
    $announcement = isset($_POST['announcement']) ? trim($_POST['announcement']) : null;

    // Basic validation
    if ($course_name === '' || $section === '' || $instructor === '' || $department === '' || $day_of_week === '' || $start_time === '' || $end_time === '') {
        echo json_encode(['success' => false, 'message' => 'All required fields must be provided.']);
        return;
    }

    // Optional: ensure times are valid HH:MM
    if (!preg_match('/^\d{2}:\d{2}$/', $start_time) || !preg_match('/^\d{2}:\d{2}$/', $end_time)) {
        echo json_encode(['success' => false, 'message' => 'Start time and end time must be in HH:MM format.']);
        return;
    }

    // Save (insert or update) using prepared statements
    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE schedules SET course_name=?, section=?, instructor=?, department=?, day_of_week=?, start_time=?, end_time=?, announcement=? WHERE id=?");
        if ($stmt === false) {
            echo json_encode(['success' => false, 'message' => 'Failed to prepare update statement.']);
            return;
        }
        $stmt->bind_param("ssssssssi", $course_name, $section, $instructor, $department, $day_of_week, $start_time, $end_time, $announcement, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO schedules (course_name, section, instructor, department, day_of_week, start_time, end_time, announcement) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt === false) {
            echo json_encode(['success' => false, 'message' => 'Failed to prepare insert statement.']);
            return;
        }
        $stmt->bind_param("ssssssss", $course_name, $section, $instructor, $department, $day_of_week, $start_time, $end_time, $announcement);
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Schedule saved successfully.']);
    } else {
        error_log("manage_schedule_api.php saveSchedule SQL error: " . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Failed to save schedule.']);
    }
    $stmt->close();
}

function deleteSchedule($conn)
{
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID required.']);
        return;
    }
    $stmt = $conn->prepare("DELETE FROM schedules WHERE id = ?");
    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare delete statement.']);
        return;
    }
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Schedule deleted.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete schedule.']);
    }
    $stmt->close();
}

function saveRules($conn)
{
    // Expect grace_period in POST
    $grace_period = isset($_POST['grace_period']) ? trim($_POST['grace_period']) : null;
    if ($grace_period === null || $grace_period === '') {
        echo json_encode(['success' => false, 'message' => 'Grace period is required.']);
        return;
    }
    // Validate numeric
    if (!is_numeric($grace_period) || intval($grace_period) < 0) {
        echo json_encode(['success' => false, 'message' => 'Grace period must be a non-negative number.']);
        return;
    }
    $grace_period_val = (string) intval($grace_period);

    $stmt = $conn->prepare("UPDATE rules SET content = ? WHERE name = 'grace_period'");
    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare rules update.']);
        return;
    }
    $stmt->bind_param("s", $grace_period_val);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Rules updated.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update rules.']);
    }
    $stmt->close();
}
?>