<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shoppn</title>
    <link rel="stylesheet" href="/shoppn/css/style.css">
</head>
<body>

<header>
    <h1>Shoppn</h1>

    <nav>
        <a href="/shoppn/index.php">Home</a>

        <?php if (!is_logged_in()): ?>
            | <a href="/shoppn/views/register.php">Register</a>
            | <a href="/shoppn/views/login.php">Login</a>
        <?php else: ?>
            | <span>Welcome <?php echo htmlspecialchars($_SESSION['customer_name'] ?? 'Customer'); ?></span>
            | <a href="/shoppn/views/account/my_account.php">My Account</a>

            <?php if (is_admin()): ?>
                | <a href="/shoppn/views/admin/">Admin</a>
            <?php endif; ?>

            | <a href="/shoppn/logout.php">Logout</a>
        <?php endif; ?>
    </nav>

    <form action="/shoppn/views/search_results.php" method="GET">
        <input type="text" name="q" placeholder="Search products">
        <button type="submit">Search</button>
    </form>
</header>

<hr>
