<?php
// navbar.php - for consistency
?>
<style>
    .logo {
        font-size: 24px;
        font-weight: 700;
        color: var(--primary-color);
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .navbar {
        background-color: var(--bg-card);
        box-shadow: var(--shadow);
        position: sticky;
        top: 0;
        z-index: 100;
        border-bottom: 1px solid var(--border-color);
    }

    .navbar-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 0;
    }

    .nav-links {
        display: flex;
        gap: 30px;
        align-items: center;
    }

    .nav-links a {
        text-decoration: none;
        color: var(--text-primary);
        font-weight: 500;
        transition: color 0.3s;
        padding: 8px 16px;
        border-radius: 8px;
    }

    .nav-links a:hover {
        color: var(--primary-color);
        background: var(--bg-secondary);
    }

    .nav-links a.active {
        color: var(--primary-color);
        background: var(--bg-secondary);
    }

    .user-info {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .profile-pic {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--primary-color);
        cursor: pointer;
        transition: transform 0.3s;
    }

    .profile-pic:hover {
        transform: scale(1.05);
    }

    .profile-container {
        display: grid;
        grid-template-columns: <?php echo $current_user['role'] === 'doctor' ? '350px 1fr' : '1fr'; ?>;
        gap: 30px;
        padding: 30px 0;
        min-height: calc(100vh - 80px);
    }

    /* Mobile Menu Toggle */
    .mobile-menu-toggle {
        display: none;
        background: none;
        border: none;
        color: var(--text-primary);
        font-size: 1.5rem;
        cursor: pointer;
        padding: 8px;
        border-radius: 8px;
        transition: background 0.3s;
        margin-left: 25px;
    }

    .mobile-menu-toggle:hover {
        background: var(--bg-secondary);
    }

    /* Tablet and smaller desktop */
    @media (max-width: 1024px) {
        .nav-links {
            gap: 20px;
        }
        
        .nav-links a {
            padding: 6px 12px;
            font-size: 0.9rem;
        }
        
        .user-info span {
            display: none;
        }
    }

    /* Mobile devices (phones, 768px and down) */
    @media (max-width: 768px) {
        .navbar-content {
            padding: 12px 0;
            flex-wrap: wrap;
        }

        .logo {
            font-size: 20px;
        }

        .logo img {
            width: 60px !important;
        }

        /* Mobile menu toggle */
        .mobile-menu-toggle {
            display: block;
            margin-left: 35px;
        }

        /* Navigation links - hidden by default on mobile */
        .nav-links {
            display: none;
            flex-direction: column;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: var(--bg-card);
            border-top: 1px solid var(--border-color);
            box-shadow: var(--shadow);
            padding: 20px;
            gap: 10px;
            z-index: 99;
        }

        .nav-links.mobile-open {
            display: flex;
        }

        .nav-links a {
            padding: 12px 16px;
            width: 100%;
            text-align: center;
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }

        .nav-links a:hover {
            background: var(--bg-secondary);
        }

        /* User info adjustments */
        .user-info {
            gap: 10px;
            margin-left: auto;
        }

        .user-info span {
            display: none;
        }

        .profile-pic {
            width: 40px;
            height: 40px;
        }

        .theme-toggle {
            font-size: 1.1rem;
            padding: 6px;
        }

        /* Logout button */
        .user-info .btn {
            margin-left: 0;
            padding: 6px 12px;
            font-size: 0.8rem;
        }

        .user-info .btn i {
            margin-right: 0;
        }

        .user-info .btn span {
            display: none;
        }
    }

    /* Small mobile devices (480px and down) */
    @media (max-width: 480px) {
        .navbar-content {
            padding: 10px 0;
        }

        .logo {
            font-size: 18px;
            gap: 8px;
        }

        .logo img {
            width: 50px !important;
        }

        .user-info {
            gap: 8px;
        }

        .profile-pic {
            width: 35px;
            height: 35px;
        }

        .theme-toggle {
            font-size: 1rem;
            padding: 5px;
        }

        .user-info .btn {
            padding: 5px 10px;
            font-size: 0.75rem;
        }

        .nav-links {
            padding: 15px;
        }

        .nav-links a {
            padding: 10px 14px;
            font-size: 0.9rem;
        }
    }

    /* Very small devices (360px and down) */
    @media (max-width: 360px) {
        .logo {
            font-size: 16px;
        }

        .logo img {
            width: 45px !important;
        }

        .user-info {
            gap: 5px;
        }

        .profile-pic {
            width: 32px;
            height: 32px;
        }

        .mobile-menu-toggle {
            font-size: 1.3rem;
            padding: 6px;
            margin-left: 40px;
        }
    }
