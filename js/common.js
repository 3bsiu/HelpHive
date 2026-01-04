const DEMO_CREDENTIALS = {
    student: { username: 'student', password: '123', role: 'student' },
    admin: { username: 'admin', password: '123', role: 'admin' }
};

const DEFAULT_FAQS = [
    {
        id: 1,
        category: 'IT',
        question: 'How do I reset my university password?',
        answer: 'You can reset your password by visiting the IT Self-Service Portal at portal.university.edu/reset. Click "Forgot Password" and follow the instructions. You\'ll receive a verification code to your registered email. If you still have issues, contact the IT Help Desk at ext. 1234.'
    },
    {
        id: 2,
        category: 'IT',
        question: 'How do I connect to the campus WiFi?',
        answer: 'To connect to campus WiFi: 1) Select "UniSecure" from available networks. 2) Enter your university email and password. 3) Accept the certificate when prompted. For guests, select "UniGuest" and register with your email for temporary access (24 hours).'
    },
    {
        id: 3,
        category: 'IT',
        question: 'How do I access my student email?',
        answer: 'Your student email can be accessed at mail.university.edu or through the Microsoft Outlook app. Your email address format is firstname.lastname@student.university.edu. Use your university credentials to log in.'
    },
    {
        id: 4,
        category: 'Finance',
        question: 'When is the tuition fee deadline?',
        answer: 'Tuition fees are due at the beginning of each semester. Fall semester: August 15th, Spring semester: January 15th. Late payments incur a 5% penalty. Payment plans are available - contact the Finance Office to set up a plan.'
    },
    {
        id: 5,
        category: 'Finance',
        question: 'How do I apply for a scholarship?',
        answer: 'Scholarship applications are available on the Student Portal under "Financial Aid". Most scholarships require: GPA above 3.0, personal statement, and recommendation letter. Deadlines vary - check the scholarship listing for specific requirements and dates.'
    },
    {
        id: 6,
        category: 'Finance',
        question: 'How can I get a refund for dropped courses?',
        answer: 'Refund eligibility depends on when you drop the course. Week 1: 100% refund, Week 2: 75% refund, Week 3: 50% refund, After Week 3: No refund. Submit a Course Drop Form and refund request to the Registrar\'s Office.'
    },
    {
        id: 7,
        category: 'Registration',
        question: 'How do I register for classes?',
        answer: 'Class registration opens based on your credit standing. Log into the Student Portal, navigate to "Course Registration", and select your desired courses. Make sure to check prerequisites and time conflicts. If a course is full, you can join the waitlist.'
    },
    {
        id: 8,
        category: 'Registration',
        question: 'How do I change my major?',
        answer: 'To change your major: 1) Meet with an academic advisor from your desired department. 2) Complete the "Change of Major" form. 3) Get approval signatures from both departments. 4) Submit to the Registrar. Processing takes 5-7 business days.'
    },
    {
        id: 9,
        category: 'Registration',
        question: 'How do I request an official transcript?',
        answer: 'Official transcripts can be requested through the Student Portal under "Academic Records" > "Request Transcript". Cost: $10 per copy. Electronic transcripts are delivered within 24 hours. Physical copies take 3-5 business days to mail.'
    }
];

const SAMPLE_TICKETS = [
    {
        id: 'TKT-001',
        user: 'student',
        category: 'IT',
        subject: 'Cannot connect to campus WiFi',
        description: 'I have been trying to connect to the UniSecure network but it keeps failing. I have tried on multiple devices.',
        status: 'Open',
        date: '2024-12-28'
    },
    {
        id: 'TKT-002',
        user: 'student',
        category: 'Finance',
        subject: 'Scholarship application status',
        description: 'I submitted my scholarship application two weeks ago but have not received any confirmation.',
        status: 'Pending',
        date: '2024-12-25'
    }
];

class HelpHiveCommon {
    constructor() {
        this.currentUser = null;
        this.tickets = [];
        this.faqs = [];
    }

    loadData() {
        const savedTickets = localStorage.getItem('helphive_tickets');
        this.tickets = savedTickets ? JSON.parse(savedTickets) : [...SAMPLE_TICKETS];

        const savedFaqs = localStorage.getItem('helphive_faqs');
        this.faqs = savedFaqs ? JSON.parse(savedFaqs) : [...DEFAULT_FAQS];

        if (!savedTickets) this.saveTickets();
        if (!savedFaqs) this.saveFaqs();
    }

    saveTickets() {
        localStorage.setItem('helphive_tickets', JSON.stringify(this.tickets));
    }

    saveFaqs() {
        localStorage.setItem('helphive_faqs', JSON.stringify(this.faqs));
    }

    getSession() {
        const session = localStorage.getItem('helphive_session');
        return session ? JSON.parse(session) : null;
    }

    setSession(user) {
        localStorage.setItem('helphive_session', JSON.stringify(user));
    }

    clearSession() {
        localStorage.removeItem('helphive_session');
    }

    async logout() {
        try {
            await fetch('api/logout.php');
            this.currentUser = null;
            this.clearSession();
            window.location.href = 'index.php';
        } catch (error) {
            console.error('Logout error:', error);
            this.currentUser = null;
            this.clearSession();
            window.location.href = 'index.php';
        }
    }

