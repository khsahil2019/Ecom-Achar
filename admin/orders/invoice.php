<?php
/**
 * Achar Heritage - Official GST Tax Invoice & Printable Packing Slip
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

require_admin();
$pdo = db();

$orderId = (int)($_GET['id'] ?? 0);
if (!$orderId) {
    die("Invalid order ID.");
}

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    die("Order not found.");
}

// Fetch Items
$itemStmt = $pdo->prepare("
    SELECT oi.*, p.sku as orig_sku
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$itemStmt->execute([$orderId]);
$items = $itemStmt->fetchAll();

$gstin = get_setting('gst_number', '08AAAAA0000A1Z5');
$businessName = get_setting('business_name', 'Achar Heritage Artisanal Foods');
$businessAddress = get_setting('business_address', 'Heritage Haveli, Bapu Bazaar, Jaipur, Rajasthan - 302003');
$businessPhone = get_setting('contact_phone', '+91 98765 43210');
$businessEmail = get_setting('contact_email', 'support@achar.com');

// Calculate GST split (Pickles in India are 5% GST: 2.5% CGST + 2.5% SGST)
$totalAmount = (float)$order['total_amount'];
$taxableValue = round($totalAmount / 1.05, 2);
$totalGst = round($totalAmount - $taxableValue, 2);
$cgst = round($totalGst / 2, 2);
$sgst = round($totalGst - $cgst, 2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tax Invoice - <?= e($order['order_number']) ?> - Achar Heritage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #F3F4F6;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #1F2937;
            padding: 30px 0;
        }
        .invoice-card {
            background: #FFFFFF;
            max-width: 860px;
            margin: 0 auto;
            padding: 40px 50px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }
        .invoice-brand-title {
            font-family: Georgia, serif;
            font-weight: 800;
            color: #B91C1C;
            letter-spacing: -0.5px;
        }
        .table-invoice th {
            background-color: #F9FAFB;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-top: 1px solid #E5E7EB;
            border-bottom: 2px solid #E5E7EB;
        }
        .table-invoice td {
            font-size: 0.9rem;
            vertical-align: middle;
            border-bottom: 1px solid #F3F4F6;
        }
        @media print {
            body {
                background: #FFF;
                padding: 0;
            }
            .invoice-card {
                box-shadow: none;
                border: none;
                padding: 10px;
                max-width: 100%;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

<div class="container">
    
    <!-- Action Bar (Non-Printable) -->
    <div class="d-flex justify-content-between align-items-center mb-4 max-w-860 mx-auto no-print" style="max-width: 860px;">
        <a href="<?= BASE_URL ?>/admin/orders/view.php?id=<?= $orderId ?>" class="btn btn-outline-secondary rounded-pill btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Order
        </a>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary rounded-pill btn-sm px-4" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Print / Save PDF
            </button>
        </div>
    </div>

    <!-- Printable Invoice Sheet -->
    <div class="invoice-card">
        
        <!-- Header -->
        <div class="row align-items-center pb-4 mb-4 border-bottom">
            <div class="col-sm-7">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div style="width:36px; height:36px; background:#B91C1C; color:#FFF; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:1.2rem;">
                        <i class="bi bi-fire"></i>
                    </div>
                    <span class="fs-3 invoice-brand-title">Achar Heritage</span>
                </div>
                <div class="small text-muted leading-tight">
                    <strong><?= e($businessName) ?></strong><br>
                    <?= e($businessAddress) ?><br>
                    <strong>GSTIN:</strong> <?= e($gstin) ?> | <strong>FSSAI:</strong> 12224026000192<br>
                    Email: <?= e($businessEmail) ?> | Phone: <?= e($businessPhone) ?>
                </div>
            </div>
            <div class="col-sm-5 text-sm-end mt-3 mt-sm-0">
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1 text-uppercase fw-bold">Tax Invoice</span>
                <h4 class="mt-2 mb-1 fw-bold">INV-<?= e($order['order_number']) ?></h4>
                <div class="small text-muted"><strong>Invoice Date:</strong> <?= date('d M Y', strtotime($order['created_at'])) ?></div>
                <div class="small text-muted"><strong>Order Status:</strong> <?= e($order['order_status']) ?></div>
                <?php if ($order['tracking_number']): ?>
                    <div class="small text-muted"><strong>Tracking AWB:</strong> <?= e($order['tracking_number']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Bill To / Ship To -->
        <div class="row mb-4">
            <div class="col-6">
                <h6 class="text-uppercase fw-bold text-muted small mb-2">Billed & Shipped To:</h6>
                <div class="fw-bold fs-6"><?= e($order['customer_name']) ?></div>
                <div class="small text-muted">
                    <?= nl2br(e($order['shipping_address'])) ?><br>
                    <?= e($order['shipping_city']) ?>, <?= e($order['shipping_state']) ?> - <?= e($order['shipping_pincode']) ?><br>
                    <strong>Phone:</strong> <?= e($order['customer_mobile']) ?><br>
                    <strong>Email:</strong> <?= e($order['customer_email']) ?>
                </div>
            </div>
            <div class="col-6 text-end">
                <h6 class="text-uppercase fw-bold text-muted small mb-2">Order Information:</h6>
                <div class="small text-muted">
                    <strong>Order Number:</strong> <?= e($order['order_number']) ?><br>
                    <strong>Order Date:</strong> <?= format_date($order['created_at']) ?><br>
                    <strong>Payment Mode:</strong> <?= strtoupper(e($order['payment_method'])) ?> (<?= ucfirst(e($order['payment_status'])) ?>)<br>
                    <strong>Place of Supply:</strong> <?= e($order['shipping_state']) ?> (India)
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="table-responsive mb-4">
            <table class="table table-invoice mb-0">
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 45%;">Item Description</th>
                        <th style="width: 15%;">HSN Code</th>
                        <th style="width: 10%;" class="text-center">Qty</th>
                        <th style="width: 12%;" class="text-end">Rate</th>
                        <th style="width: 13%;" class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $idx = 1; foreach ($items as $item): ?>
                        <tr>
                            <td><?= $idx++ ?></td>
                            <td>
                                <div class="fw-semibold"><?= e($item['product_name']) ?></div>
                                <?php if ($item['variant_name']): ?>
                                    <div class="small text-muted">Packaging: <?= e($item['variant_name']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>20019000</td>
                            <td class="text-center"><?= (int)$item['quantity'] ?></td>
                            <td class="text-end"><?= format_price($item['unit_price']) ?></td>
                            <td class="text-end fw-semibold"><?= format_price($item['subtotal']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Totals & Taxes -->
        <div class="row justify-content-end mb-4">
            <div class="col-sm-6">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td class="text-muted">Item Subtotal:</td>
                        <td class="text-end fw-semibold"><?= format_price($order['subtotal']) ?></td>
                    </tr>
                    <?php if ($order['discount_amount'] > 0): ?>
                        <tr>
                            <td class="text-success">Discount (<?= e($order['coupon_code'] ?? 'Coupon') ?>):</td>
                            <td class="text-end text-success fw-semibold">-<?= format_price($order['discount_amount']) ?></td>
                        </tr>
                    <?php endif; ?>
                    <tr>
                        <td class="text-muted">Shipping & Logistics:</td>
                        <td class="text-end fw-semibold">
                            <?= $order['shipping_fee'] > 0 ? format_price($order['shipping_fee']) : '<span class="text-success">FREE</span>' ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted small">CGST (2.5%):</td>
                        <td class="text-end small"><?= format_price($cgst) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted small">SGST (2.5%):</td>
                        <td class="text-end small"><?= format_price($sgst) ?></td>
                    </tr>
                    <tr class="border-top">
                        <td class="fs-5 fw-bold text-dark pt-2">Grand Total:</td>
                        <td class="text-end fs-5 fw-bold text-danger pt-2"><?= format_price($order['total_amount']) ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Footer / Signatory -->
        <div class="pt-4 border-top">
            <div class="row align-items-end">
                <div class="col-sm-8">
                    <h6 class="text-uppercase fw-bold text-muted small mb-1">Declaration & Terms:</h6>
                    <p class="small text-muted mb-0">
                        1. Pickles are traditional food products manufactured under authentic sun-cured hygienic standards.<br>
                        2. Goods once sold are non-returnable except in case of transit breakage or seal damage reported within 48h.<br>
                        3. All disputes subject to Jaipur, Rajasthan jurisdiction.
                    </p>
                </div>
                <div class="col-sm-4 text-sm-end mt-4 mt-sm-0">
                    <div class="small fw-bold text-dark mb-4">For <?= e($businessName) ?></div>
                    <div class="border-top border-dark d-inline-block pt-1" style="width: 150px;">
                        <span class="small text-muted">Authorized Signatory</span>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    // Auto-trigger print if ?print=1 in URL
    if (window.location.search.includes('print=1')) {
        window.addEventListener('load', function() {
            window.print();
        });
    }
</script>

</body>
</html>
