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
            header("Location: pending_found_report.php");
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
    <title>Admin Log in</title>
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

        background-image: url('images/greenBlur.png');
        background-size: cover;
        background-position: center center;
        background-attachment: fixed;
        background-repeat: no-repeat;
    }




    .main-container {
        display: flex;
        flex-direction: column;
        /* Stack the children vertically */
        align-items: center;
        /* Center the children horizontally */
        gap: 20px;
        /* Space between the info and form containers */
    }

    .info-container {
        width: 100%;
        text-align: center;
        background-color: transparent;
        color: #fff;
        padding: 20px;
    }

    .info-container img {
        width: 180px;
        /* Adjust the width as needed */
        height: auto;
        margin-bottom: 20px;
    }

    .form-container {
        width: 550px;
        padding: 20px;

        border: 1px solid #ddd;
        border-radius: 0px;
        background-color: white;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.5);


    }

    .form-container h4 {
        text-align: flex-start;
        margin-bottom: 10px;
        font-weight: normal;
    }

    .form-hr {
        background-color: #545454;
        margin-bottom: 30px;
        height: 1px;
        border: none;
    }

    .form-group {
        margin-bottom: 15px;
        display: flex;
        flex-direction: column;
    }

    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-size: 14px;
    }

    .form-group input[type="text"],
    .form-group input[type="password"] {
        width: 100%;
        padding: 8px;
        border: 1px solid black;
        border-radius: 0px;
        box-sizing: border-box;
    }

    .checkbox-container {
        display: flex;
        align-items: center;
        margin-top: 10px;
    }

    .checkbox-container input[type="checkbox"] {
        margin-right: 10px;
    }


    /* Style the label for the checkbox */
    .checkbox-container label {
        font-size: 12px;
        cursor: pointer;
    }

    /* Optionally, add a hover effect on the label */
    .checkbox-container label:hover {
        color: #fdd400;
    }



    .label-under {
        margin-top: 5px;
        font-size: 12px !important;
        margin-bottom: 20px !important;
    }

    .form-group:last-child {
        display: flex;
        justify-content: flex-end;
        /* Aligns only the submit button to the right */

    }

    .form-group input[type="submit"] {
        width: 150px;
        padding: 0px;
        height: 30px;
        line-height: 30px;
        border: 1px solid #545454;
        border-radius: 3px;
        background-color: #545454;
        color: #fff;
        margin-left: auto;
        cursor: pointer;

    }

    .form-group input[type="submit"]:hover {
        background-color: #000;
    }

    input::placeholder {
        color: #b3b3b3;
        /* Lighter grey color */
        opacity: 1;
    }

    /* Footer */
    .footer {
        background-color: #dcb396;
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
        color: #545454;
    }

    .footer-logo {
        align-self: flex-start;
    }

    .footer-logo img {
        max-width: 50px;
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

    .error-message {
        color: red;
        font-size: 14px;
        margin-bottom: 10px;
        text-align: center;
    }
    </style>
</head>

<body>



    <!-- Form container -->
    <div class="main-container">
        <div class="info-container">
            <img src="images/logo.png" alt="" class="imgLogo">
        </div>

        <div class="form-container">
            <h4>WELCOME, ADMIN</h4>
            <hr class="form-hr">

            <!-- Display error message if there is one -->
            <?php if ($errorMessage): ?>
            <div class="error-message"><?php echo $errorMessage; ?></div>
            <?php endif; ?>

            <form action="login_admin.php" method="post">
                <div class="form-group">
                    <label for="cardNumber">Username</label>
                    <input type="text" id="cardNumber" name="cardNumber" required maxlength="11" placeholder=""
                        oninput="formatCardNumber(this)">
                    <label for="" class="label-under"></label>
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
    </div>
    </main>
    <script>
    function togglePassword() {
        var passwordField = document.getElementById("password");
        var showPasswordCheckbox = document.getElementById("showPassword");

        if (showPasswordCheckbox.checked) {
            passwordField.type = "text";
        } else {
            passwordField.type = "password";
        }
    }
    </script>


    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
</body>

</html>