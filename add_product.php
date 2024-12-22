<?php
session_start();
include('db_connect.php'); // Include the database connection file
include('sidebar.php'); // Include the sidebar

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit();
}

// Fetch the shop_id from the users table
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT shop_id FROM shops WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($shop_id);
$stmt->fetch();
$stmt->close();

if (!$shop_id) {
    die("Error: Shop ID is not set. Please create a shop first.");
}

// Function to sanitize input data
function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get data from the form submission
    $product_name = sanitize_input($_POST['product_name']);
    $description = sanitize_input($_POST['description']);
    $price = sanitize_input($_POST['price']);
    $quantity = sanitize_input($_POST['quantity']);

    // Validate input data
    if (empty($product_name) || empty($price) || empty($quantity)) {
        die("All fields are required.");
    }

    if (!is_numeric($price) || !is_numeric($quantity)) {
        die("Price and quantity must be numeric.");
    }

    // Prepare and execute the SQL query
    $stmt = $conn->prepare("INSERT INTO products (product_name, description, price, quantity, shop_id) VALUES (?, ?, ?, ?, ?)");
    if ($stmt === false) {
        die("Error preparing the statement: " . $conn->error);
    }
    $stmt->bind_param("ssdii", $product_name, $description, $price, $quantity, $shop_id);

    if ($stmt->execute()) {
        // Redirect to the manage products page on success
        $stmt->close();
        $conn->close();
        header("Location: my_products.php");
        exit();
    } else {
        // Handle error if the query fails
        $error_message = "Error executing the statement: " . $stmt->error;
        $stmt->close();
        $conn->close();
        die($error_message);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - iShiply.com</title>
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
            padding: 20px;
            width: 100%;
            justify-content: center;
            box-sizing: border-box;
        }

        .form-container {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            width: 100%;
            max-width: 600px;
            box-sizing: border-box;
            margin-left: 270px; /* Adjust margin for the sidebar */
        }

        .form-container h1 {
            margin-bottom: 20px;
            color: #0047ab;
        }

        .form-container h1 small {
            font-size: 0.6em;
            color: #555;
        }

        .form-container form {
            display: flex;
            flex-direction: column;
        }

        .form-container form label {
            margin-bottom: 10px;
            font-weight: bold;
        }

        .form-container form input {
            margin-bottom: 20px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .form-container form button {
            padding: 10px;
            background-color: #0047ab;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .form-container form button:hover {
            background-color: #003080;
        }

        @media (max-width: 768px) {
            .content {
                flex-direction: column;
            }

            .form-container {
                margin-left: 0;
                margin-top: 20px;
            }

            .form-container form input, .form-container form button {
                font-size: 12px;
                padding: 8px;
            }
        }
    </style>
</head>
<body>

<!-- Header (Navbar) -->
<header>
    <a href="index.php" class="logo">iShiply.com</a>
    <nav class="navbar">
        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>
</header>

<!-- Sidebar -->
<?php include('sidebar.php'); ?>

<!-- Content with Form to Add Product -->
<div class="content">
    <div class="form-container">
        <h1>Add Product <small>(Shop ID: <?= htmlspecialchars($shop_id) ?>)</small></h1>
        <form method="POST" action="add_product.php">
            <label for="product_name">Product Name</label>
            <input type="text" id="product_name" name="product_name" required>

            <label for="description">Description</label>
            <input type="text" id="description" name="description" required>

            <label for="price">Product Price</label>
            <input type="text" id="price" name="price" required>

            <label for="quantity">Product Quantity</label>
            <input type="number" id="quantity" name="quantity" required>

            <button type="submit">Add Product</button>
        </form>
    </div>
</div>

</body>
</html>
