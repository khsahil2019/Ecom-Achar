<?php
/**
 * Customer Saved Addresses
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

$pdo = db();
$cid = customer_id();
$errors = [];

// Handle New Address Submission
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_address'])) {
    if (verify_csrf()) {
        $fullName = sanitize($_POST['full_name'] ?? '');
        $mobile = sanitize($_POST['mobile'] ?? '');
        $houseFlat = sanitize($_POST['house_flat'] ?? '');
        $street = sanitize($_POST['street'] ?? '');
        $landmark = sanitize($_POST['landmark'] ?? '');
        $city = sanitize($_POST['city'] ?? '');
        $state = sanitize($_POST['state'] ?? '');
        $pincode = sanitize($_POST['pincode'] ?? '');
        $type = in_array($_POST['type'] ?? '', ['home', 'work', 'other']) ? $_POST['type'] : 'home';
        $isDefault = !empty($_POST['is_default']) ? 1 : 0;

        if (empty($fullName) || empty($mobile) || empty($houseFlat) || empty($street) || empty($city) || empty($state) || empty($pincode)) {
            $errors[] = 'Please fill out all required address fields.';
        } else {
            if ($isDefault) {
                $pdo->prepare("UPDATE addresses SET is_default = 0 WHERE customer_id = ?")->execute([$cid]);
            }
            $ins = $pdo->prepare("
                INSERT INTO addresses (customer_id, full_name, mobile, house_flat, street, landmark, city, state, pincode, type, is_default)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $ins->execute([$cid, $fullName, $mobile, $houseFlat, $street, $landmark, $city, $state, $pincode, $type, $isDefault]);
            set_flash('success', 'Address added successfully.');
            header('Location: ' . BASE_URL . '/account/addresses.php');
            exit;
        }
    } else {
        $errors[] = 'Security token expired. Please try again.';
    }
}

// Handle Delete Address
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM addresses WHERE id = ? AND customer_id = ?")->execute([$delId, $cid]);
    set_flash('info', 'Address deleted.');
    header('Location: ' . BASE_URL . '/account/addresses.php');
    exit;
}

// Fetch Addresses
$stmt = $pdo->prepare("SELECT * FROM addresses WHERE customer_id = ? ORDER BY is_default DESC, id DESC");
$stmt->execute([$cid]);
$addresses = $stmt->fetchAll();

$pageTitle = 'Saved Delivery Addresses - Achar Heritage';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="py-4 bg-white border-bottom">
    <div class="container">
        <h1 class="font-heading display-6 m-0">Delivery Addresses</h1>
        <p class="text-muted small m-0 mt-1">Manage multiple delivery destinations for family and gifting.</p>
    </div>
</div>

<div class="container py-5">
    <?= render_flash() ?>

    <div class="row g-4">
        <div class="col-lg-4">
            <?php require_once __DIR__ . '/account_nav.php'; ?>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 rounded-4 shadow-sm p-4 bg-white mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold font-heading m-0">Saved Addresses (<?= count($addresses) ?>)</h5>
                    <button class="btn btn-brand-primary btn-sm rounded-pill" type="button" data-bs-toggle="collapse" data-bs-target="#newAddressForm">
                        <i class="bi bi-plus-lg me-1"></i> Add New Address
                    </button>
                </div>

                <!-- Add New Address Collapsible Form -->
                <div class="collapse mb-4 <?= !empty($errors) ? 'show' : '' ?>" id="newAddressForm">
                    <div class="card card-body bg-light border-0 rounded-4 p-4">
                        <h6 class="fw-bold mb-3">Add Shipping Destination</h6>
                        
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger small mb-3">
                                <ul class="mb-0 ps-3">
                                    <?php foreach ($errors as $e): ?>
                                        <li><?= e($e) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form action="<?= BASE_URL ?>/account/addresses.php" method="POST">
                            <?= csrf_field() ?>
                            <input type="hidden" name="add_address" value="1">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Full Name *</label>
                                    <input type="text" name="full_name" required class="form-control" placeholder="Recipient's Name">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Mobile Number *</label>
                                    <input type="tel" name="mobile" required class="form-control" placeholder="10-digit Phone">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">House / Flat / Block *</label>
                                    <input type="text" name="house_flat" required class="form-control" placeholder="Flat No / Apartment">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Street / Locality *</label>
                                    <input type="text" name="street" required class="form-control" placeholder="Street Name, Area">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Landmark</label>
                                    <input type="text" name="landmark" class="form-control" placeholder="Nearby landmark">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Pincode *</label>
                                    <input type="text" name="pincode" required class="form-control" placeholder="6-digit Pincode">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">City *</label>
                                    <input type="text" name="city" required class="form-control" placeholder="City">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">State *</label>
                                    <input type="text" name="state" required class="form-control" placeholder="State">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Address Type</label>
                                    <select name="type" class="form-select">
                                        <option value="home">Home (All day delivery)</option>
                                        <option value="work">Work / Office (9 AM - 6 PM)</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-6 d-flex align-items-center">
                                    <div class="form-check mt-3">
                                        <input class="form-check-input" type="checkbox" name="is_default" value="1" id="isDefaultCheck">
                                        <label class="form-check-label small" for="isDefaultCheck">Make default address</label>
                                    </div>
                                </div>
                                <div class="col-12 mt-3">
                                    <button type="submit" class="btn btn-brand-primary px-4 rounded-pill">Save Address</button>
                                    <button type="button" class="btn btn-outline-secondary px-3 rounded-pill ms-2" data-bs-toggle="collapse" data-bs-target="#newAddressForm">Cancel</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Existing Addresses Grid -->
                <?php if (!empty($addresses)): ?>
                    <div class="row g-3">
                        <?php foreach ($addresses as $addr): ?>
                            <div class="col-md-6">
                                <div class="border rounded-4 p-3 bg-light position-relative h-100 d-flex flex-column justify-content-between">
                                    <div>
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <span class="fw-bold fs-6"><?= e($addr['full_name']) ?></span>
                                            <div>
                                                <?php if ($addr['is_default']): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success me-1">Default</span>
                                                <?php endif; ?>
                                                <span class="badge bg-secondary-subtle text-dark"><?= strtoupper(e($addr['type'])) ?></span>
                                            </div>
                                        </div>
                                        <div class="small text-muted mb-2">
                                            <?= e($addr['house_flat']) ?>, <?= e($addr['street']) ?><br>
                                            <?php if ($addr['landmark']): ?>Near <?= e($addr['landmark']) ?>, <?php endif; ?>
                                            <?= e($addr['city']) ?>, <?= e($addr['state']) ?> - <strong><?= e($addr['pincode']) ?></strong>
                                        </div>
                                        <div class="small text-dark fw-semibold">
                                            <i class="bi bi-phone me-1 text-success"></i> <?= e($addr['mobile']) ?>
                                        </div>
                                    </div>
                                    <div class="pt-3 border-top mt-3 text-end">
                                        <a href="<?= BASE_URL ?>/account/addresses.php?delete=<?= $addr['id'] ?>" 
                                           class="btn btn-sm btn-outline-danger rounded-pill"
                                           onclick="return confirm('Remove this address?');">
                                            <i class="bi bi-trash"></i> Delete
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-geo-alt fs-2 d-block mb-2"></i>
                        No saved addresses yet.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
