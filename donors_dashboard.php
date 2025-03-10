<?php
session_start();
require_once '../config/database.php';
require_once 'donors_functions.php';

// Check if donor is logged in
if (!isset($_SESSION['user']) || $_SESSION['user']['user_type'] != 'donor') {
    header('Location: ../auth/index.php');
    exit();
}

$userId = $_SESSION['user']['id'];
$donorProfile = getDonorProfile($userId);
$stats = getDonorStats($userId);
$recentActivities = getRecentActivities($userId);
$helpProofs = getHelpProofs($userId);
$ratings = getRatingsAndFeedback($userId);
$notifications = getNotifications($userId);
$unreadCount = getUnreadNotificationCount($userId);

// Format statistics
$familiesHelped = $stats['families_helped'] ?? 0;
$totalDonations = formatCurrency($stats['total_amount'] ?? 0);
$avgRating = number_format($stats['avg_rating'] ?? 0, 1);
$completedDonations = $stats['completed_donations'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Samaritan System - Donor Dashboard</title>
    <link rel="stylesheet" href="donors_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="logo">
                <i class="fas fa-hands-helping"></i>
                <span>Samaritan</span>
            </div>
            <ul class="nav-links">
                <li class="active" data-target="dashboard">
                    <a href="#"><i class="fas fa-chart-line"></i><span>Dashboard</span></a>
                </li>
                <li data-target="requests">
                    <a href="#"><i class="fas fa-hand-holding-heart"></i><span>Requests</span></a>
                </li>
                <li data-target="feedback">
                    <a href="#"><i class="fas fa-comment-alt"></i><span>Feedback</span></a>
                </li>
                <li data-target="profile">
                    <a href="#"><i class="fas fa-user"></i><span>Profile</span></a>
                </li>
                <li id="logout-btn">
                    <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
                </li>
            </ul>
            <div class="user-info">
                <div class="user-avatar">
                    <img src="<?php echo $donorProfile['profile_image'] ?? '../assets/images/default-avatar.png'; ?>" alt="User Avatar">
                </div>
                <div class="user-details">
                    <h4><?php echo htmlspecialchars($donorProfile['username']); ?></h4>
                    <p>Donor</p>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header>
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchBeneficiaries" placeholder="Search for beneficiaries...">
                </div>
                <div class="header-right">
                    <div class="notification">
                        <i class="fas fa-bell"></i>
                        <?php if ($unreadCount > 0): ?>
                            <span class="badge"><?php echo $unreadCount; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="theme-toggle">
                        <i class="fas fa-moon"></i>
                    </div>
                </div>
            </header>

            <!-- Dashboard Section -->
            <section id="dashboard" class="content-section active">
                <h1 class="section-title">Dashboard</h1>
                
                <!-- Stats Cards -->
                <div class="stats-container">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $familiesHelped; ?></h3>
                            <p>Families Helped</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-hand-holding-usd"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $totalDonations; ?></h3>
                            <p>Total Donations</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $avgRating; ?></h3>
                            <p>Average Rating</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $completedDonations; ?></h3>
                            <p>Completed Donations</p>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="recent-activity">
                    <div class="section-header">
                        <h2>Recent Activity</h2>
                        <a href="#" class="view-all">View All</a>
                    </div>
                    <div class="activity-list">
                        <?php foreach ($recentActivities as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <i class="fas fa-<?php echo $activity['type'] === 'donation' ? 'gift' : 'comment'; ?>"></i>
                                </div>
                                <div class="activity-details">
                                    <h4><?php echo $activity['type'] === 'donation' ? 
                                        "Donation to " . htmlspecialchars($activity['beneficiary_name']) :
                                        "Feedback from " . htmlspecialchars($activity['beneficiary_name']); ?></h4>
                                    <p><?php echo htmlspecialchars($activity['description']); ?></p>
                                    <span class="time"><?php echo timeAgo($activity['created_at']); ?></span>
                                </div>
                                <div class="activity-status <?php echo $activity['status']; ?>">
                                    <?php if ($activity['type'] === 'donation'): ?>
                                        <span><?php echo ucfirst($activity['status']); ?></span>
                                    <?php else: ?>
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star<?php echo $i <= $activity['rating'] ? '' : '-o'; ?>"></i>
                                        <?php endfor; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Proof of Help Provided -->
                <div class="proof-container">
                    <div class="section-header">
                        <h2>Proof of Help Provided</h2>
                        <a href="#" class="view-all">View All</a>
                    </div>
                    <div class="proof-gallery">
                        <?php foreach ($helpProofs as $proof): ?>
                            <div class="proof-item">
                                <img src="<?php echo htmlspecialchars($proof['image_path']); ?>" alt="Donation Proof">
                                <div class="proof-overlay">
                                    <h4><?php echo htmlspecialchars($proof['post_title']); ?></h4>
                                    <p><?php echo htmlspecialchars($proof['description']); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Ratings & Feedback -->
                <div class="ratings-container">
                    <div class="section-header">
                        <h2>Ratings & Feedback</h2>
                        <a href="#" class="view-all">View All</a>
                    </div>
                    <div class="ratings-summary">
                        <div class="rating-overall">
                            <h3><?php echo number_format($ratings['avg_rating'] ?? 0, 1); ?></h3>
                            <div class="stars">
                                <?php
                                $avgRating = $ratings['avg_rating'] ?? 0;
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= floor($avgRating)) {
                                        echo '<i class="fas fa-star"></i>';
                                    } elseif ($i - 0.5 <= $avgRating) {
                                        echo '<i class="fas fa-star-half-alt"></i>';
                                    } else {
                                        echo '<i class="far fa-star"></i>';
                                    }
                                }
                                ?>
                            </div>
                            <p>Based on <?php echo $ratings['total_ratings'] ?? 0; ?> ratings</p>
                        </div>
                        <div class="rating-bars">
                            <?php
                            $totalRatings = max(1, $ratings['total_ratings'] ?? 1);
                            $ratingLevels = [
                                5 => $ratings['five_star'] ?? 0,
                                4 => $ratings['four_star'] ?? 0,
                                3 => $ratings['three_star'] ?? 0,
                                2 => $ratings['two_star'] ?? 0,
                                1 => $ratings['one_star'] ?? 0
                            ];
                            foreach ($ratingLevels as $stars => $count):
                                $percentage = ($count / $totalRatings) * 100;
                            ?>
                                <div class="rating-bar-item">
                                    <span><?php echo $stars; ?> stars</span>
                                    <div class="progress-bar">
                                        <div class="progress" style="width: <?php echo $percentage; ?>%"></div>
                                    </div>
                                    <span><?php echo $count; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Requests Section -->
            <section id="requests" class="content-section">
                <h1 class="section-title">Beneficiaries Requests</h1>
                
                <!-- Filter Options -->
                <div class="filter-container">
                    <div class="filter-group">
                        <label>Filter by:</label>
                        <select id="location-filter">
                            <option value="">Location</option>
                            <option value="manila">Manila</option>
                            <option value="quezon">Quezon City</option>
                            <option value="makati">Makati</option>
                        </select>
                        <select id="urgency-filter">
                            <option value="">Urgency</option>
                            <option value="high">High</option>
                            <option value="medium">Medium</option>
                            <option value="low">Low</option>
                        </select>
                        <select id="type-filter">
                            <option value="">Type of Help</option>
                            <option value="food">Food</option>
                            <option value="medical">Medical</option>
                            <option value="education">Education</option>
                            <option value="financial">Financial</option>
                        </select>
                    </div>
                    <div class="search-filter">
                        <input type="text" placeholder="Search requests...">
                        <button><i class="fas fa-search"></i></button>
                    </div>
                </div>

                <!-- Requests Feed -->
                <div class="requests-feed">
                    <div class="request-card">
                        <div class="request-header">
                            <div class="user-info">
                                <img src="/placeholder.svg?height=50&width=50" alt="Beneficiary">
                                <div>
                                    <h3>Maria Santos</h3>
                                    <p><i class="fas fa-map-marker-alt"></i> Quezon City</p>
                                </div>
                            </div>
                            <div class="urgency high">
                                <span>High Urgency</span>
                            </div>
                        </div>
                        <div class="request-content">
                            <p>My family needs food assistance. We are a family of 4 with two young children. Any help would be greatly appreciated.</p>
                            <div class="request-images">
                                <img src="/placeholder.svg?height=150&width=200" alt="Request Image">
                            </div>
                        </div>
                        <div class="request-footer">
                            <div class="request-details">
                                <span><i class="fas fa-tag"></i> Food Assistance</span>
                                <span><i class="fas fa-calendar"></i> Posted 2 days ago</span>
                            </div>
                            <div class="request-actions">
                                <button class="btn-contact"><i class="fas fa-envelope"></i> Contact</button>
                                <button class="btn-help"><i class="fas fa-hand-holding-heart"></i> Help Now</button>
                            </div>
                        </div>
                    </div>

                    <div class="request-card">
                        <div class="request-header">
                            <div class="user-info">
                                <img src="/placeholder.svg?height=50&width=50" alt="Beneficiary">
                                <div>
                                    <h3>Juan Cruz</h3>
                                    <p><i class="fas fa-map-marker-alt"></i> Manila</p>
                                </div>
                            </div>
                            <div class="urgency medium">
                                <span>Medium Urgency</span>
                            </div>
                        </div>
                        <div class="request-content">
                            <p>I need assistance with medical bills for my child who has been diagnosed with pneumonia. Any help would be a blessing.</p>
                            <div class="request-images">
                                <img src="/placeholder.svg?height=150&width=200" alt="Request Image">
                                <img src="/placeholder.svg?height=150&width=200" alt="Request Image">
                            </div>
                        </div>
                        <div class="request-footer">
                            <div class="request-details">
                                <span><i class="fas fa-tag"></i> Medical Assistance</span>
                                <span><i class="fas fa-calendar"></i> Posted 1 week ago</span>
                            </div>
                            <div class="request-actions">
                                <button class="btn-contact"><i class="fas fa-envelope"></i> Contact</button>
                                <button class="btn-help"><i class="fas fa-hand-holding-heart"></i> Help Now</button>
                            </div>
                        </div>
                    </div>

                    <div class="request-card">
                        <div class="request-header">
                            <div class="user-info">
                                <img src="/placeholder.svg?height=50&width=50" alt="Beneficiary">
                                <div>
                                    <h3>Elena Reyes</h3>
                                    <p><i class="fas fa-map-marker-alt"></i> Makati</p>
                                </div>
                            </div>
                            <div class="urgency low">
                                <span>Low Urgency</span>
                            </div>
                        </div>
                        <div class="request-content">
                            <p>Looking for school supplies for my three children for the upcoming school year. Any educational materials would help.</p>
                            <div class="request-images">
                                <img src="/placeholder.svg?height=150&width=200" alt="Request Image">
                            </div>
                        </div>
                        <div class="request-footer">
                            <div class="request-details">
                                <span><i class="fas fa-tag"></i> Educational Support</span>
                                <span><i class="fas fa-calendar"></i> Posted 3 days ago</span>
                            </div>
                            <div class="request-actions">
                                <button class="btn-contact"><i class="fas fa-envelope"></i> Contact</button>
                                <button class="btn-help"><i class="fas fa-hand-holding-heart"></i> Help Now</button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Feedback Section -->
            <section id="feedback" class="content-section">
                <h1 class="section-title">Feedback & Ratings</h1>
                
                <div class="feedback-container">
                    <div class="feedback-summary">
                        <div class="rating-card">
                            <h3>Your Donor Rating</h3>
                            <div class="rating-value">4.8</div>
                            <div class="stars">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star-half-alt"></i>
                            </div>
                            <p>Based on 15 ratings</p>
                        </div>
                        <div class="feedback-stats">
                            <div class="stat-item">
                                <div class="stat-value">15</div>
                                <div class="stat-label">Total Feedbacks</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value">12</div>
                                <div class="stat-label">5-Star Ratings</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value">10</div>
                                <div class="stat-label">Beneficiaries Helped</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="feedback-list-container">
                        <h3>Recent Feedback</h3>
                        <div class="feedback-list">
                            <div class="feedback-item">
                                <div class="feedback-header">
                                    <div class="user-info">
                                        <img src="/placeholder.svg?height=40&width=40" alt="User">
                                        <div>
                                            <h4>Maria Santos</h4>
                                            <div class="stars">
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="date">2 days ago</span>
                                </div>
                                <p class="feedback-text">Thank you so much for the food supplies. My family is very grateful for your kindness and generosity.</p>
                                <div class="feedback-images">
                                    <img src="/placeholder.svg?height=100&width=150" alt="Feedback Image">
                                </div>
                            </div>
                            
                            <div class="feedback-item">
                                <div class="feedback-header">
                                    <div class="user-info">
                                        <img src="/placeholder.svg?height=40&width=40" alt="User">
                                        <div>
                                            <h4>Pedro Reyes</h4>
                                            <div class="stars">
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="date">1 week ago</span>
                                </div>
                                <p class="feedback-text">The school supplies you donated have helped my children continue their education. We are forever grateful.</p>
                                <div class="feedback-images">
                                    <img src="/placeholder.svg?height=100&width=150" alt="Feedback Image">
                                    <img src="/placeholder.svg?height=100&width=150" alt="Feedback Image">
                                </div>
                            </div>
                            
                            <div class="feedback-item">
                                <div class="feedback-header">
                                    <div class="user-info">
                                        <img src="/placeholder.svg?height=40&width=40" alt="User">
                                        <div>
                                            <h4>Juan Cruz</h4>
                                            <div class="stars">
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="far fa-star"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="date">2 weeks ago</span>
                                </div>
                                <p class="feedback-text">Thank you for the medical assistance. My child is now recovering well thanks to your help.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Profile Section -->
            <section id="profile" class="content-section">
                <h1 class="section-title">Personal Profile</h1>
                
                <div class="profile-container">
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <img src="<?php echo $donorProfile['profile_image'] ?? '../assets/images/default-avatar.png'; ?>" alt="Profile Picture">
                            <input type="file" id="profile-pic-input" accept="image/*" style="display: none;">
                            <button id="profile-pic-button" class="edit-avatar"><i class="fas fa-camera"></i></button>
                        </div>
                        <div class="profile-info">
                            <h2><?php echo htmlspecialchars($donorProfile['username']); ?></h2>
                            <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($donorProfile['address']); ?></p>
                            <p class="email"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($donorProfile['email']); ?></p>
                            <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($donorProfile['phone']); ?></p>
                        </div>
                    </div>
                    
                    <div class="profile-tabs">
                        <button class="tab-btn active" data-tab="personal-info">Personal Information</button>
                        <button class="tab-btn" data-tab="account-settings">Account Settings</button>
                        <button class="tab-btn" data-tab="notification-settings">Notification Settings</button>
                    </div>
                    
                    <div class="tab-content">
                        <div id="personal-info" class="tab-pane active">
                            <form id="profile-form" class="profile-form">
                                <div class="form-group">
                                    <label for="full_name">Full Name</label>
                                    <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($donorProfile['username']); ?>" required>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="email">Email</label>
                                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($donorProfile['email']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="phone">Phone Number</label>
                                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($donorProfile['phone']); ?>">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="address">Address</label>
                                    <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($donorProfile['address']); ?>">
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="birth_date">Birth Date</label>
                                        <input type="date" id="birth_date" name="birth_date" value="<?php echo htmlspecialchars($donorProfile['birth_date'] ?? ''); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="gender">Gender</label>
                                        <select id="gender" name="gender">
                                            <option value="male" <?php echo ($donorProfile['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                                            <option value="female" <?php echo ($donorProfile['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                                            <option value="other" <?php echo ($donorProfile['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="bio">Bio</label>
                                    <textarea id="bio" name="bio" rows="4"><?php echo htmlspecialchars($donorProfile['bio'] ?? ''); ?></textarea>
                                </div>
                                <button type="submit" class="btn-save">Save Changes</button>
                            </form>
                        </div>
                        
                        <div id="account-settings" class="tab-pane">
                            <form id="password-form" class="settings-form">
                                <div class="form-group">
                                    <label for="current_password">Current Password</label>
                                    <input type="password" id="current_password" name="current_password" required>
                                </div>
                                <div class="form-group">
                                    <label for="new_password">New Password</label>
                                    <input type="password" id="new_password" name="new_password" required>
                                </div>
                                <div class="form-group">
                                    <label for="confirm_password">Confirm New Password</label>
                                    <input type="password" id="confirm_password" name="confirm_password" required>
                                </div>
                                <button type="submit" class="btn-save">Update Password</button>
                            </form>
                        </div>
                        
                        <div id="notification-settings" class="tab-pane">
                            <form id="notification-form" class="settings-form">
                                <div class="setting-item">
                                    <div>
                                        <h4>Email Notifications</h4>
                                        <p>Receive email notifications for new requests and updates</p>
                                    </div>
                                    <label class="switch">
                                        <input type="checkbox" id="email-notifications" <?php echo ($donorProfile['email_notifications'] ?? false) ? 'checked' : ''; ?>>
                                        <span class="slider round"></span>
                                    </label>
                                </div>
                                <div class="setting-item">
                                    <div>
                                        <h4>SMS Notifications</h4>
                                        <p>Receive text messages for urgent requests</p>
                                    </div>
                                    <label class="switch">
                                        <input type="checkbox" id="sms-notifications" <?php echo ($donorProfile['sms_notifications'] ?? false) ? 'checked' : ''; ?>>
                                        <span class="slider round"></span>
                                    </label>
                                </div>
                                <div class="setting-item">
                                    <div>
                                        <h4>Feedback Notifications</h4>
                                        <p>Receive notifications when beneficiaries leave feedback</p>
                                    </div>
                                    <label class="switch">
                                        <input type="checkbox" id="feedback-notifications" <?php echo ($donorProfile['feedback_notifications'] ?? false) ? 'checked' : ''; ?>>
                                        <span class="slider round"></span>
                                    </label>
                                </div>
                                <button type="submit" class="btn-save">Save Notification Settings</button>
                            </form>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- Notification Modal -->
    <div class="notification-modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Notifications</h3>
                <button class="close-modal">&times;</button>
            </div>
            <div class="notification-list">
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>">
                        <div class="notification-content">
                            <h4><?php echo htmlspecialchars($notification['title']); ?></h4>
                            <p><?php echo htmlspecialchars($notification['message']); ?></p>
                            <span class="notification-time"><?php echo timeAgo($notification['created_at']); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Contact Modal -->
    <div id="contact-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Contact Beneficiary</h2>
                <span class="close-modal">&times;</span>
            </div>
            <form id="contact-form">
                <input type="hidden" id="contact-beneficiary-id" name="beneficiary_id">
                <div class="form-group">
                    <label for="contact-message">Message:</label>
                    <textarea id="contact-message" name="message" rows="4" required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel">Cancel</button>
                    <button type="submit" class="btn-primary">Send Message</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Help Modal -->
    <div id="help-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Offer Help</h2>
                <span class="close-modal">&times;</span>
            </div>
            <form id="help-form">
                <input type="hidden" id="help-beneficiary-id" name="beneficiary_id">
                <div class="form-group">
                    <label for="help-amount">Amount:</label>
                    <input type="number" id="help-amount" name="amount" min="1" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="help-message">Message (Optional):</label>
                    <textarea id="help-message" name="message" rows="4"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel">Cancel</button>
                    <button type="submit" class="btn-primary">Send Help Offer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Image Preview Modal -->
    <div id="image-preview-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <img id="preview-image" src="" alt="Preview">
            <div class="image-details">
                <h3 id="image-title"></h3>
                <p id="image-description"></p>
                <span id="image-date"></span>
            </div>
        </div>
    </div>

    <!-- Profile Picture Upload Modal -->
    <div id="profile-upload-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Update Profile Picture</h2>
                <span class="close-modal">&times;</span>
            </div>
            <div class="image-preview-container">
                <img id="profile-preview" src="" alt="Preview">
            </div>
            <form id="profile-picture-form">
                <div class="form-group">
                    <label for="profile-picture">Choose Image</label>
                    <input type="file" id="profile-picture" name="profile_picture" accept="image/*" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel">Cancel</button>
                    <button type="submit" class="btn-primary">Upload Picture</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmation-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="confirm-title">Confirm Action</h2>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <p id="confirm-message"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel">Cancel</button>
                <button type="button" class="btn-confirm btn-primary">Confirm</button>
            </div>
        </div>
    </div>

    <!-- Request Details Modal -->
    <div id="request-details-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Request Details</h2>
                <span class="close-modal">&times;</span>
            </div>
            <div class="request-details-content">
                <div class="beneficiary-info">
                    <img id="beneficiary-image" src="" alt="Beneficiary">
                    <div>
                        <h3 id="beneficiary-name"></h3>
                        <p id="beneficiary-location"></p>
                        <span id="beneficiary-rating"></span>
                    </div>
                </div>
                <div class="request-info">
                    <div class="info-group">
                        <label>Request Type:</label>
                        <span id="request-type"></span>
                    </div>
                    <div class="info-group">
                        <label>Amount Needed:</label>
                        <span id="request-amount"></span>
                    </div>
                    <div class="info-group">
                        <label>Posted Date:</label>
                        <span id="request-date"></span>
                    </div>
                    <div class="info-group">
                        <label>Status:</label>
                        <span id="request-status"></span>
                    </div>
                </div>
                <div class="request-description">
                    <h4>Description</h4>
                    <p id="request-description"></p>
                </div>
                <div class="request-images">
                    <h4>Supporting Documents</h4>
                    <div id="request-images-container"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-contact" data-beneficiary-id="">Contact</button>
                    <button type="button" class="btn-help" data-request-id="">Help Now</button>
                </div>
            </div>
        </div>
    </div>

    <script src="donors_script.js"></script>
</body>
</html>

