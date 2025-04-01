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



// Initialize variables
$edit_mode = false;
$edit_id = null;

// Handle form submission for adding a new user
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $card_number = $_POST['card_number'];
    $card_password = password_hash($_POST['card_password'], PASSWORD_BCRYPT);
    $role = isset($_POST['role']) ? $_POST['role'] : "Student"; // Default to "Student" role if not provided

    if (isset($_POST['edit_id']) && !empty($_POST['edit_id'])) {
        // Update user
        $update_id = $_POST['edit_id'];
        $stmt = $conn->prepare("UPDATE user SET name = ?, card_number = ?, card_password = ? WHERE id = ?");
        $stmt->bind_param("sssi", $name, $card_number, $card_password, $update_id);

        if ($stmt->execute()) {
            echo "<script>alert('User updated successfully!'); window.location.href='user_profile.php';</script>";
        } else {
            echo "<script>alert('Error: " . $conn->error . "');</script>";
        }
    } else {
        // Add new user
        $stmt = $conn->prepare("INSERT INTO user (name, card_number, card_password, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $card_number, $card_password, $role);

        if ($stmt->execute()) {
            echo "<script>alert('User added successfully!'); window.location.href='user_profile.php';</script>";
        } else {
            echo "<script>alert('Error: " . $conn->error . "');</script>";
        }
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


if (isset($_POST["import"])) {
    if (isset($_FILES["csv_file"]) && $_FILES["csv_file"]["error"] == 0) {
        $file = $_FILES["csv_file"]["tmp_name"];

        if (($handle = fopen($file, "r")) !== false) {
            fgetcsv($handle); // skip header row

            $stmt = $conn->prepare("INSERT INTO user (name, card_number, card_password, role) VALUES (?, ?, ?, ?)");

            while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                if (count($data) < 4) continue; // skip invalid row

                $name = trim($data[0]);
                $card_number = trim($data[1]);
                $card_password = password_hash(trim($data[2]), PASSWORD_BCRYPT);
                $role = trim($data[3]); // student, faculty, utilities

                if (!empty($name) && !empty($card_number) && !empty($role)) {
                    $stmt->bind_param("ssss", $name, $card_number, $card_password, $role);
                    $stmt->execute();
                }
            }
            fclose($handle);
            $stmt->close();

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
$sql = "SELECT * FROM user WHERE role = 'student'";
$students = $conn->query($sql);

$sql = "SELECT * FROM user WHERE role = 'faculty'";
$faculty = $conn->query($sql);

$sql = "SELECT * FROM user WHERE role = 'utilities'";
$utilities = $conn->query($sql);




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
            background-color: #f4f4f9;
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
            margin-left: 0px;
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

        .navbar-logo {
            height: 90px;
            width: auto;
            /* Maintain aspect ratio */
            margin-right: 20px;
            margin-left: 20px;
            margin-top: 10px;
            margin-bottom: 10px;
        }

        /* UCC */
        .main-title {
            font-family: "Times New Roman", Times, serif;
            font-size: 36px;
            font-weight: bold;
            white-space: nowrap;
            color: #000 !important;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .subtitle {
            font-family: 'Work Sans', sans-serif;
            display: block;
            font-size: 24px;
            color: black;
            text-shadow: 0px 0px 0px;
            font-weight: normal;
            padding-right: 298px;
        }

        .navbar a {
            color: #545454;
            text-decoration: none;
            margin: 20px;
            display: flex;
            margin-top: 10px;
        }

        .navbar a:hover {
            text-decoration: underline;
            text-decoration-thickness: 1px;
        }

        /* navbar dropdown container */
        .navbar .dropdown {
            position: relative;
            display: inline-block;
        }

        .navbar .dropbtn {
            background-color: transparent;
            color: #545454;
            padding-left: 20px;
            border: none;
            cursor: pointer;
            text-align: center;
            font-size: 16px;
            margin: 20px;
            display: inline-block;
        }

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







        /* notif button */

        .notif-btn {
            position: relative;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 24px;
            color: #FF7701;
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
            background-color: #545454;
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
            z-index: 999;
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


        /* ++ icon hover */
        .navbar>.icon-btn:hover {
            background-color: #f4f4f9;
            border-color: #000;
            /* Darker border on hover */
        }

        .navbar>.icon-btn:hover .user-icon {
            color: #000;
        }

        .user-icon {
            font-size: 24px;
            /* Icon size */
            color: #FF7701;
            transition: color 0.3s ease;
            /* Smooth color change on hover */
        }

        .user-icon:hover {
            color: #FF7701;
            /* Darken color on hover */
        }

        /* -- icon hover */



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
            top: 17%;
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

        .modal-content3 {
            background-color: #fefefe;
            padding: 30px;
            color: #545454;
            border-radius: 10px;
            border: 1px solid #fefefe;
            width: 240px;
            max-width: 100%;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            animation: fadeIn 0.3s ease-out;
            margin-bottom: 0px;
            position: absolute;
            top: 30%;
            right: 0;
            transform: translate(-50%, -50%);
            padding-top: 20px;

        }

        /* Adding the arrow */


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

        .modal-title3 h2 {
            margin-bottom: 2px;

        }

        .modal-title3 hr {
            margin-bottom: 12px;

        }

        .transparent-btn {
            background: transparent;
            /* Makes the background transparent */
            text-align: start;
            /* Optional: Add a border for visibility */
            color: #333;
            /* Text color */

            /* Adjust padding */
            border: none;
            /* Rounded edges */
            font-size: 14px;
            /* Adjust font size */
            cursor: pointer;
            /* Changes cursor to pointer on hover */
            transition: all 0.3s ease;
            margin-bottom: 10px;
            /* Smooth hover effect */
        }

        .transparent-btn:hover {
            background: rgba(0, 0, 0, 0.1);
            /* Slightly darkens background on hover */

            /* Darkens border on hover */
            color: #000;
            /* Darkens text color on hover */
        }



        .modal-title2 p {
            margin-bottom: 2px;
            font-size: 14px;
        }

        .butclass1 {
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

        .btn-ok3:hover {
            background-color: #ccc;
        }


        .close-btn:hover {
            color: #f00;
            /* Optional: Add hover effect */
        }

        .alert {
            padding: 10px;
            color: #4CAF50;
            background-color: #e8f5e9;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }

        .text-center {
            text-align: center;
        }

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


        table tr:nth-child(even) .btn-success {
            background-color: #28a745;
            color: #fff;
            font-weight: bold;
            padding: 5px 23px;
            border: 1px solid #545454;

        }

        table tr:nth-child(even) .btn-danger {
            background-color: #dc3545;
            color: #fff;
            font-weight: bold;
            padding: 5px 23px;
            border: 1px solid #545454;

        }

        table tr:nth-child(odd) .btn-danger {
            background-color: #dc3545;
            color: #fff;
            font-weight: bold;
            padding: 5px 23px;
            border: 1px solid #545454;

        }


        table tr:nth-child(odd) .btn-success {
            background-color: #28a745;
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

        .approve-delete-form {
            display: flex;
            align-items: center;
            flex: 1;
            justify-content: flex-end;
        }

        .approve-delete-form button {
            margin-right: 5px;
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
            td:nth-child(5) {
                display: none;
            }

            .form-container {
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

        .message {
            padding: 20px;
            margin: 20px auto;
            border-radius: 5px;
            font-size: 16px;
            text-align: center;
            width: 80%;
            /* Adjust width as necessary */
            max-width: 600px;
            /* Max width for larger screens */
        }

        .success {
            background-color: #4CAF50;
            color: white;
        }

        .error {
            background-color: #f44336;
            color: white;
        }

        .modaluser {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: white;
            padding: 20px;
            margin: 10% auto;
            width: 40%;
            border-radius: 8px;
            position: relative;
        }

        .btn-close {
            position: absolute;
            top: 10px;
            right: 15px;
            background: red;
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
        }

        .password-wrapper {
            display: flex;
            align-items: center;
        }

        .password-wrapper ion-icon {
            cursor: pointer;
            margin-left: 5px;
        }









        /* -----------CONTENT OF THEW WEBSITE ----------------- */

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

        /* END table container styles */

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
            background-color: #584636;
            color: #fff;
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
            background-color: #FF7701;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 4px;
        }

        form input[type="submit"]:hover {
            background-color: rgb(186, 86, 4);
        }

        .btn-cont {
            display: flex;
            gap: 5px;
        }

        .btn-add {
            background-color: #ff7701;
            color: white;
            padding: 8px 7px;
            border: none;
            cursor: pointer;
            border-radius: 6px;
        }

        .btn-add:hover {
            background-color: #e66a00;
            color: white;
            padding: 8px 7px;
            border: none;
            cursor: pointer;
            border-radius: 6px;

        }

        .btn-edit {
            background-color: #ccc;
            color: #545454;
            padding: 8px 13px;
            border: none;
            display: flex;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
            border: 1px solid;
        }

        .btn-delete {
            background-color: #dc3545;
            color: #fff;
            padding: 8px 12px;
            border: 1px solid #545454;
            display: flex;
            text-decoration: none;
            border-radius: 6px;
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
            border: 1px solid #ccc;
            padding: 20px;
            border-radius: 8px;
            background-color: #fff;
            width: 78.5%;
            margin-left: 170px;
            margin-bottom: 100px;
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

        .btn btn-add {
            color: #ff7701;
            border-radius: 6px;
        }
    </style>


</head>

<body>
    <main>

        <div class="navbar">
            <img src="images/logo.png" alt="Logo" class="navbar-logo">
            <h1 class="main-title">
                UNIVERSITY OF CALOOCAN CITY
                <span class="subtitle"> LOST AND FOUND ADMIN</span>
            </h1>

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
            <a href="Guidelines.php" class="guidelinesLink">Guidelines</a>

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
            style="width: 300px; margin-top: 10px; margin-left: 170px; padding: 20px; border-radius: 8px; background: #f8f9fa; box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1); text-align: center;">
            <h2 style="margin-bottom: 35px; font-size: 18px; color: #333;">Import Users from CSV</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="file" name="csv_file" accept=".csv" required style="display: block; margin: 10px auto;">
                <button type="submit" name="import"
                    style="background: #ff7701; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer;">Import</button>
            </form>
        </div>

        <div id="userModal" class="modaluser">
            <div class="modal-content">
                <h2><?php echo $edit_mode ? "Edit User" : "Add New User"; ?></h2>

                <!-- close button -->
                <button type="button" id="closeFormBtn" class="btn btn-close">X</button>

                <form id="addUserForm" method="POST">
                    <?php if ($edit_mode): ?>
                        <input type="hidden" name="update_user" value="1">
                        <input type="hidden" name="edit_id" value="<?php echo $edit_id; ?>">
                    <?php else: ?>
                        <input type="hidden" name="add_user" value="1">
                    <?php endif; ?>

                    <label for="name">Name:</label>
                    <input type="text" id="name" name="name" value="<?php echo $edit_mode ? $edit_row['name'] : ''; ?>"
                        required>

                    <label for="card_number">Student ID:</label>
                    <input type="text" id="card_number" name="card_number"
                        value="<?php echo $edit_mode ? $edit_row['card_number'] : ''; ?>" required>

                    <label for="card_password">Password:</label>
                    <div class="password-wrapper">
                        <input type="password" id="card_password" name="card_password"
                            value="<?php echo $edit_mode ? $edit_row['card_password'] : ''; ?>" required>
                        <ion-icon name="eye-outline" id="togglePassword"></ion-icon>
                    </div>

                    <input type="submit" value="<?php echo $edit_mode ? "Update User" : "Add User"; ?>">
                </form>
            </div>
        </div>



        <div class="container">
            <h1>User Profile</h1>

            <!-- Table displaying all users -->
            <table>
                <a href="#" id="addUserBtn" class="btn btn-add">
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
                                    <a href="#" class="btn btn-edit editUserBtn"
                                        data-user-id="<?php echo $row['id']; ?>">Edit</a>

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




        </div>


        <!-- success modal -->
    </main>

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
        document.getElementById("addUserBtn").addEventListener("click", function(event) {
            event.preventDefault();
            document.getElementById("userModal").style.display = "block";
        });

        document.getElementById("closeFormBtn").addEventListener("click", function() {
            document.getElementById("userModal").style.display = "none";
        });
        // password toggle
        document.getElementById("togglePassword").addEventListener("click", function() {
            let passwordField = document.getElementById("card_password");
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.setAttribute('name', 'eye-off-outline');
            } else {
                passwordInput.type = 'password';
                icon.setAttribute('name', 'eye-outline');
            }
        });
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const userModal = document.getElementById("userModal");
            const closeFormBtn = document.getElementById("closeFormBtn");
            const addUserBtn = document.getElementById("addUserBtn");
            const nameInput = document.getElementById("name");
            const cardNumberInput = document.getElementById("card_number");
            const cardPasswordInput = document.getElementById("card_password");
            const formTitle = document.querySelector("#userModal h2");
            const submitButton = document.querySelector("#addUserForm input[type='submit']");
            const hiddenUpdateInput = document.querySelector("input[name='update_user']");
            const hiddenEditIdInput = document.querySelector("input[name='edit_id']");

            // 🚀 Open modal for adding new user
            addUserBtn.addEventListener("click", function(event) {
                event.preventDefault();
                userModal.style.display = "block";

                // Reset form for adding a new user
                formTitle.innerText = "Add New User"; // ✅ Set title correctly
                nameInput.value = "";
                cardNumberInput.value = "";
                cardPasswordInput.value = "";
                submitButton.value = "Add User";

                // Ensure hidden inputs are reset
                hiddenUpdateInput.removeAttribute("value");
                hiddenEditIdInput.removeAttribute("value");
            });

            // 🚀 Attach event listeners to all edit buttons
            document.querySelectorAll(".editUserBtn").forEach(editBtn => {
                editBtn.addEventListener("click", function(event) {
                    event.preventDefault();
                    userModal.style.display = "block";

                    let userId = this.getAttribute("data-user-id");

                    // ✅ Fetch user data from backend
                    fetch(`get_user.php?id=${userId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data) {
                                formTitle.innerText = "Edit User"; // ✅ Update title dynamically
                                nameInput.value = data.name;
                                cardNumberInput.value = data.card_number;
                                cardPasswordInput.value = data.card_password;
                                submitButton.value = "Update User";

                                hiddenUpdateInput.setAttribute("value", "1");
                                hiddenEditIdInput.setAttribute("value", data.id);
                            }
                        })
                        .catch(error => console.error("Error fetching user data:", error));
                });
            });

            // 🚀 Close modal
            closeFormBtn.addEventListener("click", function() {
                userModal.style.display = "none";
            });

            // ✅ Fix password toggle issue
            document.getElementById("togglePassword").addEventListener("click", function() {
                let passwordField = document.getElementById("card_password");
                if (passwordField.type === "password") {
                    passwordField.type = "text";
                    this.setAttribute("name", "eye-off-outline");
                } else {
                    passwordField.type = "password";
                    this.setAttribute("name", "eye-outline");
                }
            });
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