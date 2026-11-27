<?php

function renderLayout(string $title, callable $content, string $baseUrl = '/ERPSC'): void
{
    $pageTitle = $title;
    require __DIR__ . '/partials/header.php';
    require __DIR__ . '/partials/navbar.php';
    ?>
    <div class="d-flex flex-grow-1">
        <?php require __DIR__ . '/partials/menu.php'; ?>
        <main id="app-content" class="flex-grow-1 container-fluid p-3 p-md-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 pb-2 border-bottom">
                <div>
                    <h1 class="h4 mb-0 fw-bold text-dark"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
                </div>
            </div>
            <?php $content(); ?>
        </main>
    </div>
    <?php
    require __DIR__ . '/partials/loading.php';
    require __DIR__ . '/partials/footer.php';
}
