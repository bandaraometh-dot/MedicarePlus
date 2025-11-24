document.addEventListener('DOMContentLoaded', function() {
    // Role tabs functionality
    const roleTabs = document.querySelectorAll('.role-tab');
    const loginForms = document.querySelectorAll('.login-form');
    const benefitContents = document.querySelectorAll('.benefit-content');
    
    roleTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const role = this.getAttribute('data-role');
            
            // Remove active class from all tabs
            roleTabs.forEach(t => t.classList.remove('active'));
            // Add active class to clicked tab
            this.classList.add('active');
            
            // Hide all login forms
            loginForms.forEach(form => form.classList.remove('active'));
            // Show selected login form
            document.getElementById(`${role}-form`).classList.add('active');
            
            // Hide all benefit contents
            benefitContents.forEach(content => content.classList.remove('active'));
            // Show selected benefit content
            document.getElementById(`${role}-benefits`).classList.add('active');
        });
    });
    
    // Password visibility toggle
    const toggleButtons = document.querySelectorAll('.toggle-password');
    
    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const passwordInput = this.parentElement.querySelector('input');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            // Toggle eye icon
            const eyeIcon = this.querySelector('i');
            if (type === 'password') {
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            } else {
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            }
        });
    });
    
    // Form submission handlers
    const authForms = document.querySelectorAll('.auth-form');
    
    authForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const role = this.closest('.login-form').id.replace('-form', '');
            
            // Get form data
            const formData = new FormData(this);
            const username = formData.get('username');
            const password = formData.get('password');
            
            // Simple authentication (in real app, this would be server-side)
            if (authenticateUser(username, password, role)) {
                // Store user session
                localStorage.setItem('currentUser', JSON.stringify({
                    username: username,
                    role: role,
                    loginTime: new Date().toISOString()
                }));
                
                // Redirect to appropriate dashboard
                window.location.href = `${role}-dashboard.html`;
            } else {
                alert('Invalid credentials! Please try again.');
            }
        });
    });
    
    // Simple authentication function (for demo purposes)
    function authenticateUser(username, password, role) {
        // Demo credentials - in real app, this would be server-side
        const demoUsers = {
            'admin': { password: 'admin123', role: 'admin' },
            'doctor1': { password: 'doctor123', role: 'doctor' },
            'patient1': { password: 'patient123', role: 'patient' }
        };
        
        return demoUsers[username] && 
               demoUsers[username].password === password && 
               demoUsers[username].role === role;
    }
    
    // Check if user is already logged in
    const currentUser = JSON.parse(localStorage.getItem('currentUser'));
    if (currentUser && !window.location.href.includes('dashboard')) {
        window.location.href = `${currentUser.role}-dashboard.html`;
    }
});