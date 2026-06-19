<?php
$pageTitle = isset($page_title) ? $page_title . ' | Owebku' : 'Owebku';
$bodyClass = isset($body_class) ? $body_class : '';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token-name" content="_csrf_token">
    <meta name="csrf-token-hash" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo e($pageTitle); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['IBM Plex Sans', 'Helvetica Neue', 'Arial', 'sans-serif']
                    }
                }
            }
        };
    </script>
    <link rel="stylesheet" href="<?php echo asset_url('css/app.css'); ?>">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="<?php echo e($bodyClass); ?> bg-white text-[#161616] font-sans">
