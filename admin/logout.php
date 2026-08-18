<?php
/**
 * RESTAURANT PREMIUM — Logout
 * Archivo: admin/logout.php
 */
session_start();
session_destroy();
header('Location: ' . (defined('APP_URL') ? APP_URL : '..') . '/admin/login.php');
exit;
