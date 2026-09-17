<?php require_once __DIR__ . '/auth.php'; ?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= e($title ?? $pageTitle ?? APP_NAME) ?></title>
    <link rel="stylesheet" href="/olfms/css/app.css">
    <?php if (!empty($pageCss)): ?><link rel="stylesheet" href="<?= e($pageCss) ?>"><?php endif; ?>
</head>

<body>
    <header class="site-header">
        <div class="site-header__inner">
            <a class="brand" href="/olfms/">OLFMS</a>
            <?php if (user()): ?>
                <nav class="site-nav" aria-label="Main navigation">
                    <a href="/olfms/layouts/<?= e(user()['role']) ?>/dashboard.php">Dashboard</a>
                    <a href="/olfms/actions/logout.php">Logout</a>
                </nav>
            <?php endif; ?>
        </div>
    </header>
    <main class="container site-main">