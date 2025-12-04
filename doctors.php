<?php
require_once 'dbconnect.php';
require_once 'helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Fetch all doctors with user details
$sql = "SELECT d.id, d.specialization, d.fees, d.availability, u.first_name, u.last_name, u.email 
        FROM doctors d 
        JOIN users u ON d.user_id = u.id 
        ORDER BY d.specialization, u.last_name";
$result = $conn->query($sql);

$doctors_by_dept = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $dept = $row['specialization'] ?: 'General';
        // Normalize department key for grouping
        $deptKey = ucwords(strtolower(trim($dept)));
        if (!isset($doctors_by_dept[$deptKey])) {
            $doctors_by_dept[$deptKey] = [];
        }
        $doctors_by_dept[$deptKey][] = $row;
    }
}

function get_avatar_url($first, $last) {
    $name = urlencode(trim($first . ' ' . $last));
    return "https://ui-avatars.com/api/?name={$name}&background=2a7de1&color=fff&size=200";
}

function get_dept_icon($dept) {
    $dept = strtolower($dept);
    if (strpos($dept, 'cardio') !== false) return 'fa-heart';
    if (strpos($dept, 'pediatric') !== false) return 'fa-baby';
    if (strpos($dept, 'neuro') !== false) return 'fa-brain';
    if (strpos($dept, 'radio') !== false) return 'fa-x-ray';
    if (strpos($dept, 'dental') !== false) return 'fa-tooth';
    if (strpos($dept, 'eye') !== false) return 'fa-eye';
    return 'fa-user-md';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Doctors - MediCare Plus</title>
    <link rel="stylesheet" href="doctors.css">
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
                    <li><a href="home.html">Home</a></li>
                    <li><a href="services.html">Services</a></li>
                    <li><a href="doctors.php" class="active">Doctors</a></li>
                    <li><a href="appointments.html">Appointments</a></li>
                    <li><a href="about.html">About Us</a></li>
                    <li><a href="contact.html">Contact</a></li>
                </ul>
            </nav>
            <div class="auth-buttons">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php 
                        $dashboard = 'patient-dashboard.php';
                        if (isset($_SESSION['role'])) {
                            if ($_SESSION['role'] === 'doctor') $dashboard = 'doctor-dashboard.php';
                            if ($_SESSION['role'] === 'admin') $dashboard = 'admin-dashboard.php';
                        }
                    ?>
                    <a href="<?php echo $dashboard; ?>" class="btn-register">Dashboard</a>
                    <a href="logout.php" class="btn-login">Logout</a>
                <?php else: ?>
                    <a href="login.html" class="btn-login">Login</a>
                    <a href="register.html" class="btn-register">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Doctors Hero Section -->
    <section class="doctors-hero">
        <div class="container">
            <h1>Meet Our Expert Doctors</h1>
            <p>Highly qualified medical professionals dedicated to providing you with the best healthcare experience</p>
        </div>
    </section>

    <!-- Filter Section -->
    <section class="filter-section">
        <div class="container">
            <div class="filter-container">
                <h3>Filter by Availability</h3>
                <div class="filter-options">
                    <div class="filter-group">
                        <label for="day-filter">Available Day:</label>
                        <select id="day-filter" class="filter-select">
                            <option value="all">All Days</option>
                            <option value="monday">Monday</option>
                            <option value="tuesday">Tuesday</option>
                            <option value="wednesday">Wednesday</option>
                            <option value="thursday">Thursday</option>
                            <option value="friday">Friday</option>
                            <option value="saturday">Saturday</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="department-filter">Department:</label>
                        <select id="department-filter" class="filter-select">
                            <option value="all">All Departments</option>
                            <?php foreach (array_keys($doctors_by_dept) as $dept): ?>
                                <option value="<?php echo strtolower(esc($dept)); ?>"><?php echo esc($dept); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="filter-reset" id="reset-filters">Reset Filters</button>
                </div>
            </div>
        </div>
    </section>

    <?php if (empty($doctors_by_dept)): ?>
        <div class="container" style="padding: 50px; text-align: center;">
            <h2>No doctors found.</h2>
        </div>
    <?php else: ?>
        <?php $is_alt = false; ?>
        <?php foreach ($doctors_by_dept as $dept => $doctors): ?>
            <section class="department-doctors <?php echo $is_alt ? 'alt-section' : ''; ?>" data-department="<?php echo strtolower(esc($dept)); ?>">
                <div class="container">
                    <h2 class="department-title">
                        <i class="fas <?php echo get_dept_icon($dept); ?>"></i>
                        <?php echo esc($dept); ?> Specialists
                    </h2>
                    <div class="doctors-grid">
                        <?php foreach ($doctors as $doc): ?>
                            <?php 
                                $fullname = esc($doc['first_name'] . ' ' . $doc['last_name']);
                                $avail = strtolower($doc['availability'] ?? 'monday,tuesday,wednesday,thursday,friday');
                                // If availability is empty, default to Mon-Fri
                                if (empty($avail)) $avail = 'monday,tuesday,wednesday,thursday,friday';
                            ?>
                            <div class="doctor-card" data-availability="<?php echo esc($avail); ?>">
                                <div class="doctor-image">
                                    <img src="<?php echo get_avatar_url($doc['first_name'], $doc['last_name']); ?>" alt="Dr. <?php echo $fullname; ?>">
                                </div>
                                <div class="doctor-info">
                                    <h3>Dr. <?php echo $fullname; ?></h3>
                                    <p class="specialization"><?php echo esc($doc['specialization']); ?></p>
                                    <p class="experience">Experienced Specialist</p>
                                    <div class="rating">
                                        <span class="star">★★★★★</span>
                                        <span>(5.0/5)</span>
                                    </div>
                                    <div class="availability">
                                        <i class="fas fa-calendar-alt"></i>
                                        <span>Available: <?php echo esc($doc['availability'] ?: 'Mon-Fri'); ?></span>
                                    </div>
                                    <a href="appointments.html?doctor=<?php echo $doc['id']; ?>&doctor_name=<?php echo urlencode($fullname); ?>" class="btn-book">Book Appointment</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
            <?php $is_alt = !$is_alt; ?>
        <?php endforeach; ?>
    <?php endif; ?>

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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dayFilter = document.getElementById('day-filter');
            const deptFilter = document.getElementById('department-filter');
            const resetBtn = document.getElementById('reset-filters');
            const deptSections = document.querySelectorAll('.department-doctors');

            function filterDoctors() {
                const selectedDay = dayFilter.value.toLowerCase();
                const selectedDept = deptFilter.value.toLowerCase();

                deptSections.forEach(section => {
                    const sectionDept = section.getAttribute('data-department').toLowerCase();
                    let hasVisibleDoctors = false;

                    // Check if section matches department filter
                    const deptMatch = selectedDept === 'all' || sectionDept === selectedDept;

                    if (deptMatch) {
                        const cards = section.querySelectorAll('.doctor-card');
                        cards.forEach(card => {
                            const availability = card.getAttribute('data-availability').toLowerCase();
                            // Check if card matches day filter
                            const dayMatch = selectedDay === 'all' || availability.includes(selectedDay);

                            if (dayMatch) {
                                card.style.display = 'flex'; 
                                hasVisibleDoctors = true;
                            } else {
                                card.style.display = 'none';
                            }
                        });
                        
                        // Show/hide section based on whether it has visible doctors
                        section.style.display = hasVisibleDoctors ? 'block' : 'none';
                    } else {
                        section.style.display = 'none';
                    }
                });
            }

            dayFilter.addEventListener('change', filterDoctors);
            deptFilter.addEventListener('change', filterDoctors);

            resetBtn.addEventListener('click', function() {
                dayFilter.value = 'all';
                deptFilter.value = 'all';
                filterDoctors();
            });
        });
    </script>
</body>
</html>
