<?php
/**
 * Customer Profile & Security Settings
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

$pdo = db();
$cid = customer_id();
$user = current_user();
$errors = [];

// Handle Profile Update
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    if (verify_csrf()) {
        $name = sanitize($_POST['name'] ?? '');
        $mobile = sanitize($_POST['mobile'] ?? '');
        $email = strtolower(sanitize($_POST['email'] ?? ''));

        if (empty($name) || empty($mobile) || empty($email)) {
            $errors[] = 'All profile fields are required.';
        } else {
            // Check email/mobile clash
            $check = $pdo->prepare("SELECT id FROM customers WHERE (email = ? OR mobile = ?) AND id != ? LIMIT 1");
            $check->execute([$email, $mobile, $cid]);
            if ($check->fetch()) {
                $errors[] = 'Email or mobile is already in use by another customer.';
            } else {
                $up = $pdo->prepare("UPDATE customers SET name = ?, mobile = ?, email = ? WHERE id = ?");
                $up->execute([$name, $mobile, $email, $cid]);
                set_flash('success', 'Profile updated successfully.');
                header('Location: ' . BASE_URL . '/account/profile.php');
                exit;
            }
        }
    } else {
        $errors[] = 'Security token expired. Please try again.';
    }
}

// Handle Password Change
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if (verify_csrf()) {
        $currentPass = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';

        $cStmt = $pdo->prepare("SELECT password FROM customers WHERE id = ?");
        $cStmt->execute([$cid]);
        $row = $cStmt->fetch();

        if (!$row || !password_verify($currentPass, $row['password'])) {
            $errors[] = 'Current password does not match.';
        } elseif (strlen($newPass) < 6) {
            $errors[] = 'New password must be at least 6 characters.';
        } elseif ($newPass !== $confirmPass) {
            $errors[] = 'New passwords do not match.';
        } else {
            $newHash = password_hash($newPass, PASSWORD_BCRYPT);
            $pUp = $pdo->prepare("UPDATE customers SET password = ? WHERE id = ?");
            $pUp->execute([$newHash, $cid]);
            set_flash('success', 'Your password has been changed securely.');
            header('Location: ' . BASE_URL . '/account/profile.php');
            exit;
        }
    }
}

$pageTitle = 'Edit Profile - Achar Heritage';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="py-4 bg-white border-bottom">
    <div class="container">
        <h1 class="font-heading display-6 m-0">Account Settings</h1>
        <p class="text-muted small m-0 mt-1">Update your contact profile and account security password.</p>
    </div>
</div>

<div class="container py-5">
    <?= render_flash() ?>

    <div class="row g-4">
        <div class="col-lg-4">
            <?php require_once __DIR__ . '/account_nav.php'; ?>
        </div>

        <div class="col-lg-8">
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger rounded-4 shadow-sm mb-4">
                    <ul class="mb-0 small ps-3">
                        <?php foreach ($errors as $e): ?>
                            <li><?= e($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Personal Info Card -->
            <div class="card border-0 rounded-4 shadow-sm p-4 bg-white mb-4">
                <h5 class="fw-bold font-heading mb-3">Personal Details</h5>
                <form action="<?= BASE_URL ?>/account/profile.php" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="update_profile" value="1">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Full Name</label>
                            <input type="text" name="name" required class="form-control" value="<?= e($user['name']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Mobile Number</label>
                            <input type="tel" name="mobile" required class="form-control" value="<?= e($user['mobile']) ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Email Address</label>
                            <input type="email" name="email" required class="form-control" value="<?= e($user['email']) ?>">
                        </div>
                        <div class="col-12 mt-3">
                            <button type="submit" class="btn btn-brand-primary px-4 rounded-pill">Save Profile Changes</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Change Password Card -->
            <div class="card border-0 rounded-4 shadow-sm p-4 bg-white">
                <h5 class="fw-bold font-heading mb-3">Security & Password</h5>
                <form action="<?= BASE_URL ?>/account/profile.php" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="change_password" value="1">

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Current Password</label>
                            <input type="password" name="current_password" required class="form-control" placeholder="••••••••">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">New Password</label>
                            <input type="password" name="new_password" required class="form-control" placeholder="••••••••">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Confirm New</label>
                            <input type="password" name="confirm_password" required class="form-control" placeholder="••••••••">
                        </div>
                        <div class="col-12 mt-3">
                            <button type="submit" class="btn btn-outline-brand px-4 rounded-pill">Update Password</button>
                        </div>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
