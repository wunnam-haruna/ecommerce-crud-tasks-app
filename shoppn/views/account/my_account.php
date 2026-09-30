<?php

require_once __DIR__ . '/../../core/core.php';

if (!is_logged_in()) {
    $_SESSION['error'] = 'Please login to access your account.';
    redirect('../login.php');
}

require __DIR__ . '/../layout/header.php';

?>

<main>
    <h2>My Account</h2>

    <p>
        Welcome,
        <?php echo htmlspecialchars(
            $_SESSION['customer_name'] ?? 'Customer'
        ); ?>.
    </p>

    <p>
        Email:
        <?php echo htmlspecialchars(
            $_SESSION['customer_email'] ?? ''
        ); ?>
    </p>

    <p>Your customer account is active.</p>
</main>

<?php require __DIR__ . '/../layout/footer.php'; ?>
