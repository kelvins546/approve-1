<?php
// Start session to check if user is logged in
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$database = "approve";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


// check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
// Initialize search
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// Set default limit and page
$limit = isset($_POST['entry_limit']) ? (int)$_POST['entry_limit'] : 5;
if ($limit === 'all') {
    $limit = 10000;  // Show all entries if 'all' is selected
}

// Get current page number
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Build the SQL query for counting the total number of entries
$sqlCount = "SELECT COUNT(*) AS total FROM pending_found_reports WHERE status='unclaimed'";
if (!empty($search)) {
    $sqlCount .= " AND (item_name LIKE '%$search%' OR location_found LIKE '%$search%' OR category LIKE '%$search%' OR user_id LIKE '%$search%')";
}

// Get the total number of results
$resultCount = $conn->query($sqlCount);
$totalRecords = 0;
if ($resultCount) {
    $totalRecords = $resultCount->fetch_assoc()['total'];
}

// Build the SQL query for fetching reports with limit and search functionality
$sql = "SELECT * FROM pending_found_reports 
        UNION ALL 
        SELECT * FROM approved_found_reports";



if (!empty($search)) {
    $sql .= " AND (item_name LIKE '%$search%' OR location_found LIKE '%$search%' OR category LIKE '%$search%' OR user_id LIKE '%$search%')";
}

$sql .= " LIMIT $offset, $limit"; // apply limit and offset to the query

// Execute the query
$result = $conn->query($sql);

// Calculate total pages for pagination
if ($limit == 10000) {
    // If "All" is selected, set total pages to 1
    $totalPages = 1;
} else {
    // Calculate total pages as usual
    $totalPages = ceil($totalRecords / $limit);
}

// Calculate the range for the current page
$currentEntriesStart = $offset + 1;  // First entry on this page
$currentEntriesEnd = min($offset + $limit, $totalRecords);  // Last entry on this page

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

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
// Get logged-in user's ID
$user_id = $_SESSION['user_id'];

$successMessage = ''; // Initialize success message
if (isset($_POST['claim'])) {
    $report_id = $_POST['report_id'];

    // fetch from both approved_found_reports and pending_found_reports
    $query = "SELECT * FROM approved_found_reports WHERE id = ? 
              UNION 
              SELECT * FROM pending_found_reports WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $report_id, $report_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $item = $result->fetch_assoc();

    if ($item) {
        // insert into pending_claim_reports
        $insert_query = "INSERT INTO pending_claim_reports 
                        (user_id, item_name, category, brand, primary_color, description, picture, location_found, date_found, time_found, status, position) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $position = "Pending";
        $status = $item['status'];

        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param(
            "ssssssssssss",
            $_SESSION['user_id'],
            $item['item_name'],
            $item['category'],
            $item['brand'],
            $item['primary_color'],
            $item['description'],
            $item['picture'],
            $item['location_found'],
            $item['date_found'],
            $item['time_found'],
            $status,
            $position
        );
        $insert_stmt->execute();

        // delete from approved_found_reports
        $delete_approved_query = "DELETE FROM approved_found_reports WHERE id = ?";
        $delete_approved_stmt = $conn->prepare($delete_approved_query);
        $delete_approved_stmt->bind_param("i", $report_id);
        $delete_approved_stmt->execute();

        // delete from pending_found_reports
        $delete_pending_query = "DELETE FROM pending_found_reports WHERE id = ?";
        $delete_pending_stmt = $conn->prepare($delete_pending_query);
        $delete_pending_stmt->bind_param("i", $report_id);
        $delete_pending_stmt->execute();

        $_SESSION['message'] = 'Your claim request for the item has been successfully submitted!';
        header("Location: userview.php?success=true");
        exit();
    } else {
        echo "Item not found.";
    }
}



