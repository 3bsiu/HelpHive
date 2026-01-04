<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header('Location: index.php');
    exit();
}

if ($_SESSION['role'] !== 'admin') {
    header('Location: student.php');
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
    error_log("Database error in admin.php: " . $e->getMessage());
    $userName = $_SESSION['username'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="HelpHive - Admin Dashboard">
    <title>HelpHive - Admin Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>
    <section id="admin-view" class="view active">
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-hexagon-vertical-nft"></i>
                    <span>HelpHive</span>
                </div>
            </div>
            <nav class="sidebar-nav">
                <a href="#" class="nav-item active" data-tab="admin-tickets">
                    <i class="fas fa-ticket-alt"></i>
                    <span>All Tickets</span>
                </a>
                <a href="#" class="nav-item" data-tab="admin-faq">
                    <i class="fas fa-question-circle"></i>
                    <span>Manage FAQs</span>
                </a>
            </nav>
            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="avatar admin">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="user-details">
                        <span class="user-name"><?php echo htmlspecialchars($userName); ?></span>
                        <span class="user-role">Administrator</span>
                    </div>
                </div>
                <button id="admin-logout" class="btn btn-logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </button>
            </div>
        </aside>
        <main class="main-content">
            <header class="content-header">
                <button class="menu-toggle" id="admin-menu-toggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 id="admin-page-title">All Tickets</h1>
            </header>
            <div id="admin-tickets-tab" class="tab-content active">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon total"><i class="fas fa-layer-group"></i></div>
                        <div class="stat-info">
                            <span class="stat-value" id="admin-total-count">0</span>
                            <span class="stat-label">Total Tickets</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon open"><i class="fas fa-folder-open"></i></div>
                        <div class="stat-info">
                            <span class="stat-value" id="admin-open-count">0</span>
                            <span class="stat-label">Open</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon pending"><i class="fas fa-clock"></i></div>
                        <div class="stat-info">
                            <span class="stat-value" id="admin-pending-count">0</span>
                            <span class="stat-label">Pending</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon closed"><i class="fas fa-check-circle"></i></div>
                        <div class="stat-info">
                            <span class="stat-value" id="admin-closed-count">0</span>
                            <span class="stat-label">Closed</span>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <h2>Ticket Management</h2>
                        <div class="filter-controls">
                            <select id="filter-status" class="filter-select">
                                <option value="all">All Statuses</option>
                                <option value="Open">Open</option>
                                <option value="Pending">Pending</option>
                                <option value="Closed">Closed</option>
                            </select>
                            <select id="filter-category" class="filter-select">
                                <option value="all">All Categories</option>
                                <option value="IT">IT</option>
                                <option value="Finance">Finance</option>
                                <option value="Registration">Registration</option>
                            </select>
                        </div>
                    </div>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Subject</th>
                                    <th>Category</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="admin-tickets-body"></tbody>
                        </table>
                        <div id="admin-no-tickets-message" class="empty-state hidden">
                            <i class="fas fa-inbox"></i>
                            <p>No tickets found matching filters.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div id="admin-faq-tab" class="tab-content">
                <div class="card">
                    <div class="card-header">
                        <h2>FAQ Management</h2>
                        <button id="add-faq-btn" class="btn btn-primary">
                            <i class="fas fa-plus"></i><span>Add FAQ</span>
                        </button>
                    </div>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Question</th>
                                    <th>Category</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="admin-faq-body"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </section>

    <div id="edit-ticket-modal" class="modal">
        <div class="modal-overlay"></div>
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h2><i class="fas fa-ticket-alt"></i> Ticket Details & Management</h2>
                <button class="modal-close" data-modal="edit-ticket-modal"><i class="fas fa-times"></i></button>
            </div>
            <form id="edit-ticket-form">
                <input type="hidden" id="edit-ticket-id">
                
                <div class="ticket-details-card">
                    <div class="ticket-header-info">
                        <div class="ticket-id-badge">
                            <i class="fas fa-hashtag"></i>
                            <span id="edit-ticket-id-display"></span>
                        </div>
                        <div class="ticket-meta">
                            <span class="ticket-user"><i class="fas fa-user"></i> <span id="edit-ticket-user"></span></span>
                            <span class="ticket-date"><i class="fas fa-calendar"></i> <span id="edit-ticket-date"></span></span>
                        </div>
                    </div>

                    <div class="ticket-info-grid">
                        <div class="info-item">
                            <label><i class="fas fa-tag"></i> Category</label>
                            <span class="category-badge" id="edit-ticket-category"></span>
                        </div>
                        <div class="info-item">
                            <label><i class="fas fa-exclamation-circle"></i> Priority</label>
                            <span class="priority-badge" id="edit-ticket-priority"></span>
                        </div>
                        <div class="info-item">
                            <label><i class="fas fa-info-circle"></i> Status</label>
                            <span class="status-badge" id="edit-ticket-status-display"></span>
                        </div>
                    </div>

                    <div class="ticket-content-section">
                        <h3><i class="fas fa-heading"></i> Subject</h3>
                        <p id="edit-ticket-subject" class="ticket-subject"></p>
                    </div>

                    <div class="ticket-content-section">
                        <h3><i class="fas fa-align-left"></i> Description</h3>
                        <div id="edit-ticket-description" class="ticket-description"></div>
                    </div>

                    <div class="ticket-contact-info" id="edit-ticket-contact" style="display: none;">
                        <h3><i class="fas fa-address-card"></i> Contact Information</h3>
                        <div class="contact-details">
                            <span id="edit-ticket-email"></span>
                            <span id="edit-ticket-phone"></span>
                        </div>
                    </div>
                </div>

                <div class="ticket-management-section">
                    <h3><i class="fas fa-cog"></i> Update Ticket</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit-ticket-status"><i class="fas fa-toggle-on"></i> Status</label>
                            <select id="edit-ticket-status" required>
                                <option value="Open">Open</option>
                                <option value="Pending">Pending</option>
                                <option value="Closed">Closed</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit-ticket-priority"><i class="fas fa-exclamation-circle"></i> Priority</label>
                            <select id="edit-ticket-priority-select">
                                <option value="Low">Low</option>
                                <option value="Medium">Medium</option>
                                <option value="High">High</option>
                                <option value="Urgent">Urgent</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="edit-ticket-notes"><i class="fas fa-sticky-note"></i> Admin Notes</label>
                        <textarea id="edit-ticket-notes" rows="4" 
                            placeholder="Add internal notes, comments, or resolution steps..."></textarea>
                        <small class="form-hint">These notes are only visible to admins</small>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" data-modal="edit-ticket-modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Ticket
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="faq-modal" class="modal">
        <div class="modal-overlay"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="faq-modal-title">Add FAQ</h2>
                <button class="modal-close" data-modal="faq-modal"><i class="fas fa-times"></i></button>
            </div>
            <form id="faq-form">
                <input type="hidden" id="faq-id">
                <div class="form-group">
                    <label for="faq-category">Category</label>
                    <select id="faq-category" required>
                        <option value="">Select a category</option>
                        <option value="IT">IT</option>
                        <option value="Finance">Finance</option>
                        <option value="Registration">Registration</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="faq-question">Question</label>
                    <input type="text" id="faq-question" placeholder="Enter the question" required>
                </div>
                <div class="form-group">
                    <label for="faq-answer">Answer</label>
                    <textarea id="faq-answer" rows="4" placeholder="Enter the answer" required></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" data-modal="faq-modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save FAQ</button>
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
                <div class="chatbot-title"><i class="fas fa-robot"></i><span>HelpHive Assistant</span></div>
                <button id="chatbot-close"><i class="fas fa-times"></i></button>
            </div>
            <div id="chatbot-messages" class="chatbot-messages">
                <div class="chat-message bot">
                    <div class="message-avatar"><i class="fas fa-robot"></i></div>
                    <div class="message-content">
                        <p>مرحباً! 👋 أنا مساعد HelpHive. كيف يمكنني مساعدتك اليوم؟</p>
                        <p class="message-hint">جرب أن تسأل عن: "إعادة تعيين كلمة المرور"، "التسجيل"، "الرسوم"، أو "الواي فاي"
                        </p>
                    </div>
                </div>
            </div>
            <form id="chatbot-form" class="chatbot-input">
                <input type="text" id="chatbot-input" placeholder="اكتب رسالتك..." autocomplete="off">
                <button type="submit"><i class="fas fa-paper-plane"></i></button>
            </form>
        </div>
    </div>

    <div id="toast-container"></div>
    <script src="js/common.js"></script>
    <script src="js/admin.js"></script>
</body>

</html>

