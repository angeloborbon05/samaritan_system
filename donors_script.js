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
  const navLinks = document.querySelectorAll(".nav-links li[data-target]")
  const sections = document.querySelectorAll(".content-section")

  navLinks.forEach((link) => {
    link.addEventListener("click", function () {
      const target = this.getAttribute("data-target")
      
      // Update active nav link
      navLinks.forEach((l) => l.classList.remove("active"))
      this.classList.add("active")
      
      // Show target section
      sections.forEach((section) => {
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

  // Message handling
  let currentBeneficiaryId = null;
  let messagePollingInterval = null;

  function startMessagePolling() {
    if (messagePollingInterval) {
      clearInterval(messagePollingInterval);
    }
    if (currentBeneficiaryId) {
      loadMessages();
      messagePollingInterval = setInterval(loadMessages, 10000); // Poll every 10 seconds
    }
  }

  function stopMessagePolling() {
    if (messagePollingInterval) {
      clearInterval(messagePollingInterval);
      messagePollingInterval = null;
    }
  }

  async function loadMessages() {
    if (!currentBeneficiaryId) return;
    
    try {
      const response = await fetch('donor_actions.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          action: 'get_messages',
          beneficiary_id: currentBeneficiaryId
        })
      });
      
      const data = await response.json();
      if (data.success) {
        displayMessages(data.messages);
      }
    } catch (error) {
      console.error('Error loading messages:', error);
    }
  }

  function displayMessages(messages) {
    const messagesContainer = document.querySelector('.messages-container');
    if (!messagesContainer) return;
    
    messagesContainer.innerHTML = messages.map(message => `
      <div class="message ${message.direction}">
        <div class="message-header">
          <img src="${message.sender_image || '../assets/images/default-avatar.png'}" alt="${message.sender_name}">
          <span class="sender-name">${message.sender_name}</span>
          <span class="message-time">${formatMessageTime(message.created_at)}</span>
        </div>
        <div class="message-content">
          ${message.message}
        </div>
      </div>
    `).join('');
    
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
  }

  function formatMessageTime(timestamp) {
    const date = new Date(timestamp);
    return date.toLocaleString();
  }

  // Update contact modal to include messages
  const contactModal = document.getElementById('contact-modal');
  if (contactModal) {
    contactModal.querySelector('.modal-content').innerHTML = `
      <div class="modal-header">
        <h2>Contact Beneficiary</h2>
        <span class="close-modal">&times;</span>
      </div>
      <div class="messages-container"></div>
      <form id="contact-form">
        <input type="hidden" id="contact-beneficiary-id" name="beneficiary_id">
        <div class="form-group">
          <textarea id="contact-message" name="message" rows="3" placeholder="Type your message..." required></textarea>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-cancel">Cancel</button>
          <button type="submit" class="btn-primary">Send Message</button>
        </div>
      </form>
    `;
  }

  // Update contact button click handler
  document.querySelectorAll('.btn-contact').forEach(button => {
    button.addEventListener('click', function() {
      const beneficiaryId = this.dataset.beneficiaryId;
      currentBeneficiaryId = beneficiaryId;
      document.getElementById('contact-beneficiary-id').value = beneficiaryId;
      contactModal.style.display = 'block';
      startMessagePolling();
    });
  });

  // Update contact modal close handlers
  contactModal.querySelector('.close-modal').addEventListener('click', function() {
    contactModal.style.display = 'none';
    stopMessagePolling();
    currentBeneficiaryId = null;
  });

  contactModal.querySelector('.btn-cancel').addEventListener('click', function() {
    contactModal.style.display = 'none';
    stopMessagePolling();
    currentBeneficiaryId = null;
  });

  // Update help form submission
  const helpForm = document.getElementById('help-form');
  helpForm.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'submit_donation');
    
    try {
      const response = await fetch('donor_actions.php', {
        method: 'POST',
        body: formData
      });
      
      const result = await response.json();
      
      if (result.success) {
        showConfirmation('Success', result.message);
        helpModal.style.display = 'none';
        this.reset();
        
        // Refresh the requests list if we're on the requests section
        if (document.getElementById('requests').classList.contains('active')) {
          filterRequests();
        }
      } else {
        showConfirmation('Error', result.message || 'Error submitting help offer. Please try again.');
      }
    } catch (error) {
      console.error('Error:', error);
      showConfirmation('Error', 'An error occurred. Please try again.');
    }
  });

  // Handle help completion
  function markHelpCompleted(helpId) {
    showConfirmation(
      'Confirm Completion',
      'Are you sure you want to mark this help as completed?',
      async () => {
        try {
          const response = await fetch('donor_actions.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({
              action: 'mark_help_completed',
              help_id: helpId
            })
          });
          
          const result = await response.json();
          
          if (result.success) {
            showConfirmation('Success', result.message);
            // Refresh the activity list
            loadRecentActivities();
          } else {
            showConfirmation('Error', result.message || 'Error updating help status. Please try again.');
          }
        } catch (error) {
          console.error('Error:', error);
          showConfirmation('Error', 'An error occurred. Please try again.');
        }
      }
    );
  }

  // Add complete buttons to pending help items
  document.querySelectorAll('.activity-item[data-status="pending"]').forEach(item => {
    const helpId = item.dataset.helpId;
    if (helpId) {
      const completeBtn = document.createElement('button');
      completeBtn.className = 'btn-complete';
      completeBtn.innerHTML = '<i class="fas fa-check"></i> Mark Complete';
      completeBtn.addEventListener('click', () => markHelpCompleted(helpId));
      item.querySelector('.activity-status').appendChild(completeBtn);
    }
  });

  // Helper function to load recent activities
  async function loadRecentActivities() {
    try {
      const response = await fetch('donor_actions.php?action=get_recent_activities');
      const data = await response.json();
      
      if (data.success) {
        const activityList = document.querySelector('.activity-list');
        if (activityList) {
          activityList.innerHTML = data.activities.map(activity => `
            <div class="activity-item" data-status="${activity.status}" data-help-id="${activity.id}">
              <div class="activity-icon">
                <i class="fas fa-${activity.type === 'donation' ? 'gift' : 'comment'}"></i>
              </div>
              <div class="activity-details">
                <h4>${activity.title}</h4>
                <p>${activity.description}</p>
                <span class="time">${timeAgo(activity.created_at)}</span>
              </div>
              <div class="activity-status ${activity.status}">
                <span>${activity.status.charAt(0).toUpperCase() + activity.status.slice(1)}</span>
                ${activity.status === 'pending' ? `
                  <button class="btn-complete" onclick="markHelpCompleted(${activity.id})">
                    <i class="fas fa-check"></i> Mark Complete
                  </button>
                ` : ''}
              </div>
            </div>
          `).join('');
        }
      }
    } catch (error) {
      console.error('Error loading activities:', error);
    }
  }

  // Request Filters
  const locationFilter = document.getElementById("location-filter")
  const urgencyFilter = document.getElementById("urgency-filter")
  const typeFilter = document.getElementById("type-filter")

  async function filterRequests() {
    const filters = {
      location: locationFilter.value,
      urgency: urgencyFilter.value,
      type: typeFilter.value
    }

    try {
      const response = await fetch("donor_actions.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify({
          action: "filter_requests",
          filters: filters
        })
      })

      const result = await response.json()

      if (result.success) {
        updateRequestsList(result.requests)
      } else {
        alert(result.message || "Error filtering requests. Please try again.")
      }
    } catch (error) {
      console.error("Error:", error)
      alert("An error occurred. Please try again.")
    }
  }

  locationFilter.addEventListener("change", filterRequests)
  urgencyFilter.addEventListener("change", filterRequests)
  typeFilter.addEventListener("change", filterRequests)

  function updateRequestsList(requests) {
    const requestsList = document.querySelector(".requests-list")
    requestsList.innerHTML = ""

    requests.forEach(request => {
      const requestCard = createRequestCard(request)
      requestsList.appendChild(requestCard)
    })
  }

  function createRequestCard(request) {
    const card = document.createElement("div")
    card.className = "request-card"
    card.innerHTML = `
            <div class="request-header">
                <div class="user-info">
                    <img src="${request.profile_image || '/placeholder.svg?height=40&width=40'}" alt="Beneficiary">
                    <div>
                        <h3>${escapeHtml(request.beneficiary_name)}</h3>
                        <span class="location"><i class="fas fa-map-marker-alt"></i> ${escapeHtml(request.location)}</span>
                    </div>
                </div>
                <div class="urgency-badge ${request.urgency_level}">
                    ${request.urgency_level.charAt(0).toUpperCase() + request.urgency_level.slice(1)} Priority
                </div>
            </div>
            <div class="request-content">
                <h4>${escapeHtml(request.title)}</h4>
                <p>${escapeHtml(request.description)}</p>
                <div class="request-details">
                    <span class="amount">₱${parseFloat(request.amount_needed).toLocaleString('en-US', {minimumFractionDigits: 2})}</span>
                    <span class="date">Posted ${new Date(request.created_at).toLocaleDateString()}</span>
                </div>
            </div>
            <div class="request-footer">
                <button class="btn-help" data-request-id="${request.id}">
                    <i class="fas fa-hand-holding-heart"></i> Help Now
                </button>
                <a href="#" class="btn-view-details">View Details</a>
            </div>
        `

    // Add event listener to the help button
    const helpBtn = card.querySelector(".btn-help")
    helpBtn.addEventListener("click", function() {
      document.getElementById("request-id").value = request.id
      helpModal.style.display = "block"
    })

    return card
  }

  // Profile Actions
  const profileForm = document.getElementById("profile-form");
  const passwordForm = document.getElementById("password-form");
  const notificationForm = document.getElementById("notification-form");

  // Handle profile form submission
  profileForm.addEventListener("submit", async function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    formData.append("action", "update_profile");

    try {
      const response = await fetch("donor_actions.php", {
        method: "POST",
        body: formData
      });

      const result = await response.json();

      if (result.success) {
        alert("Profile updated successfully!");
        // Update displayed name and email if needed
        document.querySelector(".profile-info h2").textContent = formData.get("full_name");
        document.querySelector(".profile-info .email").textContent = formData.get("email");
      } else {
        alert(result.message || "Error updating profile. Please try again.");
      }
    } catch (error) {
      console.error("Error:", error);
      alert("An error occurred. Please try again.");
    }
  });

  // Handle password form submission
  passwordForm.addEventListener("submit", async function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    formData.append("action", "update_password");

    try {
      const response = await fetch("donor_actions.php", {
        method: "POST",
        body: formData
      });

      const result = await response.json();

      if (result.success) {
        alert("Password updated successfully!");
        this.reset();
      } else {
        alert(result.message || "Error updating password. Please try again.");
      }
    } catch (error) {
      console.error("Error:", error);
      alert("An error occurred. Please try again.");
    }
  });

  // Handle notification settings form submission
  notificationForm.addEventListener("submit", async function(e) {
    e.preventDefault();

    const formData = new FormData();
    formData.append("action", "update_notification_settings");
    formData.append("email_notifications", this.querySelector("#email-notifications").checked);
    formData.append("sms_notifications", this.querySelector("#sms-notifications").checked);
    formData.append("feedback_notifications", this.querySelector("#feedback-notifications").checked);

    try {
      const response = await fetch("donor_actions.php", {
        method: "POST",
        body: formData
      });

      const result = await response.json();

      if (result.success) {
        alert("Notification settings updated successfully!");
      } else {
        alert(result.message || "Error updating notification settings. Please try again.");
      }
    } catch (error) {
      console.error("Error:", error);
      alert("An error occurred. Please try again.");
    }
  });

  // Profile picture upload
  const profilePicInput = document.getElementById("profile-pic-input");
  const profilePicButton = document.getElementById("profile-pic-button");

  profilePicButton.addEventListener("click", function() {
    profilePicInput.click();
  });

  profilePicInput.addEventListener("change", async function(e) {
    const file = e.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append("action", "update_profile_picture");
    formData.append("profile_picture", file);

    try {
      const response = await fetch("donor_actions.php", {
        method: "POST",
        body: formData
      });

      const result = await response.json();

      if (result.success) {
        // Update profile picture preview
        document.querySelector(".profile-avatar img").src = result.image_url;
        alert("Profile picture updated successfully!");
      } else {
        alert(result.message || "Error updating profile picture. Please try again.");
      }
    } catch (error) {
      console.error("Error:", error);
      alert("An error occurred. Please try again.");
    }
  });

  // Profile tabs
  const tabButtons = document.querySelectorAll(".tab-btn");
  const tabPanes = document.querySelectorAll(".tab-pane");

  tabButtons.forEach(button => {
    button.addEventListener("click", function() {
      const target = this.getAttribute("data-tab");
      
      // Update active tab button
      tabButtons.forEach(btn => btn.classList.remove("active"));
      this.classList.add("active");
      
      // Show target tab pane
      tabPanes.forEach(pane => {
        pane.classList.remove("active");
        if (pane.id === target) {
          pane.classList.add("active");
        }
      });
    });
  });

  // Utility function to escape HTML
  function escapeHtml(unsafe) {
    return unsafe
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;")
  }

  // Notifications
  const notificationBtn = document.querySelector('.notification')
  const notificationModal = document.querySelector('.notification-modal')
  const closeNotificationBtn = document.querySelector('.close-modal')

  notificationBtn.addEventListener('click', function() {
    notificationModal.style.display = 'block'
    // Mark notifications as read
    fetch('mark_notifications_read.php', {
      method: 'POST'
    }).then(response => response.json())
    .then(data => {
      if (data.success) {
        const badge = this.querySelector('.badge')
        if (badge) {
          badge.remove()
        }
      }
    })
  })

  closeNotificationBtn.addEventListener('click', function() {
    notificationModal.style.display = 'none'
  })

  // Search Beneficiaries
  const searchInput = document.getElementById('searchBeneficiaries')
  let searchTimeout

  searchInput.addEventListener('input', function() {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
      const searchTerm = this.value.trim()
      if (searchTerm.length >= 2) {
        fetch(`search_beneficiaries.php?term=${encodeURIComponent(searchTerm)}`)
          .then(response => response.json())
          .then(data => {
            // Handle search results
            displaySearchResults(data)
          })
      }
    }, 300)
  })

  function displaySearchResults(results) {
    const searchResults = document.createElement('div')
    searchResults.className = 'search-results'
    
    results.forEach(result => {
      const resultItem = document.createElement('div')
      resultItem.className = 'search-result-item'
      resultItem.innerHTML = `
        <div class="result-info">
          <h4>${result.full_name}</h4>
          <p>${result.address}</p>
          ${result.latest_post_title ? `
            <div class="latest-post">
              <span>Latest Request:</span>
              <p>${result.latest_post_title}</p>
            </div>
          ` : ''}
        </div>
        <button class="btn-view-profile" data-id="${result.id}">View Profile</button>
      `
      searchResults.appendChild(resultItem)
    })

    // Remove existing search results
    const existingResults = document.querySelector('.search-results')
    if (existingResults) {
      existingResults.remove()
    }

    // Add new search results
    searchInput.parentNode.appendChild(searchResults)
  }

  // Handle click outside search results
  document.addEventListener('click', function(e) {
    const searchResults = document.querySelector('.search-results')
    if (searchResults && !searchResults.contains(e.target) && !searchInput.contains(e.target)) {
      searchResults.remove()
    }
  })

  // Handle proof gallery
  const proofItems = document.querySelectorAll('.proof-item')
  proofItems.forEach(item => {
    item.addEventListener('click', function() {
      // Create and show modal with larger image
      const modal = document.createElement('div')
      modal.className = 'proof-modal'
      modal.innerHTML = `
        <div class="proof-modal-content">
          <span class="close">&times;</span>
          <img src="${this.querySelector('img').src}" alt="Proof">
          <div class="proof-details">
            <h3>${this.querySelector('h4').textContent}</h3>
            <p>${this.querySelector('p').textContent}</p>
          </div>
        </div>
      `
      document.body.appendChild(modal)

      // Close modal
      modal.querySelector('.close').addEventListener('click', function() {
        modal.remove()
      })
      modal.addEventListener('click', function(e) {
        if (e.target === modal) {
          modal.remove()
        }
      })
    })
  })

  // Modal Handling
  const modals = document.querySelectorAll('.modal');
  const closeButtons = document.querySelectorAll('.close-modal');
  const cancelButtons = document.querySelectorAll('.btn-cancel');

  // Close modal when clicking close button or cancel button
  closeButtons.forEach(button => {
    button.addEventListener('click', function() {
      const modal = this.closest('.modal');
      modal.style.display = 'none';
    });
  });

  cancelButtons.forEach(button => {
    button.addEventListener('click', function() {
      const modal = this.closest('.modal');
      modal.style.display = 'none';
    });
  });

  // Close modal when clicking outside
  window.addEventListener('click', function(event) {
    modals.forEach(modal => {
      if (event.target === modal) {
        modal.style.display = 'none';
      }
    });
  });

  // Image Preview Modal
  const galleryImages = document.querySelectorAll('.proof-gallery img');
  const imagePreviewModal = document.getElementById('image-preview-modal');
  const previewImage = document.getElementById('preview-image');

  galleryImages.forEach(image => {
    image.addEventListener('click', function() {
      previewImage.src = this.src;
      document.getElementById('image-title').textContent = this.alt;
      document.getElementById('image-date').textContent = this.dataset.date || '';
      document.getElementById('image-description').textContent = this.dataset.description || '';
      imagePreviewModal.style.display = 'block';
    });
  });

  // Profile Picture Upload Modal
  const profileUploadModal = document.getElementById('profile-upload-modal');
  const profilePictureInput = document.getElementById('profile-picture');
  const profilePreview = document.getElementById('profile-preview');

  // Use existing profilePicButton variable
  profilePicButton.addEventListener('click', function() {
    profileUploadModal.style.display = 'block';
  });

  profilePictureInput.addEventListener('change', function() {
    const file = this.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function(e) {
        profilePreview.src = e.target.result;
      };
      reader.readAsDataURL(file);
    }
  });

  // Profile Picture Upload Form
  const profilePictureForm = document.getElementById('profile-picture-form');
  profilePictureForm.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'update_profile_picture');

    try {
      const response = await fetch('donor_actions.php', {
        method: 'POST',
        body: formData
      });

      const result = await response.json();
      if (result.success) {
        document.querySelector('.profile-avatar img').src = result.image_url;
        profileUploadModal.style.display = 'none';
        showConfirmation('Success', 'Profile picture updated successfully');
      } else {
        showConfirmation('Error', result.message || 'Error updating profile picture');
      }
    } catch (error) {
      console.error('Error:', error);
      showConfirmation('Error', 'An error occurred while updating profile picture');
    }
  });

  // Confirmation Modal
  function showConfirmation(title, message, onConfirm = null) {
    const modal = document.getElementById('confirmation-modal');
    document.getElementById('confirm-title').textContent = title;
    document.getElementById('confirm-message').textContent = message;
    
    const confirmButton = modal.querySelector('.btn-confirm');
    
    if (onConfirm) {
      confirmButton.style.display = 'block';
      confirmButton.onclick = onConfirm;
    } else {
      confirmButton.style.display = 'none';
    }
    
    modal.style.display = 'block';
  }

  // Request Details Modal
  const viewDetailsButtons = document.querySelectorAll('.btn-view-details');
  const requestDetailsModal = document.getElementById('request-details-modal');

  viewDetailsButtons.forEach(button => {
    button.addEventListener('click', async function(e) {
      e.preventDefault();
      const requestId = this.closest('.request-card').querySelector('.btn-help').dataset.requestId;
      
      try {
        const response = await fetch(`donor_actions.php?action=get_request_details&request_id=${requestId}`);
        const data = await response.json();
        
        if (data.success) {
          const request = data.request;
          
          // Update modal content
          document.getElementById('beneficiary-image').src = request.profile_image || '/placeholder.svg?height=40&width=40';
          document.getElementById('beneficiary-name').textContent = request.beneficiary_name;
          document.getElementById('beneficiary-location').textContent = request.location;
          document.getElementById('beneficiary-rating').innerHTML = generateStarRating(request.rating);
          document.getElementById('request-type').textContent = request.request_type;
          document.getElementById('request-amount').textContent = formatCurrency(request.amount_needed);
          document.getElementById('request-date').textContent = new Date(request.created_at).toLocaleDateString();
          document.getElementById('request-status').textContent = request.status;
          document.getElementById('request-description').textContent = request.description;
          
          // Update buttons
          const contactBtn = requestDetailsModal.querySelector('.btn-contact');
          const helpBtn = requestDetailsModal.querySelector('.btn-help');
          contactBtn.dataset.beneficiaryId = request.beneficiary_id;
          helpBtn.dataset.requestId = request.id;
          
          // Display images
          const imagesContainer = document.getElementById('request-images-container');
          imagesContainer.innerHTML = request.images.map(image => `
            <img src="${image.url}" alt="${image.title}" 
                 data-date="${image.date}"
                 data-description="${image.description}"
                 onclick="showImagePreview(this)">
          `).join('');
          
          requestDetailsModal.style.display = 'block';
        } else {
          showConfirmation('Error', data.message || 'Error loading request details');
        }
      } catch (error) {
        console.error('Error:', error);
        showConfirmation('Error', 'An error occurred while loading request details');
      }
    });
  });

  // Helper function to generate star rating HTML
  function generateStarRating(rating) {
    const fullStars = Math.floor(rating);
    const hasHalfStar = rating % 1 >= 0.5;
    const emptyStars = 5 - fullStars - (hasHalfStar ? 1 : 0);
    
    return `${'<i class="fas fa-star"></i>'.repeat(fullStars)}
            ${hasHalfStar ? '<i class="fas fa-star-half-alt"></i>' : ''}
            ${'<i class="far fa-star"></i>'.repeat(emptyStars)}`;
  }

  // Helper function to format currency
  function formatCurrency(amount) {
    return '₱' + parseFloat(amount).toLocaleString('en-US', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  }
})

