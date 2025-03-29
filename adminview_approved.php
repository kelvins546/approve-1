<?php
session_start(); // Start the session

error_reporting(E_ALL);
ini_set('display_errors', 1);

$servername = "localhost";
$username = "root";
$password = "";
$database = "approve";

// Create database connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the report_id is set and is a valid integer
if (isset($_GET['_id']) && filter_var($_GET['_id'], FILTER_VALIDATE_INT)) {
    $id = $_GET['_id'];

    // Query to fetch item details by report_id from the pending_found_reports table
    $sql = "SELECT * FROM approved_lost_reports WHERE id = ?";
    $stmt = $conn->prepare($sql);

    // Check if the prepare statement was successful
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error); // Display MySQL error if prepare fails
    }

    $stmt->bind_param("i", $id);
    if (!$stmt->execute()) {
        die("Execution failed: " . $stmt->error);
    }

    $result = $stmt->get_result();

    // Check if item exists
    if ($result->num_rows > 0) {
        $item = $result->fetch_assoc();
    } else {
        echo "Item not found.";
        exit;
    }

    // Close the statement
    $stmt->close();

    // Handle approve request
    if (isset($_POST['approve'])) {
        // Query to insert the item into the approved_found_reports table
        // Query to insert the item into the approved_lost_reports table
        $insertSql = "INSERT INTO approved_lost_reports 
(user_id, item_name, category, description, picture, location_found, date_found, time_found, brand, primary_color, 
 first_name, last_name, phone_number, email)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $insertStmt = $conn->prepare($insertSql);

        if ($insertStmt === false) {
            die("Prepare failed for insert: " . $conn->error); // Show error if prepare fails
        }

        // Bind parameters to insert statement (make sure the correct number of parameters are passed)
        $insertStmt->bind_param(
            "isssssssssssss", // Updated to match 14 parameters
            $item['user_id'],
            $item['item_name'],
            $item['category'],
            $item['description'],
            $item['picture'],
            $item['location_found'],
            $item['date_found'],
            $item['time_found'],
            $item['brand'],
            $item['primary_color'],
            $item['first_name'],
            $item['last_name'],
            $item['phone_number'],
            $item['email']
        );


        // Execute insert
        if ($insertStmt->execute()) {
            // Query to delete the item from the pending_lost_reports table
            $deleteSql = "DELETE FROM approved_lost_reports WHERE id = ?";
            $deleteStmt = $conn->prepare($deleteSql);

            if ($deleteStmt === false) {
                die("Prepare failed for delete: " . $conn->error); // Show error if prepare fails
            }

            $deleteStmt->bind_param("i", $id);

            if ($deleteStmt->execute()) {
                $_SESSION['message'] = "Item approved and moved to the approved list.";
            } else {
                $_SESSION['message'] = "Error deleting item: " . $deleteStmt->error;
            }

            // Close the statements before redirecting
            $deleteStmt->close();
            $insertStmt->close();

            // Redirect to pending_lost_report.php
            header("Location: approved_lost_report.php");
            exit;
        } else {
            $_SESSION['message'] = "Error approving item: " . $insertStmt->error;
            $insertStmt->close();
        }
    }

    // Handle delete request (if needed)
    if (isset($_POST['delete'])) {
        // Query to delete the item from the pending_found_reports table
        $deleteSql = "DELETE FROM approved_lost_reports WHERE id = ?";
        $deleteStmt = $conn->prepare($deleteSql);

        if ($deleteStmt === false) {
            die("Prepare failed for delete: " . $conn->error); // Show error if prepare fails
        }

        $deleteStmt->bind_param("i", $id);

        if ($deleteStmt->execute()) {
            $_SESSION['message'] = "Item deleted successfully.";
            $deleteStmt->close();
            header("Location: approved_lost_report.php");
            exit;
        } else {
            $_SESSION['message'] = "Error deleting item: " . $deleteStmt->error;
            $deleteStmt->close();
            header("Location: approved_lost_report.php");
            exit;
        }
    }
} else {
    echo "Invalid ID or missing parameter.";
    exit;
}

