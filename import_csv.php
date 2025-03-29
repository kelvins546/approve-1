<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "approve";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_POST['import'])) {
    $file = $_FILES['csv_file']['tmp_name'];

    if (!$file) {
        die("No file uploaded.");
    }

    $handle = fopen($file, "r");
    fgetcsv($handle); // Skip first row (header)

    while (($row = fgetcsv($handle, 1000, ",")) !== false) {
        $name = $row[0];
        $card_number = $row[1];
        $card_password = $row[2];

        $stmt = $conn->prepare("INSERT INTO user (name, card_number, card_password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $card_number, $card_password);
        $stmt->execute();
    }

    fclose($handle);
    echo "<script>alert('Users imported successfully!'); window.location.href='user_profile.php';</script>";
}
