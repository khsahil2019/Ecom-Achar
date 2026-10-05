<?php
/**
 * Admin Business & Website Settings
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();
$pdo = db();

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    if (verify_csrf()) {
        $fields = [
            'site_name', 'site_tagline', 'contact_email', 'contact_phone', 'whatsapp_number',
            'business_address', 'gst_number', 'delivery_fee', 'free_delivery_threshold',
            'cod_enabled', 'online_payment_enabled', 'razorpay_key_id', 'razorpay_key_secret',
            'facebook_url', 'instagram_url', 'youtube_url', 'footer_about'
        ];

        $uStmt = $pdo->prepare("
            INSERT INTO settings (setting_key, setting_value) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");

        // Use standard update for SQLite / MySQL compatibility
        foreach ($fields as $key) {
            $val = sanitize($_POST[$key] ?? '');
            
            // Check if exists
            $check = $pdo->prepare("SELECT id FROM settings WHERE setting_key = ?");
            $check->execute([$key]);
            if ($check->fetch()) {
                $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?")->execute([$val, $key]);
            } else {
                $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)")->execute([$key, $val]);
            }
        }

        set_flash('success', 'Store settings updated successfully.');
        header('Location: ' . BASE_URL . '/admin/settings.php');
        exit;
    }
}

$adminTitle = 'Store Settings - Achar Heritage';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="font-heading m-0 fs-3">Business & Store Settings</h2>
        <p class="text-muted small m-0 mt-1">Configure your brand identity, delivery rules, payment gateway, and contact information.</p>
    </div>
</div>

<form action="<?= BASE_URL ?>/admin/settings.php" method="POST">
    <?= csrf_field() ?>
    <input type="hidden" name="save_settings" value="1">

    <div class="row g-4">
        
        <!-- Left: Brand & Commerce -->
        <div class="col-lg-6">
            
            <!-- Brand Info -->
            <div class="card border-0 rounded-4 shadow-sm p-4 bg-white mb-4">
                <h5 class="fw-bold font-heading mb-3"><i class="bi bi-shop text-success me-2"></i> Brand Profile</h5>
                
                <div class="mb-3">
                    <label class="form-label small fw-bold">Business / Store Name</label>
                    <input type="text" name="site_name" class="form-control" value="<?= e(get_setting('site_name', 'Achar Heritage')) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Brand Tagline</label>
                    <input type="text" name="site_tagline" class="form-control" value="<?= e(get_setting('site_tagline', 'Authentic Handcrafted Indian Pickles')) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">GSTIN Number / FSSAI Registration</label>
                    <input type="text" name="gst_number" class="form-control" value="<?= e(get_setting('gst_number', '08AAAAA0000A1Z5')) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Footer Brand Narrative</label>
                    <textarea name="footer_about" rows="3" class="form-control"><?= e(get_setting('footer_about', '')) ?></textarea>
                </div>
            </div>

            <!-- Delivery & Shipping Settings -->
            <div class="card border-0 rounded-4 shadow-sm p-4 bg-white mb-4">
                <h5 class="fw-bold font-heading mb-3"><i class="bi bi-truck text-warning me-2"></i> Delivery & Charges</h5>
                
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-bold">Standard Delivery Fee (₹)</label>
                        <input type="number" step="0.01" name="delivery_fee" class="form-control" value="<?= e(get_setting('delivery_fee', '49')) ?>">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold">Free Delivery Above (₹)</label>
                        <input type="number" step="0.01" name="free_delivery_threshold" class="form-control" value="<?= e(get_setting('free_delivery_threshold', '499')) ?>">
                    </div>
                </div>

                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" name="cod_enabled" value="1" id="codSwitch" <?= get_setting('cod_enabled', '1') == '1' ? 'checked' : '' ?>>
                    <label class="form-check-label small fw-semibold" for="codSwitch">Enable Cash on Delivery (COD)</label>
                </div>

                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="online_payment_enabled" value="1" id="onlineSwitch" <?= get_setting('online_payment_enabled', '1') == '1' ? 'checked' : '' ?>>
                    <label class="form-check-label small fw-semibold" for="onlineSwitch">Enable Online Payment Gateway (UPI / Cards)</label>
                </div>
            </div>

        </div>

        <!-- Right: Contact & Payment Gateway -->
        <div class="col-lg-6">
            
            <!-- Contact Details -->
            <div class="card border-0 rounded-4 shadow-sm p-4 bg-white mb-4">
                <h5 class="fw-bold font-heading mb-3"><i class="bi bi-telephone text-primary me-2"></i> Contact & Support</h5>
                
                <div class="mb-3">
                    <label class="form-label small fw-bold">Customer Helpline Phone</label>
                    <input type="text" name="contact_phone" class="form-control" value="<?= e(get_setting('contact_phone', DEFAULT_PHONE)) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Customer Support Email</label>
                    <input type="email" name="contact_email" class="form-control" value="<?= e(get_setting('contact_email', DEFAULT_EMAIL)) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">WhatsApp Helpline Number (Country Code + 10 Digits)</label>
                    <input type="text" name="whatsapp_number" class="form-control" value="<?= e(get_setting('whatsapp_number', DEFAULT_WHATSAPP)) ?>" placeholder="919876543210">
                    <small class="text-muted">Used for the floating WhatsApp button and instant chats.</small>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Culinary Workshop Physical Address</label>
                    <textarea name="business_address" rows="2" class="form-control"><?= e(get_setting('business_address', DEFAULT_ADDRESS)) ?></textarea>
                </div>
            </div>

            <!-- Payment Gateway Integration -->
            <div class="card border-0 rounded-4 shadow-sm p-4 bg-white mb-4">
                <h5 class="fw-bold font-heading mb-3"><i class="bi bi-credit-card-2-front text-danger me-2"></i> Payment Gateway (Razorpay/PayU)</h5>
                
                <div class="mb-3">
                    <label class="form-label small fw-bold">Razorpay Key ID</label>
                    <input type="text" name="razorpay_key_id" class="form-control" value="<?= e(get_setting('razorpay_key_id', 'rzp_test_placeholderKey123')) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Razorpay Key Secret</label>
                    <input type="password" name="razorpay_key_secret" class="form-control" value="<?= e(get_setting('razorpay_key_secret', 'rzp_test_placeholderSecret456')) ?>">
                </div>
            </div>

            <!-- Social Media -->
            <div class="card border-0 rounded-4 shadow-sm p-4 bg-white mb-4">
                <h5 class="fw-bold font-heading mb-3"><i class="bi bi-share text-info me-2"></i> Social Media Profiles</h5>
                
                <div class="mb-2">
                    <label class="form-label small">Instagram Profile URL</label>
                    <input type="url" name="instagram_url" class="form-control form-control-sm" value="<?= e(get_setting('instagram_url', '')) ?>">
                </div>

                <div class="mb-2">
                    <label class="form-label small">Facebook Page URL</label>
                    <input type="url" name="facebook_url" class="form-control form-control-sm" value="<?= e(get_setting('facebook_url', '')) ?>">
                </div>

                <div class="mb-2">
                    <label class="form-label small">YouTube Channel URL</label>
                    <input type="url" name="youtube_url" class="form-control form-control-sm" value="<?= e(get_setting('youtube_url', '')) ?>">
                </div>
            </div>

            <button type="submit" class="btn btn-brand-primary w-100 py-3 rounded-pill fw-bold shadow">
                <i class="bi bi-save me-2"></i> Save All Settings
            </button>

        </div>

    </div>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
