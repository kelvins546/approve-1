<?php
// Database connection
$servername = "localhost";   // Your server name
$username = "root";          // Your database username
$password = "";              // Your database password (empty by default for XAMPP)
$database = "approve";       // Your database name

$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize error message
$errorMessage = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cardNumber = $_POST['cardNumber'];
    $password = $_POST['password'];

    // Query to verify user
    $sql = "SELECT * FROM user WHERE card_number = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $cardNumber);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Compare the plain text password with the one in the database
        if ($password === $user['card_password']) {
            // Password is correct, start a session or redirect the user
            session_start();
            $_SESSION['user_id'] = $user['card_number'];
            $_SESSION['name'] = $user['name'];

            // Redirect to user dashboard or homepage
            header("Location: userview.php");
            exit();
        } else {
            $errorMessage = "Invalid card number or password.";
        }
    } else {
        $errorMessage = "Invalid card number or password.";
    }

    $stmt->close();
}

$conn->close();
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>
    <div class="form-container">
        <h4>Fill out the details first.</h4>
        <hr class="form-hr">

        <!-- Display error message if there is one -->
        <?php if ($errorMessage): ?>
            <div class="error-message"><?php echo $errorMessage; ?></div>
        <?php endif; ?>

        <form action="login.php" method="post">
            <div class="form-group">
                <label for="cardNumber">Card Number</label>
                <input type="text" id="cardNumber" name="cardNumber" required maxlength="11"
                    placeholder="XXX-XXX-XXX" oninput="formatCardNumber(this)">
                <label for="" class="label-under">Located in your card pass.</label>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>


                <!-- Show password checkbox -->
                <div class="checkbox-container">
                    <input type="checkbox" id="showPassword" onclick="togglePassword()">
                    <label for="showPassword">Show Password</label>
                </div>
            </div>

            <div class="form-group">
                <input type="submit" value="SUBMIT">
            </div>
        </form>
    </div>
</body>

</html>