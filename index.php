<?php
require_once __DIR__ . '/../app/auth.php';
$user = current_user();
if ($user) header('Location: nuevo_curso.php'); else header('Location: login.php');
exit;
