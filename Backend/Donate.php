<?php
// Database connection and data fetching
require_once 'config/db.php'; // Update this path

// Initialize variables
$profiles = [];
$error = null;
$search = $_GET['search'] ?? '';
$selectedTown = $_GET['town'] ?? '';

try {
    $conn = getDBConnection();

    // Base query
    $query = "SELECT * FROM orphanage_profiles WHERE 1=1";
    $params = [];
    $types = '';

    // Add search filter
    if (!empty($search)) {
        $query .= " AND (orphanage_name LIKE ? OR location LIKE ? OR project LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
        $types .= 'sss';
    }

    // Add town filter
    if (!empty($selectedTown)) {
        $query .= " AND location = ?";
        $params[] = $selectedTown;
        $types .= 's';
    }

    // Prepare and execute query
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $profiles = $result->fetch_all(MYSQLI_ASSOC);

    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ORPHANAGE CONNECT</title>
    <link rel="stylesheet" href="../CSS/Donate.CSS">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body>
    <header>
        <h1><img src="../Images/donationimage.jpeg" alt="Make a donation" class="donation-image"><br>SUPPORT ORPHANAGES</h1>
        <p><button class="nav-btn"><a href="home.html"><i class="fas fa-home"></i> Home</a></button></p>
        <p><button class="nav-btn"><a href="Donation.php"><i class="fas fa-donate"></i> Donation</a></button></p>
        <p><button class="nav-btn"><a href="welcome.html"><i class="fas fa-sign-out-alt"></i> Logout</a></button></p>
        <p><button class="nav-btn"><a href="#Contact-Us"><i class="fas fa-phone"></i> Contact Us</a></button></p>
    </header>

    <div class="container">
        <h2>Donate Now</h2>

        <!-- Search and Filter Section -->
        <div class="filters-container">
            <div class="dropdown">
                <span class="arrow">▼</span>
                <div class="dropdown-content">
                    <?php
                    $towns = [
                        'Douala',
                        'Yaoundé',
                        'Bamenda',
                        'Limbe',
                        'Buea',
                        'Maroua',
                        'Kumba',
                        'Bafoussam',
                        'Foumban',
                        'Nkongsamba',
                        'Garoua',
                        'Ebolowa',
                        'Kribi',
                        'Bafang',
                        'Dschang',
                        'Loum',
                        'Nkambe',
                        'Obala',
                        'Mala',
                        'Tiko'
                    ];
                    foreach ($towns as $town) {
                        echo "<p onclick=\"selectTown('$town')\">$town</p>";
                    }
                    ?>
                </div>
                <input type="text" id="selectedTown" placeholder="Selected Town" readonly>
            </div>

            <div class="search-bar">
                <form action="../Backend/Donate.php" method="GET">
                    <input type="text" placeholder="Search by name or location..." name="search" value="<?php echo htmlspecialchars($search); ?>">
                    <input type="hidden" name="town" id="townInput" value="<?php echo htmlspecialchars($selectedTown); ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
            </div>
        </div>

        <!-- Error Display -->
        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Profiles Display -->
        <div class="content">
            <?php if (!empty($profiles)): ?>
                <div class="profiles-grid">
                    <?php foreach ($profiles as $profile): ?>
                        <div class="profile-card">
                            <?php if (!empty($profile['profile_picture'])): ?>
                                <img src="<?php echo htmlspecialchars($profile['profile_picture']); ?>" alt="Profile Picture" class="profile-img">
                            <?php else: ?>
                                <div class="default-profile-icon">
                                    <i class="fas fa-user-circle profile-icon"></i>
                                </div>
                            <?php endif; ?>

                            <h3><?php echo htmlspecialchars($profile['orphanage_name']); ?></h3>
                            <p><strong>Location:</strong> <?php echo htmlspecialchars($profile['location']); ?></p>
                            <p><strong>Children:</strong> <?php echo htmlspecialchars($profile['num_children'] ?? 'N/A'); ?></p>

                            <?php
                            $project = !empty($profile['project'])
                                ? (strlen($profile['project']) > 100
                                    ? substr($profile['project'], 0, 100) . '...'
                                    : $profile['project'])
                                : 'No project description available';
                            ?>
                            <p><strong>Project:</strong> <?php echo htmlspecialchars($project); ?></p>

                            <button class="donate-btn" onclick="donateTo(<?php echo $profile['id']; ?>)">
                                <i class="fas fa-hand-holding-heart"></i> Donate Now
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-profiles">
                    <p>No orphanage profiles found matching your criteria.</p>
                    <a href="Profile.php" class="register-btn">Register an Orphanage</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="Contact-Us" id="Contact-Us">
        <h3>Contact us at:</h3>
        <ul>
            <li><i class="fas fa-phone"></i> 678950512</li>
            <li><i class="fas fa-phone"></i> 671562474</li>
            <li><i class="fas fa-envelope"></i> njobelovelinenkeni@gmail.com</li>
        </ul>
    </div>

    <footer>
        <p>© 2025 Supports Projects. All Fulfilled and Blessed.</p>
    </footer>

    <script>
        function selectTown(town) {
            document.getElementById('selectedTown').value = town;
            document.getElementById('townInput').value = town;
            document.querySelector('form').submit();
        }

        function donateTo(profileId) {
            window.location.href = `donate_process.php?profile_id=${profileId}`;
        }

        // Make dropdown functional
        document.querySelector('.arrow').addEventListener('click', function() {
            this.nextElementSibling.classList.toggle('show');
        });

        // Close dropdown when clicking outside
        window.addEventListener('click', function(e) {
            if (!e.target.matches('.arrow')) {
                const dropdowns = document.querySelectorAll('.dropdown-content');
                dropdowns.forEach(dropdown => {
                    if (dropdown.classList.contains('show')) {
                        dropdown.classList.remove('show');
                    }
                });
            }
        });
    </script>
</body>

</html>