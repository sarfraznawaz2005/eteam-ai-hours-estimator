<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eTeam AI Tools</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

    <link rel="icon" type="image/png" href="/assets/favicon.ico">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="/assets/js/app.js"></script>

    <style>
    body {
        margin-bottom: 50px;
    }

    a {
        text-decoration: none;
        color: #0d84cc;
    }

    pre,
    pre p,
    pre strong,
    pre ol,
    pre ul,
    pre li {
        line-height: 1rem;
    }

    pre p {
        margin: 0;
        padding: 0;
        line-height: 1.5rem;
    }

    pre {
        border-radius: 15px;
        border: 1px solid #aeb6b6;
        background: #b6bdbd radial-gradient(#eee, #bbbbbb);
        padding: 25px;
    }

    pre p,
    pre li {
        word-break: break-all !important;
        white-space: normal !important;
        width: 100%;
    }

    pre table {
        width: 100% !important;
    }

    pre table {
        --tw-border-spacing-x: 0;
        --tw-border-spacing-y: 0;
        border-collapse: separate;
        border-spacing: var(--tw-border-spacing-x) var(--tw-border-spacing-y);
        margin-bottom: 0.25rem;
        margin-top: 0.25rem;
        width: 100%;
    }

    pre th {
        background-color: #ddd;
        border-bottom-width: 1px;
        border-left-width: 1px;
        border-top-width: 1px;
        border-right-width: 1px;
        padding: 0.25rem 0.75rem;
    }

    pre td {
        border-bottom-width: 1px;
        border-left-width: 1px;
        border-top-width: 1px;
        border-right-width: 1px;
        padding: 0.25rem 0.75rem;
    }
    </style>
</head>

<body>

    <div class="container mt-2">

        <?php if ($_SERVER['SCRIPT_NAME'] !== '/index.php'): ?>
        <a href="/index.php" title="back to home">
            <i class="bi bi-house-door-fill" style="font-size: 2rem; color: #999; cursor: pointer;"
                onmouseover="this.style.color='#0d84cc'" onmouseout="this.style.color='#999'">
            </i>
        </a>
        <?php endif;?>