<?php
// Start session
session_start();

// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "signup_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Prepare and bind
    $stmt = $conn->prepare("SELECT password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);

    // Execute statement
    $stmt->execute();
    $stmt->store_result();

    // Check if user exists
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($hashed_password);
        $stmt->fetch();

        // Verify the password
        if (password_verify($password, $hashed_password)) {
            // Authentication successful
            $_SESSION['username'] = $username; // Store username in session
            header("Location: ../../Frontend/home.html");
            exit();
        } else {
            // Invalid password
            $error_message = "Invalid username or password.";
        }
    } else {
        // User not found
        $error_message = "Invalid username or password.";
    }

    // Close statement and connection
    $stmt->close();
    $conn->close();
}

// Include the login form
//include 'login_form.php';
?>