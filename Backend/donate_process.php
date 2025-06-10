<?php
require_once 'config/db.php';
$conn = getDBConnection();

$profileId = isset($_GET['profile_id']) ? intval($_GET['profile_id']) : 0;

$stmt = $conn->prepare("SELECT * FROM orphanage_profiles WHERE id = ?");
$stmt->bind_param("i", $profileId);
$stmt->execute();
$result = $stmt->get_result();
$profile = $result->fetch_assoc();
$stmt->close();

if (!$profile) {
    die("Orphanage profile not found");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donate to <?php echo htmlspecialchars($profile['orphanage_name']); ?></title>
    <link rel="stylesheet" href="css/Donate.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <h2>Donate to <?php echo htmlspecialchars($profile['orphanage_name']); ?></h2>
        
        <div class="profile-details">
            <?php if (!empty($profile['profile_picture'])): ?>
                <img src="<?php echo $profile['profile_picture']; ?>" alt="Profile Picture" class="profile-img">
            <?php endif; ?>
            
            <div class="info">
                <p><strong>Location:</strong> <?php echo htmlspecialchars($profile['location']); ?></p>
                <p><strong>Mobile Money:</strong> <?php echo htmlspecialchars($profile['mobile_money_number']); ?> (<?php echo htmlspecialchars($profile['mobile_money_name']); ?>)</p>
                <p><strong>Project:</strong> <?php echo nl2br(htmlspecialchars($profile['project_description'])); ?></p>
            </div>
        </div>
        
        <form action="process_donation.php" method="POST" class="donation-form">
            <input type="hidden" name="profile_id" value="<?php echo $profile['id']; ?>">
            
            <div class="form-group">
                <label for="amount">Amount:</label>
                <input type="number" id="amount" name="amount" min="1" required>
            </div>
            
            <div class="form-group">
                <label for="donor_name">Your Name:</label>
                <input type="text" id="donor_name" name="donor_name" required>
            </div>
            
            <div class="form-group">
                <label for="donor_email">Your Email:</label>
                <input type="email" id="donor_email" name="donor_email" required>
            </div>
            
            <button type="submit" class="submit-btn">Complete Donation</button>
        </form>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
<?php $conn->close(); ?>