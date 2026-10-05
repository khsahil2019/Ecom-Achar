<?php
/**
 * Master Admin Header & Top Bar
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

require_admin();
$admin = current_admin();
$adminTitle = $adminTitle ?? 'Admin Dashboard - Achar Heritage';

// Check unread contact messages
try {
    $unreadMsgStmt = db()->query("SELECT COUNT(*) as cnt FROM contact_messages WHERE status = 'new'");
    $unreadMessagesCount = (int)$unreadMsgStmt->fetch()['cnt'];
} catch (Exception $e) {
    $unreadMessagesCount = 0;
}

// Check pending orders count
try {
    $pendingOrdStmt = db()->query("SELECT COUNT(*) as cnt FROM orders WHERE order_status = 'Pending' OR order_status = 'Confirmed'");
    $pendingOrdersCount = (int)$pendingOrdStmt->fetch()['cnt'];
} catch (Exception $e) {
    $pendingOrdersCount = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($adminTitle) ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom Style & Admin Design System -->
    <link href="<?= BASE_URL ?>/assets/css/style.css?v=<?= filemtime(__DIR__ . '/../../assets/css/style.css') ?>" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/admin.css?v=<?= filemtime(__DIR__ . '/../../assets/css/admin.css') ?>" rel="stylesheet">
</head>
<body class="admin-body">

<div class="admin-wrapper">
    <!-- Sidebar Included Next -->
