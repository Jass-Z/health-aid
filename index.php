<?php
// Database configuration
require_once __DIR__ . '/includes/config.php';   
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Aid - Smart Health Monitoring & Emergency Response</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #6A2C70 0%, #3A1740 100%); 
            color: white; 
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            padding: 20px;
            line-height: 1.6;
        }
        
        .container { text-align: center;  max-width: 1200px;  width: 100%; }
        
        .logo-container { margin-bottom: 30px; }
        .logo { 
            font-size: 3rem;
            font-weight: 800;
            background: linear-gradient(45deg, #ffffff, #e0e0e0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 2px;
            text-shadow: 0 4px 15px rgba(0,0,0,0.2); }
        
        .tagline { font-size: 1.4rem; opacity: 0.9; font-weight: 300; margin-bottom: 40px; }
        
        .hero-section { display: grid; grid-template-columns: 1fr 1fr; gap: 50px; align-items: center; margin-bottom: 50px; text-align: left; }
        .hero-content { padding-right: 20px; }
        .herotitle { font-size: 2rem; font-weight: 700; margin-bottom: 20px; line-height: 1.2; }
        .hero-subtitle { font-size: 1.3rem; opacity: 0.9; margin-bottom: 30px; font-weight: 300; }

        .features-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 40px; }
        .feature-item { 
            display: flex;
            align-items: flex-start;
            gap: 15px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1); }
        .feature-icon { 
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0; }
        .feature-text h4 { font-size: 1.1rem; margin-bottom: 5px; font-weight: 600; }
        .feature-text p { font-size: 0.9rem; opacity: 0.8; }
        
        .auth-section {
            background: rgba(255, 255, 255, 0.1);
            padding: 40px;
            border-radius: 20px;
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            max-width: 500px;
            margin: 0 auto; }
        .auth-title { font-size: 1.8rem; margin-bottom: 10px; font-weight: 600; }
        .auth-subtitle { opacity: 0.8; margin-bottom: 30px; }
        .auth-buttons { display: flex; gap: 15px; justify-content: center; margin-bottom: 30px; }
        
        .btn { 
            padding: 15px 35px; 
            border-radius: 50px; 
            font-weight: 600; 
            transition: all 0.3s ease; 
            border: none; 
            font-size: 1rem; 
            text-decoration: none; 
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            flex: 1; }
        .btn-primary { background: linear-gradient(45deg, #ffffff, #f0f0f0); color: #6A2C70; box-shadow: 0 8px 25px rgba(255, 255, 255, 0.2); }
        .btn-outline { background: transparent; color: white; border: 2px solid rgba(255, 255, 255, 0.3); backdrop-filter: blur(10px); }
        .btn:hover { transform: translateY(-3px); box-shadow: 0 12px 30px rgba(0, 0, 0, 0.3); }
        .btn-primary:hover { background: linear-gradient(45deg, #f8f8f8, #e8e8e8); box-shadow: 0 12px 30px rgba(255, 255, 255, 0.3); }
        .btn-outline:hover { background: rgba(255, 255, 255, 0.1); border-color: rgba(255, 255, 255, 0.5); }
        
        @keyframes float { 0%, 100% { transform: translateY(0px); } 50% { transform: translateY(-10px); } }
        .floating { animation: float 6s ease-in-out infinite; 
        }
        
        /* Responsive Design */
        @media (max-width: 968px) {
            .hero-section { grid-template-columns: 1fr; text-align: center; gap: 30px; }
            .hero-content { padding-right: 0; }
            .features-grid { grid-template-columns: 1fr; }
         }

        @media (max-width: 576px) {
            .logo { font-size: 3rem; }

            .hero-title { font-size: 2.2rem; }
            .hero-subtitle { font-size: 1.1rem; }

            .auth-buttons { flex-direction: column; gap: 12px; }
            .auth-section { padding: 30px 20px; }

            .btn { padding: 12px 25px; }
        }
        
        /* circles in the background so it's not bland */
        .floating-shapes { position: absolute; top: 0; left: 0; width: 100%; height: 100%; overflow: hidden; z-index: -1; }        
        .shape { position: absolute; opacity: 0.1; border-radius: 50%; background: white; }        
        .shape-1 { width: 200px; height: 200px; top: 10%; left: 5%; animation: float 8s ease-in-out infinite; }
        .shape-2 { width: 150px; height: 150px; top: 40%; right: 10%; animation: float 10s ease-in-out infinite reverse; }
        .shape-3 { width: 100px; height: 100px; top: 30%; left: 80%; animation: float 12s ease-in-out infinite; }
        .shape-4 { width: 100px; height: 100px; top: 30%; left: 5%; animation: float 12s ease-in-out infinite; }
        .shape-5 { width: 175px; height: 175px; top: 100%; left: 45%; animation: float 8s ease-in-out infinite; }
        .shape-6 { width: 100px; height: 100px; top: 100%; right: 40%; animation: float 12s ease-in-out infinite; }
    </style>
</head>
<body>
    <!-- Background shapes -->
    <div class="floating-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
        <div class="shape shape-4"></div>
    </div>
<!-- shapes again but this time at the bottom of the page & outside container-->
            <div class="shape shape-5"></div>
            <div class="shape shape-6"></div>
            
    <div class="container">
        <!-- Logo and Tagline -->
        <div class="logo-container">
           <br> <img src="uploads/Hlogo.PNG" style="width: 150px" alt="HealthAid Logo"/>
            <h1 class="logo">Health <span style="-webkit-text-fill-color: #e2a9f1">Aid</span></h1>
            <p class="tagline">Your Intelligent Health Monitor</p>         
        </div>
        
        <!-- Hero Section -->
        <div class="hero-section">
            <div class="hero-content">
                <h2 class="herotitle">Monitor Your Health in <span style="color: #e2a9f1">Real-Time</span></h2>
                <p class="hero-subtitle">
                    Health Aid is a comprehensive IoT-powered health monitoring system that provides real-time patient monitoring, 
                    emergency alerts, and comprehenesive health analytics.
                </p>
                
                <!-- Key Features -->
                <div class="features-grid">
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-user-md"></i>
                        </div>
                        <div class="feature-text">
                            <h4>Multi-Patient Dashboard</h4>
                            <p>Monitor multiple patients with individual health dashboards and real-time alerts</p>
                        </div>
                    </div>
                    
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-heartbeat"></i>
                        </div>
                        <div class="feature-text">
                            <h4>Live Vitals Tracking</h4>
                            <p>Real-time monitoring of heart rate, SpO2, temperature, and blood pressure</p>
                        </div>
                    </div>
                    
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="feature-text" text-align="centre">
                            <h4>Live Navigation</h4>
                            <p>Track patient locations and get optimized routes to nearest hospitals</p>
                        </div>
                    </div>
                    
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="feature-text">
                            <h4>Health Analytics</h4>
                            <p>Detailed charts and insights for each patient</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Auth Section -->
            <div class="auth-section floating">
                <h3 class="auth-title">Get Started Today</h3>
                <p class="auth-subtitle">Join us today to safely monitor your health!</p>
                
                <div class="auth-buttons">
                    <a href="login.php" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                    <a href="signup.php" class="btn btn-outline">
                        <i class="fas fa-user-plus"></i> Sign Up
                    </a>
                </div>
        
        <!-- Additional Info Section -->
        <div style="margin-top: 50px; opacity: 0.8;">
            <h3 style="margin-bottom: 20px; font-weight: 500;">For Patients and Healthcare Professionals</h3>
            <div style="display: flex; justify-content: center; gap: 30px; flex-wrap: wrap;">
                <div style="text-align: center;">
                    <i class="fas fa-shield-alt" style="font-size: 2rem; margin-bottom: 10px; color: #e2a9f1;"></i>
                    <div>Secure & Private</div>
                </div>
                <div style="text-align: center;">
                    <i class="fas fa-bolt" style="font-size: 2rem; margin-bottom: 10px; color: #e2a9f1;"></i>
                    <div>Real-Time Alerts</div>
                </div>
                <div style="text-align: center;">
                    <i class="fas fa-hospital" style="font-size: 2rem; margin-bottom: 10px; color: #e2a9f1;"></i>
                    <div>Emergency Ready</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Interactive elements
        document.addEventListener('DOMContentLoaded', function() {
            // Hover effects to feature items
            const featureItems = document.querySelectorAll('.feature-item');
            featureItems.forEach(item => {
                item.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                    this.style.background = 'rgba(255, 255, 255, 0.15)';
                });
                
                item.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.background = 'rgba(255, 255, 255, 0.1)';
                });
            });
            
            // Add typing effect to tagline
            const tagline = document.querySelector('.tagline');
            const originalText = tagline.textContent;
            tagline.textContent = '';
            let i = 0;
            
            function typeWriter() {
                if (i < originalText.length) {
                    tagline.textContent += originalText.charAt(i);
                    i++;
                    setTimeout(typeWriter, 50);
                }
            }
            // Start typing effect after a few
            setTimeout(typeWriter, 1000);
        });
    </script>
</body>
</html>