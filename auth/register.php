<?php
/**
 * Customer Registration Page
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) {
    header('Location: ' . BASE_URL . '/account/dashboard.php');
    exit;
}

$pdo = db();
$errors = [];

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Session expired. Please try again.';
    } else {
        $name = sanitize($_POST['name'] ?? '');
        $email = strtolower(sanitize($_POST['email'] ?? ''));
        $mobile = sanitize($_POST['mobile'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($name)) $errors[] = 'Full name is required.';
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
        if (empty($mobile) || strlen($mobile) < 10) $errors[] = 'Valid 10-digit mobile number is required.';
        if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
        if ($password !== $confirmPassword) $errors[] = 'Passwords do not match.';

        if (empty($errors)) {
            // Check if email or mobile already exists
            $checkStmt = $pdo->prepare("SELECT id FROM customers WHERE email = ? OR mobile = ? LIMIT 1");
            $checkStmt->execute([$email, $mobile]);
            if ($checkStmt->fetch()) {
                $errors[] = 'An account with this email or mobile already exists. Please log in.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $ins = $pdo->prepare("INSERT INTO customers (name, email, mobile, password, status) VALUES (?, ?, ?, ?, 'active')");
                $ins->execute([$name, $email, $mobile, $hash]);
                $newId = (int)$pdo->lastInsertId();

                // Login
                $_SESSION['customer_id'] = $newId;
                $_SESSION['customer_logged_in'] = true;

                // Associate guest cart
                $cartId = get_or_create_cart_id();
                $pdo->prepare("UPDATE cart SET customer_id = ? WHERE id = ?")->execute([$newId, $cartId]);

                set_flash('success', 'Welcome to Achar Heritage, ' . $name . '! Your account has been created.');
                header('Location: ' . BASE_URL . '/account/dashboard.php');
                exit;
            }
        }
    }
}

$pageTitle = 'Create Customer Account - Achar Heritage';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <div class="card border-0 rounded-4 shadow-sm p-4 p-md-5 bg-white">
                <div class="text-center mb-4">
                    <div class="brand-logo-badge mx-auto mb-3" style="width:50px;height:50px;font-size:1.4rem;">
                        <i class="bi bi-fire"></i>
                    </div>
                    <h2 class="font-heading fs-3">Join Achar Heritage</h2>
                    <p class="text-muted small">Create an account for faster checkout, order tracking, and exclusive discounts.</p>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger rounded-3 small">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $e): ?>
                                <li><?= e($e) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="<?= BASE_URL ?>/auth/register.php" method="POST">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Full Name</label>
                        <input type="text" name="name" required class="form-control" value="<?= e($_POST['name'] ?? '') ?>" placeholder="e.g. Meera Sharma">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Email Address</label>
                        <input type="email" name="email" required class="form-control" value="<?= e($_POST['email'] ?? '') ?>" placeholder="e.g. meera@example.com">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Mobile Number (10 Digits)</label>
                        <input type="tel" name="mobile" required class="form-control" value="<?= e($_POST['mobile'] ?? '') ?>" placeholder="e.g. 9876543210">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Password (Min 6 Characters)</label>
                        <input type="password" name="password" required class="form-control" placeholder="••••••••">
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold">Confirm Password</label>
                        <input type="password" name="confirm_password" required class="form-control" placeholder="••••••••">
                    </div>

                    <button type="submit" class="btn btn-brand-primary w-100 py-3 rounded-pill fw-bold shadow">
                        <i class="bi bi-person-check-fill me-2"></i> Register Account
                    </button>
                </form>

                <div class="text-center mt-4 pt-3 border-top small">
                    Already have an account? <a href="<?= BASE_URL ?>/auth/login.php" class="fw-bold text-danger">Sign In</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
