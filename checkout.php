<?php
session_start();
include('db_connect.php'); // Include the database connection file

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit();
}

// Ensure user_id is set in the session
if (!isset($_SESSION['user_id'])) {
    echo "Error: User ID is not set.";
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user details from the database
$user_details = [];
$stmt = $conn->prepare("SELECT name, address, latitude, longitude FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($name, $address, $user_latitude, $user_longitude);
if ($stmt->fetch()) {
    $user_details = ['name' => $name, 'address' => $address, 'latitude' => $user_latitude, 'longitude' => $user_longitude];
}
$stmt->close();

// Fetch shop details (assuming only one shop for simplicity)
$shop_details = [];
$shop_id = 1; // Replace with actual shop ID
$stmt = $conn->prepare("SELECT shop_name, address, latitude, longitude FROM shops WHERE shop_id = ?");
$stmt->bind_param("i", $shop_id);
$stmt->execute();
$stmt->bind_result($shop_name, $shop_address, $shop_latitude, $shop_longitude);
if ($stmt->fetch()) {
    $shop_details = [
        'shop_name' => $shop_name,
        'address' => $shop_address,
        'latitude' => $shop_latitude,
        'longitude' => $shop_longitude
    ];
}
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
        $customer_name = $_POST['customer_name'];
        $customer_address = $_POST['customer_address'];
        $total_amount = 0;

        // Calculate the total amount
        foreach ($_SESSION['cart'] as $product_id => $quantity) {
            $stmt = $conn->prepare("SELECT price FROM products WHERE id = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $stmt->bind_result($price);
            $stmt->fetch();
            $stmt->close();
            $total_amount += $price * $quantity;
        }

        // Insert into orders table
        $stmt = $conn->prepare("INSERT INTO orders (user_id, shop_id, customer_name, customer_address, total_amount, status) VALUES (?, ?, ?, ?, ?, 'Pending')");
        if (!$stmt) {
            echo "Error preparing statement: " . $conn->error;
            exit();
        }
        $stmt->bind_param("iissd", $user_id, $shop_id, $customer_name, $customer_address, $total_amount);
        if (!$stmt->execute()) {
            echo "Error executing statement: " . $stmt->error;
            exit();
        }
        $order_id = $stmt->insert_id; // Get the last inserted order ID
        $stmt->close();

        // Insert into order_items table
        foreach ($_SESSION['cart'] as $product_id => $quantity) {
            $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            if (!$stmt) {
                echo "Error preparing statement: " . $conn->error;
                exit();
            }
            $stmt->bind_param("iiid", $order_id, $product_id, $quantity, $price);
            if (!$stmt->execute()) {
                echo "Error executing statement: " . $stmt->error;
                exit();
            }
            $stmt->close();
        }

        // Clear the cart
        $_SESSION['cart'] = [];

        // Set a session variable to indicate success
        $_SESSION['order_success'] = true;

        // Redirect to the same page to display the confirmation message
        header('Location: checkout.php');
        exit();
    } else {
        echo "Your cart is empty.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - iShiply.com</title>
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
            justify-content: space-between;
        }

        .checkout-container {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            flex: 1;
            max-width: 45%;
            margin-right: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .checkout-container h1 {
            margin-bottom: 20px;
            color: #0047ab;
        }

        .checkout-container form {
            display: flex;
            flex-direction: column;
            width: 100%; /* Ensure the form takes full width */
            align-items: center;
        }

        .checkout-container label {
            margin-bottom: 10px;
            font-weight: bold;
            width: 100%;
            max-width: 350px; /* Ensure labels fit within the container */
        }

        .checkout-container input, .checkout-container textarea {
            margin-bottom: 20px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            width: 100%;
            max-width: 350px; /* Adjusted to fit better */
            font-size: 1rem;
            box-sizing: border-box;
        }

        .checkout-container button {
            padding: 10px 20px;
            background-color: #0047ab;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            font-size: 1rem;
            max-width: 350px; /* Adjusted to fit better */
        }

        .checkout-container button:hover {
            background-color: #003380;
        }

        .map-container {
            flex: 1;
            height: calc(100vh - 100px);
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        #map {
            height: 100%;
            width: 100%;
        }

        @media (max-width: 768px) {
            .content {
                flex-direction: column;
            }

            .checkout-container {
                max-width: 100%;
                margin-right: 0;
                margin-bottom: 20px;
            }

            .map-container {
                height: 300px;
            }
        }
    </style>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAB_AfGB4IoEDSb5JUox7frx9E60WqFOBg&libraries=places"></script>
    <script>
        let map, droneMarker;

        function initMap() {
            const shopLocation = { lat: <?= $shop_details['latitude'] ?>, lng: <?= $shop_details['longitude'] ?> };
            const customerLocation = { lat: <?= $user_details['latitude'] ?>, lng: <?= $user_details['longitude'] ?> };

            map = new google.maps.Map(document.getElementById('map'), {
                center: { lat: 0, lng: 0 },
                zoom: 2
            });

            const bounds = new google.maps.LatLngBounds();
            bounds.extend(shopLocation);
            bounds.extend(customerLocation);
            map.fitBounds(bounds);

            const shopMarker = new google.maps.Marker({
                position: shopLocation,
                map: map,
                title: "<?= htmlspecialchars($shop_details['shop_name']) ?>"
            });

            const customerMarker = new google.maps.Marker({
                position: customerLocation,
                map: map,
                title: "Customer Location"
            });

            droneMarker = new google.maps.Marker({
                position: shopLocation,
                map: map,
                title: "Drone",
                icon: {
                    url: 'drone.png', // URL to the drone icon
                    scaledSize: new google.maps.Size(50, 50),
                    origin: new google.maps.Point(0, 0),
                    anchor: new google.maps.Point(25, 25)
                }
            });

            const flightPath = new google.maps.Polyline({
                path: [shopLocation, customerLocation, shopLocation],
                geodesic: true,
                strokeColor: '#FF0000',
                strokeOpacity: 1.0,
                strokeWeight: 2
            });
            flightPath.setMap(map);
        }

        function moveDrone() {
            const path = [droneMarker.getPosition(), { lat: <?= $user_details['latitude'] ?>, lng: <?= $user_details['longitude'] ?> }];
            let step = 0;

            const intervalId = setInterval(() => {
                if (step >= 100) {
                    clearInterval(intervalId);
                    return;
                }
                const lat = path[0].lat() + (path[1].lat() - path[0].lat()) * step / 100;
                const lng = path[0].lng() + (path[1].lng() - path[0].lng()) * step / 100;
                droneMarker.setPosition(new google.maps.LatLng(lat, lng));
                step++;
            }, 100); // Move drone every 100 milliseconds
        }

        document.addEventListener('DOMContentLoaded', function () {
            initMap();
            document.querySelector('button[type="submit"]').addEventListener('click', moveDrone);
        });
    </script>
</head>
<body>

<!-- Header (Navbar) -->
<header>
    <a href="index.php" class="logo">iShiply.com</a>
    <nav class="navbar">
        <ul>
            <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
                <li><a href="dashboard.php">Dashboard</a></li>
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
    <div class="checkout-container">
        <h1>Checkout</h1>
        <?php if (isset($_SESSION['order_success'])): ?>
            <div class="confirmation-message">
                <p>Your order has been placed successfully!</p>
            </div>
            <?php unset($_SESSION['order_success']); ?>
        <?php endif; ?>
        <form method="POST" action="checkout.php">
            <label for="customer_name">Name:</label>
            <input type="text" id="customer_name" name="customer_name" value="<?= htmlspecialchars($user_details['name']) ?>" required>

            <label for="customer_address">Address:</label>
            <textarea id="customer_address" name="customer_address" required><?= htmlspecialchars($user_details['address']) ?></textarea>

            <label for="takeoff_coordinates">Take Off Coordinates:</label>
            <input type="text" id="takeoff_coordinates" value="<?= htmlspecialchars($shop_details['latitude'] . ', ' . $shop_details['longitude']) ?>" readonly>

            <label for="drop_location">Drop Location:</label>
            <input type="text" id="drop_location" value="<?= htmlspecialchars($user_details['latitude'] . ', ' . $user_details['longitude']) ?>" readonly>

            <label for="landing_coordinates">Landing Coordinates:</label>
            <input type="text" id="landing_coordinates" value="<?= htmlspecialchars($shop_details['latitude'] . ', ' . $shop_details['longitude']) ?>" readonly>

            <button type="submit">Place Order</button>
        </form>
    </div>
    <div class="map-container">
        <div id="map"></div>
    </div>
</div>

</body>
</html>
