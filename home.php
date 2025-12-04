<?php
require_once 'dbconnect.php';
require_once 'helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$showDoctorChat = false;
$unread = 0;
if (!empty($_SESSION['user_id'])) {
    $uid = (int) $_SESSION['user_id'];
    $role = isset($_SESSION['role']) ? strtolower(trim($_SESSION['role'])) : '';
    if ($role === 'doctor') {
        $showDoctorChat = true;
        try {
            $stmt = $conn->prepare('SELECT COUNT(*) FROM messages WHERE to_user = ?');
            $stmt->bind_param('i', $uid);
            $stmt->execute();
            $stmt->bind_result($unread);
            $stmt->fetch();
            $stmt->close();
        } catch (Exception $e) {
            $unread = 0;
        }
    }
}

function esc_attr($v) { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediCare Plus - Quality Healthcare Services</title>
    <link rel="stylesheet" href="home.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" href="logo.png">
</head>
<body>
    <!-- Header Section -->
    <header>
        <div class="container">
            <div class="logo">
                <img src="logo.png" width="100px" height="100px">
                <h1>MediCare Plus</h1>
            </div>
            <nav>
                <ul>
                    <li><a href="home.php" class="active">Home</a></li>
                    <li><a href="services.html">Services</a></li>
                    <li><a href="doctors.php">Doctors</a></li>
                    <li><a href="appointments.html">Appointments</a></li>
                    <li><a href="about.html">About Us</a></li>
                    <li><a href="contact.html">Contact</a></li>
                </ul>
            </nav>
            <div class="auth-buttons">
                <?php if ($showDoctorChat): ?>
                    <a href="messages.php" class="btn-login">Messages <?php if ((int)$unread>0) echo '<span class="badge" style="background:#dc3545;color:#fff;padding:2px 6px;border-radius:12px;margin-left:6px;font-size:12px;">'.(int)$unread.'</span>'; ?></a>
                    <a href="doctor-dashboard.php" class="btn-register">Dashboard</a>
                <?php else: ?>
                    <a href="login.html" class="btn-login">Login</a>
                    <a href="register.html" class="btn-register">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h2>Your Health is Our <span>Priority</span></h2>
                <p>Experience world-class healthcare with our team of expert doctors and state of the art facilities. Book appointments, access medical records, and get personalized care all in one place.</p>
            </div>
            <div class="hero-image">
                <img src="medicareplus.png">
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section class="services">
        <div class="container">
            <h2 class="section-title">Our Medical Services</h2>
            <p class="section-subtitle">Comprehensive healthcare services tailored to your needs</p>
            <div class="services-grid">
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <h3>Cardiology</h3>
                    <p>Expert heart care with advanced diagnostic tools and treatment options.</p>
                </div>
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-baby"></i>
                    </div>
                    <h3>Pediatrics</h3>
                    <p>Specialized care for children from infancy through adolescence.</p>
                </div>
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-brain"></i>
                    </div>
                    <h3>Neurology</h3>
                    <p>Comprehensive diagnosis and treatment for neurological disorders.</p>
                </div>
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-x-ray"></i>
                    </div>
                    <h3>Radiology</h3>
                    <p>Advanced imaging services for accurate diagnosis and treatment planning.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="how-it-works">
        <div class="container">
            <h2 class="section-title">How It Works</h2>
            <p class="section-subtitle">Simple steps to get the care you need</p>
            <div class="steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <h3>Register Account</h3>
                    <p>Create your personal account with basic information</p>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <h3>Find a Doctor</h3>
                    <p>Browse specialists and check availability</p>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <h3>Book Appointment</h3>
                    <p>Schedule your visit at a convenient time</p>
                </div>
                <div class="step">
                    <div class="step-number">4</div>
                    <h3>Get Treatment</h3>
                    <p>Receive quality care and follow-up support</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <div class="logo">
                        <img src="logo.png" width="50px" height="50px">
                        <h2>MediCare Plus</h2>
                    </div>
                    <p>Providing quality healthcare services with compassion and excellence since 2010.</p>
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="home.html">Home</a></li>
                        <li><a href="services.html">Services</a></li>
                        <li><a href="doctors.php">Doctors</a></li>
                        <li><a href="appointments.html">Appointments</a></li>
                        <li><a href="about.html">About Us</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Services</h3>
                    <ul>
                        <li><a href="services.html">Cardiology</a></li>
                        <li><a href="services.html">Pediatrics</a></li>
                        <li><a href="services.html">Neurology</a></li>
                        <li><a href="services.html">Radiology</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Contact Us</h3>
                    <ul class="contact-info">
                        <li><i class="fas fa-map-marker-alt"></i> 123 Health Street, Medical City</li>
                        <li><i class="fas fa-phone"></i> +1 (555) 123-4567</li>
                        <li><i class="fas fa-envelope"></i> info@medicareplus.com</li>
                        <li><i class="fas fa-clock"></i> Mon-Fri: 8am-8pm, Sat: 9am-5pm</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 MediCare Plus. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>
