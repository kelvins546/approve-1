<?php

session_start();
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
if (isset($_GET['report_id']) && filter_var($_GET['report_id'], FILTER_VALIDATE_INT)) {
    $report_id = $_GET['report_id']; // Use $report_id consistently

    // Query to fetch item details by report_id from the pending_lost_reports table
    $sql = "SELECT * FROM pending_lost_reports WHERE id = ?";
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
        // Redirect or show error message and exit
        echo "Item not found.";
        exit;
    }

    // Close the statement
    $stmt->close();
} else {
    // Redirect if ID is invalid or not provided
    echo "Invalid ID or missing parameter.";
    exit;
}

// Handle Form Submission (Approve)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Approve action
    if (isset($_POST['approve_id'])) {
        $approve_id = $_POST['approve_id'];
        // Update status to 'Approved' for the report
        $sql = "UPDATE pending_lost_reports SET status = 'Approved' WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $approve_id);
        if ($stmt->execute()) {
            echo "<script>alert('Report Approved');
            window.location.href = 'usersoloview.php';
            </script>";
        } else {
            echo "<script>alert('Failed to approve report');</script>";
        }
        $stmt->close();
    }

    // Reject action
    if (isset($_POST['delete_id'])) {
        $delete_id = $_POST['delete_id'];

        // Delete the report from the database
        $sql = "DELETE FROM pending_lost_reports WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $delete_id);

        if ($stmt->execute()) {
            echo "<script>
                    alert('great! the item has been marked as found.');
                    window.location.href = 'usersoloview.php';
                  </script>";
        } else {
            echo "<script>alert('Failed to cancel report');</script>";
        }

        $stmt->close();
    }
}


//edit
// Check if report_id is provided and is a valid integer
if (isset($_GET['report_id']) && filter_var($_GET['report_id'], FILTER_VALIDATE_INT)) {
    $report_id = $_GET['report_id'];

    // Fetch the existing data for editing
    $sql = "SELECT * FROM pending_lost_reports WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $report_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // If the record exists, fetch the data
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
    } else {
        // If no record is found, show an error message
        $message = 'Item not found or invalid ID.';
    }
} else {
    $message = 'Invalid ID or missing parameter.';
}
// Update action
if (isset($_POST['item_name']) && isset($_POST['date_found'])) {
    $item_name = $_POST['item_name'];
    $date_found = $_POST['date_found'];
    $category = $_POST['category'];
    $time_found = $_POST['time_found'];
    $brand = $_POST['brand'] ?: null; // Handle optional fields
    $location_found = $_POST['location_found'];
    $primary_color = $_POST['primary_color'] ?: null;
    $description = $_POST['description'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $phone_number = $_POST['phone_number'];
    $email = $_POST['email'];

    // File upload handling
    if (isset($_FILES['picture']) && $_FILES['picture']['error'] == 0) {
        // Sanitize file name
        $file_tmp = $_FILES['picture']['tmp_name'];
        $file_name = basename($_FILES['picture']['name']); // Get the base name
        $file_path = 'uploads/' . $file_name;

        // Move uploaded file
        if (move_uploaded_file($file_tmp, $file_path)) {
            // File uploaded successfully
        } else {
            echo "Failed to upload the file.";
            exit;
        }
    } else {
        // If no new picture, use the existing picture
        $file_path = $item['picture'];
    }

    // Update query
    $update_sql = "UPDATE pending_lost_reports SET 
                    item_name = ?, date_found = ?, category = ?, time_found = ?, 
                    brand = ?, location_found = ?, primary_color = ?, description = ?, 
                    first_name = ?, last_name = ?, phone_number = ?, email = ?, picture = ? 
                    WHERE id = ?";

    $stmt = $conn->prepare($update_sql);
    // Correct the bind_param to match 13 placeholders
    $stmt->bind_param(
        "sssssssssssssi", // 13 parameters (1 for each field)
        $item_name,
        $date_found,
        $category,
        $time_found,
        $brand,
        $location_found,
        $primary_color,
        $description,
        $first_name,
        $last_name,
        $phone_number,
        $email,
        $file_path,
        $report_id
    );

    if ($stmt->execute()) {
        echo "<script>alert('Report updated successfully.'); </script>";
    } else {
        echo "<script>alert('Failed to update report.');</script>";
    }
    $stmt->close();
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
        margin-top: 70px;
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
        cursor: pointer;
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

    }

    .btn-danger {
        background-color: #dc3545;
        color: #fff;
        cursor: pointer !important;
        font-weight: bold;
    }

    .btn:hover {

        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }
    </style>

</head>

<body>
    <div class="navbar">

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
            <h2 class="btn-action">Action</h2>
            <hr>
            <div class="btn-container">
                <a href="usersoloview.php" class="btn btn-back">Back</a>
                <form action="" method="POST" style="display:inline;">
                    <input type="hidden" name="delete_id" value="<?= htmlspecialchars($row["id"]) ?>">
                    <button type="submit" class="btn btn-success" aria-label="Delete Report">Mark as found</button>
                </form>

            </div>

        </div>
    </div>





    <!-- Modal -->




    <script>
    // Get modal and buttons
    var modal = document.getElementById("editModal");
    var openModalBtn = document.getElementById("openModalBtn");
    var closeModalBtn = document.getElementById("closeModalBtn");

    // Open the modal when the button is clicked
    openModalBtn.onclick = function() {
        modal.style.display = "block";
    }

    // Close the modal when the close button is clicked
    closeModalBtn.onclick = function() {
        modal.style.display = "none";
    }

    // Close the modal if the user clicks outside the modal content
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }

    window.onload = function() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('action') === 'edit') {
            const editButton = document.getElementById('openModalBtn');
            if (editButton) {
                editButton.click(); // Trigger the edit button programmatically
            }
        }
    };
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
    </script>
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>


</body>

</html>