<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit();
}

include('db_connect.php'); // Include the database connection file

// Initialize the cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle updates to the cart
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_cart'])) {
        foreach ($_POST['quantities'] as $product_id => $quantity) {
            if ($quantity == 0) {
                unset($_SESSION['cart'][$product_id]);
            } else {
                $_SESSION['cart'][$product_id] = $quantity;
            }
        }
    } elseif (isset($_POST['empty_cart'])) {
        // Handle emptying the cart
        $_SESSION['cart'] = [];
    } elseif (isset($_POST['checkout'])) {
        // Handle checkout
        $user_id = $_SESSION['user_id'];
        $conn->begin_transaction();

        try {
            foreach ($_SESSION['cart'] as $product_id => $quantity) {
                // Insert purchase record
                $stmt = $conn->prepare("INSERT INTO purchases (user_id, product_id, quantity) VALUES (?, ?, ?)");
                $stmt->bind_param("iii", $user_id, $product_id, $quantity);
                $stmt->execute();
                $stmt->close();

                // Update product quantity
                $stmt = $conn->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
                $stmt->bind_param("ii", $quantity, $product_id);
                $stmt->execute();
                $stmt->close();
            }
            $conn->commit();
            $_SESSION['cart'] = []; // Clear the cart
            $_SESSION['success_message'] = "Purchase successful!";
            header('Location: cart.php');
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            die("Error processing purchase: " . $e->getMessage());
        }
    }
}

// Fetch product details for items in the cart
$cart_items = [];
$total_price = 0;
if (!empty($_SESSION['cart'])) {
    $product_ids = array_keys($_SESSION['cart']);
    $in_query = implode(',', array_fill(0, count($product_ids), '?'));
    $types = str_repeat('i', count($product_ids));
    $stmt = $conn->prepare("SELECT id, product_name, price FROM products WHERE id IN ($in_query)");
    $stmt->bind_param($types, ...$product_ids);
    $stmt->execute();
    $stmt->bind_result($product_id, $product_name, $price);

    while ($stmt->fetch()) {
        $quantity = $_SESSION['cart'][$product_id];
        $cart_items[] = [
            'product_id' => $product_id,
            'product_name' => $product_name,
            'price' => $price,
            'quantity' => $quantity,
            'subtotal' => $price * $quantity
        ];
        $total_price += $price * $quantity;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - iShiply.com</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
            min-height: 100vh;
            flex-direction: column;
        }

        header {
            background-color: #0047ab;
            color: white;
            padding: 10px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
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

        .content {
            display: flex;
            margin-top: 60px;
            width: 100%;
        }

        .main-content {
            margin-left: 270px;
            padding: 20px;
            width: calc(100% - 270px);
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .cart-container {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }

        .cart-container h1 {
            margin-bottom: 20px;
        }

        .cart-container table {
            width: 100%;
            border-collapse: collapse;
        }

        .cart-container table th, .cart-container table td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }

        .cart-container table th {
            background-color: #f4f4f4;
        }

        .cart-container table td input[type="number"] {
            width: 50px;
        }

        .cart-container .total {
            text-align: right;
            font-size: 1.2em;
            margin-top: 20px;
        }

        .cart-container .actions {
            text-align: right;
            margin-top: 20px;
        }

        .cart-container .actions button {
            padding: 10px 20px;
            background-color: #0047ab;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .cart-container .actions button:hover {
            background-color: #003380;
        }

        .cart-container .actions .checkout {
            background-color: #28a745;
        }

        .cart-container .actions .checkout:hover {
            background-color: #218838;
        }

        .cart-container .actions .empty-cart {
            background-color: #dc3545;
        }

        .cart-container .actions .empty-cart:hover {
            background-color: #c82333;
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
                <li><a href="list_of_shops.php">List of Shops</a></li>
                <li><a href="cart.php">Cart</a></li>
                <li><a href="logout.php">Logout</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>

<!-- Content with Sidebar and Main Content -->
<div class="content">
    <!-- Sidebar -->
    <?php include('sidebar.php'); ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="cart-container">
            <h1>Shopping Cart</h1>
            <?php if (!empty($_SESSION['success_message'])): ?>
                <p class="success-message"><?= htmlspecialchars($_SESSION['success_message']) ?></p>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            <?php if (empty($cart_items)): ?>
                <p>Your cart is empty.</p>
            <?php else: ?>
                <form method="post" action="cart.php">
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cart_items as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                                    <td>$<?= htmlspecialchars(number_format($item['price'], 2)) ?></td>
                                    <td>
                                        <input type="number" name="quantities[<?= $item['product_id'] ?>]" value="<?= $item['quantity'] ?>" min="0">
                                    </td>
                                    <td>$<?= htmlspecialchars(number_format($item['subtotal'], 2)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="total">
                        <strong>Total: $<?= htmlspecialchars(number_format($total_price, 2)) ?></strong>
                    </div>
                    <div class="actions">
                        <button type="submit" name="update_cart">Update Cart</button>
                        <button type="submit" name="empty_cart" class="empty-cart">Empty Cart</button>
                        <button type="submit" name="checkout" class="checkout">Proceed to Checkout</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
