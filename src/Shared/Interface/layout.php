<?php

function renderLayout(string $title, callable $content, string $baseUrl = '/ERSPC'): void
{
    $pageTitle = $title;
    require __DIR__ . '/partials/header.php';
    require __DIR__ . '/partials/navbar.php';
    ?>
    <div class="d-flex min-vh-100">
        <?php require __DIR__ . '/partials/menu.php'; ?>
        <main id="app-content" class="flex-grow-1 container-fluid py-4">
            <h1 class="h4 mb-4 text-dark fw-semibold"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
            <?php $content(); ?>
        </main>
    </div>
    <?php
    require __DIR__ . '/partials/loading.php';
    require __DIR__ . '/partials/footer.php';
}