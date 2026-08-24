<?php /** @var array $data */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .error-box { text-align: center; color: #fff; }
        .error-code { font-size: 120px; font-weight: 800; line-height: 1; margin: 0; opacity: 0.3; }
        .error-message { font-size: 24px; margin-top: -40px; }
        .error-desc { opacity: 0.8; margin-bottom: 30px; }
        .btn-back { background: #fff; color: #4F46E5; padding: 12px 30px; border-radius: 50px; text-decoration: none; font-weight: 600; }
        .btn-back:hover { background: #f8f9fa; color: #4F46E5; }
    </style>
</head>
<body>
    <div class="error-box">
        <div class="error-code">404</div>
        <div class="error-message">Page Not Found</div>
        <p class="error-desc">The page you're looking for doesn't exist or has been moved.</p>
        <a href="<?= BASE_URL ?>/dashboard" class="btn-back"><i class="bi bi-house-door"></i> Back to Dashboard</a>
    </div>
</body>
</html>
