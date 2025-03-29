<?php
// Error Reportin
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start Session
session_start();




// Check if logout is requested
if (isset($_GET['logout'])) {
    // Destroy session
    session_unset();
    session_destroy();

    // Redirect to login page
    header("Location: login.php");
    exit;
}

// Database Configuration
$servername = "localhost";
$username = "root";
$password = "";
$database = "approve";

// Establish Connection
$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// Initialize Message
$message = '';

// Redirect to login if user not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
// Form Submission Handling
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $category = $_POST['category'] ?? '';
    $other_category = $_POST['other_category'] ?? ''; // Get the "Other" category if provided

    // If "Other" is selected, use the custom category
    if ($category === 'Other' && !empty($other_category)) {
        $category = $other_category;
    }

    // Proceed with the rest of your form processing...
}

// Form Submission Handling
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $item_name = $conn->real_escape_string($_POST['item_name'] ?? '');
    $category = $conn->real_escape_string($_POST['category'] ?? '');
    $item_details = $conn->real_escape_string($_POST['description'] ?? '');
    $location_found = $conn->real_escape_string($_POST['location_found'] ?? '');
    $date_found = $_POST['date_found'] ?? '';
    $time_found = $_POST['time_found'] ?? '';
    $picture = null;

    // Handle additional fields
    $brand = $conn->real_escape_string($_POST['brand'] ?? '');
    $primary_color = $conn->real_escape_string($_POST['primary_color'] ?? '');
    $description = $conn->real_escape_string($_POST['description'] ?? '');
    // If "Other" is selected, override the category with the "other_category" field value
    if ($category === 'Other' && !empty($other_category)) {
        $category = $other_category;
    }

    // Comment out or remove validation for now
    if (empty($item_name) || empty($category) || empty($location_found) || empty($date_found) || empty($time_found)) {
        $message = "<div class='alert alert-danger'>Please fill out all required fields.</div>";
    } else {
        // File Upload
        if (!empty($_FILES['picture']['tmp_name'])) {
            if ($_FILES['picture']['error'] === UPLOAD_ERR_OK) {
                $fileType = mime_content_type($_FILES['picture']['tmp_name']);
                if (strpos($fileType, 'image/') === 0) {
                    $targetDir = "uploads/pending/";
                    $uniqueName = uniqid() . "_" . basename($_FILES["picture"]["name"]);
                    $targetFile = $targetDir . $uniqueName;
                    if (move_uploaded_file($_FILES['picture']['tmp_name'], $targetFile)) {
                        $picture = $conn->real_escape_string($targetFile);
                    } else {
                        $message = "<div class='alert alert-danger'>File upload failed. Please try again.</div>";
                    }
                } else {
                    $message = "<div class='alert alert-danger'>Only image files are allowed.</div>";
                }
            } else {
                $message = "<div class='alert alert-danger'>File upload error. Code: " . $_FILES['picture']['error'] . "</div>";
            }
        }
    }

    // SQL Query
    $sql = "INSERT INTO pending_found_reports 
            (user_id, item_name, category, description, brand, primary_color, picture, location_found, date_found, time_found, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Unclaimed')";

    // Prepare statement
    $stmt = $conn->prepare($sql);

    // Check if prepare() fails
    if ($stmt === false) {
        error_log("SQL Error: " . $conn->error . " - Query: " . $sql); // Log the SQL error and query
        $message = "<div class='alert alert-danger'>SQL Error: " . $conn->error . "</div>";
    } else {
        // Bind Parameters
        $stmt->bind_param(
            "isssssssss",  // 10 placeholders (adjust as needed for your database types)
            $user_id,
            $item_name,
            $category,
            $item_details,
            $brand,
            $primary_color,
            $picture,
            $location_found,
            $date_found,
            $time_found
        );

        // Execute Query for the report submission
        if ($stmt->execute()) {
            $_SESSION['message'] = 'report submitted successfully!'; // Success flag if needed
            header('Location: found_report.php?success=true'); // Redirect to trigger success modal
            exit(); // Prevent further execution after redirect
        } else {
            error_log('SQL Error during execute: ' . $stmt->error);
        }
    }
}





