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
    $sql = "SELECT * FROM approved_reports WHERE report_id = ?";
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
        exit;
    }
}

// Close the database connection
$conn->close();

$userId = $_SESSION['user_id'];

// Count user reports
$approvedReportCount = countUserReports($conn, $userId);

function countUserReports($conn, $userId)
{
    $query = "
        SELECT COUNT(*) AS total_reports FROM (
            SELECT id FROM pending_lost_reports WHERE user_id = ?
            UNION ALL
            SELECT id FROM pending_found_reports WHERE user_id = ?
            UNION ALL
            SELECT id FROM approved_lost_reports WHERE user_id = ?
            UNION ALL
            SELECT id FROM approved_found_reports WHERE user_id = ?
        ) AS user_reports
    ";

    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param('ssss', $userId, $userId, $userId, $userId);
        $stmt->execute();

        // Initialize $totalReports to prevent IDE warnings
        $totalReports = 0;
        $stmt->bind_result($totalReports);
        $stmt->fetch();
        $stmt->close();

        return $totalReports;
    }


    return 0; // Default to 0 if query fails
}
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// Get the current script name
$currentPage = basename($_SERVER['PHP_SELF']);

// Set the button label based on the current page
$buttonLabel = ($currentPage === 'found_report.php') ? 'Report Found' : (($currentPage === 'lost_report.php') ? 'Report
Lost' : 'Report');

$userName = htmlspecialchars($_SESSION['name'] ?? 'User');

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

        background-image: url('images/bgfinal1.png');
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
        z-index: 10;
        width: 100%;
        display: flex;
        align-items: flex-start;
        flex-direction: column;

    }

    .nav-content {
        background-image: url('images/bgfinal.png');
        background-size: cover;
        background-position: center center;
        background-attachment: fixed;
        background-repeat: no-repeat;
        padding: 60px 0;


    }

    .nav-content-cont {
        display: flex;
        flex-direction: column;
        align-items: center;
        /* margin-left: 70px;*/
    }

    .nav-main {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        gap: 20px;

        /* Add some spacing between nav-main and nav-content */
    }

    .nav-btn {
        background-color: transparent;
        color: #545454;
        border: none;
        font-size: 16px;
        margin-top: 12px;
        margin-left: 30px;
        cursor: pointer;
        text-align: center;
        display: inline-block;
        transition: color 0.3s ease, text-decoration 0.3s ease;
    }

    /* Hover effect on button */
    .nav-btn:hover {

        text-decoration: underline;
    }

    .nav-main>.icon-btn {
        background-color: #f4f5f6;
        /* Transparent background for the button */
        border: 2px solid #000;
        /* Consistent border color */
        /* Border for circular shape */
        border-radius: 50%;
        /* Makes the border circular */
        cursor: pointer;
        /* Pointer cursor */
        padding: 3px;
        /* Space around the icon */
        display: flex;
        /* Center the icon inside the button */
        align-items: center;
        /* Vertical centering */
        justify-content: center;
        /* Horizontal centering */
        margin-left: 830px;
        /* Push the button to the far right */
        transition: background-color 0.3s ease, border-color 0.3s ease;
        /* Smooth hover effect */
        z-index: 99999;

    }

    .icon-btn {
        z-index: 99999;
    }




    .nav-main>.icon-btn:hover {
        background-color: #f4f4f9;
        /* Light background on hover */
        border-color: #000;
        /* Darker border on hover */
    }



    .nav-main>.icon-btn:hover .user-icon {
        color: #000;
        /* Darker icon color on hover */
    }

    .user-icon {
        font-size: 24px;
        /* Icon size */
        color: #545454;
        transition: color 0.3s ease;
        /* Smooth color change on hover */
    }

    .user-icon:hover {
        color: #fff;
        /* Darken color on hover */
    }

    .navbar a {
        color: #545454;
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
        margin-top: 10px;
    }



    .nav-title h1 {
        font-size: 78px;
        color: #fff;
        font-weight: bold;
        line-height: 1.1;



    }

    .nav-text p {
        font-size: 16px;
        color: #fff;
        line-height: 1.4;
        margin-bottom: 20px;

    }

    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.7);
        z-index: 999;
    }

    .modal-content2 {
        background-color: #fefefe;
        padding: 30px;
        color: #545454;
        border-radius: 10px;
        border: 1px solid #fefefe;
        width: 200px;
        max-width: 100%;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        animation: fadeIn 0.3s ease-out;
        margin-bottom: 0px;
        position: absolute;
        top: 23%;
        right: 0;
        transform: translate(-50%, -50%);
        padding-top: 20px;

    }

    /* Adding the arrow */
    .modal-content2::after {
        content: "";
        position: absolute;
        top: 5px;
        /* Position the arrow vertically */
        right: -10px;
        /* Place the arrow to the right side of the modal */
        width: 0;
        height: 0;
        border-top: 10px solid transparent;
        /* Transparent top edge */
        border-bottom: 10px solid transparent;
        /* Transparent bottom edge */
        border-left: 10px solid #fff;
        /* The arrow color matches the modal background */
        z-index: 1000;
        /* Ensures it appears above other elements */
    }

    /* Style for the close button */
    .close-btn {
        position: absolute;
        top: 0px;
        /* Adjust based on your design */
        right: 10px;
        /* Adjust based on your design */
        background: transparent;
        border: none;
        font-size: 18px;
        font-weight: bold;
        cursor: pointer;
        color: #333;
        /* Change color to match your theme */
    }

    .dropdown {
        position: relative;
        display: inline-block;
        margin-bottom: 10px;
        z-index: 1;
    }

    .dropdown-btn {
        padding: 5px 20px;
        background-color: #e5e5e5;
        color: #545454;
        border: 3px solid #545454;
        border-radius: 22px;
        cursor: pointer;
        font-size: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background-color 0.3s ease;
    }

    .dropdown-btn::after {
        content: '';
        width: 0;
        height: 0;
        border-top: 5px solid transparent;
        border-bottom: 5px solid transparent;
        border-left: 5px solid #545454;
        margin-left: 10px;
        transition: background-color 0.3s ease;
        transform: rotate(270deg);
    }

    .dropdown-content {
        display: none;
        position: absolute;
        background-color: white;
        min-width: 180px;
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        z-index: 1;
        margin-top: 0px;
        border-radius: 4px;
    }

    .dropdown:hover .dropdown-content {
        display: block;
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
    }

    .dropdown-content a {
        padding: 0;
        text-decoration: none;
        display: block;
        color: #545454;
    }

    .dropdown-content a:hover {
        background-color: #f1f1f1;
    }

    /* Rotate the arrow when hovering over the dropdown button */
    .dropdown:hover .dropdown-btn::after {
        transform: rotate(90deg);
        /* Rotates the arrow */
    }

    /* Hover effect on the button (optional, for visual feedback) */
    .dropdown-btn:hover {
        background-color: #ccc
    }

    .modal-title2 {
        display: inline;
        text-align: center;
    }

    .modal-title2 h3 {
        margin-bottom: 2px;
        font-size: 17px;
    }

    .modal-title2 p {
        margin-bottom: 2px;
        font-size: 14px;
    }

    .butclass {
        display: flex;
        /* Enables flexbox */
        flex-direction: column;
        /* Align items vertically */
        align-items: center;
        /* Center items horizontally */
        gap: 10px;
        /* Adds spacing between the buttons */
        margin-top: 20px;
        /* Optional: add some spacing above the buttons */
    }

    .btn-ok2 {
        padding: 5px 20px;
        color: #545454;
        border: none;
        border-radius: 0px;
        cursor: pointer;
        margin-bottom: 10px;
        text-align: center;
        border: 2px solid #545454;

        /* Allow the button to resize based on content */
        width: 120px;
        /* Optional: Ensure buttons have consistent size */
    }

    .btn-ok2:hover {
        background-color: #ccc;
    }


    .close-btn:hover {
        color: #f00;
        /* Optional: Add hover effect */
    }



    .container {
        width: 500px;
        margin: 10px;

        background-color: #fff;
        padding-left: 40px;
        padding-right: 40px;
        border-radius: 8px;
        border: 1px solid #d3d3d3;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    .hr-container,
    .hr-container-second {
        margin-top: 20px;
        margin-bottom: 20px;
        border: 0;
        border-top: 2px solid #ccc;
        width: 100%;
    }

    .item-info-container {
        display: flex;
        flex-direction: column;
        padding: 0;
        margin-bottom: 0px;
        border-radius: 5px;
    }

    .item-info-container-row {
        display: flex;
        justify-content: space-between;
        padding: 0;
        margin-bottom: 10px;
    }

    .item-info-tit,
    .item-info-container-des,
    .item-info-tit-half,
    .item-info-container-des-half {
        margin: 0;
        padding: 0;
    }

    /* Ensure Item Name and Description are side by side */
    .item-info-tit,
    .item-info-container-des {
        width: 48%;
    }

    /* Ensure Category, Location Found, Date Found, and Time Found are stacked */
    .item-info-tit-half,
    .item-info-container-des-half {
        width: 48%;
        margin-right: 4%;
    }

    .item-info-title {
        font-weight: normal;
        color: #333;
        margin: 0;
    }

    .item-info-data {
        color: #555;
        font-size: 14px;
        padding: 10px;
        border: 1px solid #ccc;
        margin: 0;
        box-sizing: border-box;
    }

    h2 {
        margin-bottom: 10px;
        margin-top: 20px;
    }

    .item-info strong {
        color: #545454;
    }

    .item-image {
        max-width: 100%;
        max-height: 400px;
        border-radius: 4px;
        margin-top: 10px;
        display: block;
        margin-left: auto;
        margin-right: auto;
        margin-bottom: 20px !important;
    }

    .btn-container {
        display: flex;
        gap: 5px;
        margin-top: 0px;
        width: 100%;
        justify-content: flex-end;
    }

    .btn {
        display: inline-block;
        border: none;
        border-radius: 2px;
        font-size: 14px;
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

    .btn:hover {

        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    /* Footer */
    .footer {
        background-color: #fff;
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
        <div class="nav-main">
            <img src="images/star.png" alt="Logo" class="navbar-logo">
            <a href="found_report.php">Home</a>
            <a href="guidelines.php">Guidelines</a>

            <div class="dropdown">
                <button class="nav-btn">Browse Reports</button>
                <div class="dropdown-content">
                    <a href="userview.php">Found Reports</a>
                    <a href="lost_reports.php">Lost Reports</a>
                </div>
            </div>

            <button class="icon-btn" onclick="openModal('loginclickmodal')">
                <ion-icon name="person" class="user-icon"></ion-icon>
            </button>

        </div>
    </div>

    <div id="loginclickmodal" class="modal-overlay" style="display: none;">
        <div class="modal-content2">
            <!-- Close Button -->
            <button class="close-btn" onclick="closeModal('loginclickmodal')">&times;</button>

            <div class="modal-title2">
                <h3>Good day, <strong><?= htmlspecialchars($userName) ?></strong>!</h3>
                <p><?= htmlspecialchars($_SESSION['user_id'] ?? '') ?></p>
                <hr>
            </div>
            <div class="butclass">
                <button class="btn-ok2" onclick="window.location.href='usersoloview.php'">
                    See report details (<?= htmlspecialchars($approvedReportCount); ?>)
                </button>
                <button class="btn-ok2" onclick="window.location.href='usersoloviewclaim.php'">See claim status</button>
                <button class="btn-ok2" onclick="window.location.href='?logout'">LOG OUT</button>
            </div>
        </div>
    </div>

    <div class="main">
        <div class="container">
            <h2 class="item-title">Item Details</h2>
            <hr class="hr-container">

            <!-- Item Name and Description side by side -->
            <div class="item-info-container-row">
                <div class="item-info-tit">
                    <p class="item-info-title">Item Name</p>
                    <p class="item-info-data"><?= htmlspecialchars($item["item_name"]) ?></p>
                </div>

                <div class="item-info-container-des">
                    <p class="item-info-title">Description</p>
                    <p class="item-info-data"><?= htmlspecialchars($item["item_details"]) ?></p>
                </div>
            </div>

            <!-- Category, Location Found, Date Found, and Time Found below -->
            <div class="item-info-container-row">
                <div class="item-info-tit-half">
                    <p class="item-info-title">Category</p>
                    <p class="item-info-data"><?= htmlspecialchars($item["category"]) ?></p>
                </div>
            </div>

            <div class="item-info-container-row">
                <div class="item-info-tit-half">
                    <p class="item-info-title">Location Found</p>
                    <p class="item-info-data"><?= htmlspecialchars($item["location_found"]) ?></p>
                </div>
            </div>

            <div class="item-info-container-row">
                <div class="item-info-tit-half">
                    <p class="item-info-title">Date Found</p>
                    <p class="item-info-data"><?= htmlspecialchars($item["date_found"]) ?></p>
                </div>
            </div>

            <div class="item-info-container-row">
                <div class="item-info-tit-half">
                    <p class="item-info-title">Time Found</p>
                    <p class="item-info-data"><?= htmlspecialchars($item["time_found"]) ?></p>
                </div>
            </div>

            <div class="btn-container">
                <a href="userview.php" class="btn btn-back">Back</a>
                <a href="login.php" class="btn btn-info">Claim</a>
            </div>
        </div>

        <div class="container">
            <?php if (!empty($item["picture"])): ?>
            <h2 class="item-title">Image</h2>
            <hr class="hr-container-second">
            <img src="<?= htmlspecialchars($item["picture"]) ?>" alt="Item Picture" class="item-image">
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
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
    <script>
    // Function to close the modal by ID
    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    // Function to open the modal by ID
    function openModal(modalId) {
        document.getElementById(modalId).style.display = 'block';
    }

    // Show success modal if report submission is successful
    <?php if (isset($_GET['success']) && $_GET['success'] == 'true') { ?>
    document.addEventListener('DOMContentLoaded', function() {
        openModal('successModal');

        // Remove 'success' query parameter from URL to prevent modal from showing again on refresh
        const url = new URL(window.location.href);
        url.searchParams.delete('success');
        window.history.replaceState({}, document.title, url.toString());
    });
    <?php } ?>

    // Show greeting modal only if logged in and no report was submitted
    <?php if (isset($_SESSION['user_id']) && !isset($_GET['success']) && !isset($_SESSION['greeting_shown'])) { ?>
    document.addEventListener('DOMContentLoaded', function() {
        openModal('greetingModal');
    });
    <?php } ?>

    // Function to close the modal by ID
    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    // Function to open the modal by ID
    function openModal(modalId) {
        document.getElementById(modalId).style.display = 'block';
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }
    </script>

</body>


</html>