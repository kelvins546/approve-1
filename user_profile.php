<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

$servername = "localhost";
$username = "root";
$password = "";
$database = "approve";

$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$successMessage = '';

session_start(); // Start the session to access the message

// Display the message if it's set
if (isset($_SESSION['message'])) {
    $successMessage = $_SESSION['message'];
    unset($_SESSION['message']); // Clear the session message after displaying it
} else {
    $successMessage = ''; // Set empty if no message exists
}
// Check if there is a message in the URL
if (isset($_GET['message'])) {
    $successMessage = urldecode($_GET['message']);
}

// Handle approve request


// Handle delete request


// Handle approve all request


// Handle delete all request







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



// Check if logout is requested
if (isset($_GET['logout'])) {
    // Destroy session
    session_unset();
    session_destroy();

    // Redirect to login page
    header("Location: login_admin.php");
    exit;
}



// Function to fetch reports with pending position

// Function to fetch reports with reporter name

// Function to fetch reports with reporter name
function fetchReports($table, $conn)
{
    // Debugging: Log the query being executed
    $query = "SELECT $table.*, user.name AS reporter_name 
              FROM $table 
              JOIN user 
              ON $table.user_id = user.card_number 
              WHERE $table.position = 'Pending'";

    // Debug: Display query (for testing purposes only, remove in production)
    // echo $query;

    $result = $conn->query($query);

    // Check for query errors
    if (!$result) {
        die("Query failed: " . $conn->error);
    }

    $reports = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $reports[] = $row;
        }
    }
    return $reports;
}

// Fetch pending reports for each table
$pendingClaimReports = fetchReports("pending_claim_reports", $conn);
$pendingFoundReports = fetchReports("pending_found_reports", $conn);
$pendingLostReports = fetchReports("pending_lost_reports", $conn);

// Count the number of pending reports for each table
$claim_count = count($pendingClaimReports);
$found_count = count($pendingFoundReports);
$lost_count = count($pendingLostReports);

// Example: Replace with your actual queries


// Total pending notifications
$total_notifications = $claim_count + $found_count + $lost_count;


// Include database connection
$servername = "localhost";
$username = "root";
$password = "";
$database = "approve";

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


// Initialize variables
$edit_mode = false;
$edit_id = null;

// Handle form submission for adding a new user
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
    $name = $_POST['name'];
    $card_number = $_POST['card_number'];
    $card_password = $_POST['card_password']; // Plain password (no hashing)


    $insert_sql = "INSERT INTO user (name, card_number, card_password)
                   VALUES ('$name', '$card_number', '$card_password')";

    if ($conn->query($insert_sql) === TRUE) {
        echo "<script>alert('User added successfully!'); window.location.href='user_profile.php';</script>";
    } else {
        echo "<script>alert('Error: " . $conn->error . "');</script>";
    }
}

// Handle delete user
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $delete_sql = "DELETE FROM user WHERE id=$delete_id";

    if ($conn->query($delete_sql) === TRUE) {
        echo "<script>alert('User deleted successfully!'); window.location.href='user_profile.php';</script>";
    } else {
        echo "<script>alert('Error: " . $conn->error . "');</script>";
    }
}

// Check if there's an edit request
$edit_mode = false;
$edit_row = [];
if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    $edit_mode = true;

    // Fetch user data for editing
    $stmt = $conn->prepare("SELECT * FROM user WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_row = $result->fetch_assoc();
}

// Check if the form is being submitted for adding or updating a user
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) ? $_POST['name'] : null;
    $card_number = isset($_POST['card_number']) ? $_POST['card_number'] : null;
    $card_password = isset($_POST['card_password']) ? $_POST['card_password'] : null;



    if (isset($_POST['edit_id'])) {
        // Update the user
        $update_id = $_POST['edit_id'];
        if (!empty($card_password)) {
            // Only update password if it's provided
            $stmt = $conn->prepare("UPDATE user SET name = ?, card_number = ?, card_password = ? WHERE id = ?");
            $stmt->bind_param("sssi", $name, $card_number, $card_password, $update_id);
        } else {
            // Do not update password if it's empty
            $stmt = $conn->prepare("UPDATE user SET name = ?, card_number = ? WHERE id = ?");
            $stmt->bind_param("ssi", $name, $card_number, $update_id);
        }
        $stmt->execute();
    } else {
        // Add a new user
        $stmt = $conn->prepare("INSERT INTO user (name, card_number, card_password) VALUES ( ?, ?, ?)");
        $stmt->bind_param("sss", $name, $card_number, $card_password);
        //  print_r($data); // debug output

        // $stmt->execute();
    }
}