?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Reports</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:ital,wght@0,100..900;1,100..900&family=Londrina+Outline&family=Open+Sans:ital,wght@0,300..800;1,300..800&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Rubik+80s+Fade&family=Rubik+Burned&family=Source+Code+Pro:ital,wght@0,200..900;1,200..900&display=swap"
        rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:ital,wght@0,100..900;1,100..900&family=League+Spartan:wght@100..900&family=Londrina+Outline&family=Open+Sans:ital,wght@0,300..800;1,300..800&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Rubik+80s+Fade&family=Rubik+Burned&family=Source+Code+Pro:ital,wght@0,200..900;1,200..900&display=swap"
        rel="stylesheet">

        
    <link rel="stylesheet" href="admin_report.css">

    
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Work+Sans:wght@300;400;600&display=swap"

        );

    /* General styles */
    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
        font-family: 'Hanken Grotesk', Arial, sans-serif;
    }

    body {

        margin: 0;
        padding: 0;
        overflow-x: hidden;
        display: flex;
        color: #545454;
        flex-direction: column;
        min-height: 100vh;
        background-image: url('images/blur\ brown.png');
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

    .modal-content1 {
        background: white;
        padding: 30px;
        color: #545454;
        border-radius: 10px;
        border: 1px solid #888;
        width: 400px;
        max-width: 100%;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        animation: fadeIn 0.3s ease-out;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
    }

    .modal-title1 {
        text-align: center;
    }

    .modal-title1 h3 {
        font-size: 22px;
        margin-bottom: 10px;
        font-weight: bold;
    }

    .modal-title1 p {
        margin-bottom: 15px;
        font-size: 14px;
        color: #666;
    }

    .confirmation-section {
        margin-bottom: 20px;
        padding: 15px;
        background: #f9f9f9;
        border-left: 4px solid #f39c12;
        border-radius: 5px;
    }

    .confirmation-section h3 {
        font-size: 18px;
        font-weight: bold;
        margin-bottom: 10px;
    }

    .confirmation-section p {
        font-size: 14px;
        line-height: 1.5;
        color: #333;
        margin-top: 5px;
    }

    .confirmation-form {
        text-align: left;
    }

    .form-group {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 20px;
    }

    .checkbox-label {
        cursor: pointer;
        font-size: 14px;
        color: #333;
    }

    .button-container {
        display: flex;
        justify-content: space-between;
        margin-top: 20px;
    }

    .btn-cancel {
        padding: 10px 20px;
        background-color: red;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
    }

    .btn-cancel:hover {
        background-color: darkred;
    }

    .btn-success {
        padding: 10px 20px;
        background-color: green;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
    }

    .btn-success:hover {
        background-color: darkgreen;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
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

    .modal-title {
        display: inline;
        text-align: center;
    }

    ul {
        margin-left: 30px;
        margin-top: 5px;
        font-size: 14px;
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

    .btn-ok12 {
        padding: 5px 40px;
        background-color: #545454;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        margin-left: 5px;
    }

    .btn-ok12:hover {
        background-color: #ccc;
    }

    /* Modal Content Styling */
    .modal-content12 {
        background-color: #fff;
        margin: 10% auto;
        /* Center vertically and horizontally */

        border: 1px solid #888;
        width: 40%;
        /* Adjust width as needed */
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        text-align: center;
        /* Center the text */
    }

    /* Header Styling */
    .modal-content12 h2 {
        font-size: 24px;
        margin-bottom: 20px;
        color: #333;
    }

    /* Form Group Styling */
    .form-group-tip {
        margin-bottom: 20px;
        text-align: left;
        /* Align label and textarea to the left */
    }

    .form-group-tip label {
        display: block;
        font-weight: bold;
        margin-bottom: 5px;
        padding-left: 10px;
        color: #555;
    }

    .form-group-tip textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 4px;
        resize: none;
        font-size: 16px;
        box-sizing: border-box;
        /* Ensure padding doesn't affect width */
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



    /* Table container styles */
    .table-container {
        width: 100%;
        overflow-x: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin: 0;
    }

    th,
    td {
        padding: 10px;
        text-align: center;
        border: 2px solid #545454;
    }

    th {

        color: #fff;
        padding-bottom: 15px !important;
        margin: 0;
        background-color: #584636;
    }

    tr:nth-child(even) {
        background-color: #fff;
    }

    /* Container styles */
    .container {
        max-width: 1240px;
        width: 100%;
        margin: 0px auto;
        background-color: #fff;
        padding: 0;
        border-radius: 0px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    .button-container button {
        margin: 5px;
        padding: 10px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }
    /* START Table container styles */


    /* start of search btn style */
    .hr-center {
        border: none;
        /* Removes the default border */
        border-top: 1px solid #fff;
        width: 20%;
        margin: 0 auto;
        padding-bottom: 20px;

    }

    .search-container {
        text-align: center;
        margin: 20px 0;
    }

    .search-container h2 {
        color: #fff;
        padding-top: 15px;
        margin-bottom: 9px;
        margin-top: 10px !important;
        font-style: bold;
        font-size: 65px;
        font-family: 'Work Sans', sans-serif;
    }

    .search-form {
        display: inline-flex;
        justify-content: center;
        align-items: center;
    }

    .search-input {
        padding: 10px;
        width: 500px;
        border: 2px solid #fff;
        border-radius: 0px;
        font-size: 14px;
        margin-right: 0px;
        background-color: white;
    }

    .search-input:focus {
        border-radius: 0px;
        outline: none;
    }

    .search-btn {
        padding: 10px 20px;
        background-color: #fff;
        color: white;
        border: 2px solid #fff;
        border-radius: 0px;
        cursor: pointer;
        font-size: 14px;
    }

    .search-btn:hover {
        background-color: #d2d2d4;

    }

    .search-btn ion-icon {
        font-size: 14px;
        color: #FF7701;

    }
    /* end of search btn style */

    .text-center {
        text-align: center;
    }

    /* limit start*/
    .transparent-form {
        background: transparent;
        border: none;
        padding: 0;
        margin-left: 140px;
        margin-bottom: 10px;

    }

    .transparent-select {
        background: #fff;
        border: 1px solid #545454;
        color: #333;
        padding: 5px 10px;
        font-size: 14px;
    }

    .transparent-form label {
        color: #fff;
        font-size: 14px;
    }
     /* limit end*/


    /* Button styles */
    .btn .view-button {
        padding: 4px 8px;
        color: #545454 !important;
        text-decoration: none;

        display: inline-block;
        margin: 0 2px;
        text-align: center;
        border: none;
        outline: none;
        cursor: pointer;
        transition: background-color 0.3s, box-shadow 0.3s;
    }


    .btn:hover {
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    .btn-success {
        background-color: #545454;
        color: #fff;
        font-weight: bold;
        padding: 5px 23px;
        border: 1px solid #545454;
    }

    .btn:hover {
        opacity: 0.9;
    }

    /* Hover effect */
    .view-button:hover {
        opacity: 0.9;
    }

    table tr:nth-child(even) .view-button {
        color: #545454;
        font-style: italic;
    }

    /* Odd rows button color */
    table tr:nth-child(odd) .view-button {
        color: #545454;
        font-style: italic;
    }

    .form-container {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        flex-wrap: wrap;
        margin-bottom: 20px;
    }

    /* Pagination: start */
    .pagination-info {
        font-size: 14px;
        color: #fff;
        margin: 10px 0;
        font-family: 'Arial', sans-serif;
        display: inline-block;
        margin-left: 140px;
        margin-bottom: 60px;
        margin-right: 0px;
    }

    .pagination {
        display: inline-flex;
        list-style-type: none;
        padding: 0;
        margin: 10px 0;
        justify-content: flex-start;
    }

    .pagination a {
        display: inline-block;
        padding: 8px 12px;
        margin: 0;
        color: #545454;
        text-decoration: none;
        background-color: transparent;
        border: 1px solid #545454;
        border-radius: 0px;
        font-size: 14px;
        transition: background-color 0.3s, color 0.3s;
    }
    
    .pagination a.active {
        background-color: #fff;
        color: #545454;
        border-color: #545454;
    }

    .pagination a:hover {
        background-color: #ddd;
        color: #545454;
    }
    /* Pagination: end */


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
        margin-top: 25px;
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

    @media (max-width: 768px) {

        th,
        td {
            padding: 8px;
            font-size: 14px;
        }

        th:nth-child(1),
        td:nth-child(1),
        th:nth-child(3),
        td:nth-child(3),
        th:nth-child(5),
        td:nth-child(5) .form-container {
            flex-direction: column;
            align-items: flex-start;
        }

        .search-form {
            margin-right: 14px;
            margin-bottom: 10px;
            width: 50%;
        }

        .approve-delete-form {
            justify-content: flex-start;
            width: 50%;
        }
    }

    @media (max-width: 480px) {

        th,
        td {
            padding: 5px;
            font-size: 12px;
        }

        .form-container {
            align-items: flex-start;
        }

        .search-form input[type="text"] {
            margin-right: 5px;
        }

        .search-form select {
            margin-right: 5px;
        }

        .approve-delete-form button {
            margin-right: 3px;
        }
    }
    </style>
</head>

<body>
    <main>
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
                    <button class="btn-ok2" onclick="window.location.href='usersoloviewclaim.php'">See claim
                        status</button>
                    <button class="btn-ok2" onclick="window.location.href='?logout'">LOG OUT</button>
                </div>
            </div>
        </div>
        <div id="successModal" class="modal-overlay" style="display: none;">
            <div class="modal-content">
                <div class="modal-title">
                    <h3>Claim Request Received!</h3>
                    <p>Thank you for submitting your claim. You can now go to the Lost & Found office to retrieve your
                        item. (Near the EXIT)</p>
                </div>
                <strong>
                    <p>Before You Go: <a href="Guidelines.php" class="italic">(See Guidelines)</a></p>
                </strong>
                <ul>
                    <li><strong></strong>Bring identification (ID card, driver’s license, or passport).</li>
                    <li><strong></strong>Proof of ownership may be requested (such as a receipt, unique item details, or
                        photos).</li>
                </ul>
                <p class="modal-ques"><strong>Questions or Updates?</strong></p>
                <p class="modal-cont">If you have any questions along the way, please call us at 87-99886.</p>

                <div class="button-container">
                    <button class="btn-ok" onclick="closeModal('successModal')">OKAY</button>
                    <button class="btn-ok12" onclick="openModal('tipModal')">Add Tip</button>
                </div>
            </div>
        </div>
        <!-- Add Tip Modal -->
        <div id="tipModal" class="modal" style="display:none;">
            <div class="modal-content12">

                <h2>Add a Tip</h2>
                <form id="tipForm">
                    <div class="form-group-tip">
                        <label for="tip">Tip:</label>
                        <textarea id="tip" name="tip" rows="4" cols="50" required></textarea>
                    </div>
                    <div class="button-container">
                        <button type="button" onclick="submitTip()">Submit</button>
                        <button type="button" onclick="closeModal('tipModal')">Close</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="search-container">
            <h2>
                Browse Found Items
            </h2>
            

            <hr class="hr-center">

            <form class="search-form">
                <input type="text" id="search-bar" name="search" placeholder="Search for reports..."
                    class="search-input">
                <button type="submit" class="search-btn">
                    <ion-icon name="search-outline"></ion-icon>
                </button>
            </form>
        </div>
        <form method="POST" action="userview.php" class="transparent-form">
            <select name="entry_limit" id="entry_limit" onchange="this.form.submit()" class="transparent-select">
                <option value="5" <?= $limit == 5 ? 'selected' : ''; ?>>5</option>
                <option value="10" <?= $limit == 10 ? 'selected' : ''; ?>>10</option>
                <option value="15" <?= $limit == 15 ? 'selected' : ''; ?>>15</option>
            </select>
            <label for="entry_limit">entries per rows</label>
        </form>

        <div class="container">
            <?php if (!empty($successMessage)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($successMessage) ?></div>
            <?php endif; ?>
            <div class="form-container">
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Category</th>
                            <th>Location</th>
                            <th>Details</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row["item_name"]) ?></td>
                            <td><?= htmlspecialchars($row["category"]) ?></td>
                            <td><?= htmlspecialchars($row["location_found"]) ?></td>
                            <td>
                                <a href="userfounddetailsaapprove.php?_id=<?= htmlspecialchars($row["id"]) ?>"
                                    class="view-button">View</a>

                            </td>
                            <td><?= htmlspecialchars($row["status"]) ?></td>
                            <td>



                                <!-- Claim Button -->




                                <form style="display:inline;">
                                    <button type="button" class="btn btn-success" aria-label="Claim"
                                        onclick="showClaimModal(<?php echo $row['id']; ?>)">Claim</button>
                                </form>






                                <!--           <form action="" method="POST" style="display:inline;" onsubmit="return confirmClaim()">
                                    <input type="hidden" name="report_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" class="btn btn-success" name="claim"
                                        aria-label="Claim">Proceed</button>
                                </form>   -->

                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">No reports found</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>


        <!-- confirmation modal -->
        <div id="confirmationModal" class="modal-overlay" style="display: none;">
            <div class="modal-content1">
                <div class="modal-title1">
                    <h3>Claim Confirmation</h3>
                    <p>Please review the legal notice before proceeding with your claim.</p>
                </div>
                <hr>

                <div class="confirmation-section">
                    <h3>Important Notice</h3>
                    <p><strong>WARNING:</strong> Providing false claims or attempting to claim an item that does not
                        belong to you is a serious offense. Fraudulent claims may result in legal actions under
                        <strong>Article 308 of the Revised Penal Code of the Philippines (Theft)</strong> and other
                        applicable laws. You may face criminal charges, fines, and penalties.
                    </p>
                    <p>By proceeding, you confirm that you are the rightful owner of the item and that all provided
                        information is truthful and accurate.</p>
                </div>

                <div class="form-group">
                    <input type="checkbox" name="confirmation_agreement" id="confirmation_agreement" required>
                    <label for="confirmation_agreement"><strong>I solemnly affirm that I am the rightful owner of this
                            item and understand that any false claim will result in legal consequences.</strong></label>
                </div>

                <div class="button-container">
                    <button type="button" class="btn-cancel" onclick="hideClaimModal()">Cancel</button>
                    <button type="button" class="btn btn-success" onclick="submitClaim()"
                        aria-label="Claim">Proceed</button>
                </div>
            </div>
        </div>




        <p class="pagination-info">Showing <?php echo $currentEntriesStart; ?> to <?php echo $currentEntriesEnd; ?> of
            <?php echo $totalRecords; ?> entries</p>
        <!-- Pagination (optional) -->
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++) { ?>
            <a href="?page=<?php echo $i; ?>&search=<?php echo $search; ?>"
                <?php echo ($i == $page) ? 'class="active"' : ''; ?>><?php echo $i; ?></a>
            <?php } ?>
        </div>
    </main>
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
    let savedReportId = null; // store the report ID globally

    function showClaimModal(reportId) {
        savedReportId = reportId; // save report id
        document.getElementById("confirmationModal").style.display = "block";
    }

    function hideClaimModal() {
        document.getElementById("confirmationModal").style.display = "none";
    }

    function submitClaim() {
        if (!document.getElementById("confirmation_agreement").checked) {
            alert("You must confirm that you are the rightful owner before proceeding.");
            return;
        }

        // create a form dynamically to submit the claim
        let form = document.createElement("form");
        form.method = "POST";
        form.action = ""; // submit to the current page

        let input = document.createElement("input");
        input.type = "hidden";
        input.name = "report_id";
        input.value = savedReportId;

        let claimInput = document.createElement("input");
        claimInput.type = "hidden";
        claimInput.name = "claim";
        claimInput.value = "1"; // simulate claim button being clicked

        form.appendChild(input);
        form.appendChild(claimInput);
        document.body.appendChild(form);
        form.submit();
    }
    </script>


    <SCript>
    function openModal(reportId) {
        document.getElementById("confirmationModal").style.display = "block";
        document.getElementById("report_id").value = reportId; // pass report ID to the modal form
    }

    function closeModal() {
        document.getElementById("confirmationModal").style.display = "none";
    }
    </SCript>
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
    <script>
    // Function to ask the user for confirmation before submitting the form
    </script>
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

    function openModal(modalId) {
        document.getElementById(modalId).style.display = "block";
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = "none";
    }

    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) modal.style.display = "block";
    }

    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) modal.style.display = "none";
    }

    function submitTip() {
        const tip = document.getElementById('tip').value.trim();
        if (!tip) {
            alert("Please enter a valid tip.");
            return;
        }
        // Log or send tip to the server
        console.log("Tip submitted:", tip);

        // Close the modal and reset the form
        closeModal('tipModal');
        document.getElementById('tipForm').reset();
    }
    </script>
</body>

</html>
<?php
if ($conn) {
    $conn->close();
}
?>