<?php
session_start();
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['admin','superadmin'])) {
    header('Location: login.php');
    exit;
}
include 'db_connection.php';

// Handle manual creation (admin/superadmin types the code)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    if ($action === 'manual_create') {
        $code = trim($_POST['code'] ?? '');
        $expires_at = trim($_POST['expires_at'] ?? '');
        if (empty($code)) {
            header("Location: manage_codes.php?error=1");
            exit;
        }
        // Validate code length if you want, here we allow up to 64 chars
        $expires_param = null;
        if (!empty($expires_at)) {
            // Expecting YYYY-MM-DD or datetime; safe conversion
            $expires_param = date('Y-m-d H:i:s', strtotime($expires_at));
        } else {
            // default to 1 month from now
            $expires_param = date('Y-m-d H:i:s', strtotime('+1 month'));
        }

        $stmt = $conn->prepare("INSERT INTO invitation_codes (code, expires_at) VALUES (?, ?)");
        $stmt->bind_param("ss", $code, $expires_param);
        $stmt->execute();
        $stmt->close();
        header("Location: manage_codes.php?generated=true");
        exit;
    } elseif ($action === 'generate') {
        // keep the existing random generation
        $new_code = bin2hex(random_bytes(8));
        $expires_param = date('Y-m-d H:i:s', strtotime('+1 month'));
        $stmt = $conn->prepare("INSERT INTO invitation_codes (code, expires_at) VALUES (?, ?)");
        $stmt->bind_param("ss", $new_code, $expires_param);
        $stmt->execute();
        $stmt->close();
        header("Location: manage_codes.php?generated=true");
        exit;
    }
}

$codes_result = $conn->query("SELECT id, code, is_used, used_by, created_at, expires_at FROM invitation_codes ORDER BY created_at DESC");
$all_codes = $codes_result->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Invitation Codes - SAMS-PE</title>
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
                    <h1>Manage Teacher Codes</h1>
                    <p>Create manual invitation codes and set expiry for teacher registration.</p>
                </div>
            </header>

            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1.5rem; align-items: start;">
                <div class="card">
                    <h2 class="card-title">Create Invitation Code</h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="manual_create">
                        <div class="input-group">
                            <label>Invitation Code (manual)</label>
                            <input type="text" name="code" class="form-input" placeholder="Type invitation code (e.g. FAC-2025-001)" maxlength="64" required>
                        </div>
                        <div class="input-group">
                            <label>Expiry Date (optional)</label>
                            <input type="date" name="expires_at" class="form-input">
                            <small style="display:block;color:var(--text-secondary);margin-top:0.4rem">Leave empty to set expiry 1 month from now.</small>
                        </div>
                        <button type="submit" class="btn btn-primary">Create Code</button>
                    </form>

                    <hr style="margin: 1.25rem 0; border-color: var(--border-color);">

                    <h3 style="margin-top:0.25rem;">Quick Generate</h3>
                    <p style="margin-bottom:1rem;color:var(--text-secondary)">Generate a random 16-character code with 1-month expiry.</p>
                    <form method="POST" style="margin-top:0.5rem;">
                        <input type="hidden" name="action" value="generate">
                        <button type="submit" class="btn btn-secondary">Generate Random Code</button>
                    </form>
                </div>

                <div class="card">
                    <h2 class="card-title">Existing Codes</h2>
                    <div style="overflow-x: auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Invitation Code</th>
                                    <th>Expiry</th>
                                    <th>Status</th>
                                    <th>Used By (Teacher ID)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($all_codes)): ?>
                                    <tr>
                                        <td colspan="4" style="text-align:center; padding: 2rem;">No codes generated yet.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($all_codes as $code): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($code['code']); ?></strong></td>
                                            <td><?= htmlspecialchars($code['expires_at'] ? date('Y-m-d', strtotime($code['expires_at'])) : 'No expiry') ?></td>
                                            <td>
                                                <?php if ($code['is_used']): ?>
                                                    <span class="status-badge status-absent">Used</span>
                                                <?php else: ?>
                                                    <?php $expired = ($code['expires_at'] && strtotime($code['expires_at']) <= time()); ?>
                                                    <?php if ($expired): ?>
                                                        <span class="status-badge status-absent">Expired</span>
                                                    <?php else: ?>
                                                        <span class="status-badge status-present">Available</span>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($code['used_by'] ?? 'N/A'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="overlay"></div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('generated')) {
                Swal.fire({ icon: 'success', title: 'New Code Created!', timer: 1600, showConfirmButton: false });
                window.history.replaceState({}, document.title, window.location.pathname);
            } else if (urlParams.has('error')) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Please provide a code.' });
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        });
        const menuToggle = document.getElementById('menu-toggle'), sidebar = document.querySelector('.sidebar'), overlay = document.querySelector('.overlay');
        if(menuToggle) { menuToggle.addEventListener('click', () => { sidebar.classList.add('show'); overlay.classList.add('show'); }); overlay.addEventListener('click', () => { sidebar.classList.remove('show'); overlay.classList.remove('show'); }); }
    </script>
</body>

</html>