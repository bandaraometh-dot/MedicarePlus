<!DOCTYPE html>
<html>
<head>
    <title>Doctor Registration - MediCare Plus</title>
    <style>
        body { font-family: Arial; max-width: 600px; margin: 40px auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input, select, textarea { width: 100%; padding: 8px; box-sizing: border-box; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; cursor: pointer; }
        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>
    <h2>Doctor Registration</h2>
    
    <?php
    if (isset($_GET['success'])) {
        echo '<p class="success">Registration successful!</p>';
    }
    if (isset($_GET['error'])) {
        echo '<p class="error">Registration failed. Please try again.</p>';
    }
    ?>
    
    <form action="process_doctor.php" method="POST">
        <div class="form-group">
            <label>First Name:</label>
            <input type="text" name="first_name" required>
        </div>
        
        <div class="form-group">
            <label>Last Name:</label>
            <input type="text" name="last_name" required>
        </div>
        
        <div class="form-group">
            <label>Username:</label>
            <input type="text" name="username" required>
        </div>
        
        <div class="form-group">
            <label>Email:</label>
            <input type="email" name="email" required>
        </div>
        
        <div class="form-group">
            <label>Password:</label>
            <input type="password" name="password" required>
        </div>
        
        <div class="form-group">
            <label>Phone:</label>
            <input type="tel" name="phone">
        </div>
        
        <div class="form-group">
            <label>Specialization:</label>
            <input type="text" name="specialization" required>
        </div>
        
        <div class="form-group">
            <label>Consultation Fees ($):</label>
            <input type="number" name="fees" step="0.01" required>
        </div>
        
        <div class="form-group">
            <label>Availability:</label>
            <textarea name="availability" rows="3" placeholder="e.g., Mon-Fri: 9AM-5PM"></textarea>
        </div>
        
        <input type="hidden" name="role" value="doctor">
        
        <button type="submit">Register as Doctor</button>
    </form>
    
    <p>Already have an account? <a href="login.php">Login here</a></p>
</body>
</html>