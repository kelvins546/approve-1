<?php
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
$report_id = null; // Initialize variable

if (isset($_GET['report_id']) && filter_var($_GET['report_id'], FILTER_VALIDATE_INT)) {
    $report_id = $_GET['report_id'];

    // Query to fetch item details by report_id from the approved_reports table
    $sql = "SELECT * FROM pending_reports WHERE report_id = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("i", $report_id);
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
} else {
    // If the report_id is not valid or missing, skip the process without any message
    if (!$report_id) {
        exit; // Simply stop execution if no valid report ID is found
    }
}

// Close the database connection
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
        background-color: #fff;
        padding: 10px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        color: #545454;
        position: sticky;
        top: 0;
        z-index: 1000;
        width: 100%;
        display: flex;

        align-items: center;
        flex-wrap: wrap;
    }

    .navbar a {
        color: #fff;
        padding: 3px;
        text-decoration: none;
        margin: 20px;
        display: inline-block;
    }



    .navbar-logo {
        height: 90px;
        width: auto;
        /* Maintain aspect ratio */
        margin-right: 20px;
        margin-left: 10px;
        margin-top: 10px
    }

    .nav-login:hover {
        background-color: #fdd400;
    }

    .main {
        margin-top: 40px;
        display: flex;
        justify-content: center;
        margin-bottom: 100px;


    }

    /* Styles for item details container */
    .container {

        width: 500px;
        margin: 10px;
        background-color: #fff;
        padding: 20px;
        border-radius: 8px;
        border: 1px solid #d3d3d3;
        /* Adds a light border */
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    /* Styles for the fetched data items */
    .item-info-container {

        padding: 10px;
        margin-bottom: 10px;
        border-radius: 5px;
        max-width: 300px;
        /* Optional: Adjust to fit your layout */
    }

    .item-info-title {
        font-weight: normal;
        color: #333;
        margin-bottom: 5px;
    }

    .item-info-data {
        color: #555;
        font-size: 14px;
        padding: 10px;
        border: 1px solid #ccc;
    }

    h2 {
        margin-bottom: 10px;
    }

    .hr-container {
        margin-bottom: 40px;
    }

    /* Class for the <p> elements */


    .item-info strong {
        color: #545454;

    }

    /* Class for the <img> element */
    .item-image {
        max-width: 100%;
        height: auto;
        border-radius: 4px;
        margin-top: 10px;
    }

    .btn {
        display: inline-block;

        border: none;
        border-radius: 4px;
        font-size: 14px;
        text-decoration: none;
        text-align: center;
        transition: background-color 0.3s, box-shadow 0.3s;
        background-color: #2b4257;
        color: #fff;
        font-weight: bold;
        padding: 5px 23px;
        border: 1px solid #545454;
    }

    .btn:hover {
        background-color: #138496;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    /* Footer */
    .footer {
        background-color: #fff;
        padding: 20px 0;
        color: #fff;
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
        color: #fff;
        text-decoration: none;
        font-size: 14px;
    }

    .footer-separator {
        width: 90%;
        height: 1px;
        background-color: #fff;
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
        color: #fff;
        position: absolute;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);

    }
    </style>

</head>

<body>
    <div class="navbar">
        <img src="images/star.png" alt="Logo" class="navbar-logo">
        <a href="found_report.php">Home</a>
        <a href="submit_report.php">Guidelines</a>
        <a href="userview.php">Browse Reports</a>

    </div>

    <div class="main">
        <div class="container">
            <h2 class="item-title">Item Details</h2>
            <hr class="hr-container">

            <!-- Item Name -->
            <div class="item-info-container">
                <p class="item-info-title">Item Name</p>
                <p class="item-info-data"><?= htmlspecialchars($item["item_name"]) ?></p>
            </div>

            <!-- Category -->
            <div class="item-info-container">
                <p class="item-info-title">Category</p>
                <p class="item-info-data"><?= htmlspecialchars($item["category"]) ?></p>
            </div>

            <!-- Location Found -->
            <div class="item-info-container">
                <p class="item-info-title">Location Found</p>
                <p class="item-info-data"><?= htmlspecialchars($item["location_found"]) ?></p>
            </div>

            <!-- Date Found -->
            <div class="item-info-container">
                <p class="item-info-title">Date Found</p>
                <p class="item-info-data"><?= htmlspecialchars($item["date_found"]) ?></p>
            </div>

            <!-- Time Found -->
            <div class="item-info-container">
                <p class="item-info-title">Time Found</p>
                <p class="item-info-data"><?= htmlspecialchars($item["time_found"]) ?></p>
            </div>

            <!-- Details -->
            <div class="item-info-container">
                <p class="item-info-title">Descruption</p>
                <p class="item-info-data"><?= htmlspecialchars($item["item_details"]) ?></p>
            </div>



            <!-- Buttons -->
            <a href="admin_report.php" class="btn btn-info">Back</a>

        </div>
        <div class="container">
            <!-- Picture -->
            <?php if (!empty($item["picture"])): ?>
            <div class="item-info-container">
                <p class="item-info-title">Image</p>
                <img src="<?= htmlspecialchars($item["picture"]) ?>" alt="Item Picture" class="item-image">
            </div>
            <?php endif; ?>
        </div>
    </div>


    <footer class="footer">
        <div class="footer-content">
            <div class="footer-logo">
                <img src="images/star.png" alt="Logo" />
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