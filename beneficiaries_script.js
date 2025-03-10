document.addEventListener("DOMContentLoaded", () => {
    // Toggle sidebar
    const sidebar = document.querySelector(".sidebar")
    const mainContent = document.querySelector(".main-content")
    const header = document.querySelector("header")
  
    function toggleSidebar() {
      sidebar.classList.toggle("collapsed")
      mainContent.classList.toggle("expanded")
    }
  
    // Mobile sidebar toggle
    function toggleMobileSidebar() {
      sidebar.classList.toggle("active")
    }
  
    // Add mobile menu button
    const mobileMenuBtn = document.createElement("button")
    mobileMenuBtn.classList.add("mobile-menu-btn")
    mobileMenuBtn.innerHTML = '<i class="fas fa-bars"></i>'
    mobileMenuBtn.addEventListener("click", toggleMobileSidebar)
  
    header.prepend(mobileMenuBtn)
  
    // Navigation
    const navLinks = document.querySelectorAll(".nav-links li")
    const contentSections = document.querySelectorAll(".content-section")
  
    navLinks.forEach((link) => {
      link.addEventListener("click", function (e) {
        const target = this.getAttribute("data-target")
  
        // Handle logout separately
        if (target === "logout") {
          e.preventDefault()
          handleLogout()
          return
        }
  
        // Remove active class from all links
        navLinks.forEach((item) => item.classList.remove("active"))
  
        // Add active class to clicked link
        this.classList.add("active")
  
        // Show corresponding content section
        contentSections.forEach((section) => {
          section.classList.remove("active")
          if (section.id === target) {
            section.classList.add("active")
          }
        })
  
        // Close mobile sidebar after navigation
        if (window.innerWidth <= 768) {
          sidebar.classList.remove("active")
        }
      })
    })
  
    // Logout functionality
    function handleLogout() {
      fetch('../auth/logout.php', {
        method: 'POST',
        credentials: 'same-origin'
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          window.location.href = '../auth/index.php'
        } else {
          alert('Logout failed. Please try again.')
        }
      })
      .catch(error => {
        console.error('Error:', error)
        alert('An error occurred during logout. Please try again.')
      })
    }
  
    // Dark mode toggle
    const themeToggle = document.querySelector(".theme-toggle")
    const body = document.body
  
    themeToggle.addEventListener("click", function () {
      body.classList.toggle("dark")
  
      // Update icon
      const icon = this.querySelector("i")
      if (body.classList.contains("dark")) {
        icon.classList.remove("fa-moon")
        icon.classList.add("fa-sun")
      } else {
        icon.classList.remove("fa-sun")
        icon.classList.add("fa-moon")
      }
  
      // Save preference to localStorage
      localStorage.setItem("darkMode", body.classList.contains("dark"))
    })
  
    // Check for saved theme preference
    if (localStorage.getItem("darkMode") === "true") {
      body.classList.add("dark")
      const icon = themeToggle.querySelector("i")
      icon.classList.remove("fa-moon")
      icon.classList.add("fa-sun")
    }
  
    // Profile tabs
    const tabBtns = document.querySelectorAll(".tab-btn")
    const tabPanes = document.querySelectorAll(".tab-pane")
  
    tabBtns.forEach((btn) => {
      btn.addEventListener("click", function () {
        // Remove active class from all buttons
        tabBtns.forEach((item) => item.classList.remove("active"))
  
        // Add active class to clicked button
        this.classList.add("active")
  
        // Show corresponding tab pane
        const target = this.getAttribute("data-tab")
        tabPanes.forEach((pane) => {
          pane.classList.remove("active")
          if (pane.id === target) {
            pane.classList.add("active")
          }
        })
      })
    })
  
    // Posts filter
    const filterBtns = document.querySelectorAll(".filter-btn")
    const postItems = document.querySelectorAll(".post-item")
  
    filterBtns.forEach((btn) => {
      btn.addEventListener("click", function () {
        // Remove active class from all buttons
        filterBtns.forEach((item) => item.classList.remove("active"))
  
        // Add active class to clicked button
        this.classList.add("active")
  
        // Filter posts
        const filter = this.getAttribute("data-filter")
        postItems.forEach((item) => {
          if (filter === "all" || item.getAttribute("data-status") === filter) {
            item.style.display = "block"
          } else {
            item.style.display = "none"
          }
        })
      })
    })
  
    // Notification functionality
    const notificationBtn = document.querySelector('.notification');
    const notificationModal = document.querySelector('.notification-modal');
    const notificationSettingsModal = document.querySelector('.notification-settings-modal');
    const closeModalBtns = document.querySelectorAll('.close-modal');
    const markAllReadBtn = document.querySelector('.mark-all-read');
    const notificationSettingsForm = document.getElementById('notificationSettingsForm');
  
    // Open notification modal
    notificationBtn.addEventListener('click', () => {
        notificationModal.style.display = 'block';
        markAllNotificationsAsRead();
    });
  
    // Close modals
    closeModalBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            notificationModal.style.display = 'none';
            notificationSettingsModal.style.display = 'none';
        });
    });
  
    // Close modals when clicking outside
    window.addEventListener('click', (e) => {
        if (e.target === notificationModal) {
            notificationModal.style.display = 'none';
        }
        if (e.target === notificationSettingsModal) {
            notificationSettingsModal.style.display = 'none';
        }
    });
  
    // Mark all notifications as read
    markAllReadBtn.addEventListener('click', () => {
        markAllNotificationsAsRead();
    });
  
    // Mark notification as read
    function markNotificationAsRead(notificationId) {
        fetch('mark_notification_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ notification_id: notificationId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const notification = document.querySelector(`.notification-item[data-id="${notificationId}"]`);
                if (notification) {
                    notification.classList.remove('unread');
                }
                updateNotificationBadge();
            }
        })
        .catch(error => console.error('Error:', error));
    }
  
    // Mark all notifications as read
    function markAllNotificationsAsRead() {
        fetch('mark_all_notifications_read.php', {
            method: 'POST',
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.querySelectorAll('.notification-item.unread').forEach(item => {
                    item.classList.remove('unread');
                });
                updateNotificationBadge();
            }
        })
        .catch(error => console.error('Error:', error));
    }
  
    // Delete notification
    function deleteNotification(notificationId) {
        if (confirm('Are you sure you want to delete this notification?')) {
            fetch('delete_notification.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ notification_id: notificationId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const notification = document.querySelector(`.notification-item[data-id="${notificationId}"]`);
                    if (notification) {
                        notification.remove();
                    }
                    updateNotificationBadge();
                    checkEmptyNotifications();
                }
            })
            .catch(error => console.error('Error:', error));
        }
    }
  
    // Update notification badge
    function updateNotificationBadge() {
        const badge = document.querySelector('.notification .badge');
        if (badge) {
            const count = parseInt(badge.textContent);
            if (count > 0) {
                badge.textContent = count - 1;
                if (count - 1 === 0) {
                    badge.remove();
                }
            }
        }
    }
  
    // Check if there are any notifications
    function checkEmptyNotifications() {
        const notifications = document.querySelectorAll('.notification-item');
        const noNotificationsDiv = document.querySelector('.no-notifications');
        
        if (notifications.length === 0 && !noNotificationsDiv) {
            const notificationList = document.querySelector('.notification-list');
            notificationList.innerHTML = `
                <div class="no-notifications">
                    <i class="fas fa-bell-slash"></i>
                    <p>No notifications yet</p>
                </div>
            `;
        }
    }
  
    // Handle notification settings
    document.querySelector('.notification-settings-btn').addEventListener('click', () => {
        notificationSettingsModal.style.display = 'block';
    });
  
    notificationSettingsForm.addEventListener('submit', (e) => {
        e.preventDefault();
        
        const formData = new FormData(notificationSettingsForm);
        const settings = {
            email_notifications: formData.get('email_notifications') === 'on' ? 1 : 0,
            sms_notifications: formData.get('sms_notifications') === 'on' ? 1 : 0,
            feedback_notifications: formData.get('feedback_notifications') === 'on' ? 1 : 0
        };
        
        fetch('update_notification_settings.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(settings)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Notification settings updated successfully');
                notificationSettingsModal.style.display = 'none';
            } else {
                showToast('Failed to update notification settings', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('An error occurred while updating settings', 'error');
        });
    });
  
    // Add event listeners for notification actions
    document.addEventListener('click', (e) => {
        if (e.target.closest('.delete-notification')) {
            const notificationId = e.target.closest('.notification-item').dataset.id;
            deleteNotification(notificationId);
        }
    });
  
    // Show toast message
    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.add('show');
        }, 100);
        
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                toast.remove();
            }, 300);
        }, 3000);
    }
  
    // Rate donor functionality
    document.querySelectorAll('.btn-rate').forEach(btn => {
      if (!btn.dataset.rated) {
        btn.addEventListener('click', function() {
          const helpId = this.dataset.helpId
          showRatingModal(helpId)
        })
      }
    })
  
    // Rating modal
    function showRatingModal(helpId) {
      const modal = document.createElement('div')
      modal.className = 'rating-modal'
      modal.innerHTML = `
        <div class="modal-content">
          <h3>Rate the Donor</h3>
          <div class="rating-stars">
            ${Array(5).fill().map((_, i) => `
              <i class="far fa-star" data-rating="${i + 1}"></i>
            `).join('')}
          </div>
          <textarea placeholder="Add a comment (optional)"></textarea>
          <div class="modal-actions">
            <button class="btn-cancel">Cancel</button>
            <button class="btn-submit">Submit</button>
          </div>
        </div>
      `
  
      document.body.appendChild(modal)
  
      // Star rating functionality
      const stars = modal.querySelectorAll('.rating-stars i')
      let selectedRating = 0
  
      stars.forEach(star => {
        star.addEventListener('mouseover', function() {
          const rating = this.dataset.rating
          stars.forEach(s => {
            if (s.dataset.rating <= rating) {
              s.classList.remove('far')
              s.classList.add('fas')
            } else {
              s.classList.remove('fas')
              s.classList.add('far')
            }
          })
        })
  
        star.addEventListener('click', function() {
          selectedRating = this.dataset.rating
          stars.forEach(s => {
            if (s.dataset.rating <= selectedRating) {
              s.classList.remove('far')
              s.classList.add('fas')
            } else {
              s.classList.remove('fas')
              s.classList.add('far')
            }
          })
        })
      })
  
      // Reset stars on mouseout
      modal.querySelector('.rating-stars').addEventListener('mouseout', () => {
        stars.forEach(star => {
          if (star.dataset.rating <= selectedRating) {
            star.classList.remove('far')
            star.classList.add('fas')
          } else {
            star.classList.remove('fas')
            star.classList.add('far')
          }
        })
      })
  
      // Handle modal actions
      modal.querySelector('.btn-cancel').addEventListener('click', () => {
        modal.remove()
      })
  
      modal.querySelector('.btn-submit').addEventListener('click', () => {
        const comment = modal.querySelector('textarea').value
        submitRating(helpId, selectedRating, comment)
      })
    }
  
    // Submit rating
    function submitRating(helpId, rating, comment) {
      fetch('submit_rating.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          help_id: helpId,
          rating: rating,
          comment: comment
        })
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          const btn = document.querySelector(`.btn-rate[data-help-id="${helpId}"]`)
          if (btn) {
            btn.dataset.rated = 'true'
            btn.innerHTML = '<i class="fas fa-star"></i> Rated'
          }
          document.querySelector('.rating-modal').remove()
        } else {
          alert('Failed to submit rating. Please try again.')
        }
      })
      .catch(error => console.error('Error:', error))
    }
  
    // Edit post functionality
    document.querySelectorAll('.btn-edit').forEach(btn => {
      btn.addEventListener('click', function() {
        const postId = this.dataset.postId
        window.location.href = `edit_post.php?id=${postId}`
      })
    })
  
    // View responses functionality
    document.querySelectorAll('.btn-view-responses').forEach(btn => {
      btn.addEventListener('click', function() {
        const postId = this.dataset.postId
        window.location.href = `view_responses.php?id=${postId}`
      })
    })
  
    // Send thanks functionality
    document.querySelectorAll('.btn-thank').forEach(btn => {
      btn.addEventListener('click', function() {
        const helpItem = this.closest('.help-item')
        const donorName = helpItem.querySelector('.donor-info h3').textContent
        showThankYouModal(donorName)
      })
    })
  
    // Thank you modal
    function showThankYouModal(donorName) {
      const modal = document.createElement('div')
      modal.className = 'thank-you-modal'
      modal.innerHTML = `
        <div class="modal-content">
          <h3>Send Thank You Message</h3>
          <p>To: ${donorName}</p>
          <textarea placeholder="Write your thank you message..."></textarea>
          <div class="modal-actions">
            <button class="btn-cancel">Cancel</button>
            <button class="btn-submit">Send</button>
          </div>
        </div>
      `
  
      document.body.appendChild(modal)
  
      modal.querySelector('.btn-cancel').addEventListener('click', () => {
        modal.remove()
      })
  
      modal.querySelector('.btn-submit').addEventListener('click', () => {
        const message = modal.querySelector('textarea').value
        sendThankYouMessage(donorName, message)
      })
    }
  
    // Send thank you message
    function sendThankYouMessage(donorName, message) {
      fetch('send_thank_you.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          donor_name: donorName,
          message: message
        })
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alert('Thank you message sent successfully!')
          document.querySelector('.thank-you-modal').remove()
        } else {
          alert('Failed to send thank you message. Please try again.')
        }
      })
      .catch(error => console.error('Error:', error))
    }
  
    // File upload preview
    const fileInputs = document.querySelectorAll('input[type="file"]')
  
    fileInputs.forEach((input) => {
      input.addEventListener("change", function () {
        const fileUpload = this.closest(".file-upload")
        const uploadPlaceholder = fileUpload.querySelector(".upload-placeholder")
  
        if (this.files.length > 0) {
          uploadPlaceholder.innerHTML = `<i class="fas fa-check-circle"></i><span>${this.files.length} file(s) selected</span>`
  
          // If it's the proof images input, show previews
          if (this.id === "proof-images") {
            const uploadedImages = document.querySelector(".uploaded-images")
            uploadedImages.innerHTML = ""
  
            for (let i = 0; i < this.files.length; i++) {
              const file = this.files[i]
              const reader = new FileReader()
  
              reader.onload = (e) => {
                const img = document.createElement("img")
                img.src = e.target.result
                img.style.width = "100px"
                img.style.height = "100px"
                img.style.objectFit = "cover"
                img.style.borderRadius = "8px"
  
                uploadedImages.appendChild(img)
              }
  
              reader.readAsDataURL(file)
            }
          }
        }
      })
    })
  })
  
  