// Handle update user
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_user'])) {
    $edit_id = $_POST['edit_id'];
    $name = $_POST['name'];
    $card_number = $_POST['card_number'];
    $card_password = $_POST['card_password'];

    // Start update query
    $update_sql = "UPDATE user SET name=?, card_number=?";

    // Check if password is provided
    $params = [$name, $card_number];
    $types = "ss";

    if (!empty($card_password)) {
        $update_sql .= ", card_password=?";
        $params[] = $card_password;
        $types .= "s";
    }

    $update_sql .= " WHERE id=?";
    $params[] = $edit_id;
    $types .= "i";

    // Prepare and bind statement
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param($types, ...$params);

    // Execute query
    if ($stmt->execute()) {
        echo "<script>alert('User updated successfully!'); window.location.href='user_profile.php';</script>";
    } else {
        echo "<script>alert('Error: " . $conn->error . "');</script>";
    }
}



if (isset($_POST["import"])) {
    if (isset($_FILES["csv_file"]) && $_FILES["csv_file"]["error"] == 0) {
        $file = $_FILES["csv_file"]["tmp_name"];

        if (($handle = fopen($file, "r")) !== false) {
            fgetcsv($handle); // skip header row

            $stmt = $conn->prepare("INSERT INTO user (name, card_number, card_password) VALUES (?, ?, ?)");

            while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                if (!is_array($data) || count($data) < 3) {
                    continue; // skip invalid row
                }

                $name = trim($data[0]) ?? null;
                $card_number = trim($data[1]) ?? null;
                $card_password = trim($data[2]) ?? null;

                if (!empty($name) && !empty($card_number) && !empty($card_password)) {
                    $stmt->bind_param("sss", $name, $card_number, $card_password);
                    $stmt->execute();
                }
            }
            fclose($handle);
            $stmt->close();

            // set success message and redirect
            $_SESSION['message'] = "CSV imported successfully!";
            header("Location: user_profile.php");
            exit;
        } else {
            echo "Error opening file.";
        }
    } else {
        echo "Error: File upload failed.";
    }
}



// ✅ make sure $conn is open before using it
if (!isset($conn) || !$conn instanceof mysqli) {
    $conn = new mysqli("localhost", "root", "", "approve"); // reconnect if needed
}

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ✅ NOW IT'S SAFE TO QUERY THE DATABASE
$result = $conn->query("SELECT * FROM user");

if ($result) {
    while ($row = $result->fetch_assoc()) {
    }
} else {
    echo "Error fetching users: " . $conn->error;
}





// Fetch all users except id = 13
$sql = "SELECT * FROM user WHERE id != 1";
$result = $conn->query($sql);


?>




