class LoginApp {
    constructor() {
        this.checkSession();
        this.bindEvents();
    }

    async checkSession() {
        try {
            const response = await fetch('api/check_session.php');
            const data = await response.json();
            
            if (data.success && data.user) {
                setTimeout(() => {
                    this.redirectToDashboard(data.user.role);
                }, 100);
            }
        } catch (error) {
            console.error('Session check failed:', error);
        }
    }

    redirectToDashboard(role) {
        if (role === 'admin') {
            window.location.href = 'admin.php';
        } else {
            window.location.href = 'student.php';
        }
    }

    async login(username, password) {
        try {
            const response = await fetch('api/login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ username, password })
            });

            const data = await response.json();

            if (data.success && data.user) {
                common.currentUser = data.user;
                this.redirectToDashboard(data.user.role);
                return true;
            } else {
                return false;
            }
        } catch (error) {
            console.error('Login error:', error);
            return false;
        }
    }

    bindEvents() {
        const loginForm = document.getElementById('login-form');
        if (loginForm) {
            loginForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const username = document.getElementById('username').value;
                const password = document.getElementById('password').value;
                const errorDiv = document.getElementById('login-error');

                const success = await this.login(username, password);
                
                if (!success) {
                    errorDiv.classList.remove('hidden');
                } else {
                    errorDiv.classList.add('hidden');
                }
            });
        }
    }
}

const app = new LoginApp();
