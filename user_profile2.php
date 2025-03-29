<?php
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
    $date_printed = $_POST['date_printed'];
    $address = $_POST['address'];
    $phone_number = $_POST['phone_number'];
    $email = $_POST['email'];

    $insert_sql = "INSERT INTO user (name, card_number, card_password, date_printed, address, phone_number, email)
                   VALUES ('$name', '$card_number', '$card_password', '$date_printed', '$address', '$phone_number', '$email')";

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
    $name = $_POST['name'];
    $card_number = $_POST['card_number'];
    $card_password = $_POST['card_password']; // Plain password
    $date_printed = $_POST['date_printed'];
    $address = $_POST['address'];
    $phone_number = $_POST['phone_number'];
    $email = $_POST['email'];

    if (isset($_POST['edit_id'])) {
        // Update the user
        $update_id = $_POST['edit_id'];
        if (!empty($card_password)) {
            // Only update password if it's provided
            $stmt = $conn->prepare("UPDATE user SET name = ?, card_number = ?, card_password = ?, date_printed = ?, address = ?, phone_number = ?, email = ? WHERE id = ?");
            $stmt->bind_param("sssssssi", $name, $card_number, $card_password, $date_printed, $address, $phone_number, $email, $update_id);
        } else {
            // Do not update password if it's empty
            $stmt = $conn->prepare("UPDATE user SET name = ?, card_number = ?, date_printed = ?, address = ?, phone_number = ?, email = ? WHERE id = ?");
            $stmt->bind_param("ssssssi", $name, $card_number, $date_printed, $address, $phone_number, $email, $update_id);
        }
        $stmt->execute();
    } else {
        // Add a new user
        $stmt = $conn->prepare("INSERT INTO user (name, card_number, card_password, date_printed, address, phone_number, email) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $name, $card_number, $card_password, $date_printed, $address, $phone_number, $email);
        $stmt->execute();
    }
}

// Handle update user
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_user'])) {
    $edit_id = $_POST['edit_id'];
    $name = $_POST['name'];
    $card_number = $_POST['card_number'];
    $date_printed = $_POST['date_printed'];
    $address = $_POST['address'];
    $phone_number = $_POST['phone_number'];
    $email = $_POST['email'];
    $card_password = $_POST['card_password']; // Plain password (no hashing)

    $update_sql = "UPDATE user SET 
                   name='$name', 
                   card_number='$card_number', 
                   card_password='$card_password', 
                   date_printed='$date_printed', 
                   address='$address', 
                   phone_number='$phone_number', 
                   email='$email' 
                   WHERE id=$edit_id";

    if ($conn->query($update_sql) === TRUE) {
        echo "<script>alert('User updated successfully!'); window.location.href='user_profile.php';</script>";
    } else {
        echo "<script>alert('Error: " . $conn->error . "');</script>";
    }
}



// Fetch all users except id = 13
$sql = "SELECT * FROM user WHERE id != 13";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link rel="stylesheet" href="styles.css">
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
    </style>
</head>


<body>
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
                    <th>Date Created</th>
                    <th>Address</th>
                    <th>Phone Number</th>
                    <th>Email</th>
                    <th>Created At</th>
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
                    <td><?php echo htmlspecialchars($row['date_printed']); ?></td>
                    <td><?php echo htmlspecialchars($row['address']); ?></td>
                    <td><?php echo htmlspecialchars($row['phone_number']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td><?php echo htmlspecialchars($row['created_at']); ?></td>
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

            <label for="card_password">Password:</label>
            <input type="password" id="card_password" name="card_password"
                value="<?php echo $edit_mode ? $edit_row['card_password'] : ''; ?>" required>

            <label for="date_printed">Date Created:</label>
            <input type="date" id="date_printed" name="date_printed"
                value="<?php echo $edit_mode ? $edit_row['date_printed'] : ''; ?>" required>

            <label for="address">Address:</label>
            <textarea id="address" name="address"
                required><?php echo $edit_mode ? $edit_row['address'] : ''; ?></textarea>

            <label for="phone_number">Phone Number:</label>
            <input type="text" id="phone_number" name="phone_number" maxlength="11"
                value="<?php echo $edit_mode ? $edit_row['phone_number'] : ''; ?>" required>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?php echo $edit_mode ? $edit_row['email'] : ''; ?>"
                required>

            <input type="submit" value="<?php echo $edit_mode ? "Update User" : "Add User"; ?>">
        </form>
    </div>

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


</body>

</html>