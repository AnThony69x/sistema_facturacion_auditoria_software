<?php
// auth/logout.php
require_once dirname(__DIR__) . '/includes/functions.php';
session_destroy();
header('Location: ' . BASE_URL . 'auth/login.php');
exit;
