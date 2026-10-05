<?php
/**
 * Admin Logout Handler
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

unset($_SESSION['admin_id']);
unset($_SESSION['admin_logged_in']);
unset($_SESSION['admin_name']);
unset($_SESSION['admin_email']);
unset($_SESSION['admin_role']);

set_flash('info', 'Admin logged out successfully.');
header('Location: ' . BASE_URL . '/admin/login.php');
exit;
