<?php

declare(strict_types=1);

$pageTitle = $pageTitle ?? 'PHP OOP Demo';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f5f7fb; color: #1f2937; }
        header, footer { background: #111827; color: #fff; padding: 16px 24px; }
        nav a { color: #fff; margin-right: 14px; text-decoration: none; font-weight: 600; }
        nav a.active { text-decoration: underline; }
        main { max-width: 960px; margin: 24px auto; background: #fff; border-radius: 8px; padding: 24px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { text-align: left; border-bottom: 1px solid #e5e7eb; padding: 10px 8px; }
        th { background: #f3f4f6; }
        code { background: #f3f4f6; padding: 2px 5px; border-radius: 4px; }
    </style>
</head>
<body>
<header>
    <h1 style="margin: 0 0 10px;">SEMS PHP Tasks Demo</h1>
    <?php require __DIR__ . '/nav.php'; ?>
</header>
<main>