    formatDate(dateStr) {
        const date = new Date(dateStr);
        return date.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
    }

    showToast(type, title, message) {
        const container = document.getElementById('toast-container');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = `toast ${type}`;

        const icons = {
            success: 'fa-check-circle',
            error: 'fa-times-circle',
            info: 'fa-info-circle'
        };

        toast.innerHTML = `
            <div class="toast-icon">
                <i class="fas ${icons[type]}"></i>
            </div>
            <div class="toast-content">
                <div class="toast-title">${title}</div>
                <div class="toast-message">${message}</div>
            </div>
        `;

        container.appendChild(toast);

        setTimeout(() => {
            toast.style.animation = 'toastSlideIn 0.3s ease reverse';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }

    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }

    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    bindModalEvents() {
        document.querySelectorAll('.modal-close, .modal-overlay').forEach(el => {
            el.addEventListener('click', (e) => {
                const modal = e.target.closest('.modal') || document.getElementById(e.target.dataset.modal);
                if (modal) this.closeModal(modal.id);
            });
        });

        document.querySelectorAll('.btn-secondary[data-modal]').forEach(btn => {
            btn.addEventListener('click', () => this.closeModal(btn.dataset.modal));
        });
    }

    initChatbot() {
        this.chatHistory = [];
    }

    async getChatbotResponse(message) {
        try {
            this.chatHistory.push({ role: 'user', content: message });

            const response = await fetch('api/chatbot.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    message: message,
                    history: this.chatHistory.slice(-10)
                })
            });

            if (!response.ok) {
                let errorMessage = `HTTP error! status: ${response.status}`;
                try {
                    const errorData = await response.json();
                    if (errorData.message) {
                        errorMessage = errorData.message;
                    } else if (errorData.error) {
                        errorMessage = errorData.error;
                    }
                } catch (e) {
                }
                throw new Error(errorMessage);
            }

            const data = await response.json();

            if (data.success && data.response) {
                this.chatHistory.push({ role: 'assistant', content: data.response });
                return data.response;
            } else {
                this.chatHistory.pop();
                throw new Error(data.message || 'Failed to get response from AI');
            }
        } catch (error) {
            console.error('Chatbot error:', error);
            if (this.chatHistory.length > 0 && this.chatHistory[this.chatHistory.length - 1].role === 'user') {
                this.chatHistory.pop();
            }
            throw error;
        }
    }

    addChatMessage(content, isUser = false) {
        const messagesContainer = document.getElementById('chatbot-messages');
        if (!messagesContainer) return;

        const messageDiv = document.createElement('div');
        messageDiv.className = `chat-message ${isUser ? 'user' : 'bot'}`;

        messageDiv.innerHTML = `
            <div class="message-avatar">
                <i class="fas ${isUser ? 'fa-user' : 'fa-robot'}"></i>
            </div>
            <div class="message-content">
                <p>${content}</p>
            </div>
        `;

        messagesContainer.appendChild(messageDiv);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    async handleChatSubmit(message) {
        if (!message.trim()) return;

        this.addChatMessage(message, true);

        const typingIndicator = this.addTypingIndicator();

        try {
            const response = await this.getChatbotResponse(message);
            this.removeTypingIndicator(typingIndicator);
            this.addChatMessage(response);
        } catch (error) {
            this.removeTypingIndicator(typingIndicator);
            console.error('Chatbot error details:', error);
            this.addChatMessage("أعتذر، لكنني أواجه مشكلة في الاتصال الآن. يرجى المحاولة مرة أخرى بعد قليل أو إنشاء تذكرة دعم للحصول على المساعدة.");
        }
    }

    addTypingIndicator() {
        const messagesContainer = document.getElementById('chatbot-messages');
        if (!messagesContainer) return null;

        const typingDiv = document.createElement('div');
        typingDiv.className = 'chat-message bot typing-indicator';
        typingDiv.id = 'typing-indicator';
        typingDiv.innerHTML = `
            <div class="message-avatar">
                <i class="fas fa-robot"></i>
            </div>
            <div class="message-content">
                <div class="typing-dots">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>
        `;

        messagesContainer.appendChild(typingDiv);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
        return typingDiv;
    }

    removeTypingIndicator(typingDiv) {
        if (typingDiv) {
            typingDiv.remove();
        }
    }

    toggleChatbot() {
        const chatWindow = document.getElementById('chatbot-window');
        if (!chatWindow) return;

        chatWindow.classList.toggle('hidden');

        if (!chatWindow.classList.contains('hidden')) {
            document.getElementById('chatbot-input')?.focus();
        }
    }

    bindChatbotEvents() {
        this.initChatbot();

        const chatbotToggle = document.getElementById('chatbot-toggle');
        if (chatbotToggle) {
            chatbotToggle.addEventListener('click', () => this.toggleChatbot());
        }

        const chatbotClose = document.getElementById('chatbot-close');
        if (chatbotClose) {
            chatbotClose.addEventListener('click', () => this.toggleChatbot());
        }

        const chatbotForm = document.getElementById('chatbot-form');
        if (chatbotForm) {
            chatbotForm.addEventListener('submit', (e) => {
                e.preventDefault();
                const input = document.getElementById('chatbot-input');
                this.handleChatSubmit(input.value);
                input.value = '';
            });
        }
    }
}

const common = new HelpHiveCommon();
