<?php require_once __DIR__ . '/auth.php'; ?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= e($title ?? APP_NAME) ?></title>
    <link rel="stylesheet" href="/olfms/css/app.css">
</head>

<body>
    <header><a class="brand" href="/olfms/">OLFMS</a><?php if (user()): ?><nav><a href="/olfms/layouts/<?= e(user()['role']) ?>/dashboard.php">Dashboard</a><a href="/olfms/actions/logout.php">Logout</a></nav><?php endif; ?></header>
    <main class="container">