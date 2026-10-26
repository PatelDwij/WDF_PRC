<?php
/**
 * StudentHub Portal - Shared Header Component
 * Author: Dwij Patel (Student ID: 25CE077)
 * University: CHARUSAT - B.Tech Computer Engineering
 */

if (!isset($pageTitle)) {
    $pageTitle = 'StudentHub Portal';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> | StudentHub</title>
    <link rel="stylesheet" href="Style.css">
    <?php if (isset($extraCss)): ?>
        <?= $extraCss ?>
    <?php endif; ?>
</head>
<body>
    <header>
        <div class="header-content">
            <h1>StudentHub Portal</h1>
            <p><i><b>Your Centralized Academic & Campus Management System</b></i></p>
        </div>
        <div class="header-controls">
            <button id="theme-toggle" type="button" aria-label="Toggle dark mode">🌙 Dark Theme</button>
        </div>
        <hr>
    </header>

    <?php require_once __DIR__ . '/nav.php'; ?>
