<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';

function user(): ?array
{
	return current_user();
}

function csrf(): string
{
	return csrf_token();
}

function check_csrf(): void
{
	$token = $_POST['csrf'] ?? $_POST['csrf_token'] ?? '';
	if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
		exit('Invalid request');
	}
}
