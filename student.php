<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header('Location: index.php');
    exit();
}

if ($_SESSION['role'] !== 'student') {
    header('Location: admin.php');
    exit();
}

$userName = $_SESSION['username'];
try {
    require_once 'database/connection.php';
    $db = getDB();
    $stmt = $db->prepare("SELECT username, full_name, email FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if ($user) {
        $userName = $user['full_name'] ?: $user['username'];
    }
} catch (Exception $e) {
    error_log("Database error in student.php: " . $e->getMessage());
    $userName = $_SESSION['username'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="HelpHive - Student Dashboard">
    <title>HelpHive - Student Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>
    <!-- ==================== STUDENT DASHBOARD ==================== -->
    <section id="student-view" class="view active">
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-hexagon-vertical-nft"></i>
                    <span>HelpHive</span>
                </div>
            </div>
            <nav class="sidebar-nav">
                <a href="#" class="nav-item active" data-tab="tickets">
                    <i class="fas fa-ticket-alt"></i>
                    <span>My Tickets</span>
                </a>
                <a href="#" class="nav-item" data-tab="faq">
                    <i class="fas fa-question-circle"></i>
                    <span>FAQs</span>
                </a>
            </nav>
            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="avatar">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="user-details">
                        <span class="user-name"><?php echo htmlspecialchars($userName); ?></span>
                        <span class="user-role">Student</span>
                    </div>
                </div>
                <button id="student-logout" class="btn btn-logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </button>
            </div>
        </aside>
        <main class="main-content">
            <header class="content-header">
                <button class="menu-toggle" id="student-menu-toggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 id="student-page-title">My Tickets</h1>
                <div class="header-actions">
                    <button id="create-ticket-btn" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        <span>New Ticket</span>
                    </button>
                </div>
            </header>

            <!-- Tickets Tab -->
            <div id="tickets-tab" class="tab-content active">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon open">
                            <i class="fas fa-folder-open"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value" id="student-open-count">0</span>
                            <span class="stat-label">Open Tickets</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon pending">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value" id="student-pending-count">0</span>
                            <span class="stat-label">Pending</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon closed">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value" id="student-closed-count">0</span>
                            <span class="stat-label">Closed</span>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2>Recent Tickets</h2>
                    </div>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Subject</th>
                                    <th>Category</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="student-tickets-body">
                                <!-- Tickets will be populated by JS -->
                            </tbody>
                        </table>
                        <div id="no-tickets-message" class="empty-state hidden">
                            <i class="fas fa-inbox"></i>
                            <p>No tickets yet. Create your first ticket!</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FAQ Tab -->
            <div id="faq-tab" class="tab-content">
                <div class="card">
                    <div class="card-header">
                        <h2>Frequently Asked Questions</h2>
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="faq-search" placeholder="Search FAQs...">
                        </div>
                    </div>
                    <div class="faq-categories">
                        <button class="category-btn active" data-category="all">All</button>
                        <button class="category-btn" data-category="IT">IT</button>
                        <button class="category-btn" data-category="Finance">Finance</button>
                        <button class="category-btn" data-category="Registration">Registration</button>
                    </div>
                    <div id="faq-list" class="faq-list">
                        <!-- FAQs will be populated by JS -->
                    </div>
                </div>
            </div>
        </main>
    </section>

    <div id="view-ticket-modal" class="modal">
    <div id="view-ticket-modal" class="modal">
        <div class="modal-overlay"></div>
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h2><i class="fas fa-ticket-alt"></i> Ticket Details</h2>
                <button class="modal-close" data-modal="view-ticket-modal"><i class="fas fa-times"></i></button>
            </div>
            <div class="ticket-details-card">
                <div class="ticket-header-info">
                    <div class="ticket-id-badge">
                        <i class="fas fa-hashtag"></i>
                        <span id="view-ticket-id"></span>
                    </div>
                    <div class="ticket-meta">
                        <span class="ticket-date"><i class="fas fa-calendar"></i> <span id="view-ticket-date"></span></span>
                    </div>
                </div>

                <div class="ticket-info-grid">
                    <div class="info-item">
                        <label><i class="fas fa-tag"></i> Category</label>
                        <span class="category-badge" id="view-ticket-category"></span>
                    </div>
                    <div class="info-item">
                        <label><i class="fas fa-exclamation-circle"></i> Priority</label>
                        <span class="priority-badge" id="view-ticket-priority"></span>
                    </div>
                    <div class="info-item">
                        <label><i class="fas fa-info-circle"></i> Status</label>
                        <span class="status-badge" id="view-ticket-status"></span>
                    </div>
                </div>

                <div class="ticket-content-section">
                    <h3><i class="fas fa-heading"></i> Subject</h3>
                    <p id="view-ticket-subject" class="ticket-subject"></p>
                </div>

                <div class="ticket-content-section">
                    <h3><i class="fas fa-align-left"></i> Description</h3>
                    <div id="view-ticket-description" class="ticket-description"></div>
                </div>

                <div class="ticket-contact-info" id="view-ticket-contact" style="display: none;">
                    <h3><i class="fas fa-address-card"></i> Contact Information</h3>
                    <div class="contact-details">
                        <span id="view-ticket-email"></span>
                        <span id="view-ticket-phone"></span>
                    </div>
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" data-modal="view-ticket-modal">Close</button>
            </div>
        </div>
    </div>

    <div id="create-ticket-modal" class="modal">
        <div class="modal-overlay"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h2>Create New Ticket</h2>
                <button class="modal-close" data-modal="create-ticket-modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="create-ticket-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="ticket-category"><i class="fas fa-tag"></i> Category</label>
                        <select id="ticket-category" required>
                            <option value="">Select a category</option>
                            <option value="IT Support">IT Support</option>
                            <option value="Finance">Finance</option>
                            <option value="Registration">Registration</option>
                            <option value="Academic">Academic</option>
                            <option value="Library">Library</option>
                            <option value="Housing">Housing</option>
                            <option value="Transportation">Transportation</option>
                            <option value="Account Issue">Account Issue</option>
                            <option value="Email">Email</option>
                            <option value="Network">Network</option>
                            <option value="Software">Software</option>
                            <option value="Hardware">Hardware</option>
                            <option value="General Inquiry">General Inquiry</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="ticket-priority"><i class="fas fa-exclamation-circle"></i> Priority</label>
                        <select id="ticket-priority" required>
                            <option value="Low">Low - Can wait</option>
                            <option value="Medium" selected>Medium - Normal</option>
                            <option value="High">High - Important</option>
                            <option value="Urgent">Urgent - Critical</option>
                        </select>
                    </div>
                </div>
                

                <div class="form-group">
                    <label for="ticket-subject"><i class="fas fa-heading"></i> Subject</label>
                    <input type="text" id="ticket-subject" placeholder="Brief description of your issue" required>
                </div>
                
                <div class="form-group">
                    <label for="ticket-description"><i class="fas fa-align-left"></i> Description</label>
                    <textarea id="ticket-description" rows="6"
                        placeholder="Provide detailed information about your issue. Include steps to reproduce, error messages, or any relevant details." required></textarea>
                    <small class="form-hint">Be as detailed as possible to help us resolve your issue faster</small>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="ticket-email"><i class="fas fa-envelope"></i> Contact Email</label>
                        <input type="email" id="ticket-email" placeholder="your.email@example.com">
                        <small class="form-hint">Optional - for updates</small>
                    </div>
                    <div class="form-group">
                        <label for="ticket-phone"><i class="fas fa-phone"></i> Contact Phone</label>
                        <input type="tel" id="ticket-phone" placeholder="+1234567890">
                        <small class="form-hint">Optional</small>
                    </div>
                </div>

                <div class="form-group">
                    <label for="ticket-file"><i class="fas fa-paperclip"></i> Attachments (optional)</label>
                    <div class="file-input-wrapper">
                        <input type="file" id="ticket-file" multiple>
                        <div class="file-input-display">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <span>Choose files or drag here</span>
                            <small>Max 5MB per file</small>
                        </div>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" data-modal="create-ticket-modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i>
                        Submit Ticket
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="chatbot-widget">
        <button id="chatbot-toggle" class="chatbot-btn">
            <i class="fas fa-comment-dots"></i>
            <span class="chatbot-badge hidden">1</span>
        </button>
        <div id="chatbot-window" class="chatbot-window hidden">
            <div class="chatbot-header">
                <div class="chatbot-title">
                    <i class="fas fa-robot"></i>
                    <span>HelpHive Assistant</span>
                </div>
                <button id="chatbot-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="chatbot-messages" class="chatbot-messages">
                <div class="chat-message bot">
                    <div class="message-avatar">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div class="message-content">
                        <p>مرحباً! 👋 أنا مساعد HelpHive. كيف يمكنني مساعدتك اليوم؟</p>
                        <p class="message-hint">جرب أن تسأل عن: "إعادة تعيين كلمة المرور"، "التسجيل"، "الرسوم"، أو "الواي فاي"
                        </p>
                    </div>
                </div>
            </div>
            <form id="chatbot-form" class="chatbot-input">
                <input type="text" id="chatbot-input" placeholder="اكتب رسالتك..." autocomplete="off">
                <button type="submit">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </form>
        </div>
    </div>

    <div id="toast-container"></div>

    <script src="js/common.js"></script>
    <script src="js/student.js"></script>
</body>

</html>

