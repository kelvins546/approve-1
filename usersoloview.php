<?php
// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start Session
session_start();

// Logout Handling
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

// Database Configuration
$servername = "localhost";
$username = "root";
$password = "";
$database = "approve";

// Establish Database Connection
$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// Check User Login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');

    die("Session user_id not set. Please log in.");
} else {
}


$user_id = $_SESSION['user_id'];

// Fetch Data from pending_found_reports
$sql_found = "SELECT id, item_name, category, location_found, date_found, time_found FROM pending_found_reports WHERE user_id = ?";
$stmt_found = $conn->prepare($sql_found);
$stmt_found->bind_param("s", $user_id);
$stmt_found->execute();
$result_found = $stmt_found->get_result();

// Fetch Data from pending_lost_reports
$sql_lost = "SELECT id, item_name, category, location_found, date_found, time_found FROM pending_lost_reports WHERE user_id = ?";
$stmt_lost = $conn->prepare($sql_lost);
$stmt_lost->bind_param("s", $user_id);
$stmt_lost->execute();
$result_lost = $stmt_lost->get_result();

// Ensure the 'table' and 'delete_id' are provided
if (isset($_POST['table']) && isset($_POST['delete_id'])) {
    $table = $_POST['table'];
    $delete_id = $_POST['delete_id'];

    echo "Table selected: " . $table . "<br>";

    // Validate table names to ensure security and avoid SQL injection
    $valid_tables = [
        'pending_found_reports',
        'pending_lost_reports',
        'approved_found_reports',
        'approved_lost_reports'
    ];

    // If the table is valid, proceed with the delete action
    if (in_array($table, $valid_tables)) {
        // Prepare the DELETE query based on the selected table
        $sql = "DELETE FROM $table WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $delete_id);

        // Execute the query and provide feedback
        if ($stmt->execute()) {
            echo "<script>
                    alert('great! the item has been marked as found.');
                    window.location.href = 'usersoloview.php'; // Redirect to the same page
                  </script>";
        } else {
            echo "<script>alert('Failed to cancel report');</script>";
        }

        $stmt->close();
    } else {
        echo "<script>alert('Invalid table selected');</script>";
    }
}

$userName = htmlspecialchars($_SESSION['name'] ?? 'User');

//start deleting here to remove the count on see details
if (!isset($_SESSION['user_id'])) {
    die("User ID not set. Please log in.");
}



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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['table'], $_POST['delete_id'])) {
        $table = $_POST['table'];
        $delete_id = $_POST['delete_id'];

        // Validate the table name to prevent SQL injection
        $valid_tables = ['approved_lost_reports', 'approved_found_reports', 'pending_lost_reports', 'pending_found_reports'];

        if (in_array($table, $valid_tables)) {
            $sql = "DELETE FROM $table WHERE id = ?";
            $stmt = $conn->prepare($sql);

            if ($stmt) {
                $stmt->bind_param("i", $delete_id);

                if ($stmt->execute()) {
                    echo "<script>
                            alert('great! the item has been marked as found.');
                            window.location.href = 'usersoloview.php';
                          </script>";
                } else {
                    echo "<script>alert('Error deleting record: " . $stmt->error . "');</script>";
                }
                $stmt->close();
            } else {
                echo "<script>alert('Failed to prepare query.');</script>";
            }
        } else {
            echo "<script>alert('Invalid table selected.');</script>";
        }
    } else {
        echo "<script>alert('Invalid request.');</script>";
    }
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



