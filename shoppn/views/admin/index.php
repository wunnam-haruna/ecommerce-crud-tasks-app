<?php

require_once __DIR__ . '/../../core/core.php';

require_admin();

require __DIR__ . '/../layout/header.php';
?>

<main>
    <section class="auth-card">
        <h2>Admin Dashboard</h2>
        <p>Manage your Shoppn store below.</p>

        <p>
            <a href="<?php echo htmlspecialchars(
                app_url('views/admin/brand.php'),
                ENT_QUOTES,
                'UTF-8'
            ); ?>">
                Manage Brands
            </a>
        </p>

        <p>
            <a href="<?php echo htmlspecialchars(
                app_url('views/admin/category.php'),
                ENT_QUOTES,
                'UTF-8'
            ); ?>">
                Manage Categories
            </a>
        </p>
    </section>
</main>

<?php require __DIR__ . '/../layout/footer.php'; ?>