</style>

<nav class="navbar">
    <div class="container">
        <div class="navbar-content">
            <a href="<?php echo $current_user['role'] === 'doctor' ? 'dashboard.php' : 'patient_dashboard.php'; ?>" class="logo">
                <img src="uploads/Hlogomix.PNG" style="width: 80px" alt="HealthAid Logo"/>
                Health Aid
            </a>
            
            <!-- Mobile Menu Toggle -->
            <button class="mobile-menu-toggle" id="mobileMenuToggle">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="nav-links" id="navLinks">
                <?php if ($current_user['role'] === 'doctor'): ?>
                    <!-- Doctor Navigation -->
                    <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a href="statistics.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'statistics.php' ? 'active' : ''; ?>">
                        <i class="fas fa-chart-bar"></i> Statistics
                    </a>
                    <a href="location-map.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'location-map.php' ? 'active' : ''; ?>">
                        <i class="fas fa-map-marker-alt"></i> Location
                    </a>
                    <a href="emergency-contacts.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'emergency-contacts.php' ? 'active' : ''; ?>">
                        <i class="fas fa-phone-alt"></i> Emergency
                    </a>
                    <a href="profile.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                        <i class="fas fa-user"></i> Profile
                    </a>
                <?php else: ?>
                    <!-- Patient Navigation -->
                    <a href="patient_dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'patient_dashboard.php' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i> My Dashboard
                    </a>
                    <a href="patient_profile.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'patient_profile.php' ? 'active' : ''; ?>">
                        <i class="fas fa-user"></i> My Profile
                    </a>
                <?php endif; ?>
            </div>
            
            <div class="user-info">
                <button class="theme-toggle" id="themeToggle">
                    <i class="fas fa-moon"></i>
                </button>
                <img src="<?php echo !empty($current_user['profile_picture']) ? 'uploads/' . $current_user['profile_picture'] : './uploads/profile_placeholder_img.png'; ?>" 
                     alt="Profile" class="profile-pic"
                     onclick="window.location.href='<?php echo $current_user['role'] === 'doctor' ? 'profile.php' : 'patient_profile.php'; ?>'">
                <span><?php echo $current_user['role'] === 'doctor' ? 'Dr. ' : ''; ?><?php echo htmlspecialchars($current_user['full_name']); ?></span>
                <a href="logout.php" class="btn" style="margin-left: 10px; padding: 8px 15px; background: #e2a9f1; color: #441c4eff">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="logout-text">Logout</span>
                </a>
            </div>
        </div>
    </div>
</nav>

<script>
    // Mobile Menu Toggle
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const navLinks = document.getElementById('navLinks');

    mobileMenuToggle.addEventListener('click', function() {
        navLinks.classList.toggle('mobile-open');
        
        // Change icon based on menu state
        const icon = mobileMenuToggle.querySelector('i');
        if (navLinks.classList.contains('mobile-open')) {
            icon.className = 'fas fa-times';
        } else {
            icon.className = 'fas fa-bars';
        }
    });

    // Close mobile menu when clicking on a link
    const navLinksItems = navLinks.querySelectorAll('a');
    navLinksItems.forEach(link => {
        link.addEventListener('click', function() {
            navLinks.classList.remove('mobile-open');
            mobileMenuToggle.querySelector('i').className = 'fas fa-bars';
        });
    });

    // Close mobile menu when clicking outside
    document.addEventListener('click', function(event) {
        const isClickInsideNav = navLinks.contains(event.target) || mobileMenuToggle.contains(event.target);
        if (!isClickInsideNav && navLinks.classList.contains('mobile-open')) {
            navLinks.classList.remove('mobile-open');
            mobileMenuToggle.querySelector('i').className = 'fas fa-bars';
        }
    });
</script>