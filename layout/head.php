<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eTeam AI Tools</title>

    <link href="/assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/assets/css/app.css" rel="stylesheet">

    <link rel="icon" type="image/png" href="/assets/favicon.ico">

    <script src="/assets/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/jquery.min.js"></script>
    <script src="/assets/js/app.js"></script>
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