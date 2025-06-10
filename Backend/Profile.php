<?php
// Database connection function with error reporting
function getDBConnection()
{
    static $conn = null;

    if ($conn === null) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        try {
            $conn = new mysqli('127.0.0.1', 'root', '', 'Orphanage_db', '3306');
            if ($conn->connect_error) {
                throw new Exception("Connection failed: " . $conn->connect_error);
            }
            $conn->set_charset("utf8mb4");
        } catch (Exception $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    return $conn;
}

// Input sanitization function
function sanitizeInput($data)
{
    return htmlspecialchars(strip_tags(trim($data)));
}

// ==================== PROFILE HANDLING ====================
$conn = getDBConnection();
$error = null;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_profile'])) {
    try {
        // Handle file upload
        $targetDir = "uploads/";
        if (!file_exists($targetDir)) {
            if (!mkdir($targetDir, 0777, true)) {
                throw new Exception("Failed to create upload directory");
            }
        }

        $profilePicture = "";
        if (isset($_FILES["profile-photo"]) && $_FILES["profile-photo"]["error"] == UPLOAD_ERR_OK) {
            // Enhanced file upload security
            $fileName = preg_replace("/[^a-zA-Z0-9\._-]/", "", $_FILES["profile-photo"]["name"]);
            $fileSize = $_FILES["profile-photo"]["size"];
            $fileTmpName = $_FILES["profile-photo"]["tmp_name"];
            $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            // Validate file
            $allowTypes = ['jpg', 'png', 'jpeg', 'gif'];
            if (!in_array($fileType, $allowTypes)) {
                throw new Exception("Only JPG, JPEG, PNG & GIF files are allowed");
            }
            if ($fileSize > 2 * 1024 * 1024) { // 2MB limit
                throw new Exception("File size must be less than 2MB");
            }

            $targetFilePath = $targetDir . uniqid() . "_" . $fileName;
            if (!move_uploaded_file($fileTmpName, $targetFilePath)) {
                throw new Exception("Error uploading file");
            }
            $profilePicture = $targetFilePath;
        }

        // Validate and sanitize all inputs
        $required = [
            'orphanageName',
            'email',
            'password',
            'phone',
            'location',
            'mobile_money_number',
            'mobile_money_name',
            'num_children'
        ];
        $postData = [];

        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                throw new Exception(ucfirst(str_replace('_', ' ', $field)) . " is required");
            }
            $postData[$field] = sanitizeInput($_POST[$field]);
        }

        // Additional field processing
        $email = filter_var($postData['email'], FILTER_SANITIZE_EMAIL);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }

        $numChildren = intval($postData['num_children']);
        if ($numChildren < 1) {
            throw new Exception("Number of children must be at least 1");
        }

        $established = !empty($_POST['established']) ? $_POST['established'] : null;
        $area = !empty($_POST['area']) ? sanitizeInput($_POST['area']) : null;
        $project = !empty($_POST['project']) ? sanitizeInput($_POST['project']) : null;

        // Check for duplicate email
        $checkEmail = $conn->prepare("SELECT id FROM orphanage_profiles WHERE email = ?");
        $checkEmail->bind_param("s", $email);
        $checkEmail->execute();
        if ($checkEmail->get_result()->num_rows > 0) {
            throw new Exception("Email already registered");
        }

        // Prepare and execute SQL
        $stmt = $conn->prepare("INSERT INTO orphanage_profiles (
            profile_picture, orphanage_name, email, password, phone, location, 
            area, established, mobile_money_number, mobile_money_name, 
            num_children, project
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $hashedPassword = password_hash($postData['password'], PASSWORD_DEFAULT);

        $stmt->bind_param(
            "ssssssssisis",
            $profilePicture,
            $postData['orphanageName'],
            $email,
            $hashedPassword,
            $postData['phone'],
            $postData['location'],
            $area,
            $established,
            $postData['mobile_money_number'],
            $postData['mobile_money_name'],
            $numChildren,
            $project
        );

        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        // Success - redirect
        header("Location: Donate.php");
        exit();
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
