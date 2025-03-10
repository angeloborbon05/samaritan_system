<?php
session_start();
require_once '../config/database.php';
require_once 'beneficiaries_functions.php';

// Check if the beneficiary is logged in
if (!isset($_SESSION['user']) || $_SESSION['user']['user_type'] != 'beneficiary') {
    header('Location: ../auth/index.php');
    exit();
}

$userId = $_SESSION['user']['id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = $_POST['full_name'] ?? '';
    $address = $_POST['address'] ?? '';
    $contactNumber = $_POST['contact_number'] ?? '';
    $profileImage = $_FILES['profile_image'] ?? null;

    // Validate required fields
    if (empty($fullName) || empty($address) || empty($contactNumber)) {
        $error = "Please fill in all required fields.";
    } else {
        // Handle profile image upload
        $profileImagePath = null;
        if ($profileImage && $profileImage['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/profiles/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileExtension = strtolower(pathinfo($profileImage['name'], PATHINFO_EXTENSION));
            $fileName = uniqid() . '.' . $fileExtension;
            $targetPath = $uploadDir . $fileName;

            if (move_uploaded_file($profileImage['tmp_name'], $targetPath)) {
                $profileImagePath = 'uploads/profiles/' . $fileName;
            }
        }

        // Insert beneficiary profile
        $stmt = $conn->prepare("INSERT INTO beneficiaries (user_id, full_name, address, contact_number, profile_image) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $userId, $fullName, $address, $contactNumber, $profileImagePath);

        if ($stmt->execute()) {
            header('Location: beneficiaries_dashboard.php');
            exit();
        } else {
            $error = "Failed to create profile. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Beneficiary Profile - Samaritan System</title>
    <link rel="stylesheet" href="beneficiaries_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="create-profile-container">
            <h1>Create Your Profile</h1>
            <p>Please complete your profile to access the beneficiary dashboard.</p>

            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="create-profile-form">
                <div class="form-group">
                    <label for="full_name">Full Name *</label>
                    <input type="text" id="full_name" name="full_name" required>
                </div>

                <div class="form-group">
                    <label for="address">Address *</label>
                    <textarea id="address" name="address" required></textarea>
                </div>

                <div class="form-group">
                    <label for="contact_number">Contact Number *</label>
                    <input type="tel" id="contact_number" name="contact_number" required>
                </div>

                <div class="form-group">
                    <label for="profile_image">Profile Image</label>
                    <div class="file-upload">
                        <input type="file" id="profile_image" name="profile_image" accept="image/*">
                        <div class="upload-placeholder">
                            <i class="fas fa-user-circle"></i>
                            <span>Click to upload or drag and drop</span>
                            <p>Supported formats: JPG, PNG (Max: 5MB)</p>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-primary">Create Profile</button>
            </form>
        </div>
    </div>

    <script>
        // File upload preview
        const fileInput = document.getElementById('profile_image');
        const uploadPlaceholder = document.querySelector('.upload-placeholder');

        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                uploadPlaceholder.innerHTML = `
                    <i class="fas fa-check-circle"></i>
                    <span>${this.files[0].name}</span>
                `;
            } else {
                uploadPlaceholder.innerHTML = `
                    <i class="fas fa-user-circle"></i>
                    <span>Click to upload or drag and drop</span>
                    <p>Supported formats: JPG, PNG (Max: 5MB)</p>
                `;
            }
        });
    </script>
</body>
</html> 