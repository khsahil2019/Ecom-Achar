<?php
/**
 * Customer Login Page
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
        $errors[] = 'Session expired. Please try logging in again.';
    } else {
        $loginInput = trim($_POST['login_input'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($loginInput) || empty($password)) {
            $errors[] = 'Please enter your email/mobile and password.';
        } else {
            // Find customer by email OR mobile
            $stmt = $pdo->prepare("SELECT * FROM customers WHERE email = ? OR mobile = ? LIMIT 1");
            $stmt->execute([$loginInput, $loginInput]);
            $customer = $stmt->fetch();

            if ($customer && password_verify($password, $customer['password'])) {
                if ($customer['status'] === 'banned') {
                    $errors[] = 'Your account has been suspended. Please contact customer support.';
                } else {
                    // Set session
                    $_SESSION['customer_id'] = (int)$customer['id'];
                    $_SESSION['customer_logged_in'] = true;

                    // Sync cart: link current guest cart session to this customer
                    $guestSessionId = get_cart_session_id();
                    $pdo->prepare("UPDATE cart SET customer_id = ? WHERE session_id = ?")->execute([$customer['id'], $guestSessionId]);

                    set_flash('success', 'Welcome back, ' . $customer['name'] . '!');

                    $redirect = $_SESSION['intended_url'] ?? (BASE_URL . '/account/dashboard.php');
                    unset($_SESSION['intended_url']);
                    header('Location: ' . $redirect);
                    exit;
                }
            } else {
                $errors[] = 'Invalid email/mobile or password.';
            }
        }
    }
}

$pageTitle = 'Customer Login - Achar Heritage';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card border-0 rounded-4 shadow-sm p-4 p-md-5 bg-white">
                <div class="text-center mb-4">
                    <div class="brand-logo-badge mx-auto mb-3" style="width:50px;height:50px;font-size:1.4rem;">
                        <i class="bi bi-fire"></i>
                    </div>
                    <h2 class="font-heading fs-3">Customer Sign In</h2>
                    <p class="text-muted small">Access your pickle orders, saved addresses, and wishlist.</p>
                </div>

                <?= render_flash() ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger rounded-3 small">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $e): ?>
                                <li><?= e($e) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="<?= BASE_URL ?>/auth/login.php" method="POST">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Email or Mobile Number</label>
                        <input type="text" name="login_input" id="loginInput" required class="form-control" 
                               value="<?= e($_POST['login_input'] ?? '') ?>" placeholder="rahul@example.com or 9876543210">
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <label class="form-label small fw-bold">Password</label>
                            <a href="#" onclick="alert('Please contact care@acharheritage.com to reset password'); return false;" class="small text-muted text-decoration-none">Forgot?</a>
                        </div>
                        <input type="password" name="password" id="passwordInput" required class="form-control" placeholder="••••••••">
                    </div>

                    <div class="mb-4 form-check">
                        <input type="checkbox" class="form-check-input" id="rememberMe" checked>
                        <label class="form-check-label small text-muted" for="rememberMe">Remember me on this browser</label>
                    </div>

                    <button type="submit" class="btn btn-brand-primary w-100 py-3 rounded-pill fw-bold shadow">
                        <i class="bi bi-box-arrow-in-right me-2"></i> Sign In to Account
                    </button>
                </form>

                <!-- Demo Credentials Quick Autofill -->
                <div class="mt-4 p-3 bg-light rounded-3 border text-center small">
                    <span class="text-muted d-block mb-1"><strong>Demo Customer Account:</strong></span>
                    <code>rahul@example.com</code> / <code>Customer@123</code>
                    <button type="button" class="btn btn-sm btn-link d-block mx-auto text-danger p-0 mt-1 fw-bold" 
                            onclick="document.getElementById('loginInput').value='rahul@example.com'; document.getElementById('passwordInput').value='Customer@123';">
                        Click to Auto-fill Demo
                    </button>
                </div>

                <div class="text-center mt-4 pt-3 border-top small">
                    Don't have an account yet? <a href="<?= BASE_URL ?>/auth/register.php" class="fw-bold text-danger">Create One Now</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
