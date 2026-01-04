class StudentApp {
    constructor() {
        this.checkSession();
        this.bindEvents();
    }

    async checkSession() {
        try {
            const response = await fetch('api/check_session.php');
            const data = await response.json();
            
            if (data.success && data.user) {
                if (data.user.role !== 'student') {
                    window.location.href = 'admin.php';
                    return;
                }
                common.currentUser = data.user;
                this.initDashboard();
            } else {
                console.log('No active session');
            }
        } catch (error) {
            console.error('Session check failed:', error);
        }
    }

    initDashboard() {
        this.renderTickets();
        this.renderFaqs();
        this.updateStats();
    }

    async renderTickets() {
        const tbody = document.getElementById('student-tickets-body');
        const noTicketsMsg = document.getElementById('no-tickets-message');
        if (!tbody) return;

        try {
            const response = await fetch(`api/tickets.php?username=${common.currentUser.username}`);
            const data = await response.json();

            if (data.success && data.tickets) {
                const userTickets = data.tickets;

                if (userTickets.length === 0) {
                    tbody.innerHTML = '';
                    noTicketsMsg?.classList.remove('hidden');
                    return;
                }

                noTicketsMsg?.classList.add('hidden');
                tbody.innerHTML = userTickets.map(ticket => {
                    const priority = ticket.priority || 'Medium';
                    const priorityClass = priority.toLowerCase();
                    
                    return `
                    <tr>
                        <td><strong>${ticket.id}</strong></td>
                        <td>${ticket.subject}</td>
                        <td><span class="category-badge">${ticket.category}</span></td>
                        <td><span class="priority-badge ${priorityClass}">${priority}</span></td>
                        <td><span class="status-badge ${ticket.status.toLowerCase()}">${ticket.status}</span></td>
                        <td>${common.formatDate(ticket.date)}</td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-icon" onclick="app.viewTicketDetails('${ticket.id}')" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
                }).join('');

                common.tickets = userTickets;
            }
        } catch (error) {
            console.error('Error loading tickets:', error);
            tbody.innerHTML = '';
            noTicketsMsg?.classList.remove('hidden');
        }
    }

    async updateStats() {
        try {
            const response = await fetch(`api/tickets.php?username=${common.currentUser.username}`);
            const data = await response.json();

            if (data.success && data.tickets) {
                const userTickets = data.tickets;
                const openCount = document.getElementById('student-open-count');
                const pendingCount = document.getElementById('student-pending-count');
                const closedCount = document.getElementById('student-closed-count');

                if (openCount) openCount.textContent = userTickets.filter(t => t.status === 'Open').length;
                if (pendingCount) pendingCount.textContent = userTickets.filter(t => t.status === 'Pending').length;
                if (closedCount) closedCount.textContent = userTickets.filter(t => t.status === 'Closed').length;
            }
        } catch (error) {
            console.error('Error loading stats:', error);
        }
    }

    async createTicket(data) {
        try {
            const response = await fetch('api/tickets.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    category: data.category,
                    subject: data.subject,
                    description: data.description,
                    priority: data.priority,
                    contact_email: data.contact_email,
                    contact_phone: data.contact_phone
                })
            });

            const result = await response.json();

            if (result.success) {
                this.renderTickets();
                this.updateStats();
                common.showToast('success', 'Ticket Created!', `Your ticket ${result.ticket_id} has been submitted`);
            } else {
                common.showToast('error', 'Error', result.message || 'Failed to create ticket');
            }
        } catch (error) {
            console.error('Error creating ticket:', error);
            common.showToast('error', 'Error', 'Failed to create ticket');
        }
    }

    async renderFaqs(searchTerm = '', category = 'all') {
        const container = document.getElementById('faq-list');
        if (!container) return;

        try {
            let url = 'api/faqs.php';
            if (category !== 'all') {
                url += `?category=${category}`;
            }

            const response = await fetch(url);
            const data = await response.json();

            if (data.success && data.faqs) {
                let filteredFaqs = data.faqs;

                if (searchTerm) {
                    const term = searchTerm.toLowerCase();
                    filteredFaqs = filteredFaqs.filter(faq =>
                        faq.question.toLowerCase().includes(term) ||
                        faq.answer.toLowerCase().includes(term)
                    );
                }

                if (filteredFaqs.length === 0) {
                    container.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-search"></i>
                            <p>No FAQs found matching your criteria</p>
                        </div>
                    `;
                    return;
                }

                container.innerHTML = filteredFaqs.map(faq => `
                    <div class="faq-item" data-id="${faq.id}">
                        <div class="faq-question" onclick="app.toggleFaq(${faq.id})">
                            <span>${faq.question}</span>
                            <div class="faq-meta">
                                <span class="faq-category-tag">${faq.category}</span>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                        </div>
                        <div class="faq-answer">
                            ${faq.answer}
                        </div>
                    </div>
                `).join('');

                common.faqs = data.faqs;
            }
        } catch (error) {
            console.error('Error loading FAQs:', error);
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-exclamation-circle"></i>
                    <p>Error loading FAQs</p>
                </div>
            `;
        }
    }

    toggleFaq(id) {
        const faqItem = document.querySelector(`.faq-item[data-id="${id}"]`);
        if (faqItem) {
            faqItem.classList.toggle('open');
        }
    }

    viewTicketDetails(ticketId) {
        const ticket = common.tickets.find(t => t.id === ticketId);
        if (!ticket) {
            console.error('Ticket not found:', ticketId);
            return;
        }

        document.getElementById('view-ticket-id').textContent = ticket.id;
        document.getElementById('view-ticket-date').textContent = common.formatDate(ticket.date);
        document.getElementById('view-ticket-subject').textContent = ticket.subject;
        document.getElementById('view-ticket-category').textContent = ticket.category;
        document.getElementById('view-ticket-description').innerHTML = ticket.description.replace(/\n/g, '<br>');
        
        const priority = ticket.priority || 'Medium';
        document.getElementById('view-ticket-priority').innerHTML = `<span class="priority-badge ${priority.toLowerCase()}">${priority}</span>`;
        
        document.getElementById('view-ticket-status').innerHTML = `<span class="status-badge ${ticket.status.toLowerCase()}">${ticket.status}</span>`;
        
        if (ticket.contact_email || ticket.contact_phone) {
            document.getElementById('view-ticket-contact').style.display = 'block';
            document.getElementById('view-ticket-email').innerHTML = ticket.contact_email ? `<i class="fas fa-envelope"></i> ${ticket.contact_email}` : '';
            document.getElementById('view-ticket-phone').innerHTML = ticket.contact_phone ? `<i class="fas fa-phone"></i> ${ticket.contact_phone}` : '';
        } else {
            document.getElementById('view-ticket-contact').style.display = 'none';
        }

        common.openModal('view-ticket-modal');
    }

    bindEvents() {
        const logoutBtn = document.getElementById('student-logout');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', () => common.logout());
        }

        document.querySelectorAll('#student-view .nav-item').forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                const tab = item.dataset.tab;

                document.querySelectorAll('#student-view .nav-item').forEach(n => n.classList.remove('active'));
                item.classList.add('active');

                document.querySelectorAll('#student-view .tab-content').forEach(t => t.classList.remove('active'));
                document.getElementById(`${tab}-tab`)?.classList.add('active');

                const pageTitle = document.getElementById('student-page-title');
                if (pageTitle) pageTitle.textContent = tab === 'tickets' ? 'My Tickets' : 'FAQs';

                const createBtn = document.getElementById('create-ticket-btn');
                if (createBtn) createBtn.style.display = tab === 'tickets' ? 'flex' : 'none';
            });
        });

        const createTicketBtn = document.getElementById('create-ticket-btn');
        if (createTicketBtn) {
            createTicketBtn.addEventListener('click', () => common.openModal('create-ticket-modal'));
        }

        const createTicketForm = document.getElementById('create-ticket-form');
        if (createTicketForm) {
            createTicketForm.addEventListener('submit', (e) => {
                e.preventDefault();
                const data = {
                    category: document.getElementById('ticket-category').value,
                    subject: document.getElementById('ticket-subject').value,
                    description: document.getElementById('ticket-description').value,
                    priority: document.getElementById('ticket-priority').value,
                    contact_email: document.getElementById('ticket-email').value || null,
                    contact_phone: document.getElementById('ticket-phone').value || null
                };
                this.createTicket(data);
                common.closeModal('create-ticket-modal');
                e.target.reset();
            });
        }

        const faqSearch = document.getElementById('faq-search');
        if (faqSearch) {
            faqSearch.addEventListener('input', (e) => {
                const activeCategory = document.querySelector('#student-view .category-btn.active');
                this.renderFaqs(e.target.value, activeCategory?.dataset.category || 'all');
            });
        }

        document.querySelectorAll('#student-view .category-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('#student-view .category-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                const searchTerm = document.getElementById('faq-search')?.value || '';
                this.renderFaqs(searchTerm, btn.dataset.category);
            });
        });

        const menuToggle = document.getElementById('student-menu-toggle');
        if (menuToggle) {
            menuToggle.addEventListener('click', () => {
                document.querySelector('#student-view .sidebar')?.classList.toggle('open');
            });
        }

        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 768) {
                if (!e.target.closest('.sidebar') && !e.target.closest('.menu-toggle')) {
                    document.querySelector('#student-view .sidebar')?.classList.remove('open');
                }
            }
        });

        const fileInput = document.getElementById('ticket-file');
        if (fileInput) {
            fileInput.addEventListener('change', (e) => {
                const fileName = e.target.files[0]?.name;
                const display = e.target.nextElementSibling;
                if (fileName && display) {
                    display.innerHTML = `<i class="fas fa-file"></i><span>${fileName}</span>`;
                }
            });
        }

        common.bindModalEvents();
        common.bindChatbotEvents();
    }
}

const app = new StudentApp();
