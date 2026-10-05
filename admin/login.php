<?php
/**
 * Admin Panel Authentication Login
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (is_admin_logged_in()) {
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}

$pdo = db();
$errors = [];

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Session token expired. Please try again.';
    } else {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $errors[] = 'Please enter your administrator email and password.';
        } else {
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ? AND status = 'active' LIMIT 1");
            $stmt->execute([$email]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password'])) {
                $_SESSION['admin_id'] = (int)$admin['id'];
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_name'] = $admin['name'];
                $_SESSION['admin_email'] = $admin['email'];
                $_SESSION['admin_role'] = $admin['role'];

                // Update last login
                $pdo->prepare("UPDATE admins SET last_login = CURRENT_TIMESTAMP WHERE id = ?")->execute([$admin['id']]);

                set_flash('success', 'Welcome to Achar Heritage Admin Console, ' . $admin['name'] . '!');
                header('Location: ' . BASE_URL . '/admin/dashboard.php');
                exit;
            } else {
                $errors[] = 'Invalid administrator credentials or inactive account.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal Login - Achar Heritage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #180507 0%, #450A0E 50%, #7A0C11 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .admin-login-card {
            background: #FFF;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 440px;
            padding: 40px;
            border: 1px solid var(--brand-border);
        }
    </style>
</head>
<body>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 d-flex justify-content-center">
            
            <div class="admin-login-card">
                <div class="text-center mb-4">
                    <div class="brand-logo-badge mx-auto mb-3" style="width: 54px; height: 54px; font-size: 1.5rem;">
                        <i class="bi bi-shield-lock-fill"></i>
                    </div>
                    <h3 class="font-heading mb-1 text-dark">Culinary Admin</h3>
                    <p class="text-muted small">Store Management & Dispatch Control</p>
                </div>

                <?= render_flash() ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger rounded-3 small py-2">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $e): ?>
                                <li><?= e($e) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="<?= BASE_URL ?>/admin/login.php" method="POST">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Admin Email</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                            <input type="email" name="email" id="adminEmail" required class="form-control" 
                                   value="<?= e($_POST['email'] ?? '') ?>" placeholder="admin@achar.com">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-key"></i></span>
                            <input type="password" name="password" id="adminPass" required class="form-control" placeholder="••••••••">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-brand-primary w-100 py-3 rounded-pill fw-bold shadow">
                        <i class="bi bi-box-arrow-in-right me-2"></i> Access Admin Console
                    </button>
                </form>

                <!-- Demo Credentials Helper -->
                <div class="mt-4 p-3 bg-light rounded-3 text-center small border">
                    <span class="text-muted d-block mb-1"><strong>Default Admin Credentials:</strong></span>
                    <code>admin@achar.com</code> / <code>Admin@123</code>
                    <button type="button" class="btn btn-sm btn-link d-block mx-auto text-danger p-0 mt-1 fw-bold"
                            onclick="document.getElementById('adminEmail').value='admin@achar.com'; document.getElementById('adminPass').value='Admin@123';">
                        Auto-fill Credentials
                    </button>
                </div>

                <div class="text-center mt-4">
                    <a href="<?= BASE_URL ?>/index.php" class="small text-muted text-decoration-none">
                        <i class="bi bi-arrow-left me-1"></i> Return to Customer Storefront
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