?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Submitted Reports</title>
    <style>
    .container-wraping {
        display: flex;
        gap: 20px;
    }

    .title-cont {
        background-color: #fff;
        padding: 40px;
        margin-bottom: 20px;
    }

    .title-cont h2 {
        font-weight: normal;
    }

    .container {
        flex: 1;
        background-color: transparent;
        padding: 15px;
        margin-top: 10px;
        margin: 0 20px;
    }

    .container h2 {
        text-align: center;
        color: #333;
    }

    /* Grid layout for the report boxes */
    .reports-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        /* Two columns */
        gap: 15px;
        /* Spacing between boxes */
    }

    .report-box {
        background-color: #fff;
        border: 1px solid #ddd;
        border-radius: 2px;
        padding: 15px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);

    }

    .report-box2 {
        background-color: #fff;
        border: 1px solid #ddd;
        border-radius: 5px;
        padding: 15px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        margin-top: 15px;
    }

    .report-status {
        font-size: 14px;
        font-weight: bold;
        text-align: center;
        padding: 5px;
        border-radius: 3px;
        margin-bottom: 10px;
    }

    /* Pending Status Style */
    /* Unclaimed Status Style */
    .report-status.pending {
        background-color: #ffc107;
        /* Yellow */
        color: #856404;
        font-style: italic;
        font-size: 11px;
        /* Dark yellow text */
    }

    .report-status.on.user {
        background-color: #ffc107;
        /* Yellow */
        color: #856404;
        font-style: italic;
        font-size: 11px;
        /* Dark yellow text */
    }

    .report-status.on.staff {
        background-color: #28a745;
        /* Green */
        color: #ffffff;
        font-style: italic;
        font-size: 11px;

        /* White text */
    }

    /* Claimed Status Style */
    .report-status.approved {
        background-color: #28a745;
        /* Green */
        color: #ffffff;
        font-style: italic;
        font-size: 11px;

        /* White text */
    }



    .report-details {
        font-size: 14px;
        line-height: 1.5;
        color: #333;
    }

    .report-buttons {
        display: flex;
        justify-content: flex-start;
        margin-top: 10px;
    }

    .report-buttons a:first-child {
        margin-right: auto;
        color: #545454;
    }

    .report-buttons a:nth-child(2) {
        margin-right: 5px;
        background-color: #bfbdbc;
        border: 1px solid #545454;
        color: #545454;
    }

    .report-buttons a:nth-child(3) {

        background-color: #fab7b0;
        border: 1px solid #545454;
        color: #545454;
    }

    .btn-action {
        padding: 5px 10px;
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 3px;
        cursor: pointer;
        text-decoration: none;
        font-size: 12px;
    }

    .btn-action:first-child {
        background-color: transparent;
        /* Transparent background */
        color: #007bff;
        /* Blue color */
        font-style: italic;
        /* Italicized text */
        text-decoration: underline;
        /* Underlined text */
        border: none;
        /* Remove borders */
        padding: 5px 0;
        /* Adjust padding for a minimal look */
        cursor: pointer;
    }

    .btn-action:first-child:hover {

        filter: brightness(0.8);
        /* Darken the button */
        transition: filter 0.3s ease;
        /* Smooth transition */
    }


    .btn-action:hover {
        filter: brightness(0.8);
        /* Darken the button */
        transition: filter 0.3s ease;
        /* Smooth transition */
    }

    .btn-cancel {


        font-weight: normal;

        background-color: #28a745;
        color: #fff;

    }

    .btn-cancel:hover {
        filter: brightness(0.8);
        /* Darken the button */
        transition: filter 0.3s ease;
        /* Smooth transition */
    }

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

        background-image: url('images/PNG\ GREEN\ BG.png');
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
        align-items: center;
        /* Center items vertically */
        justify-content: space-between;
        /* Distribute space between items */
    }

    /* Navigation main container */
    .nav-main {
        display: flex;
        align-items: center;
        /* Center items vertically */
        gap: 20px;
    }

    .nav-content {
        background-image: url('images/f1.png');
        background-size: cover;
        background-position: center center;
        background-attachment: fixed;
        background-repeat: no-repeat;
        padding: 60px 0;
    }

    .nav-content-cont {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        margin-left: 70px;

    }

    .nav-main {
        display: flex;
        align-items: center;
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

    .icon-btn {
        background-color: #f4f5f6;
        border: 2px solid #ff7701;
        border-radius: 50%;
        cursor: pointer;
        padding: 3px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-left: 20px;
        /* Adjust this value as needed */
        transition: background-color 0.3s ease, border-color 0.3s ease;
        z-index: 99999;
        position: relative;
        /* Enable relative positioning */
        right: -100px;
    }


    .icon-btn {
        z-index: 99999;
        margin-left: auto;
        width: 40px;
        /* Set to desired size */
        height: 40px;
    }




    .nav-main>.icon-btn:hover {
        background-color: #f4f4f9;
        /* Light background on hover */
        border-color: #e66a00;
        /* Darker border on hover */
    }



    .nav-main>.icon-btn:hover .user-icon {
        color: #e66a00;
        /* Darker icon color on hover */
    }

    .user-icon {
        font-size: 24px;
        /* Icon size */
        color: #ff7701 !important;
        transition: color 0.3s ease;
        /* Smooth color change on hover */
    }

    .user-icon:hover {
        color: #e66a00;
        /* Darken color on hover */
    }

    .navbar-links {
        margin-left: 100px;
        margin-right: 90px;
    }

    .navbar-links a {
        color: #545454;
        padding: 3px;
        text-decoration: none;
        margin: 20px;
        display: inline-block;

    }

    .navbar-links a:hover {
        text-decoration: underline;
    }

    .navbar-logo {
        height: 90px;
        width: auto;
        margin-right: 0px;
        margin-left: 30px;
        margin-top: 0;
    }

    .navbar-text {
        font-family: "Times New Roman", Times, serif;
        font-size: 36px;
        font-weight: bold;
        white-space: nowrap;
        color: #000 !important;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);

    }

    .LAFh1 {
        font-family: "League Spartan", sans-serif;
        font-optical-sizing: auto;
        font-weight: bold;
    }

    .nav-title h1 {
        font-size: 78px;
        color: #f6efe0;
        font-style: italic;
        font-weight: bold;
        line-height: 1.1;
        width: 700px;
        font-family: 'Hanken Grotesk', Arial, sans-serif;


    }

    .nav-text p {
        font-size: 16px;
        color: #fff;
        line-height: 1.4;
        margin-bottom: 20px;

    }

    /* Dropdown Content */
    .dropdown-content1 {
        display: none;
        position: absolute;
        background-color: #fff;
        min-width: 180px;
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        z-index: 1;
        margin-top: 0;
        border-radius: 4px;
        left: 0 !important;
        right: auto;

    }



    /* Show Dropdown Content on Hover */
    .dropdown:hover .dropdown-content1 {
        display: block;
    }

    /* Dropdown Links */
    .dropdown-content1 a {
        padding: 5px 5px;
        text-decoration: none;
        display: block;
        color: #333;
        /* Link text color */
        transition: background-color 0.3s ease;
    }

    /* Dropdown Links Hover Effect */
    .dropdown-content1 a:hover {
        background-color: #ccc;
        /* Darker hover background color */
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
        margin-top: 100px;
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
        margin-top: 10px;
    }

    .footer-logo img {
        max-width: 70px;
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

<body>
    <div class="navbar">
        <div class="nav-main">
            <img src="images/logo.png" alt="Logo" class="navbar-logo">
            <span class="navbar-text">UNIVERSITY OF CALOOCAN CITY</span>
            <div class="navbar-links">
                <a href="found_report.php">Home</a>
                <a href="guidelines.php">Guidelines</a>
                <div class="dropdown">
                    <a href="userview.php">Browse Reports</a>
                    <div class="dropdown-content1">

                    </div>
                </div>
            </div>
            <!-- Move the icon button inside nav-main -->
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



    <div class="container-wraping">
        <!-- Found Reports Container -->
        <div class="container">

            <div class="title-cont">
                <h2><strong>FOUND </strong>REPORTS</h2>
            </div>
            <div class="reports-grid">
                <!-- Pending Found Reports -->

                <?php
                $sql_pending_found = "
            SELECT id, item_name, category, location_found, date_found, time_found, position 
            FROM pending_found_reports
            ORDER BY date_found DESC";
                $result_pending_found = $conn->query($sql_pending_found);

                if (!$result_pending_found) {
                    die("Error in Pending Found Reports Query: " . $conn->error);
                }

                while ($row = $result_pending_found->fetch_assoc()):
                ?>
                <div class="report-box">
                    <!-- Status Label -->
                    <div class="report-status <?= strtolower($row['position']) ?>">
                        <?= htmlspecialchars($row['position']) ?>
                    </div>

                    <!-- Report Details -->
                    <div class="report-details">
                        <strong>Item Found:</strong> <?= htmlspecialchars($row['item_name']) ?><br>
                        <strong>Category:</strong> <?= htmlspecialchars($row['category']) ?><br>
                        <strong>Location:</strong> <?= htmlspecialchars($row['location_found']) ?><br>
                        <strong>Date & Time:</strong> <?= htmlspecialchars($row['date_found']) ?>,
                        <?= htmlspecialchars($row['time_found']) ?>
                    </div>

                    <!-- Report Buttons -->
                    <div class="report-buttons">
                        <a href="usersolofounddetails.php?report_id=<?= $row['id'] ?>" class="btn-action">View Item</a>
                        <form method="get" action="usersolofounddetails.php">
                            <input type="hidden" name="action" value="edit">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($row['id']); ?>">
                            <input type="hidden" name="table" value="pending_found_reports">

                        </form>

                    </div>
                </div>
                <?php endwhile; ?>
            </div>

            <div class="reports-grid">
                <!-- Approved Found Reports -->

                <?php
                $sql_approved_found = "
            SELECT id, item_name, category, location_found, date_found, time_found, position 
            FROM approved_found_reports
            ORDER BY date_found DESC";
                $result_approved_found = $conn->query($sql_approved_found);

                if (!$result_approved_found) {
                    die("Error in Approved Found Reports Query: " . $conn->error);
                }

                while ($row = $result_approved_found->fetch_assoc()):
                ?>
                <div class="report-box2">
                    <!-- Status Label -->
                    <div class="report-status <?= strtolower($row['position']) ?>">
                        <?= htmlspecialchars($row['position']) ?>
                    </div>

                    <!-- Report Details -->
                    <div class="report-details">
                        <strong>Item Found:</strong> <?= htmlspecialchars($row['item_name']) ?><br>
                        <strong>Category:</strong> <?= htmlspecialchars($row['category']) ?><br>
                        <strong>Location:</strong> <?= htmlspecialchars($row['location_found']) ?><br>
                        <strong>Date & Time:</strong> <?= htmlspecialchars($row['date_found']) ?>,
                        <?= htmlspecialchars($row['time_found']) ?>
                    </div>

                    <!-- Report Buttons -->
                    <div class="report-buttons">
                        <a href="userfounddetailsaapprove.php?report_id=<?= $row['id'] ?>" class="btn-action">View
                            Item</a>
                        <form method="get" action="userfounddetailsaapprove.php">
                            <input type="hidden" name="action" value="edit">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($row['id']); ?>">
                            <input type="hidden" name="table" value="approved_found_reports">

                        </form>

                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Lost Reports Container -->
        <div class="container">
            <div class="title-cont">
                <h2><strong>LOST </strong>REPORTS</h2>
            </div>
            <div class="reports-grid">
                <!-- Pending Lost Reports -->

                <?php
                $sql_pending_lost = "
            SELECT id, item_name, category, location_found, date_found, time_found, position 
            FROM pending_lost_reports
            ORDER BY date_found DESC";
                $result_pending_lost = $conn->query($sql_pending_lost);

                if (!$result_pending_lost) {
                    die("Error in Pending Lost Reports Query: " . $conn->error);
                }

                while ($row = $result_pending_lost->fetch_assoc()):
                ?>
                <div class="report-box">
                    <!-- Status Label -->
                    <div class="report-status <?= strtolower($row['position']) ?>">
                        <?= htmlspecialchars($row['position']) ?>
                    </div>

                    <!-- Report Details -->
                    <div class="report-details">
                        <strong>Item Lost:</strong> <?= htmlspecialchars($row['item_name']) ?><br>
                        <strong>Category:</strong> <?= htmlspecialchars($row['category']) ?><br>
                        <strong>Location:</strong> <?= htmlspecialchars($row['location_found']) ?><br>
                        <strong>Date & Time:</strong> <?= htmlspecialchars($row['date_found']) ?>,
                        <?= htmlspecialchars($row['time_found']) ?>
                    </div>

                    <!-- Report Buttons -->
                    <div class="report-buttons">
                        <a href="usersololostdetails.php?report_id=<?= $row['id'] ?>" class="btn-action">View Item</a>
                        <form method="get" action="usersolofounddetails.php">
                            <input type="hidden" name="action" value="edit">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($row['id']); ?>">
                            <input type="hidden" name="table" value="pending_lost_reports">

                        </form>
                        <form method="post" action="usersoloview.php">
                            <input type="hidden" name="table" value="pending_lost_reports">
                            <input type="hidden" name="delete_id" value="<?= $row['id']; ?>">
                            <button type="submit" class="btn-action btn-cancel">Mark as found</button>
                        </form>

                    </div>
                </div>
                <?php endwhile; ?>
            </div>

            <div class="reports-grid">
                <!-- Approved Lost Reports -->

                <?php
                $sql_approved_lost = "
            SELECT id, item_name, category, location_found, date_found, time_found, position 
            FROM approved_lost_reports
            ORDER BY date_found DESC";
                $result_approved_lost = $conn->query($sql_approved_lost);

                if (!$result_approved_lost) {
                    die("Error in Approved Lost Reports Query: " . $conn->error);
                }

                while ($row = $result_approved_lost->fetch_assoc()):
                ?>
                <div class="report-box2">
                    <!-- Status Label -->
                    <div class="report-status <?= strtolower($row['position']) ?>">
                        <?= htmlspecialchars($row['position']) ?>
                    </div>

                    <!-- Report Details -->
                    <div class="report-details">
                        <strong>Item Lost:</strong> <?= htmlspecialchars($row['item_name']) ?><br>
                        <strong>Category:</strong> <?= htmlspecialchars($row['category']) ?><br>
                        <strong>Location:</strong> <?= htmlspecialchars($row['location_found']) ?><br>
                        <strong>Date & Time:</strong> <?= htmlspecialchars($row['date_found']) ?>,
                        <?= htmlspecialchars($row['time_found']) ?>
                    </div>

                    <!-- Report Buttons -->
                    <div class="report-buttons">
                        <a href="userlostdetailsaapprove.php?report_id=<?= $row['id'] ?>" class="btn-action">View
                            Item</a>
                        <form method="get" action="usersolofounddetails.php">
                            <input type="hidden" name="action" value="edit">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($row['id']); ?>">
                            <input type="hidden" name="table" value="approved_lost_reports">

                        </form>
                        <form method="post" action="usersoloview.php">
                            <input type="hidden" name="table" value="approved_lost_reports">
                            <input type="hidden" name="delete_id" value="<?= $row['id']; ?>">
                            <button type="submit" class="btn-action btn-cancel">I FOUND ALREADY</button>
                        </form>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-logo">
                <img src="images/logo.png" alt="Logo" />
                <img src="images/caloocan.png" alt="Logo" />
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
                    universityofcaloocan@gmailcom</p>
            </div>
            <hr class="footer-separator">
            <p class="footer-text">&copy; University of Caloocan City, All rights reserved.</p>
        </div>
    </footer>


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
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>

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