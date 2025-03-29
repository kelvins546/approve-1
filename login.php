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
            header("Location: found_report.php");
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
    <title>Log in</title>
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
    @font-face {
        font-family: 'Canva Sans';
        src: url('fonts/CanvaSans-Regular.woff2') format('woff2'),
            url('fonts/CanvaSans-Regular.woff') format('woff'),
            url('fonts/CanvaSans-Regular.ttf') format('truetype');
        font-weight: normal;
        font-style: normal;
    }

    /* General styles */
    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
        font-family: 'Hanken Grotesk', Arial, sans-serif;
    }

    .LAFh1 {
        font-family: "League Spartan", sans-serif;
        font-weight: bold;
        
    }

    .paragLOGIN {
        font-family: 'Canva Sans', sans-serif;
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
        z-index: 1000;
        width: 100%;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        justify-content: space-between;
    }

    .navbar-logo {
        height: 90px;
        width: auto;
        margin-right: 10px;
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

    .navbar-links {
        margin-left: auto;
        margin-right: 170px;
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

    /* .nav-login styles */
    .nav-login {
        width: 110px;
        height: 30px;
        padding: 10px;
        border: 1px solid black;
        border-radius: 3px;
        background-color: #fff89f;
        color: #545454;
        cursor: pointer;
        display: flex;
        justify-content: center;
        text-align: center;
        margin-left: auto;
        margin-right: 40px;
        align-items: center;
    }

    .nav-login:hover {
        background-color: #fdd400;
    }

    .main-container {
        display: flex;
        justify-content: space-between;
        /* Aligns both containers side by side */
        align-items: center;
        padding: 20px;
        padding-left: 60px;
        margin: 50px auto;
        /* Centers the container on the page */
    }

    .info-container {
        flex: 1;
        /* Adjusts size relative to the form container */
        padding: 20px;
        background-color: transparent;
        color: #fff;
        border-radius: 0px;
        margin-left: 100px;
        
    }

    .info-container h1 {
        margin-bottom: 15px;
        font-weight: bold;
        font-size: 49px;
        width: 440px;
    }

    .info-container p {
        font-size: 14px;
        line-height: 1.6;
        width: 500px;
        font-weight: normal;
        text-align: justify;

    }

    .form-container {
        width: 550px;
        padding: 20px;
        margin-right: 100px;
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
        border: 1px solid #ff8215;
        border-radius: 3px;
        background-color: #ff8215;
        color: #fff;
        margin-left: auto;
        cursor: pointer;

    }

    .form-group input[type="submit"]:hover {
        background-color: #cc6810;
    }

    input::placeholder {
        color: #b3b3b3;
        /* Lighter grey color */
        opacity: 1;
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

    .error-message {
        color: red;
        font-size: 14px;
        margin-bottom: 10px;
        text-align: center;
    }
    </style>
</head>

<body>

    <main>
        <div class="navbar">
            <img src="images/logo.png" alt="Logo" class="navbar-logo">
            <span class="navbar-text">UNIVERSITY OF CALOOCAN CITY</span>
            <div class="navbar-links">
                <a href="#">Home</a>
                <a href="#">Guidelines</a>
                <a href="#">Browse Reports</a>
            </div>
        </div>


        <!-- Form container -->
        <div class="main-container">
            <div class="info-container">
                <h1 class="LAFh1"><strong>LOST AND FOUND </strong>HELP DESK</h1>
                <p class="paragLOGIN">

                </p>
            </div>

            <div class="form-container">
                <h4>Fill out the details first.</h4>
                <hr class="form-hr">

                <!-- Display error message if there is one -->
                <?php if ($errorMessage): ?>
                <div class="error-message"><?php echo $errorMessage; ?></div>
                <?php endif; ?>

                <form action="login.php" method="post">
                    <div class="form-group">
                        <label for="cardNumber">Student ID:</label>
                        <input type="text" id="cardNumber" name="cardNumber" required maxlength="11"
                            placeholder="20230277-N">
                        <label for="" class="label-under">Enter your student ID.</label>
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