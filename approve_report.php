<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection variables
$servername = "localhost"; // Usually localhost for XAMPP
$username = "root"; // Default username for XAMPP
$password = ""; // Default password for root in XAMPP is usually empty
$database = "approve"; // Replace with your actual database name

// Create database connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle approve request
if (isset($_GET['approve_id'])) {
    // Sanitize input to prevent SQL injection
    $approveId = (int)$_GET['approve_id']; // Cast to int for safety

    // Prepare the UPDATE statement
    $stmt = $conn->prepare("UPDATE pending_reports SET status = 'approved' WHERE report_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $approveId); // Bind the parameter
        if ($stmt->execute()) {
            echo "Report has been approved.";
        } else {
            echo "Error approving report: " . $stmt->error; // Display error if execution fails
        }
        $stmt->close(); // Close the statement
    } else {
        echo "Failed to prepare statement: " . $conn->error; // Display error if preparation fails
    }
}

// Handle delete request
if (isset($_GET['id'])) {
    // Sanitize input to prevent SQL injection
    $id = (int)$_GET['id']; // Cast to int for safety

    // Prepare the DELETE statement
    $stmt = $conn->prepare("DELETE FROM pending_reports WHERE report_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id); // Bind the parameter
        if ($stmt->execute()) {
            echo "Report has been deleted.";
        } else {
            echo "Error deleting report: " . $stmt->error; // Display error if execution fails
        }
        $stmt->close(); // Close the statement
    } else {
        echo "Failed to prepare statement: " . $conn->error; // Display error if preparation fails
    }
}

// Fetch and display pending reports
$result = $conn->query("SELECT * FROM pending_reports WHERE status = 'unclaimed'");

if ($result->num_rows > 0) {
    echo "<table class='table'><tr><th>User ID</th><th>Item Name</th><th>Category</th><th>Status</th><th>Action</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>" . htmlspecialchars($row["user_id"]) . "</td>
                <td>" . htmlspecialchars($row["item_name"]) . "</td>
                <td>" . htmlspecialchars($row["category"]) . "</td>
                <td>" . htmlspecialchars($row["status"]) . "</td>
                <td>
                    <a href='approve_report.php?approve_id=" . htmlspecialchars($row["report_id"]) . "' class='btn btn-success'>Approve</a>
                    <a href='approve_report.php?id=" . htmlspecialchars($row["report_id"]) . "' class='btn btn-danger'>Delete</a>
                </td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "No pending reports found.";
}

// Close the database connection
$conn->close();
