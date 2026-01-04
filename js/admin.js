class AdminApp {
    constructor() {
        this.checkSession();
        this.bindEvents();
    }

    async checkSession() {
        try {
            const response = await fetch('api/check_session.php');
            const data = await response.json();
            
            if (data.success && data.user) {
                if (data.user.role !== 'admin') {
                    window.location.href = 'student.php';
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
        const tbody = document.getElementById('admin-tickets-body');
        const noTicketsMsg = document.getElementById('admin-no-tickets-message');
        if (!tbody) return;

        const statusFilter = document.getElementById('filter-status')?.value || 'all';
        const categoryFilter = document.getElementById('filter-category')?.value || 'all';

        try {
            let url = 'api/tickets.php';
            const params = [];
            if (statusFilter !== 'all') params.push(`status=${statusFilter}`);
            if (categoryFilter !== 'all') params.push(`category=${categoryFilter}`);
            
            if (params.length > 0) {
                url += '?' + params.join('&');
            }

            const response = await fetch(url);
            const data = await response.json();

            if (!data.success) {
                throw new Error(data.message);
            }

            const filteredTickets = data.tickets || [];

            if (filteredTickets.length === 0) {
                tbody.innerHTML = '';
                noTicketsMsg?.classList.remove('hidden');
                return;
            }

            noTicketsMsg?.classList.add('hidden');
            tbody.innerHTML = filteredTickets.map(ticket => {
                const priority = ticket.priority || 'Medium';
                const priorityClass = priority.toLowerCase();
                
                return `
                <tr>
                    <td><strong>${ticket.id}</strong></td>
                    <td>${ticket.user}</td>
                    <td>${ticket.subject}</td>
                    <td><span class="category-badge">${ticket.category}</span></td>
                    <td><span class="priority-badge ${priorityClass}">${priority}</span></td>
                    <td><span class="status-badge ${ticket.status.toLowerCase()}">${ticket.status}</span></td>
                    <td>${common.formatDate(ticket.date)}</td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn btn-icon" onclick="app.openEditTicketModal('${ticket.id}')" title="View & Edit">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
            }).join('');

            common.tickets = filteredTickets;
        } catch (error) {
            console.error('Error loading tickets:', error);
            tbody.innerHTML = '';
            noTicketsMsg?.classList.remove('hidden');
        }
    }

    async updateStats() {
        try {
            const response = await fetch('api/tickets.php');
            const data = await response.json();

            if (data.success && data.tickets) {
                const tickets = data.tickets;
                const totalCount = document.getElementById('admin-total-count');
                const openCount = document.getElementById('admin-open-count');
                const pendingCount = document.getElementById('admin-pending-count');
                const closedCount = document.getElementById('admin-closed-count');

                if (totalCount) totalCount.textContent = tickets.length;
                if (openCount) openCount.textContent = tickets.filter(t => t.status === 'Open').length;
                if (pendingCount) pendingCount.textContent = tickets.filter(t => t.status === 'Pending').length;
                if (closedCount) closedCount.textContent = tickets.filter(t => t.status === 'Closed').length;
            }
        } catch (error) {
            console.error('Error loading stats:', error);
        }
    }

    openEditTicketModal(ticketId) {
        const ticket = common.tickets.find(t => t.id === ticketId);
        if (!ticket) {
            console.error('Ticket not found:', ticketId);
            return;
        }

        document.getElementById('edit-ticket-id').value = ticket.id;
        document.getElementById('edit-ticket-id-display').textContent = ticket.id;
        document.getElementById('edit-ticket-user').textContent = ticket.user;
        document.getElementById('edit-ticket-date').textContent = common.formatDate(ticket.date);
        document.getElementById('edit-ticket-subject').textContent = ticket.subject;
        document.getElementById('edit-ticket-category').textContent = ticket.category;
        document.getElementById('edit-ticket-description').innerHTML = ticket.description.replace(/\n/g, '<br>');
        
        const priority = ticket.priority || 'Medium';
        document.getElementById('edit-ticket-priority').innerHTML = `<span class="priority-badge ${priority.toLowerCase()}">${priority}</span>`;
        document.getElementById('edit-ticket-priority-select').value = priority;
        
        document.getElementById('edit-ticket-status').value = ticket.status;
        document.getElementById('edit-ticket-status-display').innerHTML = `<span class="status-badge ${ticket.status.toLowerCase()}">${ticket.status}</span>`;
        
        if (ticket.contact_email || ticket.contact_phone) {
            document.getElementById('edit-ticket-contact').style.display = 'block';
            document.getElementById('edit-ticket-email').innerHTML = ticket.contact_email ? `<i class="fas fa-envelope"></i> ${ticket.contact_email}` : '';
            document.getElementById('edit-ticket-phone').innerHTML = ticket.contact_phone ? `<i class="fas fa-phone"></i> ${ticket.contact_phone}` : '';
        } else {
            document.getElementById('edit-ticket-contact').style.display = 'none';
        }
        
        document.getElementById('edit-ticket-notes').value = ticket.admin_notes || '';

        common.openModal('edit-ticket-modal');
    }

    async updateTicketStatus(ticketId, newStatus, newPriority, adminNotes) {
        try {
            const response = await fetch('api/tickets.php', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    ticket_id: ticketId,
                    status: newStatus,
                    priority: newPriority,
                    admin_notes: adminNotes
                })
            });

            const data = await response.json();

            if (data.success) {
                this.renderTickets();
                this.updateStats();
                common.showToast('success', 'Ticket Updated!', `Ticket ${ticketId} has been updated successfully`);
            } else {
                common.showToast('error', 'Error', data.message || 'Failed to update ticket');
            }
        } catch (error) {
            console.error('Error updating ticket:', error);
            common.showToast('error', 'Error', 'Failed to update ticket');
        }
    }

    async renderFaqs() {
        const tbody = document.getElementById('admin-faq-body');
        if (!tbody) return;

        try {
            const response = await fetch('api/faqs.php');
            const data = await response.json();

            if (data.success && data.faqs) {
                common.faqs = data.faqs;
                tbody.innerHTML = data.faqs.map(faq => `
                    <tr>
                        <td>${faq.question}</td>
                        <td><span class="category-badge">${faq.category}</span></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-icon" onclick="app.openEditFaqModal(${faq.id})" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-icon danger" onclick="app.deleteFaq(${faq.id})" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `).join('');
            }
        } catch (error) {
            console.error('Error loading FAQs:', error);
            tbody.innerHTML = '<tr><td colspan="3">Error loading FAQs</td></tr>';
        }
    }

    openAddFaqModal() {
        document.getElementById('faq-modal-title').textContent = 'Add FAQ';
        document.getElementById('faq-form').reset();
        document.getElementById('faq-id').value = '';
        common.openModal('faq-modal');
    }

    openEditFaqModal(faqId) {
        const faq = common.faqs.find(f => f.id === faqId);
        if (!faq) return;

        document.getElementById('faq-modal-title').textContent = 'Edit FAQ';
        document.getElementById('faq-id').value = faq.id;
        document.getElementById('faq-category').value = faq.category;
        document.getElementById('faq-question').value = faq.question;
        document.getElementById('faq-answer').value = faq.answer;

        common.openModal('faq-modal');
    }

    async saveFaq(data) {
        try {
            const method = data.id ? 'PUT' : 'POST';
            const body = data.id 
                ? JSON.stringify({ id: data.id, category: data.category, question: data.question, answer: data.answer })
                : JSON.stringify({ category: data.category, question: data.question, answer: data.answer });

            const response = await fetch('api/faqs.php', {
                method: method,
                headers: {
                    'Content-Type': 'application/json'
                },
                body: body
            });

            const result = await response.json();

            if (result.success) {
                common.showToast('success', data.id ? 'FAQ Updated!' : 'FAQ Added!', 
                    data.id ? 'The FAQ has been updated' : 'New FAQ has been created');
                this.renderFaqs();
            } else {
                common.showToast('error', 'Error', result.message || 'Failed to save FAQ');
            }
        } catch (error) {
            console.error('Error saving FAQ:', error);
            common.showToast('error', 'Error', 'Failed to save FAQ');
        }
    }

    async deleteFaq(faqId) {
        if (confirm('Are you sure you want to delete this FAQ?')) {
            try {
                const response = await fetch(`api/faqs.php?id=${faqId}`, {
                    method: 'DELETE'
                });

                const data = await response.json();

                if (data.success) {
                    this.renderFaqs();
                    common.showToast('success', 'FAQ Deleted!', 'The FAQ has been removed');
                } else {
                    common.showToast('error', 'Error', data.message || 'Failed to delete FAQ');
                }
            } catch (error) {
                console.error('Error deleting FAQ:', error);
                common.showToast('error', 'Error', 'Failed to delete FAQ');
            }
        }
    }

    bindEvents() {
        const logoutBtn = document.getElementById('admin-logout');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', () => common.logout());
        }

        document.querySelectorAll('#admin-view .nav-item').forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                const tab = item.dataset.tab;

                document.querySelectorAll('#admin-view .nav-item').forEach(n => n.classList.remove('active'));
                item.classList.add('active');

                document.querySelectorAll('#admin-view .tab-content').forEach(t => t.classList.remove('active'));
                document.getElementById(`${tab}-tab`)?.classList.add('active');

                const pageTitle = document.getElementById('admin-page-title');
                if (pageTitle) pageTitle.textContent = tab === 'admin-tickets' ? 'All Tickets' : 'Manage FAQs';
            });
        });

        const filterStatus = document.getElementById('filter-status');
        if (filterStatus) {
            filterStatus.addEventListener('change', () => this.renderTickets());
        }

        const filterCategory = document.getElementById('filter-category');
        if (filterCategory) {
            filterCategory.addEventListener('change', () => this.renderTickets());
        }

        const editTicketForm = document.getElementById('edit-ticket-form');
        if (editTicketForm) {
            editTicketForm.addEventListener('submit', (e) => {
                e.preventDefault();
                const ticketId = document.getElementById('edit-ticket-id').value;
                const newStatus = document.getElementById('edit-ticket-status').value;
                const newPriority = document.getElementById('edit-ticket-priority-select').value;
                const adminNotes = document.getElementById('edit-ticket-notes').value;
                
                this.updateTicketStatus(ticketId, newStatus, newPriority, adminNotes);
                common.closeModal('edit-ticket-modal');
            });
        }

        const addFaqBtn = document.getElementById('add-faq-btn');
        if (addFaqBtn) {
            addFaqBtn.addEventListener('click', () => this.openAddFaqModal());
        }

        const faqForm = document.getElementById('faq-form');
        if (faqForm) {
            faqForm.addEventListener('submit', (e) => {
                e.preventDefault();
                const data = {
                    id: document.getElementById('faq-id').value,
                    category: document.getElementById('faq-category').value,
                    question: document.getElementById('faq-question').value,
                    answer: document.getElementById('faq-answer').value
                };
                this.saveFaq(data);
                common.closeModal('faq-modal');
            });
        }

        const menuToggle = document.getElementById('admin-menu-toggle');
        if (menuToggle) {
            menuToggle.addEventListener('click', () => {
                document.querySelector('#admin-view .sidebar')?.classList.toggle('open');
            });
        }

        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 768) {
                if (!e.target.closest('.sidebar') && !e.target.closest('.menu-toggle')) {
                    document.querySelector('#admin-view .sidebar')?.classList.remove('open');
                }
            }
        });

        common.bindModalEvents();
        common.bindChatbotEvents();
    }
}

const app = new AdminApp();
