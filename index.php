<?php
session_start();

if (!empty($_SESSION['user']['role'])) {
    $role = $_SESSION['user']['role'];
    $dashboard = __DIR__ . '/layouts/' . $role . '/dashboard.php';

    if (is_file($dashboard)) {
        header('Location: layouts/' . rawurlencode($role) . '/dashboard.php');
        exit;
    }
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Online Law Firm Management System for secure case and appointment management.">
    <title>OLFMS | Online Law Firm Management System</title>
    <link rel="stylesheet" href="includes/base.css">
    <link rel="stylesheet" href="../css/styles/style.css">
    <style>
        .home-hero {
            padding: 72px 20px 56px;
            background: linear-gradient(135deg, #eaf2ff 0%, #fff 58%, #fff8e6 100%);
        }

        .home-hero__inner {
            display: grid;
            grid-template-columns: 1.15fr .85fr;
            gap: 44px;
            align-items: center;
        }

        .home-hero h1 {
            font-size: clamp(2.2rem, 5vw, 4rem);
            margin: 12px 0 18px;
        }

        .home-hero p {
            max-width: 650px;
            color: var(--ink-soft);
            font-size: 1.1rem;
        }

        .home-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 28px;
        }

        .home-visual {
            background: var(--navy);
            border-radius: var(--radius);
            padding: 34px;
            color: var(--white);
            box-shadow: var(--shadow);
        }

        .home-visual h2 {
            color: var(--white);
            margin-top: 0;
        }

        .home-visual li {
            margin: 16px 0;
        }

        .home-section {
            padding: 54px 20px;
        }

        .home-section h2 {
            text-align: center;
            margin-top: 0;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .feature-grid .card h3 {
            margin-top: 0;
        }

        .home-footer {
            margin-top: auto;
        }

        @media (max-width: 720px) {
            .home-hero {
                padding-top: 42px;
            }

            .home-hero__inner,
            .feature-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <header class="site-header">
        <div class="site-header__inner">
            <a class="brand" href="index.php">OLFMS</a>
            <nav class="site-nav" aria-label="Main navigation">
                <a href="#features">Features</a>
                <a href="auth/login.php">Login</a>
                <a class="btn gold" href="auth/register.php">Create account</a>
            </nav>
        </div>
    </header>

    <main>
        <section class="home-hero">
            <div class="container home-hero__inner">
                <div>
                    <p class="muted">ONLINE LAW FIRM MANAGEMENT SYSTEM</p>
                    <h1>Keep every legal matter moving.</h1>
                    <p>Manage case requests, appointments, documents, and client communication from one secure workspace.</p>
                    <div class="home-actions">
                        <a class="btn gold" href="auth/register.php">Create client account</a>
                        <a class="btn btn-outline" href="auth/login.php">Sign in</a>
                    </div>
                </div>
                <aside class="home-visual" aria-label="OLFMS features">
                    <h2>One place for the work</h2>
                    <ul>
                        <li>Submit and track legal cases</li>
                        <li>Request appointments with your firm</li>
                        <li>Protect access with role-based accounts</li>
                    </ul>
                </aside>
            </div>
        </section>

        <section class="home-section" id="features">
            <div class="container">
                <h2>Built around your workflow</h2>
                <div class="feature-grid">
                    <article class="card">
                        <h3>Case management</h3>
                        <p>Send a case request and keep its status visible as your matter progresses.</p>
                        <a href="auth/login.php">Open case workspace</a>
                    </article>
                    <article class="card">
                        <h3>Appointments</h3>
                        <p>Request consultations and keep upcoming legal meetings organized.</p>
                        <a href="auth/login.php">Manage appointments</a>
                    </article>
                    <article class="card">
                        <h3>Secure access</h3>
                        <p>Clients and staff see the tools appropriate to their role and responsibilities.</p>
                        <a href="auth/register.php">Get started</a>
                    </article>
                </div>
            </div>
        </section>
    </main>

    <footer class="site-footer home-footer">
        <p>&copy; <?= date('Y') ?> OLFMS. Secure legal work management.</p>
    </footer>
</body>

</html>