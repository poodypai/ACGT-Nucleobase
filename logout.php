<?php
require_once __DIR__ . '/includes/functions.php';

$_SESSION = [];
session_destroy();

session_start();
setFlash('success', 'You have been logged out.');
header('Location: index.php');
exit;