$conn->close();
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Item Details</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:ital,wght@0,100..900;1,100..900&family=Londrina+Outline&family=Open+Sans:ital,wght@0,300..800;1,300..800&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Rubik+80s+Fade&family=Rubik+Burned&family=Source+Code+Pro:ital,wght@0,200..900;1,200..900&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="admin_report.css">

    <style>
        /* General styles */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Hanken Grotesk', Arial, sans-serif;
        }

        body {
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            display: flex;
            color: #545454;
            flex-direction: column;
            min-height: 100vh;

            background-image: url('images/bg1.png');
            background-size: cover;
            background-position: center center;
            background-attachment: fixed;
            background-repeat: no-repeat;
        }

        /* Navbar styles */
        .navbar {

            background-color: #2b4257;
            padding: 10px;
            color: #545454;
            position: sticky;
            top: 0;
            z-index: 1000;
            width: 100%;
            display: flex;

            align-items: center;
            flex-wrap: wrap;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .navbar a {
            color: #fff;
            padding: 3px;
            text-decoration: none;
            margin: 20px;
            display: inline-block;
        }

        .navbar a:hover {
            text-decoration: underline;
        }

        .navbar-logo {
            height: 90px;
            width: auto;
            /* Maintain aspect ratio */
            margin-right: 20px;
            margin-left: 10px;
            margin-top: 10px
        }

        .nav-login {
            width: 110px;
            height: 30px;
            padding: 10px;
            border: 1px solid black;
            border-radius: 3px;
            background-color: #fff89f;
            color: #545454;
            cursor: pointer;
            display: flex;
            justify-content: center;
            text-align: center;
            margin-left: auto;
            margin-right: 40px;
            align-items: center;
        }

        .nav-login:hover {
            background-color: #fdd400;
        }

        .container-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;

            margin-top: 0;
            margin-bottom: 0;
        }


        .container {
            max-width: 500px;
            max-height: 550px;
            width: 450px;
            margin: 5px;
            background-color: #fff;
            padding: 40px 40px;
            border-radius: 2px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            box-sizing: border-box;
            z-index: 0;
            align-items: center;
            margin-top: 40px;
            margin-bottom: 40px;
            align-self: self-start;



        }


        .container img {
            display: block;
            /* Ensures the image respects margin auto for centering */
            margin: 0 auto;
            /* Horizontally centers the image */
            max-width: 100%;
            width: 320px;
            /* Makes the image responsive */
            height: auto;
            /* Maintains aspect ratio */
            border: 1px solid #ccc;
            /* Optional: Adds a border to the image */
            padding: 5px;
            /* Optional: Adds padding inside the border */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            /* Optional: Adds shadow */
            margin-top: 20px;
        }

        label {
            font-size: 13px;
        }

        .container-title {
            display: flex;
            align-items: flex-end;
            justify-content: flex-start;
        }

        .container-title2 {
            display: flex;
            align-items: flex-end;
            justify-content: flex-start;
        }



        .container-title2 h2 {
            margin: 0;
            font-size: 22px;
            color: #333;
        }

        .container-title h2 {
            margin: 0;
            font-size: 22px;
            color: #333;
            line-height: 1.2;
        }

        .container-title p {
            margin: 0;
            font-size: 12px;
            color: #777;
            margin-left: 10px;
            line-height: 1.6;
            display: inline-block;
            vertical-align: middle;
        }

        .container-title2 p {
            margin: 0;
            font-size: 12px;
            color: #777;
            margin-left: 10px;

            display: inline-block;
            vertical-align: middle;
        }

        hr {
            margin-bottom: 20px;
            margin-top: 10px;
        }

        .alert {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
            font-size: 12px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }

        .form-group {
            margin-bottom: 15px;
            flex: 1;
        }

        .form-group p {
            font-size: 12px;
            color: #777;
            margin-top: 5px;
        }

        input[type="checkbox"] {
            width: 15px;
            height: 15px;
            vertical-align: middle;
            margin-right: 4px;
            appearance: none;
            border: 1px solid #545454;
            border-radius: 0;
            background-color: #fff;
            cursor: pointer;
            outline: none;
            display: inline-block;
            position: relative;
        }

        input[type="checkbox"]:checked {
            background-color: #fdd400;
            border-color: #545454;
        }

        input[type="checkbox"]:checked::before {
            content: "✓";
            position: absolute;
            top: 0;
            left: 2px;
            font-size: 11px;
            font-weight: bold;
            text-align: center;
            color: #333;
        }

        input[type="checkbox"]:hover {
            border-color: #333;
        }

        label.terms {
            font-size: 12px;
            display: flex;
            align-items: flex-end;
            gap: 5px;
            color: #777;
            flex-wrap: nowrap;
        }

        .terms-link {
            text-decoration: none;
            color: #333;
            font-style: italic;
        }

        .terms-link:hover {
            text-decoration: underline;
        }

        .align-container {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            margin-top: 40px;
        }


        label {
            display: block;
            margin-bottom: 8px;
            font-weight: normal;
            color: #333;
        }

        input[type="text"],
        input[type="number"],
        input[type="date"],
        input[type="time"],
        textarea,
        select {
            width: 100%;
            padding: 6px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 0px;
            font-size: 14px;
        }

        textarea {
            resize: vertical;
        }

        .form-row {
            display: flex;
            justify-content: space-between;
            gap: 4%;
        }

        .form-row .form-group {
            width: 48%;
        }

        .form-row-submit {
            display: flex;
            justify-content: space-between;
            gap: 4%;
            align-items: flex-end;
        }

        .form-row-submit .form-group {
            width: 48%;
        }

        .btn-container {
            display: flex;
            gap: 5px;
            margin-top: 0px;
            width: 100%;
            justify-content: flex-end;
        }

        h2.btn-action {
            margin-top: 20px !important;
            /* Adjust the value as needed */
        }


        .btn {

            border: none;
            border-radius: 2px;
            font-size: 12px;
            text-decoration: none;
            text-align: center;
            transition: background-color 0.3s, box-shadow 0.3s;
            color: #545454;
            font-weight: normal;
            padding: 5px 23px;
            border: 1px solid #545454;
        }

        .btn-info {
            background-color: #28a745;
            color: #fff;
        }

        .btn-success {
            background-color: #28a745;
            color: #fff;
            font-weight: bold;
            margin-left: 110px;



        }

        .btn-danger {
            background-color: #dc3545;
            color: #fff;
            font-weight: bold;



        }

        .btn:hover {

            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        /* Footer */
        .footer {
            background-color: #fdd400;
            padding: 20px 0;
            color: #545454;
            font-family: 'Hanken Grotesk', sans-serif;
            width: 100%;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            position: relative;
            text-align: center;
        }

        .footer-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            /* Space out logo and contact text */
            width: 90%;
            margin: 0 auto;
            padding-bottom: 20px;
        }

        .footer-logo {
            align-self: flex-start;
        }

        .footer-logo img {
            max-width: 150px;

        }

        .footer-contact {
            text-align: right;
            /* Align text to the right */
            font-size: 14px;
            margin-left: auto;
            width: 20%;
            margin-bottom: 25px;
        }

        .footer-contact h4 {
            font-size: 18px;
            margin-bottom: 10px;
        }

        .footer-contact p {
            font-size: 14px;
            margin-top: 0;

        }

        .all-links {
            display: flex;

            width: 100%;
            margin-top: 20px;
            position: absolute;

            justify-content: center;
        }

        .footer-others {
            display: flex;
            justify-content: center;
            /* Align links in the center */
            gap: 30px;
            top: 190px;
            left: 30%;
            margin-left: 140px;
            margin-top: 20px;
            transform: translateX(-50%);
        }


        .footer-others a {
            color: #545454;
            text-decoration: none;
            font-size: 14px;
        }

        .footer-separator {
            width: 90%;
            height: 1px;
            background-color: #545454;
            margin: 10px auto;
            border: none;
            position: absolute;
            bottom: 40px;
            left: 50%;
            margin-top: 20px;
            transform: translateX(-50%);
        }

        .footer-text {
            font-size: 14px;
            margin-top: 20px;
            color: #545454;
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);

        }
    </style>

