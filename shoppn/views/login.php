<?php

require_once __DIR__ . '/../core/core.php';

if (is_logged_in()) {
    redirect(app_url('index.php'));
}

$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);

require __DIR__ . '/layout/header.php';

?>

<main>

    <h2>Customer Login</h2>

    <?php if ($error !== ''): ?>
        <p style="color: red;">
            <?php echo htmlspecialchars($error); ?>
        </p>
    <?php endif; ?>

    <form
        action="<?php echo htmlspecialchars(app_url('actions/login_action.php')); ?>"
        method="POST"
    >

        <div>
            <label for="customer_email">Email</label><br>

            <input
                type="email"
                id="customer_email"
                name="customer_email"
                required
            >
        </div>

        <br>

        <div>
            <label for="customer_pass">Password</label><br>

            <input
                type="password"
                id="customer_pass"
                name="customer_pass"
                required
            >
        </div>

        <br>

        <button type="submit">
            Login
        </button>

    </form>

    <p>
        Don't have an account?
        <a href="<?php echo htmlspecialchars(app_url('views/register.php')); ?>">
            Register
        </a>
    </p>

</main>

<?php require __DIR__ . '/layout/footer.php'; ?>
