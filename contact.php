<?php
/**
 * Contact Us & Customer Support
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = db();
$errors = [];

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verify_csrf()) {
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $mobile = sanitize($_POST['mobile'] ?? '');
        $subject = sanitize($_POST['subject'] ?? '');
        $message = sanitize($_POST['message'] ?? '');

        if (empty($name) || empty($email) || empty($message)) {
            $errors[] = 'Please provide your name, email, and inquiry message.';
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO contact_messages (name, email, mobile, subject, message, status)
                VALUES (?, ?, ?, ?, ?, 'new')
            ");
            $stmt->execute([$name, $email, $mobile, $subject ?: 'General Enquiry', $message]);
            set_flash('success', 'Thank you, ' . $name . '! Your message has been received. Our team will get back to you shortly.');
            header('Location: ' . BASE_URL . '/contact.php');
            exit;
        }
    } else {
        $errors[] = 'Session expired. Please try again.';
    }
}

$pageTitle = 'Contact Us & Culinary Support - Achar Heritage';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="py-4 bg-white border-bottom">
    <div class="container">
        <h1 class="font-heading display-6 m-0">Contact & Customer Care</h1>
        <p class="text-muted small m-0 mt-1">Have a query regarding bulk gifting, order dispatch, or recipe questions? We are always here to help.</p>
    </div>
</div>

<div class="container py-5">
    <?= render_flash() ?>

    <div class="row g-5">
        
        <!-- Left: Contact Details -->
        <div class="col-lg-5">
            <div class="card border-0 rounded-4 shadow-sm p-4 bg-white mb-4">
                <h4 class="font-heading mb-4">Get In Touch</h4>
                
                <div class="d-flex flex-column gap-4">
                    <div class="d-flex gap-3">
                        <div class="bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                            <i class="bi bi-geo-alt-fill fs-5"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1">Workshop Address</h6>
                            <p class="text-muted small m-0"><?= e(get_setting('business_address', DEFAULT_ADDRESS)) ?></p>
                        </div>
                    </div>

                    <div class="d-flex gap-3">
                        <div class="bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                            <i class="bi bi-telephone-fill fs-5"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1">Direct Helpline</h6>
                            <p class="text-muted small m-0"><?= e(get_setting('contact_phone', DEFAULT_PHONE)) ?></p>
                            <span class="text-muted small">(Mon - Sat: 9:30 AM to 6:30 PM)</span>
                        </div>
                    </div>

                    <div class="d-flex gap-3">
                        <div class="bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                            <i class="bi bi-envelope-fill fs-5"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1">Email Support</h6>
                            <p class="text-muted small m-0"><?= e(get_setting('contact_email', DEFAULT_EMAIL)) ?></p>
                        </div>
                    </div>

                    <div class="d-flex gap-3">
                        <div class="bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                            <i class="bi bi-whatsapp fs-5"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1">Instant WhatsApp Chat</h6>
                            <a href="https://wa.me/<?= e(get_setting('whatsapp_number', DEFAULT_WHATSAPP)) ?>" target="_blank" class="btn btn-sm btn-outline-success rounded-pill mt-1">
                                Chat With Us Now
                            </a>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-top small text-muted">
                    <strong>FSSAI Registration & GST:</strong> <?= e(get_setting('gst_number', '08AAAAA0000A1Z5')) ?>
                </div>
            </div>
        </div>

        <!-- Right: Inquiry Form -->
        <div class="col-lg-7">
            <div class="card border-0 rounded-4 shadow-sm p-4 p-md-5 bg-white">
                <h4 class="font-heading mb-2">Send Us a Message</h4>
                <p class="text-muted small mb-4">We usually respond within 4 business hours.</p>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger rounded-3 small">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $e): ?>
                                <li><?= e($e) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="<?= BASE_URL ?>/contact.php" method="POST">
                    <?= csrf_field() ?>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Your Name *</label>
                            <input type="text" name="name" required class="form-control" value="<?= is_logged_in() ? e(current_user()['name']) : '' ?>" placeholder="Full Name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Mobile Number</label>
                            <input type="tel" name="mobile" class="form-control" value="<?= is_logged_in() ? e(current_user()['mobile']) : '' ?>" placeholder="10-digit Phone">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Email Address *</label>
                            <input type="email" name="email" required class="form-control" value="<?= is_logged_in() ? e(current_user()['email']) : '' ?>" placeholder="name@example.com">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Subject</label>
                            <input type="text" name="subject" class="form-control" placeholder="e.g. Bulk order inquiry / Shipping delay query">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Your Message *</label>
                            <textarea name="message" rows="5" required class="form-control" placeholder="How can we assist you?"></textarea>
                        </div>
                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-brand-primary btn-lg rounded-pill px-5 shadow">
                                <i class="bi bi-send-fill me-2"></i> Send Message
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