</head>

<body>
    <div class="navbar">

        <a href="found_report.php">Home</a>
        <a href="submit_report.php">Guidelines</a>
        <a href="userview.php">Browse Reports</a>
    </div>
    <div class="container-wrapper">
        <div class="container">
            <div class="container-title">
                <h2>Item Details</h2>
                <p>Here's the complete info. about the item</p>
            </div>
            <hr>
            <div class="form-row">
                <div class="form-group">
                    <label for="item_name">Object Title</label>
                    <input type="text" name="item_name" id="item_name"
                        value="<?= htmlspecialchars($item['item_name']) ?>" class="form-control" readonly>
                </div>
                <div class="form-group">
                    <label for="date_found">Date Loss</label>
                    <input type="date" name="date_found" id="date_found"
                        value="<?= htmlspecialchars($item['date_found']) ?>" class="form-control" readonly>
                </div>
            </div>
            <!-- Category | Time Found -->
            <div class="form-row">
                <div class="form-group">
                    <label for="category">Category</label>
                    <input type="text" type="category" name="category" id="category"
                        value="<?= htmlspecialchars($item['category']) ?>" class="form-control" readonly>
                    <!-- Add options dynamically or statically here -->
                </div>
                <div class="form-group">
                    <label for="time_found">Time Loss</label>
                    <input type="time" name="time_found" id="time_found"
                        value="<?= htmlspecialchars($item['time_found']) ?>" class="form-control" readonly>
                </div>
            </div>
            <!-- Brand | Location Found -->
            <div class="form-row">
                <div class="form-group">
                    <label for="brand">Brand</label>
                    <input type="text" name="brand" id="brand" value="<?= htmlspecialchars($item['brand']) ?>"
                        class="form-control" readonly>
                </div>
                <div class="form-group">
                    <label for="location_found">Last known location</label>
                    <input type="text" name="location_found" id="location_found"
                        value="<?= htmlspecialchars($item['location_found']) ?>" class="form-control" readonly>
                    <!-- Add options dynamically or statically here -->
                </div>
            </div>
            <!-- Primary Color | Image -->
            <div class="form-row">
                <div class="form-group">
                    <label for="primary_color">Primary Color</label>
                    <input type="text" name="primary_color" id="primary_color"
                        value="<?= htmlspecialchars($item['primary_color']) ?>" class="form-control" readonly>
                </div>
                <div class="form-group">
                </div>
            </div>
            <div class="form-row-submit">
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea name="description" id="description" rows="4" class="form-control"
                        readonly><?= htmlspecialchars($item['description']) ?></textarea>
                </div>
            </div>
        </div>
        <div class="container">
            <div class="container-title">
                <h2>Image</h2>
                <p>Here’s what the item looks like for better identification.</p>

            </div>
            <hr>
            <img src="<?= htmlspecialchars($item["picture"]) ?>" alt="Item Picture" class="item-image">
        </div>
        <div class="container">

            <!-- Contact Information -->
            <div class="container-title">
                <h2>Contact Information</h2>
                <p>(individual who lost the item)</p>
            </div>
            <hr>
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input type="text" name="first_name" id="first_name"
                        value="<?= htmlspecialchars($item['first_name']) ?>" class="form-control" readonly>
                </div>

                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" name="last_name" id="last_name"
                        value="<?= htmlspecialchars($item['last_name']) ?>" class="form-control" readonly>
                </div>

            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="phone_number">Phone Number</label>
                    <input type="text" name="phone_number" id="phone_number"
                        value="<?= htmlspecialchars($item['phone_number']) ?>" class="form-control" readonly>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="text" name="email" id="email" value="<?= htmlspecialchars($item['email']) ?>"
                        class="form-control" readonly>
                </div>
            </div>
            <div class="container-title">
                <h2 class="btn-action">Action</h2>
                <p>Delete the report as needed.</p>
            </div>
            <hr>
            <div class="btn-container">
                <a href="approved_lost_report.php" class="btn btn-back">Back</a>
                <form method="POST">

                </form>
                <form method="POST">
                    <button class="btn btn-danger" type="submit" name="delete"
                        onclick="return confirm('Are you sure you want to delete this item?');">Reject</button>
                </form>

            </div>
        </div>
    </div>


    <footer class="footer">
        <div class="footer-content">
            <div class="footer-logo">

            </div>
            <div class="all-links">
                <nav class="footer-others">
                    <a href="">ABOUT US</a>
                    <a href="">TERMS</a>
                    <a href="">FAQ</a>
                    <a href="">PRIVACY</a>
                </nav>
            </div>


            <div class="footer-contact">
                <h4>Contact us</h4>
                <p>This website is currently under construction. For futher inquires, please contact us at
                    starcity@gmailcom</p>
            </div>
            <hr class="footer-separator">
            <p class="footer-text">&copy; Star City, All rights reserved.</p>
        </div>
    </footer>
</body>

</html>