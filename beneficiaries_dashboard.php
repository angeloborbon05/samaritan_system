<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once '../config/database.php';
require_once 'beneficiaries_functions.php';

// Check if the beneficiary is logged in
if (!isset($_SESSION['user']) || $_SESSION['user']['user_type'] != 'beneficiary') {
    header('Location: ../auth/index.php');
    exit();
}

$userId = $_SESSION['user']['id'];
$beneficiaryProfile = getBeneficiaryProfile($userId);

// If no profile found, redirect to create profile page
if (!$beneficiaryProfile) {
    header('Location: create_profile.php');
    exit();
}

$beneficiaryId = $beneficiaryProfile['id'];
$stats = getBeneficiaryStats($beneficiaryId);
$recentHelp = getRecentHelpReceived($beneficiaryId);
$activeRequests = getActiveRequests($beneficiaryId);
$notifications = getNotifications($userId);
$unreadCount = getUnreadNotificationCount($userId);

// Get notification settings
$notificationSettings = getNotificationSettings($userId);

// Get paginated notifications
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$notifications = getNotifications($userId, $limit, $offset);
$totalNotifications = getTotalNotificationCount($userId);
$totalPages = ceil($totalNotifications / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Samaritan System - Beneficiary Dashboard</title>
    <link rel="stylesheet" href="beneficiaries_style.css">
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
                <li data-target="my-posts">
                    <a href="#"><i class="fas fa-clipboard-list"></i><span>My Posts</span></a>
                </li>
                <li data-target="find-donors">
                    <a href="#"><i class="fas fa-search"></i><span>Find Donors</span></a>
                </li>
                <li data-target="create-post">
                    <a href="#"><i class="fas fa-plus-circle"></i><span>Create Post</span></a>
                </li>
                <li data-target="profile">
                    <a href="#"><i class="fas fa-user"></i><span>Profile</span></a>
                </li>
                <li data-target="logout">
                    <a href="#"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
                </li>
            </ul>
            <div class="user-info">
                <div class="user-avatar">
                    <img src="<?php echo $beneficiaryProfile['profile_image'] ?? '/placeholder.svg?height=40&width=40'; ?>" alt="User Avatar">
                </div>
                <div class="user-details">
                    <h4><?php echo htmlspecialchars($beneficiaryProfile['full_name']); ?></h4>
                    <p>Beneficiary</p>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header>
                <div class="welcome-message">
                    <h2>Welcome back, <?php echo htmlspecialchars($beneficiaryProfile['full_name']); ?>!</h2>
                    <p>Let's check your assistance requests and updates</p>
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
                <div class="dashboard-overview">
                    <div class="stats-container">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-hands-helping"></i>
                            </div>
                            <div class="stat-details">
                                <h3><?php echo $stats['help_received']; ?></h3>
                                <p>Help Received</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-clipboard-list"></i>
                            </div>
                            <div class="stat-details">
                                <h3><?php echo $stats['active_requests']; ?></h3>
                                <p>Active Requests</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-star"></i>
                            </div>
                            <div class="stat-details">
                                <h3><?php echo $stats['donors_rated']; ?></h3>
                                <p>Donors Rated</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="stat-details">
                                <h3><?php echo $stats['total_posts']; ?></h3>
                                <p>Total Posts</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Help Received -->
                <div class="help-received-container">
                    <div class="section-header">
                        <h2>Recent Help Received</h2>
                        <a href="#" class="view-all">View All</a>
                    </div>
                    <div class="help-received-list">
                        <?php foreach ($recentHelp as $help): ?>
                            <div class="help-item">
                                <div class="help-header">
                                    <div class="donor-info">
                                        <img src="<?php echo $help['donor_image'] ?? '/placeholder.svg?height=50&width=50'; ?>" alt="Donor">
                                        <div>
                                            <h3><?php echo htmlspecialchars($help['donor_name']); ?></h3>
                                            <div class="stars">
                                                <?php
                                                $rating = isset($help['rating']) ? $help['rating'] : 0;
                                                for ($i = 1; $i <= 5; $i++) {
                                                    echo '<i class="fas fa-star' . ($i <= $rating ? '' : ' far') . '"></i>';
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="help-date">
                                        <span><?php echo date('M d, Y', strtotime($help['created_at'])); ?></span>
                                    </div>
                                </div>
                                <div class="help-content">
                                    <p><?php echo htmlspecialchars($help['description']); ?></p>
                                    <?php if (!empty($help['image_path'])): ?>
                                        <div class="help-images">
                                            <img src="<?php echo htmlspecialchars($help['image_path']); ?>" alt="Help Image">
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="help-footer">
                                    <div class="help-type">
                                        <span><i class="fas fa-tag"></i> <?php echo htmlspecialchars($help['post_title']); ?></span>
                                    </div>
                                    <div class="help-actions">
                                        <?php if (!isset($help['rating'])): ?>
                                            <button class="btn-rate" data-help-id="<?php echo $help['id']; ?>">
                                                <i class="fas fa-star"></i> Rate
                                            </button>
                                        <?php else: ?>
                                            <button class="btn-rate" data-rated="true">
                                                <i class="fas fa-star"></i> Rated
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn-thank">
                                            <i class="fas fa-envelope"></i> Send Thanks
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Active Requests -->
                <div class="active-requests-container">
                    <div class="section-header">
                        <h2>Your Active Requests</h2>
                        <a href="#" class="view-all">View All</a>
                    </div>
                    <div class="active-requests-list">
                        <?php foreach ($activeRequests as $request): ?>
                            <div class="request-item">
                                <div class="request-header">
                                    <h3><?php echo htmlspecialchars($request['title']); ?></h3>
                                    <div class="request-status <?php echo $request['status']; ?>">
                                        <span><?php echo ucfirst(str_replace('_', ' ', $request['status'])); ?></span>
                                    </div>
                                </div>
                                <div class="request-content">
                                    <p><?php echo htmlspecialchars($request['description']); ?></p>
                                    <div class="request-meta">
                                        <span><i class="fas fa-calendar"></i> Posted: <?php echo date('M d, Y', strtotime($request['created_at'])); ?></span>
                                        <span><i class="fas fa-eye"></i> Views: <?php echo $request['views'] ?? 0; ?></span>
                                        <span><i class="fas fa-comment"></i> Responses: <?php echo $request['responses']; ?></span>
                                    </div>
                                </div>
                                <div class="request-footer">
                                    <button class="btn-edit" data-post-id="<?php echo $request['id']; ?>">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="btn-view-responses" data-post-id="<?php echo $request['id']; ?>">
                                        <i class="fas fa-comments"></i> View Responses
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <!-- My Posts Section -->
            <section id="my-posts" class="content-section">
                <h1 class="section-title">My Posts</h1>
                
                <div class="posts-filter">
                    <div class="filter-buttons">
                        <button class="filter-btn active" data-filter="all">All Posts</button>
                        <button class="filter-btn" data-filter="pending">Pending</button>
                        <button class="filter-btn" data-filter="in-progress">In Progress</button>
                        <button class="filter-btn" data-filter="completed">Completed</button>
                    </div>
                    <div class="search-filter">
                        <input type="text" placeholder="Search posts...">
                        <button><i class="fas fa-search"></i></button>
                    </div>
                </div>
                
                <div class="posts-list">
                    <div class="post-item" data-status="pending">
                        <div class="post-header">
                            <h3>School Supplies for Children</h3>
                            <div class="post-status pending">
                                <span>Pending</span>
                            </div>
                        </div>
                        <div class="post-content">
                            <p>Requesting school supplies for my three children for the upcoming school year.</p>
                            <div class="post-images">
                                <img src="/placeholder.svg?height=150&width=200" alt="Post Image">
                            </div>
                        </div>
                        <div class="post-meta">
                            <span><i class="fas fa-calendar"></i> Posted: May 10, 2023</span>
                            <span><i class="fas fa-eye"></i> Views: 45</span>
                            <span><i class="fas fa-comment"></i> Responses: 2</span>
                        </div>
                        <div class="post-footer">
                            <button class="btn-edit"><i class="fas fa-edit"></i> Edit</button>
                            <button class="btn-delete"><i class="fas fa-trash"></i> Delete</button>
                            <button class="btn-view-responses"><i class="fas fa-comments"></i> View Responses</button>
                        </div>
                    </div>

                    <div class="post-item" data-status="in-progress">
                        <div class="post-header">
                            <h3>Home Repair After Flood</h3>
                            <div class="post-status in-progress">
                                <span>In Progress</span>
                            </div>
                        </div>
                        <div class="post-content">
                            <p>Need assistance with home repairs after recent flooding damaged our walls and floor.</p>
                            <div class="post-images">
                                <img src="/placeholder.svg?height=150&width=200" alt="Post Image">
                                <img src="/placeholder.svg?height=150&width=200" alt="Post Image">
                            </div>
                        </div>
                        <div class="post-meta">
                            <span><i class="fas fa-calendar"></i> Posted: May 5, 2023</span>
                            <span><i class="fas fa-eye"></i> Views: 78</span>
                            <span><i class="fas fa-comment"></i> Responses: 5</span>
                        </div>
                        <div class="post-footer">
                            <button class="btn-edit"><i class="fas fa-edit"></i> Edit</button>
                            <button class="btn-delete"><i class="fas fa-trash"></i> Delete</button>
                            <button class="btn-view-responses"><i class="fas fa-comments"></i> View Responses</button>
                        </div>
                    </div>

                    <div class="post-item" data-status="completed">
                        <div class="post-header">
                            <h3>Food Assistance Request</h3>
                            <div class="post-status completed">
                                <span>Completed</span>
                            </div>
                        </div>
                        <div class="post-content">
                            <p>Requested food assistance for my family of four. We received help from John Doe.</p>
                            <div class="post-images">
                                <img src="/placeholder.svg?height=150&width=200" alt="Post Image">
                            </div>
                        </div>
                        <div class="post-meta">
                            <span><i class="fas fa-calendar"></i> Posted: April 20, 2023</span>
                            <span><i class="fas fa-eye"></i> Views: 92</span>
                            <span><i class="fas fa-comment"></i> Responses: 3</span>
                        </div>
                        <div class="post-footer">
                            <button class="btn-view-details"><i class="fas fa-info-circle"></i> View Details</button>
                            <button class="btn-view-responses"><i class="fas fa-comments"></i> View Responses</button>
                        </div>
                    </div>

                    <div class="post-item" data-status="completed">
                        <div class="post-header">
                            <h3>Medical Assistance for Child</h3>
                            <div class="post-status completed">
                                <span>Completed</span>
                            </div>
                        </div>
                        <div class="post-content">
                            <p>Requested medical assistance for my child's treatment. Received help from Jane Smith.</p>
                            <div class="post-images">
                                <img src="/placeholder.svg?height=150&width=200" alt="Post Image">
                                <img src="/placeholder.svg?height=150&width=200" alt="Post Image">
                            </div>
                        </div>
                        <div class="post-meta">
                            <span><i class="fas fa-calendar"></i> Posted: April 10, 2023</span>
                            <span><i class="fas fa-eye"></i> Views: 105</span>
                            <span><i class="fas fa-comment"></i> Responses: 4</span>
                        </div>
                        <div class="post-footer">
                            <button class="btn-view-details"><i class="fas fa-info-circle"></i> View Details</button>
                            <button class="btn-view-responses"><i class="fas fa-comments"></i> View Responses</button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Find Donors Section -->
            <section id="find-donors" class="content-section">
                <h1 class="section-title">Find Donors</h1>
                
                <div class="donors-filter">
                    <div class="filter-group">
                        <label>Filter by:</label>
                        <select id="location-filter">
                            <option value="">Location</option>
                            <option value="manila">Manila</option>
                            <option value="quezon">Quezon City</option>
                            <option value="makati">Makati</option>
                        </select>
                        <select id="help-type-filter">
                            <option value="">Type of Help</option>
                            <option value="food">Food</option>
                            <option value="medical">Medical</option>
                            <option value="education">Education</option>
                            <option value="financial">Financial</option>
                        </select>
                        <select id="rating-filter">
                            <option value="">Rating</option>
                            <option value="5">5 Stars</option>
                            <option value="4">4+ Stars</option>
                            <option value="3">3+ Stars</option>
                        </select>
                    </div>
                    <div class="search-filter">
                        <input type="text" placeholder="Search donors...">
                        <button><i class="fas fa-search"></i></button>
                    </div>
                </div>
                
                <div class="donors-list">
                    <div class="donor-card">
                        <div class="donor-header">
                            <div class="donor-avatar">
                                <img src="/placeholder.svg?height=80&width=80" alt="Donor">
                            </div>
                            <div class="donor-info">
                                <h3>John Doe</h3>
                                <div class="donor-rating">
                                    <div class="stars">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                    </div>
                                    <span>(15 ratings)</span>
                                </div>
                                <p><i class="fas fa-map-marker-alt"></i> Manila, Philippines</p>
                            </div>
                        </div>
                        <div class="donor-content">
                            <div class="donor-stats">
                                <div class="stat">
                                    <h4>10</h4>
                                    <p>Families Helped</p>
                                </div>
                                <div class="stat">
                                    <h4>15</h4>
                                    <p>Donations</p>
                                </div>
                                <div class="stat">
                                    <h4>Food, Medical</h4>
                                    <p>Help Types</p>
                                </div>
                            </div>
                            <div class="donor-bio">
                                <p>I am passionate about helping others and making a positive impact in my community.</p>
                            </div>
                        </div>
                        <div class="donor-footer">
                            <button class="btn-view-profile"><i class="fas fa-user"></i> View Profile</button>
                            <button class="btn-contact"><i class="fas fa-envelope"></i> Contact</button>
                        </div>
                    </div>

                    <div class="donor-card">
                        <div class="donor-header">
                            <div class="donor-avatar">
                                <img src="/placeholder.svg?height=80&width=80" alt="Donor">
                            </div>
                            <div class="donor-info">
                                <h3>Jane Smith</h3>
                                <div class="donor-rating">
                                    <div class="stars">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="far fa-star"></i>
                                    </div>
                                    <span>(12 ratings)</span>
                                </div>
                                <p><i class="fas fa-map-marker-alt"></i> Quezon City, Philippines</p>
                            </div>
                        </div>
                        <div class="donor-content">
                            <div class="donor-stats">
                                <div class="stat">
                                    <h4>8</h4>
                                    <p>Families Helped</p>
                                </div>
                                <div class="stat">
                                    <h4>12</h4>
                                    <p>Donations</p>
                                </div>
                                <div class="stat">
                                    <h4>Medical, Education</h4>
                                    <p>Help Types</p>
                                </div>
                            </div>
                            <div class="donor-bio">
                                <p>I believe in supporting education and healthcare for those in need.</p>
                            </div>
                        </div>
                        <div class="donor-footer">
                            <button class="btn-view-profile"><i class="fas fa-user"></i> View Profile</button>
                            <button class="btn-contact"><i class="fas fa-envelope"></i> Contact</button>
                        </div>
                    </div>

                    <div class="donor-card">
                        <div class="donor-header">
                            <div class="donor-avatar">
                                <img src="/placeholder.svg?height=80&width=80" alt="Donor">
                            </div>
                            <div class="donor-info">
                                <h3>Robert Johnson</h3>
                                <div class="donor-rating">
                                    <div class="stars">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star-half-alt"></i>
                                    </div>
                                    <span>(9 ratings)</span>
                                </div>
                                <p><i class="fas fa-map-marker-alt"></i> Makati, Philippines</p>
                            </div>
                        </div>
                        <div class="donor-content">
                            <div class="donor-stats">
                                <div class="stat">
                                    <h4>6</h4>
                                    <p>Families Helped</p>
                                </div>
                                <div class="stat">
                                    <h4>9</h4>
                                    <p>Donations</p>
                                </div>
                                <div class="stat">
                                    <h4>Food, Financial</h4>
                                    <p>Help Types</p>
                                </div>
                            </div>
                            <div class="donor-bio">
                                <p>I focus on providing financial assistance and food supplies to families in need.</p>
                            </div>
                        </div>
                        <div class="donor-footer">
                            <button class="btn-view-profile"><i class="fas fa-user"></i> View Profile</button>
                            <button class="btn-contact"><i class="fas fa-envelope"></i> Contact</button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Create Post Section -->
            <section id="create-post" class="content-section">
                <h1 class="section-title">Create New Request</h1>
                
                <div class="create-post-container">
                    <form class="create-post-form">
                        <div class="form-section">
                            <h3>Personal Information</h3>
                            <div class="form-group">
                                <label for="full-name">Full Name</label>
                                <input type="text" id="full-name" value="Maria Santos" required>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="birthdate">Birth Date</label>
                                    <input type="date" id="birthdate" value="1990-05-15" required>
                                </div>
                                <div class="form-group">
                                    <label for="gender">Gender</label>
                                    <select id="gender" required>
                                        <option value="female" selected>Female</option>
                                        <option value="male">Male</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="address">Address</label>
                                <input type="text" id="address" value="123 Main St, Quezon City, Philippines" required>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="contact-number">Contact Number</label>
                                    <input type="tel" id="contact-number" value="+63 912 345 6789" required>
                                </div>
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" id="email" value="maria.santos@example.com" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h3>Request Details</h3>
                            <div class="form-group">
                                <label for="request-title">Request Title</label>
                                <input type="text" id="request-title" placeholder="E.g., Food Assistance for Family of Four" required>
                            </div>
                            <div class="form-group">
                                <label for="request-type">Type of Help Needed</label>
                                <select id="request-type" required>
                                    <option value="">Select type of help</option>
                                    <option value="food">Food Assistance</option>
                                    <option value="medical">Medical Assistance</option>
                                    <option value="education">Educational Support</option>
                                    <option value="housing">Housing/Shelter</option>
                                    <option value="financial">Financial Support</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="request-description">Description</label>
                                <textarea id="request-description" rows="5" placeholder="Describe your situation and what kind of help you need..." required></textarea>
                            </div>
                            <div class="form-group">
                                <label for="request-urgency">Urgency Level</label>
                                <select id="request-urgency" required>
                                    <option value="">Select urgency level</option>
                                    <option value="low">Low - Within a month</option>
                                    <option value="medium">Medium - Within two weeks</option>
                                    <option value="high">High - Within a week</option>
                                    <option value="critical">Critical - Immediate</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h3>Verification & Documentation</h3>
                            <div class="form-group">
                                <label for="valid-id">Upload Valid ID</label>
                                <div class="file-upload">
                                    <input type="file" id="valid-id" accept="image/*" required>
                                    <div class="upload-placeholder">
                                        <i class="fas fa-id-card"></i>
                                        <span>Click to upload or drag and drop</span>
                                        <p>Supported formats: JPG, PNG, PDF (Max: 5MB)</p>
                                    </div>
                                </div>
                                <p class="form-note">Your ID will be verified but sensitive information will be blurred for privacy.</p>
                            </div>
                            <div class="form-group">
                                <label for="proof-images">Upload Proof Images</label>
                                <div class="file-upload multiple">
                                    <input type="file" id="proof-images" accept="image/*" multiple required>
                                    <div class="upload-placeholder">
                                        <i class="fas fa-images"></i>
                                        <span>Click to upload or drag and drop</span>
                                        <p>Upload images showing your situation (Max: 5 images, 5MB each)</p>
                                    </div>
                                </div>
                                <div class="uploaded-images">
                                    <!-- Images will be displayed here after upload -->
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" class="btn-secondary">Save as Draft</button>
                            <button type="submit" class="btn-primary">Submit Request</button>
                        </div>
                    </form>
                </div>
            </section>

            <!-- Profile Section -->
            <section id="profile" class="content-section">
                <h1 class="section-title">My Profile</h1>
                
                <div class="profile-container">
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <img src="/placeholder.svg?height=100&width=100" alt="Profile Picture">
                            <button class="edit-avatar"><i class="fas fa-camera"></i></button>
                        </div>
                        <div class="profile-info">
                            <h2>Maria Santos</h2>
                            <p><i class="fas fa-map-marker-alt"></i> Quezon City, Philippines</p>
                            <p><i class="fas fa-envelope"></i> maria.santos@example.com</p>
                            <p><i class="fas fa-phone"></i> +63 912 345 6789</p>
                        </div>
                    </div>
                    
                    <div class="profile-tabs">
                        <button class="tab-btn active" data-tab="personal-info">Personal Information</button>
                        <button class="tab-btn" data-tab="verification">Verification Status</button>
                        <button class="tab-btn" data-tab="account-settings">Account Settings</button>
                    </div>
                    
                    <div class="tab-content">
                        <div id="personal-info" class="tab-pane active">
                            <form class="profile-form">
                                <div class="form-group">
                                    <label for="profile-fullname">Full Name</label>
                                    <input type="text" id="profile-fullname" value="Maria Santos">
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="profile-email">Email</label>
                                        <input type="email" id="profile-email" value="maria.santos@example.com">
                                    </div>
                                    <div class="form-group">
                                        <label for="profile-phone">Phone Number</label>
                                        <input type="tel" id="profile-phone" value="+63 912 345 6789">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="profile-address">Address</label>
                                    <input type="text" id="profile-address" value="123 Main St, Quezon City, Philippines">
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="profile-birthdate">Birth Date</label>
                                        <input type="date" id="profile-birthdate" value="1990-05-15">
                                    </div>
                                    <div class="form-group">
                                        <label for="profile-gender">Gender</label>
                                        <select id="profile-gender">
                                            <option value="female" selected>Female</option>
                                            <option value="male">Male</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="profile-bio">Bio</label>
                                    <textarea id="profile-bio" rows="4">I am a mother of three children seeking assistance for their education and our daily needs.</textarea>
                                </div>
                                <button type="submit" class="btn-save">Save Changes</button>
                            </form>
                        </div>
                        
                        <div id="verification" class="tab-pane">
                            <div class="verification-status">
                                <div class="status-card verified">
                                    <div class="status-icon">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <div class="status-details">
                                        <h3>Account Verified</h3>
                                        <p>Your account has been verified. You can now post requests and receive help.</p>
                                        <span class="verification-date">Verified on: April 15, 2023</span>
                                    </div>
                                </div>
                                
                                <div class="verification-documents">
                                    <h3>Submitted Documents</h3>
                                    <div class="document-list">
                                        <div class="document-item">
                                            <div class="document-icon">
                                                <i class="fas fa-id-card"></i>
                                            </div>
                                            <div class="document-details">
                                                <h4>Valid ID</h4>
                                                <p>National ID Card</p>
                                                <span class="document-status verified">Verified</span>
                                            </div>
                                            <div class="document-actions">
                                                <button class="btn-view"><i class="fas fa-eye"></i> View</button>
                                                <button class="btn-update"><i class="fas fa-sync-alt"></i> Update</button>
                                            </div>
                                        </div>
                                        
                                        <div class="document-item">
                                            <div class="document-icon">
                                                <i class="fas fa-home"></i>
                                            </div>
                                            <div class="document-details">
                                                <h4>Proof of Address</h4>
                                                <p>Utility Bill</p>
                                                <span class="document-status verified">Verified</span>
                                            </div>
                                            <div class="document-actions">
                                                <button class="btn-view"><i class="fas fa-eye"></i> View</button>
                                                <button class="btn-update"><i class="fas fa-sync-alt"></i> Update</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div id="account-settings" class="tab-pane">
                            <form class="settings-form">
                                <div class="form-group">
                                    <label for="current-password">Current Password</label>
                                    <input type="password" id="current-password">
                                </div>
                                <div class="form-group">
                                    <label for="new-password">New Password</label>
                                    <input type="password" id="new-password">
                                </div>
                                <div class="form-group">
                                    <label for="confirm-password">Confirm New Password</label>
                                    <input type="password" id="confirm-password">
                                </div>
                                <button type="submit" class="btn-save">Update Password</button>
                            </form>
                            
                            <div class="notification-settings">
                                <h3>Notification Settings</h3>
                                <div class="setting-item">
                                    <div>
                                        <h4>Email Notifications</h4>
                                        <p>Receive email notifications for responses to your requests</p>
                                    </div>
                                    <label class="switch">
                                        <input type="checkbox" checked>
                                        <span class="slider round"></span>
                                    </label>
                                </div>
                                <div class="setting-item">
                                    <div>
                                        <h4>SMS Notifications</h4>
                                        <p>Receive text messages for urgent updates</p>
                                    </div>
                                    <label class="switch">
                                        <input type="checkbox">
                                        <span class="slider round"></span>
                                    </label>
                                </div>
                                <div class="setting-item">
                                    <div>
                                        <h4>Donor Response Notifications</h4>
                                        <p>Receive notifications when donors respond to your requests</p>
                                    </div>
                                    <label class="switch">
                                        <input type="checkbox" checked>
                                        <span class="slider round"></span>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="danger-zone">
                                <h3>Danger Zone</h3>
                                <div class="danger-item">
                                    <div>
                                        <h4>Deactivate Account</h4>
                                        <p>Temporarily disable your account</p>
                                    </div>
                                    <button class="btn-danger">Deactivate</button>
                                </div>
                                <div class="danger-item">
                                    <div>
                                        <h4>Delete Account</h4>
                                        <p>Permanently delete your account and all data</p>
                                    </div>
                                    <button class="btn-danger">Delete</button>
                                </div>
                            </div>
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
                <div class="notification-actions">
                    <button class="mark-all-read">Mark all as read</button>
                    <button class="close-modal">&times;</button>
                </div>
            </div>
            <div class="notification-list">
                <?php if (empty($notifications)): ?>
                    <div class="no-notifications">
                        <i class="fas fa-bell-slash"></i>
                        <p>No notifications yet</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($notifications as $notification): ?>
                        <div class="notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>"
                             data-id="<?php echo $notification['id']; ?>">
                            <div class="notification-icon">
                                <i class="fas fa-<?php echo getNotificationIcon($notification['type']); ?>"></i>
                            </div>
                            <div class="notification-content">
                                <h4><?php echo htmlspecialchars($notification['title']); ?></h4>
                                <p><?php echo htmlspecialchars($notification['message']); ?></p>
                                <span class="notification-time">
                                    <?php echo timeAgo($notification['created_at']); ?>
                                </span>
                            </div>
                            <div class="notification-actions">
                                <button class="delete-notification" title="Delete notification">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if ($totalPages > 1): ?>
                        <div class="notification-pagination">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <a href="?page=<?php echo $i; ?>" 
                                   class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Notification Settings Modal -->
    <div class="notification-settings-modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Notification Settings</h3>
                <button class="close-modal">&times;</button>
            </div>
            <div class="notification-settings">
                <form id="notificationSettingsForm">
                    <div class="setting-item">
                        <div>
                            <h4>Email Notifications</h4>
                            <p>Receive email notifications for responses to your requests</p>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="email_notifications" 
                                   <?php echo $notificationSettings['email_notifications'] ? 'checked' : ''; ?>>
                            <span class="slider round"></span>
                        </label>
                    </div>
                    <div class="setting-item">
                        <div>
                            <h4>SMS Notifications</h4>
                            <p>Receive text messages for urgent updates</p>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="sms_notifications"
                                   <?php echo $notificationSettings['sms_notifications'] ? 'checked' : ''; ?>>
                            <span class="slider round"></span>
                        </label>
                    </div>
                    <div class="setting-item">
                        <div>
                            <h4>Feedback Notifications</h4>
                            <p>Receive notifications when donors leave feedback</p>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="feedback_notifications"
                                   <?php echo $notificationSettings['feedback_notifications'] ? 'checked' : ''; ?>>
                            <span class="slider round"></span>
                        </label>
                    </div>
                    <button type="submit" class="btn-save">Save Settings</button>
                </form>
            </div>
        </div>
    </div>

    <script src="beneficiaries_script.js"></script>
</body>
</html>

