<?php
/**
 * Customer Logout
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

unset($_SESSION['customer_id']);
unset($_SESSION['customer_logged_in']);
set_flash('info', 'You have been securely logged out. Come back soon for fresh pickles!');
header('Location: ' . BASE_URL . '/index.php');
exit;
