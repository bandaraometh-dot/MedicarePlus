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
    // Login forms now POST to server; client-side demo auth removed.
});