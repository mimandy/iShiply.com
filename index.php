<?php
session_start(); // Start session to handle user session
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>iShiply.com</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body, h1, h2, p, a {
            margin: 0;
            padding: 0;
            font-family: 'Roboto', sans-serif;
        }

        body {
            background-color: #f4f7fa;
            color: #333;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        header {
            background-color: #0047ab;
            color: white;
            padding: 10px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 90%;
            margin: 0 auto;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 3;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: white;
            text-decoration: none;
        }

        .navbar {
            display: flex;
            align-items: center;
        }

        .navbar ul {
            list-style: none;
            display: flex;
            gap: 20px;
        }

        .navbar ul li a {
            color: white;
            text-decoration: none;
            font-size: 1rem;
            transition: color 0.3s;
        }

        .navbar ul li a:hover {
            color: #ffdd57;
        }

        .hero {
            background-image: url('images/hero-image.jpg'); /* Adjust the path as needed */
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            color: white;
            text-align: center;
            height: 100vh; /* Set height to 100% of the viewport height */
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            margin-top: 0; /* Remove margin to ensure it touches the top */
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5); /* Dark overlay */
            z-index: 1;
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero h1 {
            font-size: 3rem;
            font-weight: bold;
        }

        .hero p {
            font-size: 1.5rem;
            margin-top: 10px;
        }

    </style>
</head>
<body>

<!-- Header (Navbar) -->
<header>
    <a href="index.php" class="logo">iShiply.com</a>
    <nav class="navbar">
        <ul>
            <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
                <li><a href="dashboard.php">Dashboard</a></li>
            <?php else: ?>
                <li><a href="register.php">Register</a></li>
                <li><a href="login.php">Login</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>

<!-- Hero Section -->
<div class="hero">
    <div class="hero-content">
        <h1>Welcome to iShiply.com</h1>
        <p>Your Trusted Drone Delivery Network</p>
    </div>
</div>

</body>
</html>
