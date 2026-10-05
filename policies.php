<?php
/**
 * Customer Policies (Shipping, Returns, Privacy, Terms)
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$policyKey = $_GET['p'] ?? 'shipping';

$policyData = [
    'shipping' => [
        'title' => 'Shipping & Packaging Policy',
        'content' => '
            <h5>Pan-India Delivery</h5>
            <p>We dispatch all orders within 24 to 48 hours of confirmation. Deliveries across major metropolitan cities take 2-4 business days, while tier-2 and tier-3 towns take 4-6 business days.</p>
            
            <h5>Leak-Proof Glass Packaging Guarantee</h5>
            <p>Every glass jar is individually vacuum-sealed with a tamper-evident golden lid. The jar is subsequently nested in high-density honeycomb bubble cushions and a 5-ply corrugated carton box to ensure zero breakage or oil seepage during transit.</p>
            
            <h5>Shipping Charges</h5>
            <p>Standard delivery charge is ₹49 on orders below ₹499. All orders of ₹499 and above qualify for <strong>FREE Pan-India Delivery</strong>.</p>
        '
    ],
    'returns' => [
        'title' => 'Return, Replacement & Refund Policy',
        'content' => '
            <h5>100% Quality & Damage Guarantee</h5>
            <p>Because our products are consumable culinary perishables, we cannot accept returns once the inner bottle seal has been opened for hygiene safety.</p>
            
            <h5>Transit Damage or Spoilage</h5>
            <p>If your package arrives broken, leaked, or damaged by courier transit, simply click a photograph and send it to our WhatsApp helpline (+' . e(get_setting('whatsapp_number', DEFAULT_WHATSAPP)) . ') or email care@acharheritage.com within 48 hours of delivery. We will dispatch an immediate free replacement or issue a full refund to your original payment method.</p>
        '
    ],
    'privacy' => [
        'title' => 'Privacy & Data Protection Policy',
        'content' => '
            <h5>Your Data is Sacred</h5>
            <p>We never sell, rent, or trade your personal email, phone number, or delivery addresses with third-party advertising brokers. Your information is used strictly to fulfill orders, deliver SMS tracking notifications, and provide customer support.</p>
            
            <h5>Payment Security</h5>
            <p>We do not store your credit card, debit card, or UPI PIN numbers on our servers. All transactions are securely routed through PCI-DSS Level 1 compliant gateway partners with 256-bit encryption.</p>
        '
    ],
    'terms' => [
        'title' => 'Terms & Conditions of Service',
        'content' => '
            <h5>Artisanal Product Variations</h5>
            <p>Because our pickles are completely handcrafted in seasonal batches using natural sunlight and organic spices without artificial preservatives or stabilizers, slight variations in oil depth, color shade, or tanginess from batch to batch are natural attributes of authentic handmade food.</p>
            
            <h5>Shelf Life & Instructions</h5>
            <p>Please strictly observe the storage guidelines stated on the label (always using a dry spoon and keeping oil covering the pickle) to preserve freshness throughout the product’s shelf life.</p>
        '
    ]
];

$currentPolicy = $policyData[$policyKey] ?? $policyData['shipping'];

$pageTitle = $currentPolicy['title'] . ' - Achar Heritage';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="py-4 bg-white border-bottom">
    <div class="container">
        <h1 class="font-heading display-6 m-0"><?= e($currentPolicy['title']) ?></h1>
        <p class="text-muted small m-0 mt-1">Transparency and trust in every aspect of our culinary service.</p>
    </div>
</div>

<div class="container py-5">
    <div class="row g-4">
        <div class="col-lg-3">
            <div class="card border-0 rounded-4 shadow-sm p-3 bg-white">
                <div class="nav flex-column gap-1">
                    <a class="nav-link py-2 px-3 rounded-3 <?= ($policyKey === 'shipping') ? 'bg-success text-white fw-bold' : 'text-dark' ?>" href="<?= BASE_URL ?>/policies.php?p=shipping">Shipping Policy</a>
                    <a class="nav-link py-2 px-3 rounded-3 <?= ($policyKey === 'returns') ? 'bg-success text-white fw-bold' : 'text-dark' ?>" href="<?= BASE_URL ?>/policies.php?p=returns">Return & Refund</a>
                    <a class="nav-link py-2 px-3 rounded-3 <?= ($policyKey === 'privacy') ? 'bg-success text-white fw-bold' : 'text-dark' ?>" href="<?= BASE_URL ?>/policies.php?p=privacy">Privacy Policy</a>
                    <a class="nav-link py-2 px-3 rounded-3 <?= ($policyKey === 'terms') ? 'bg-success text-white fw-bold' : 'text-dark' ?>" href="<?= BASE_URL ?>/policies.php?p=terms">Terms of Service</a>
                </div>
            </div>
        </div>
        <div class="col-lg-9">
            <div class="card border-0 rounded-4 shadow-sm p-4 p-md-5 bg-white text-secondary" style="line-height: 1.8;">
                <?= $currentPolicy['content'] ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