// Get user name for greeting
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




if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// Get the current script name
$currentPage = basename($_SERVER['PHP_SELF']);

// Set the button label based on the current page
$buttonLabel = ($currentPage === 'found_report.php') ? 'I found an item' : (($currentPage === 'lost_report.php') ? 'I lost an item' : 'Report');

$conn->close();
?>





<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Found Item Report</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:ital,wght@0,100..900;1,100..900&family=Londrina+Outline&family=Open+Sans:ital,wght@0,300..800;1,300..800&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Rubik+80s+Fade&family=Rubik+Burned&family=Source+Code+Pro:ital,wght@0,200..900;1,200..900&display=swap"
        rel="stylesheet">
    <style>
    @import url('https://fonts.googleapis.com/css2?family=League+Spartan:wght@100..900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap');

    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
        font-family: 'Hanken Grotesk', Arial, sans-serif;
    }

    body {
        background-color: #fff;
        margin: 0;
        padding: 0;
        overflow-x: hidden;
        display: flex;
        color: #545454;
        flex-direction: column;
        min-height: 100vh;
        background-color: #2b4257;
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
        border: 2px solid #000;
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
        color: #545454;
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

    ul {
        margin-left: 30px;
        margin-top: 5px;
        font-size: 14px;
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

    .modal-content {
        background-color: #fefefe;
        padding: 30px;
        color: #545454;
        border-radius: 10px;
        border: 1px solid #888;
        width: 400px;
        max-width: 100%;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        animation: fadeIn 0.3s ease-out;
        margin-bottom: 0px;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
    }

    .modal-content-greet {
        background-color: #fefefe;
        padding: 30px;
        color: #545454;
        border-radius: 10px;
        border: 1px solid #888;
        width: 340px;
        max-width: 100%;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        animation: fadeIn 0.3s ease-out;
        margin-bottom: 0px;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
    }

    .modal-overlay.show {
        display: block;
        animation: fadeIn 2.5s ease-out;
    }

    .modal-overlay.hide {
        animation: fadeOut 2.5s ease-in;
        animation-fill-mode: forwards;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    @keyframes fadeOut {
        from {
            opacity: 1;
        }

        to {
            opacity: 0;
        }
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



    .modal-title {
        display: inline;
        text-align: center;
    }

    .modal-title h3 {
        margin-bottom: 10px;
        font-size: 22px;
    }

    .modal-title p {
        margin-bottom: 15px;
    }

    .modal-cont {
        font-size: 14px;
    }

    .modal-ques {
        margin-bottom: 5px;
        margin-top: 25px;
    }

    @keyframes fadeIn {
        0% {
            opacity: 0;
        }

        100% {
            opacity: 1;
        }
    }

    .italic {
        font-style: italic;
        color: #545454;
    }

    .button-container {
        display: flex;
        justify-content: center;
        align-items: center;
        width: 100%;
        margin-top: 20px;
    }

    .btn-ok {
        padding: 5px 40px;
        background-color: #4CAF50;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
    }

    .btn-ok:hover {
        background-color: #45a049;
    }

    /* Dropdown Container */
    .dropdown {
        position: relative;
        display: inline-block;
        margin-bottom: 10px;
        z-index: 1;
    }

    /* Dropdown Button */
    .dropdown-btn {
        padding: 5px 20px;
        background-color: #ff7701;
        color: #fff;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        font-size: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background-color 0.3s ease;
    }

    /* Dropdown Arrow */
    .dropdown-btn::after {
        content: '';
        width: 0;
        height: 0;
        border-top: 5px solid transparent;
        border-bottom: 5px solid transparent;
        border-left: 5px solid #fff;

        margin-left: 10px;
        transition: transform 0.3s ease;
        transform: rotate(270deg);
    }

    /* Dropdown Content */
    .dropdown-content {
        display: none;
        position: absolute;
        background-color: #ff7701;
        min-width: 180px;
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        z-index: 1;
        margin-top: 0;
        border-radius: 4px;
        left: 0 !important;
        right: auto;

    }



    /* Show Dropdown Content on Hover */
    .dropdown:hover .dropdown-content {
        display: block;
    }

    /* Dropdown Links */
    .dropdown-content a {
        padding: 10px 16px;
        text-decoration: none;
        display: block;
        color: #fff;
        /* Link text color */
        transition: background-color 0.3s ease;
    }

    /* Dropdown Links Hover Effect */
    .dropdown-content a:hover {
        background-color: #e66a00;
        /* Darker hover background color */
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



    /* Container styling */
    .container {
        max-width: 100%;
        width: 80%;
        margin: 1px;
        background-color: #ffffff;
        padding: 40px 40px;
        border-radius: 2px;
        box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        box-sizing: border-box;
        padding-top: 20px;
    }

    .container-wrapper {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        margin-top: 0;
        /* Remove unnecessary margin */
        margin-bottom: 0;
        /* Remove unnecessary margin */
        background-color: #FAF9F6;
    }


    /* Title container styling */
    .container-title {
        display: flex;
        align-items: flex-end;
        /* Vertically center the items */
        justify-content: flex-start;
        /* Ensure the items are aligned to the left */
    }

    .container-title h2 {
        margin: 0;
        /* Remove default margin */
        font-size: 24px;
        color: #333;
        line-height: 1.2;
        /* Ensure line height is consistent */
    }

    .container-title p {
        margin: 0;
        /* Remove default margin */
        font-size: 13px;
        color: #777;
        margin-left: 10px;
        /* Move the <p> element a little closer to the <h2> */
        line-height: 1.6;
        /* Set line height to match h2 */
        display: inline-block;
        /* Ensure <p> aligns inline with <h2> */
        vertical-align: middle;
        /* Align vertically to the middle of <h2> */
    }


    hr {
        margin-bottom: 20px;
        margin-top: 10px;
    }

    /* Alert styles */
    .alert {
        padding: 10px;
        margin-bottom: 20px;
        border-radius: 5px;
        text-align: center;
        font-size: 14px;
    }

    .alert-success {
        background-color: #d4edda;
        color: #155724;
    }

    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
    }

    /* Form input styles */
    .form-group {

        margin-bottom: 15px;
        flex: 1;
    }

    .form-group1 {

        margin-bottom: 15px;
        flex: 1;
        display: flex;
        justify-content: space-evenly;
        width: 250px;
    }



    .back-btn {
        margin-left: auto;
        /* pushes back button to the left */


        margin-bottom: 30px;



    }

    .form-group p {
        font-size: 13px;
        color: #777;
        margin-top: 5px;
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

    /* Flexbox layout for form rows */
    .form-row {
        display: flex;
        justify-content: space-between;
        gap: 4%;
    }

    .form-row .form-group {
        width: 48%;
        /* Set width to 48% for each field */
    }

    /* Flexbox for description, image, and submit button */
    .form-row-submit {
        display: flex;
        justify-content: space-between;
        gap: 4%;
        align-items: flex-end;
        /* Align items to the top */
    }

    .form-row-submit .form-group {
        width: 48%;
        /* Description and Image each takes 48% */
    }

    /* Submit button styling */
    .btn {
        display: block;
        align-items: center;
        background-color: #545454;
        color: #fff;
        border: 2px solid #545454;
        margin-left: auto;
        margin-right: 0;
        border-radius: 4px;
        text-align: center;
        cursor: pointer;
        width: 180px;
        height: 35px;
        font-size: 14px;
        transition: background-color 0.3s ease;
    }

    .btn:hover {
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

       /* Footer */
       .footer {
        background-color: #fff;
        padding: 20px 0;
        margin-top: 60px;
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
        margin-top: 15px;
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
</head>


<body>
    <div class="navbar">
        <div class="nav-main">
            <img src="images/logo.png" alt="Logo" class="navbar-logo">
            <span class="navbar-text">UNIVERSITY OF CALOOCAN CITY</span>
            <div class="navbar-links">
                <a href="found_report.php">Home</a>
                <a href="guidelines.php">Guidelines</a>
                <div class="dropdown">
                    <button class="nav-btn">Browse Reports</button>
                    <div class="dropdown-content1">
                        <a href="userview.php">Found Reports</a>
                        <a href="lost_reports.php">Lost Reports</a>
                    </div>
                </div>
            </div>
            <!-- Move the icon button inside nav-main -->
            <button class="icon-btn" onclick="openModal('loginclickmodal')">
                <ion-icon name="person" class="user-icon"></ion-icon>
            </button>
        </div>
    </div>

    <div class="nav-content">
        <div class="nav-content-cont">
            <div class="nav-title">
                <h1 class="LAFh1">LOST AND FOUND HELP DESKS</h1>
            </div>
            <div class="nav-text">
                <p>We are located at the main entrance right beside the Guard house</p>
            </div>

            <!-- Dropdown button for "Report Found" or "Report Lost" -->
            <div class="dropdown">
                <button class="dropdown-btn" aria-haspopup="true" aria-expanded="false">
                    <?php echo htmlspecialchars($buttonLabel); ?>
                </button>
                <div class="dropdown-content" role="menu">
                    <a href="found_report.php" role="menuitem">I found an Item</a>
                    <a href="lost_report.php" role="menuitem">I lost an Item</a>

                </div>
            </div>

        </div>
    </div>
    <!-- Success Modal -->
    <!-- Success Modal -->
    <div id="successModal" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <div class="modal-title">
                <h3>Report Submitted!</h3>
                <p>We appreciate your help in reuniting this item with its owner.</p>
            </div>
            <strong>
                <p>Next Steps: <a href="Guidelines.php" class="italic">(See Guidelines)</a></p>
            </strong>
            <ul>
                <li><strong>Our team will catalog the item</strong> and add it to our Lost & Found inventory.</li>
                <li><strong>Item Storage: </strong>The found item will be securely stored until it is claimed or further
                    arrangements are made.</li>
            </ul>
            <p class="modal-ques"><strong>Questions or Updates?</strong></p>
            <p class="modal-cont">If you have any questions or need to provide more details about the item, please reach
                out to us
                at 89-9999 or visit our Lost & Found Help Desk</p>

            <div class="button-container">
                <button class="btn-ok" onclick="closeModal('successModal')">OKAY</button>
            </div>
        </div>
    </div>

    <!-- Greeting Modal -->
    <div id="greetingModal" class="modal-overlay" style="display: none;">
        <div class="modal-content-greet">
            <div class="modal-title">
                <h3>Great to meet you, <strong><?php echo htmlspecialchars($userName); ?></strong>!</h3>
                <hr>
                <p>Report lost or found items, and we’ll help reconnect you with
                    your belongings.</p>
                <p>We’re excited to have you with us!</p>
            </div>

            <div class="button-container">
                <button class="btn-ok" onclick="closeModal('greetingModal')">LET'S GO</button>
            </div>
        </div>
    </div>


    <!-- Login Click Modal -->
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

                <button class="btn-ok2" onclick="window.location.href='?logout'">LOG OUT</button>
            </div>
        </div>
    </div>

    <div class="container-wrapper">
        <div class="container">
            <div class="container-title">
                <h2>Did you FIND an item?</h2>
                <p>Please provide details to help reunite the item with its owner.</p>
            </div>
            <hr>
            <?php if ($message): ?>
            <div class="alert <?= strpos($message, 'alert-danger') !== false ? 'alert-danger' : 'alert-success' ?>">
                <?= $message; ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data">
                <!-- Step Navigation 
                <div class="step-navigation">
                    <button type="button" class="btn" onclick="showStep(1)">Step 1</button>
                    <button type="button" class="btn" onclick="showStep(2)" disabled>Step 2</button>
                    <button type="button" class="btn" onclick="showStep(3)" disabled>Step 3</button>
                </div>
-->
                <!-- Step 1: Item Information -->
                <div class="step" id="step1">
                    <h3>Step 1: Item Information</h3>
                    <div class="form-group">
                        <label for="item_name">Object Title</label>
                        <input type="text" name="item_name" id="item_name" required class="form-control">
                        <p>eg. lost camera, gold ring, toyota car key</p>
                    </div>
                    <div class="form-group">
                        <label for="date_found">Date Found</label>
                        <input type="date" name="date_found" id="date_found" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="time_found">Time Found</label>
                        <input type="time" name="time_found" id="time_found" required class="form-control">
                    </div>
                    <div class="form-group">
                        <button type="button" class="btn" onclick="showStep(2)">Next</button>
                    </div>
                </div>

                <!-- Step 2: Item Details (Hidden by Default) -->
                <div class="step" id="step2" style="display: none;">
                    <h3>Step 2: Item Details</h3>
                    <div class="form-group">
                        <label for="category">Category</label>
                        <select name="category" id="category" required class="form-control">
                            <option value="Electronics & Gadgets">Electronics & Gadgets</option>
                            <option value="Jewelry & Accessories">Jewelry & Accessories</option>
                            <option value="Identification & Documents">Identification & Documents</option>
                            <option value="Clothing & Footwear">Clothing & Footwear</option>
                            <option value="Bag & Carriers">Bag & Carriers</option>
                            <option value="Wallet & Money">Wallet & Money</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="brand">Brand</label>
                        <input type="text" name="brand" id="brand" class="form-control">
                        <p>(Ralph Lauren, Samsung, KitchenAid, etc.)</p>
                    </div>
                    <div class="form-group">
                        <label for="location_found">Location Found</label>
                        <select name="location_found" id="location_found" required class="form-control">
                            <option value="Main Entrance">Main Entrance</option>
                            <option value="Courtyard">Courtyard</option>
                            <option value="Canteen">Canteen</option>
                            <option value="Social Hall">Social Hall</option>
                            <option value="First Floor Hallway">First Floor Hallway</option>
                            <option value="Second Floor Hallway">Second Floor Hallway</option>
                            <option value="Third Floor Hallway">Third Floor Hallway</option>
                            <option value="Fourth Floor Hallway">Fourth Floor Hallway</option>
                            <option value="Parking Area">Parking Area</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="primary_color">Primary Color</label>
                        <input type="text" name="primary_color" id="primary_color" class="form-control">
                        <p>Please add the color that best represents the found item (Black, Red, Blue, etc.)</p>
                    </div>
                    <div class="form-group">
                        <label for="picture">Image</label>
                        <input type="file" name="picture" id="picture" class="form-control" accept="image/*">
                    </div>
                    <div class="form-group1">
                        <button type="button" class="btn back-btn" onclick="showStep(1)">Back</button>
                        <button type="button" class="btn" onclick="showStep(3)">Next</button>
                    </div>
                </div>

                <!-- Step 3: Description (Hidden by Default) -->
                <div class="step" id="step3" style="display: none;">
                    <h3>Step 3: Description</h3>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea name="description" id="description" rows="4" class="form-control"></textarea>
                    </div>
                    <div class="form-group1">
                        <button type="button" class="btn back-btn" onclick="showStep(2)">Back</button>
                        <button type="submit" class="btn">Submit</button>
                    </div>

                </div>
            </form>
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
    function showStep(step) {
        document.querySelectorAll('.step').forEach(s => s.style.display = 'none');
        document.getElementById('step' + step).style.display = 'block';
        document.querySelector('form').addEventListener('submit', function() {
            document.querySelectorAll('.step').forEach(s => s.style.display = 'block');
        });

    }
    </script>


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
    // Show the "Other" category input field when "Other" is selected
    function showOtherField() {
        var category = document.getElementById("category").value;
        var otherCategoryField = document.getElementById("otherCategoryField");

        // Display input field if "Other" is selected
        if (category === "Other") {
            otherCategoryField.style.display = "block"; // Show the input field
        } else {
            otherCategoryField.style.display = "none"; // Hide the input field
        }
    }

    // Trigger the function initially to handle any pre-selected value
    window.onload = function() {
        showOtherField();
        s
    }
    </script>



    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>

</html>