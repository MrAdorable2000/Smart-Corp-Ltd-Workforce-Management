<?php /** @var array $data */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $title ?? 'Print' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Helvetica', sans-serif; padding: 20px; }
        @media print { body { padding: 0; } }
    </style>
</head>
<body>
<?= $content ?? '' ?>
</body>
</html>