<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lost Reports</title>
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

    /* Navbar styles */
    .navbar {
        background-color: #2b4257;
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

    .navbar-img {
        width: 0;
    }

    .navbar-text {
        font-family: "Times New Roman", Times, serif;
        font-size: 30px;
        font-weight: bold;
        white-space: nowrap;
        color: #fff !important;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);

    }

    .navbar a {
        color: #fff;
        padding: 3px;
        text-decoration: none;
        margin: 20px;
        display: flex;
        margin-top: 10px;
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

    /* Dropdown container */
    .navbar .dropdown {
        position: relative;
        display: inline-block;
    }

    /* Button that activates the dropdown */
    .navbar .dropbtn {
        background-color: transparent;
        color: white;
        padding: 3px 15px;
        border: none;
        cursor: pointer;
        text-align: center;
        font-size: 16px;
        margin: 20px;
        display: inline-block;
    }

    /* Dropdown content (hidden by default) */
    .navbar .dropdown-content {
        display: none;
        position: absolute;
        background-color: #f9f9f9;
        width: 200px;
        box-shadow: 0px 8px 16px rgba(0, 0, 0, 0.2);
        z-index: 1;
        color: #545454;
        border-radius: 10px;
    }

    /* Links inside the dropdown */
    .navbar .dropdown-content a {
        color: #545454;
        padding: 6px 10px;
        text-decoration: none;
        display: block;

    }

    .navbar .dropdown-content a:hover {
        text-decoration: underline;
    }

    /* Show the dropdown content on hover */
    .navbar .dropdown:hover .dropdown-content {
        display: block;
    }

    /* Show the dropdown button on hover */
    .navbar .dropdown:hover .dropbtn {
        text-decoration: underline;
    }

    .notif-btn {
        position: relative;
        background: none;
        border: none;
        cursor: pointer;
        font-size: 24px;
        color: #fff;
        outline: none;
        margin-left: auto;
        margin-right: -70px;
        margin-top: 10px;
    }



    .notif-badge {
        position: absolute;
        top: -5px;
        /* Adjust as needed */
        right: -5px;
        /* Adjust as needed */
        background: red;
        color: white;
        font-size: 12px;
        font-weight: bold;
        padding: 2px 6px;
        border-radius: 50%;
        display: inline-block;
        z-index: 1;
        /* Ensures it stays on top */
    }

    .notif-btn:hover {
        color: #ccc;
        /* Hover effect */
        transform: scale(1.1);
        /* Slight zoom effect */
        transition: 0.2s ease-in-out;
    }






    .navbar>.icon-btn {
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
        margin-left: 630px;
        /* Push the button to the far right */
        transition: background-color 0.3s ease, border-color 0.3s ease;
        /* Smooth hover effect */
        z-index: 99999;

    }

    .icon-btn {
        z-index: 99999;
    }

    /* Hamburger Icon */
    .hamburger-icon {
        background: none;
        border: none;
        cursor: pointer;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        height: 22px;
        width: 30px;
        margin-left: auto;
        margin-right: 20px;
        padding: 0;
    }

    .hamburger-icon span {
        background-color: white;
        height: 3px;
        width: 100%;
        border-radius: 2px;
        transition: all 0.3s;
    }

    /* Side Navigation */
    .side-nav {
        height: 100%;
        width: 0;
        position: fixed;
        top: 0;
        right: 0;
        background-color: #fff;
        overflow-x: hidden;
        transition: 0.3s;
        padding-top: 60px;
        box-shadow: -2px 0 6px rgba(0, 0, 0, 0.2);
        z-index: 2000000000;
    }

    .side-nav a {
        padding: 10px 20px;
        text-decoration: none;
        font-size: 20px;
        color: #545454;
        display: block;
        transition: 0.3s;
    }

    .side-nav a:hover {
        color: #f1f1f1;
    }

    .side-nav .close-btn {
        position: absolute;
        top: 10px;
        right: 20px;
        font-size: 30px;
        color: #545454;
    }

    /* Show the side-nav */
    .side-nav.open {
        width: 250px;
    }

    .navbar>.icon-btn:hover {
        background-color: #f4f4f9;
        /* Light background on hover */
        border-color: #000;
        /* Darker border on hover */
    }



    .navbar>.icon-btn:hover .user-icon {
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


    /* Footer */
    .footer {
        background-color: #2b4257;
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
        margin-top: 25px;
    }

    .footer-logo img {
        max-width: 70px;
    }


    .footer-contact {
        text-align: right;
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
    <style>
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        background-image: url('images/bg1.png');
    }

    .container {
        width: 80%;
        margin: 20px auto;
        background: #fff;
        padding: 20px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
        border-radius: 8px;
    }

    h1,
    h2 {
        text-align: center;
        color: #333;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
    }

    table,
    th,
    td {
        border: 1px solid #ddd;

    }

    th,
    td {
        padding: 10px;
        text-align: left;

    }

    th {
        background-color: #B1D4E0;
        color: #333;
    }

    form {
        margin-top: 20px;
    }

    form label {
        display: block;
        margin: 10px 0 5px;
    }

    form input,
    form textarea {
        width: 100%;
        padding: 8px;
        margin-bottom: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    form input[type="submit"] {
        background-color: #2b4257;
        color: white;
        border: none;
        padding: 10px 20px;
        cursor: pointer;
        border-radius: 4px;
    }

    form input[type="submit"]:hover {
        background-color: #45a049;
    }

    .btn-cont {
        display: flex;
        gap: 5px;
    }

    .btn-add {
        background-color: #2B4257;
        color: white;
        padding: 8px 7px;
        border: none;

    }

    .btn-edit {
        background-color: #ccc;
        color: #545454;
        padding: 8px 13px;
        border: none;
        display: flex;
        text-decoration: none;
        border-radius: 2px;
        font-size: 14px;
        border: 1px solid;
    }

    .btn-delete {
        background-color: #fab7b0;
        color: #545454;
        padding: 8px 13px;
        border: 1px solid #545454;
        display: flex;
        text-decoration: none;
        border-radius: 2px;
        font-size: 14px;
    }

    /* Style for the close button (X) */
    .btn-close {
        position: absolute;
        top: 10px;
        right: 10px;
        /* Align to the right */
        background-color: transparent;
        /* Red color */
        color: #545454;
        border: none;
        padding: 8px 12px;
        font-size: 14px;
        cursor: pointer;


    }

    .btn-close:hover {
        color: #333;
        /* Darker red for hover effect */
    }

    /* Style for the form */
    #addUserForm {
        position: relative;
        /* Allows close button to be positioned relative to the form */

        /* Initially hidden */
        border: 1px solid #ccc;
        padding: 20px;
        border-radius: 8px;
        background-color: #fff;
        width: 100%;
        /* Make the form responsive */
        max-width: 500px;
        /* Set maximum width */
        margin: 20px auto;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .password-wrapper {
        position: relative;
        display: flex;
        align-items: center;
        width: 100%;
    }

    .password-wrapper input {
        width: 100%;
        padding-right: 35px;
        /* space for the icon */
        border: 1px solid #ccc;
        border-radius: 5px;
        padding: 8px;
    }

    .password-wrapper ion-icon {
        position: absolute;
        right: 10px;
        margin-bottom: 10px;
        cursor: pointer;
        font-size: 20px;
        color: #666;
    }
    </style>
</head>

<body>
    <main>

        <div class="navbar">
            <img src="images/logo.png" alt="Logo" class="navbar-logo">
            <span class="navbar-text">UNIVERSITY OF CALOOCAN CITY</span>
            <!-- Claim Reports Dropdown -->
            <div class="dropdown">
                <button class="dropbtn">Claim Requests</button>
                <div class="dropdown-content">
                    <a href="pending_claim.php">Pending Claims</a>
                    <a href="approved_claim_report.php">Approved Claims</a>
                </div>
            </div>

            <!-- Lost Reports Dropdown -->
            <div class="dropdown">
                <button class="dropbtn">Lost Reports</button>
                <div class="dropdown-content">
                    <a href="pending_lost_report.php">Pending Lost</a>
                    <a href="approved_lost_report.php">Approved Lost</a>
                </div>
            </div>

            <!-- Found Reports Dropdown -->
            <div class="dropdown">
                <button class="dropbtn">Found Reports</button>
                <div class="dropdown-content">
                    <a href="pending_found_report.php">Pending Found</a>
                    <a href="approved_found_report.php">Approved Found</a>
                </div>
            </div>

            <!-- Guidelines Link -->
            <a href="Guidelines.php">Guidelines</a>

            <!-- Notification Icon Button -->
            <button class="notif-btn" onclick="showModal('notif')">
                <ion-icon name="notifications"></ion-icon>
                <span class="notif-badge">
                    <?= htmlspecialchars($total_notifications) ?>
                </span>
            </button>



            <!-- Side Navigation Toggle -->
            <button class="hamburger-icon" onclick="toggleSideNav()">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>

        <!-- Side Navigation -->
        <div id="sideNav" class="side-nav">
            <a href="javascript:void(0)" class="close-btn" onclick="toggleSideNav()">&times;</a>
            <a href="user_profile.php">User Profile</a>
            <a href="?logout">Logout</a>
        </div>


        <div id="loginclickmodal" class="modal-overlay" style="display: none;">
            <div class="modal-content2">
                <!-- Close Button -->
                <button class="close-btn" onclick="closeModal('loginclickmodal')">&times;</button>

                <div class="modal-title2">
                    <h3>Good day, <strong>ADMIN</strong>!</h3>
                    <p><?= htmlspecialchars($_SESSION['admin_id'] ?? '') ?></p>
                    <hr>
                </div>
                <div class="butclass">


                    <button class="btn-ok2" onclick="window.location.href='?logout'">LOG OUT</button>
                </div>
            </div>
        </div>








        <div
            style="width: 300px; margin-top: 10px; margin-left: 15px; padding: 20px; border-radius: 8px; background: #f8f9fa; box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1); text-align: center;">
            <h2 style="margin-bottom: 35px; font-size: 18px; color: #333;">Import Users from CSV</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="file" name="csv_file" accept=".csv" required style="display: block; margin: 10px auto;">
                <button type="submit" name="import"
                    style="background: #28a745; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer;">Import</button>
            </form>
        </div>

        <div class="container">
            <h1>User Profile</h1>

            <!-- Table displaying all users -->
            <table>
                <a href="user_profile.php" id="addUserBtn" class="btn btn-add">
                    <button type="button" class="btn btn-add">Add User</button>
                </a>

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Student ID</th>
                        <th>Password</th>

                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['card_number']); ?></td>
                        <td><?php echo htmlspecialchars($row['card_password']); ?></td>

                        <td>
                            <!-- Edit Button -->

                            <div class="btn-cont">
                                <a href="user_profile.php?edit_id=<?php echo $row['id']; ?>" class="btn btn-edit"
                                    id="editUserBtn-<?php echo $row['id']; ?>">Edit</a>
                                <!-- Delete Button -->
                                <a href="user_profile.php?delete_id=<?php echo $row['id']; ?>" class="btn btn-delete"
                                    onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>



            <!-- Add or Edit User Form -->


            <!-- Add or Edit User Form -->

            <form id="addUserForm" method="POST" style="display:show;">
                <h2><?php echo $edit_mode ? "Edit User" : "Add New User"; ?></h2>
                <?php if ($edit_mode): ?>
                <input type="hidden" name="update_user" value="1">
                <input type="hidden" name="edit_id" value="<?php echo $edit_id; ?>">
                <?php else: ?>
                <input type="hidden" name="add_user" value="1">
                <?php endif; ?>

                <!-- Close Button -->
                <button type="button" id="closeFormBtn" class="btn btn-close">X</button>

                <label for="name">Name:</label>
                <input type="text" id="name" name="name" value="<?php echo $edit_mode ? $edit_row['name'] : ''; ?>"
                    required>

                <label for="card_number">Student ID:</label>
                <input type="text" id="card_number" name="card_number"
                    value="<?php echo $edit_mode ? $edit_row['card_number'] : ''; ?>" required>

                <label for="card_password">password:</label>
                <div class="password-wrapper">
                    <input type="password" id="card_password" name="card_password"
                        value="<?php echo $edit_mode ? $edit_row['card_password'] : ''; ?>" required>
                    <ion-icon name="eye-outline" id="togglePassword"></ion-icon>
                </div>




                <!-- <label for="card_password">Password:</label>
                <input type="password" id="card_password" name="card_password"
                    value="<?php echo $edit_mode ? $edit_row['card_password'] : ''; ?>" required>
                -->

                <input type="submit" value="<?php echo $edit_mode ? "Update User" : "Add User"; ?>">
            </form>


        </div>


        <!-- success modal -->












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

    function toggleSideNav() {
        const sideNav = document.getElementById('sideNav');
        sideNav.classList.toggle('open');
    }

    function showModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'flex';
        }
    }


    function approveAction(report_id, table_type) {
        fetch('handle_report.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    report_id: report_id,
                    action: 'approve',
                    table_type: table_type
                }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    // Optionally refresh or remove the row from the table
                } else {
                    alert(data.message);
                }
            });
    }

    document.addEventListener("visibilitychange", function() {
        if (!document.hidden) {
            location.reload();
        }
    });
    </script>

    <script>
    // Show the Add or Edit form when clicking "Add User" or "Edit" link
    document.getElementById('addUserBtn').addEventListener('click', function() {
        const form = document.getElementById('addUserForm');
        form.style.display = 'block'; // Show the form
    });

    // For the Edit button, ensure the form stays open
    document.querySelectorAll('.btn-edit').forEach((editBtn) => {
        editBtn.addEventListener('click', function() {
            const form = document.getElementById('addUserForm');
            form.style.display = 'block'; // Show the form
        });
    });

    // Close the form when "X" is clicked
    document.getElementById('closeFormBtn').addEventListener('click', function() {
        const form = document.getElementById('addUserForm');
        form.style.display = 'none'; // Hide the form when "X" is clicked
    });
    </script>
    <script>
    document.getElementById('togglePassword').addEventListener('click', function() {
        let passwordInput = document.getElementById('card_password');
        let icon = this.querySelector('ion-icon');

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.setAttribute('name', 'eye-off-outline');
        } else {
            passwordInput.type = 'password';
            icon.setAttribute('name', 'eye-outline');
        }
    });
    </script>




    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
</body>

</html>
<?php
if ($conn) {
    $conn->close();
}
?>