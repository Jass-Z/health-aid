<style>
    /*dashboard*/
        :root {
            --primary-color: #6A2C70;
            --primary-light: #8B4E91;
            --primary-dark: #4D1E53;
            --secondary-color: #2E8B57;
            --accent-color: #FF6B6B;
            --text-primary: #2D3748;
            --text-secondary: #4A5568;
            --bg-primary: #FFFFFF;
            --bg-secondary: #F7FAFC;
            --bg-card: #FFFFFF;
            --border-color: #E2E8F0;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        [data-theme="dark"] {
            --primary-color: #8B4E91;
            --primary-light: #9C5FA3;
            --primary-dark: #6A2C70;
            --text-primary: #F7FAFC;
            --text-secondary: #E2E8F0;
            --bg-primary: #1A202C;
            --bg-secondary: #2D3748;
            --bg-card: #2D3748;
            --border-color: #4A5568;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3), 0 2px 4px -1px rgba(0, 0, 0, 0.2);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; transition: background-color 0.3s, color 0.3s, border-color 0.3s; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: var(--bg-primary); color: var(--text-primary); line-height: 1.6; }

        .container { max-width: 1400px; margin: 0 auto; padding: 0 20px; }
        .logo { font-size: 24px; font-weight: 700; color: var(--primary-color); text-decoration: none; display: flex; align-items: center; gap: 10px; }
        .nav-links { display: flex; gap: 30px; align-items: center; }
        .nav-links a { text-decoration: none; color: var(--text-primary); font-weight: 500; transition: color 0.3s; padding: 8px 16px; border-radius: 8px; }
        .nav-links a:hover { color: var(--primary-color); background: var(--bg-secondary); }
        .nav-links a.active { color: var(--primary-color); background: var(--bg-secondary); }

        .user-info { display: flex; align-items: center; gap: 15px; }

        .profile-pic { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary-color); cursor: pointer; transition: transform 0.3s; }
        .profile-pic:hover { transform: scale(1.05); }

        .theme-toggle { background: none; border: none; color: var(--text-primary); font-size: 1.2rem; cursor: pointer; padding: 8px; border-radius: 8px; transition: background 0.3s; }
        .theme-toggle:hover { background: var(--bg-secondary); }

        .dashboard-container { display: grid; grid-template-columns: 350px 1fr; gap: 30px; padding: 30px 0; min-height: calc(100vh - 80px); }

        .patients-sidebar { 
            background: var(--bg-card); 
            border-radius: 12px; 
            padding: 25px; 
            box-shadow: var(--shadow); 
            border: 1px solid var(--border-color); 
            max-height: calc(100vh - 140px); 
            overflow-y: auto; 
        }
        .patients-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 25px; 
            padding-bottom: 15px; 
            border-bottom: 2px solid var(--border-color); 
        }
        .patient-card { 
            background: var(--bg-secondary); 
            border-radius: 10px; 
            padding: 20px; 
            margin-bottom: 15px; 
            cursor: pointer; 
            transition: all 0.3s; 
            border: 2px solid transparent; 
        }
        .patient-card:hover { transform: translateY(-2px); box-shadow: var(--shadow); border-color: var(--primary-light); }
        .patient-card.active { border-color: var(--primary-color); }
        .patient-name { font-size: 1.1rem; font-weight: 600; color: var(--text-primary); margin-bottom: 8px; }
        .patient-info { display: flex; justify-content: space-between; font-size: 0.85rem; color: var(--text-secondary); }
        .patient-status { display: inline-block; padding: 4px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: 600; }

        .status-normal { background: #C6F6D5; color: #22543D; }
        .status-warning { background: #FEFCBF; color: #744210; }
        .status-critical { background: #FED7D7; color: #742A2A; }
        .status-offline { background: #E2E8F0; color: #4A5568; }

        .main-content { display: flex; flex-direction: column; gap: 25px; }
        .content-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }

        .page-title { font-size: 2rem; font-weight: 700;color: var(--text-primary); }

        .patient-overview { background: var(--bg-card); border-radius: 12px; padding: 30px; box-shadow: var(--shadow); border: 1px solid var(--border-color); }
        .overview-header { display: flex; align-items: center; gap: 20px; margin-bottom: 25px; }
        .patient-avatar { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid var(--primary-color); }
        .patient-details h2 { font-size: 1.8rem; margin-bottom: 5px; color: var(--text-primary); }
        .patient-meta { color: var(--text-secondary); font-size: 0.9rem; }

        /* Live Status Indicator */
        .live-status { 
            display: flex; 
            align-items: center; 
            gap: 15px; 
            margin-bottom: 20px; 
            padding: 10px 15px; 
            background: var(--bg-secondary); 
            border-radius: 8px; 
            border-left: 4px solid var(--border-color); 
        }

        .status-indicator { display: flex; align-items: center; gap: 8px; padding: 6px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .status-indicator.live { background: #27ae60; color: white; border-left: 4px solid #27ae60; }
        .status-indicator.offline { background: #e74c3c; color: white; border-left: 4px solid #e74c3c; }
        .status-indicator i { font-size: 0.6rem; }

        .device-info { display: flex; gap: 20px; font-size: 0.85rem; color: var(--text-secondary); }
        .device-info span { display: flex; align-items: center; gap: 5px; }

        .vitals-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 25px; }
        .vital-card { background: var(--bg-secondary); border-radius: 10px; padding: 20px; text-align: center; border: 1px solid var(--border-color); }
        .vital-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .vital-title { font-size: 0.9rem; color: var(--text-secondary); font-weight: 500; }
        .vital-icon { width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 14px; }
        .vital-value { font-size: 2rem; font-weight: 700; color: var(--text-primary); margin-bottom: 5px; }
        .vital-status { 
            font-size: 0.8rem; 
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 8px;
            border-radius: 12px;
        }

        .status-normal { color: #27AE60; }
        .status-warning { color: #F39C12; }
        .status-critical { color: #E74C3C; }

        .personal-info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 25px; }
        .info-card { background: var(--bg-secondary); border-radius: 10px; padding: 20px; text-align: center; border: 1px solid var(--border-color); }
        .info-value { font-size: 1.5rem;  font-weight: 700; color: var(--primary-color); margin-top: 20px; }
        .info-label { font-size: 0.9rem; color: var(--text-secondary); }

        .bmi-display {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            border: 1px solid var(--border-color);
        }
        .bmi-value { font-size: 2rem; font-weight: 700; margin-bottom: 5px; }
        .bmi-category { font-size: 1rem; opacity: 0.9; }

        .quick-actions { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-top: 25px; }
        .action-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .action-btn:hover { background: var(--primary-dark); transform: translateY(-2px); }

        .no-patient { text-align: center; padding: 60px 20px; color: var(--text-secondary); }
        .no-patient i { font-size: 4rem; margin-bottom: 20px; opacity: 0.5; }

        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer; 
            border: none;
            font-size: 14px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        .btn-primary { background: var(--primary-color); color: white; }
        .btn-primary:hover { background: var(--primary-dark); }

        /* Alert Stage Badges */
        .alert-stage { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; margin-left: 10px; }
        .stage-normal { background: #27ae60; color: white; }
        .stage-moderate { background: #f39c12; color: white; }
        .stage-critical { background: #e74c3c; color: white; }

        /* Scrollbar Styling */
        .patients-sidebar::-webkit-scrollbar { width: 6px; }
        .patients-sidebar::-webkit-scrollbar-track { background: var(--bg-secondary); border-radius: 3px; }
        .patients-sidebar::-webkit-scrollbar-thumb { background: var(--primary-light); border-radius: 3px; }
        .patients-sidebar::-webkit-scrollbar-thumb:hover { background: var(--primary-color); }

        @media (max-width: 1024px) { .dashboard-container { grid-template-columns: 1fr; } .patients-sidebar { max-height: 300px; } }

    /* Tablet and smaller desktop */
    @media (max-width: 1024px) { 
        .dashboard-container { 
            grid-template-columns: 1fr; 
            gap: 20px;
        } 

        .patients-sidebar { 
            max-height: 300px; 
            order: 2;
        }
            
         .main-content {
            order: 1;
            }
        } 

    /* Mobile devices (phones, 768px and down) */
    @media (max-width: 768px) {
        .container { 
            padding: 0 15px;
            } 
               
    .dashboard-container {
        padding: 15px 0;
        gap: 15px;
    }
    
    /* Navigation adjustments */
    .nav-links {
        gap: 15px;
    }
    
    .nav-links a {
        padding: 6px 12px;
        font-size: 0.9rem;
    }
    
    /* Patient sidebar */
    .patients-sidebar {
        padding: 15px;
        max-height: 250px;
    }
    
    .patients-header {
        margin-bottom: 15px;
        padding-bottom: 10px;
    }
    
    .patient-card {
        padding: 15px;
        margin-bottom: 10px;
    }
    
    .patient-name {
        font-size: 1rem;
    }
    
    .patient-info {
        flex-direction: column;
        gap: 5px;
    }
    
    /* Patient overview */
    .patient-overview {
        padding: 20px;
    }
    
    .overview-header {
        flex-direction: column;
        text-align: center;
        gap: 15px;
        margin-bottom: 20px;
    }
    
    .patient-details h2 {
        font-size: 1.5rem;
    }
    
    .patient-meta {
        font-size: 0.85rem;
    }
    
    /* Live status */
    .live-status {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
        padding: 15px;
    }
    
    .device-info {
        flex-direction: column;
        gap: 8px;
        width: 100%;
    }
    
    /* Grid layouts */
    .vitals-grid {
        grid-template-columns: 1fr;
        gap: 15px;
        margin-top: 20px;
    }
    
    .personal-info-grid {
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-top: 20px;
    }
    
    .bmi-display {
        grid-column: 1 / -1;
    }
    
    .vital-card,
    .info-card {
        padding: 15px;
    }
    
    .vital-value {
        font-size: 1.5rem;
    }
    
    .info-value {
        font-size: 1.3rem;
    }
    
    .bmi-value {
        font-size: 1.8rem;
    }
    
    /* Quick actions */
    .quick-actions {
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-top: 20px;
    }
    
    .action-btn {
        padding: 10px 15px;
        font-size: 0.85rem;
    }
    
    /* Page title */
    .page-title {
        font-size: 1.5rem;
    }
}

/* Small mobile devices (480px and down) */
@media (max-width: 480px) {
    .container {
        padding: 0 10px;
    }
    
    .dashboard-container {
        padding: 10px 0;
    }
    
    .patients-sidebar {
        padding: 12px;
        max-height: 200px;
    }
    
    .patient-overview {
        padding: 15px;
    }
    
    .patient-avatar {
        width: 60px;
        height: 60px;
    }
    
    .patient-details h2 {
        font-size: 1.3rem;
    }
    
    .personal-info-grid {
        grid-template-columns: 1fr;
    }
    
    .quick-actions {
        grid-template-columns: 1fr;
    }
    
    .vital-value {
        font-size: 1.3rem;
    }
    
    .info-value {
        font-size: 1.2rem;
    }
    
    .bmi-value {
        font-size: 1.5rem;
    }
    
    /* Alert stage badges */
    .alert-stage {
        display: block;
        margin-left: 0;
        margin-top: 5px;
        width: fit-content;
    }
    
    /* No patient state */
    .no-patient {
        padding: 40px 15px;
    }
    
    .no-patient i {
        font-size: 3rem;
    }
    
    .no-patient h2 {
        font-size: 1.3rem;
    }
}

/* Very small devices (360px and down) */
@media (max-width: 360px) {
    .nav-links {
        gap: 10px;
    }
    
    .nav-links a {
        padding: 5px 8px;
        font-size: 0.8rem;
    }
    
    .user-info {
        gap: 10px;
    }
    
    .profile-pic {
        width: 35px;
        height: 35px;
    }
    
    .vital-card,
    .info-card {
        padding: 12px;
    }
    
    .action-btn {
        padding: 8px 12px;
        font-size: 0.8rem;
    }
}

/* emergency contacts */
        :root {
            --primary-color: #6A2C70;
            --primary-light: #8B4E91;
            --primary-dark: #4D1E53;
            --secondary-color: #2E8B57;
            --accent-color: #FF6B6B;
            --text-primary: #2D3748;
            --text-secondary: #4A5568;
            --bg-primary: #FFFFFF;
            --bg-secondary: #F7FAFC;
            --bg-card: #FFFFFF;
            --border-color: #E2E8F0;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        [data-theme="dark"] {
            --primary-color: #8B4E91;
            --primary-light: #9C5FA3;
            --primary-dark: #6A2C70;
            --text-primary: #F7FAFC;
            --text-secondary: #E2E8F0;
            --bg-primary: #1A202C;
            --bg-secondary: #2D3748;
            --bg-card: #2D3748;
            --border-color: #4A5568;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3), 0 2px 4px -1px rgba(0, 0, 0, 0.2);
        }

        * { 
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            transition: background-color 0.3s, color 0.3s, border-color 0.3s;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
        }

        .navbar {
            background-color: var(--bg-card);
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 100;
            border-bottom: 1px solid var(--border-color);
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .navbar-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
        }

        .logo {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-color);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
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

        .theme-toggle {
            background: none;
            border: none;
            color: var(--text-primary);
            font-size: 1.2rem;
            cursor: pointer;
            padding: 8px;
            border-radius: 8px;
            transition: background 0.3s;
        }

        .theme-toggle:hover {
            background: var(--bg-secondary);
        }
/* ======== removing .contacts-container from emergreny-contacts breaks the positioning ============ */
        .contacts-container {
            display: grid;
            grid-template-columns: <?php echo $current_user['role'] === 'doctor' ? '350px 1fr' : '1fr'; ?>;
            gap: 30px;
            padding: 30px 0;
            min-height: calc(100vh - 80px);
        }

        .patients-sidebar {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 25px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            max-height: calc(100vh - 140px);
            overflow-y: auto;
        }

        .patients-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--border-color);
        }

        .patient-card {
            background: var(--bg-secondary);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
        }

        .patient-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
            border-color: var(--primary-light);
        }

        .patient-card.active {
            border-color: var(--primary-color);
        }

        .patient-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
        }

        .patient-info {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .contacts-content {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .contacts-grid {
            display: grid;
            grid-template-columns: 400px 1fr;
            gap: 30px;
        }

        .add-contact-form, .contacts-list {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 30px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 16px;
            background: var(--bg-primary);
            color: var(--text-primary);
            transition: border-color 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .contact-card {
            background: var(--bg-secondary);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            border-left: 4px solid var(--primary-color);
            transition: all 0.3s;
        }

        .contact-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .contact-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .contact-name {
            font-weight: 600;
            color: var(--primary-color);
            font-size: 18px;
        }

        .contact-relationship {
            color: var(--text-secondary);
            font-style: italic;
        }

        .contact-details {
            margin-bottom: 15px;
        }

        .contact-detail {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 5px;
            color: var(--text-primary);
        }

        .contact-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
            font-size: 14px;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-warning {
            background: #F39C12;
            color: white;
        }

        .btn-warning:hover {
            background: #E67E22;
        }

        .btn-danger {
            background: #E74C3C;
            color: white;
        }

        .btn-danger:hover {
            background: #C0392B;
        }

        .btn-outline {
            background: transparent;
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }

        .btn-outline:hover {
            background: var(--bg-secondary);
        }

        .success {
            background: #27AE60;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .error {
            background: #E74C3C;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-settings {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 30px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            margin-top: 25px;
        }

        .settings-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .setting-item {
            padding: 20px;
            background: var(--bg-secondary);
            border-radius: 8px;
            border-left: 4px solid var(--primary-color);
        }

        .setting-item h4 {
            margin-bottom: 10px;
            color: var(--text-primary);
        }

        .setting-item p, .setting-item ul {
            color: var(--text-secondary);
        }

        .setting-item ul {
            padding-left: 20px;
        }

        .setting-item li {
            margin-bottom: 5px;
        }

        @media (max-width: 1024px) {
            .contacts-container {
                grid-template-columns: 1fr;
            }
            
            .patients-sidebar {
                max-height: 300px;
            }
            
            .contacts-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Tablet and smaller desktop */
@media (max-width: 1024px) { 
    .dashboard-container { 
        grid-template-columns: 1fr; 
        gap: 20px;
    } 
    
    .patients-sidebar { 
        max-height: 300px; 
        order: 2;
    }
    
    .main-content {
        order: 1;
    }
}

/* Mobile devices (phones, 768px and down) */
@media (max-width: 768px) {
    .container {
        padding: 0 15px;
    }
    
    .dashboard-container {
        padding: 15px 0;
        gap: 15px;
    }
    
    /* Navigation adjustments */
    .nav-links {
        gap: 15px;
    }
    
    .nav-links a {
        padding: 6px 12px;
        font-size: 0.9rem;
    }
    
    /* Patient sidebar */
    .patients-sidebar {
        padding: 15px;
        max-height: 250px;
    }
    
    .patients-header {
        margin-bottom: 15px;
        padding-bottom: 10px;
    }
    
    .patient-card {
        padding: 15px;
        margin-bottom: 10px;
    }
    
    .patient-name {
        font-size: 1rem;
    }
    
    .patient-info {
        flex-direction: column;
        gap: 5px;
    }
    
    /* Patient overview */
    .patient-overview {
        padding: 20px;
    }
    
    .overview-header {
        flex-direction: column;
        text-align: center;
        gap: 15px;
        margin-bottom: 20px;
    }
    
    .patient-details h2 {
        font-size: 1.5rem;
    }
    
    .patient-meta {
        font-size: 0.85rem;
    }
    
    /* Live status */
    .live-status {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
        padding: 15px;
    }
    
    .device-info {
        flex-direction: column;
        gap: 8px;
        width: 100%;
    }
    
    /* Grid layouts */
    .vitals-grid {
        grid-template-columns: 1fr;
        gap: 15px;
        margin-top: 20px;
    }
    
    .personal-info-grid {
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-top: 20px;
    }
    
    .bmi-display {
        grid-column: 1 / -1;
    }
    
    .vital-card,
    .info-card {
        padding: 15px;
    }
    
    .vital-value {
        font-size: 1.5rem;
    }
    
    .info-value {
        font-size: 1.3rem;
    }
    
    .bmi-value {
        font-size: 1.8rem;
    }
    
    /* Quick actions */
    .quick-actions {
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-top: 20px;
    }
    
    .action-btn {
        padding: 10px 15px;
        font-size: 0.85rem;
    }
    
    /* Page title */
    .page-title {
        font-size: 1.5rem;
    }
}

/* Small mobile devices (480px and down) */
@media (max-width: 480px) {
    .container {
        padding: 0 10px;
    }
    
    .dashboard-container {
        padding: 10px 0;
    }
    
    .patients-sidebar {
        padding: 12px;
        max-height: 200px;
    }
    
    .patient-overview {
        padding: 15px;
    }
    
    .patient-avatar {
        width: 60px;
        height: 60px;
    }
    
    .patient-details h2 {
        font-size: 1.3rem;
    }
    
    .personal-info-grid {
        grid-template-columns: 1fr;
    }
    
    .quick-actions {
        grid-template-columns: 1fr;
    }
    
    .vital-value {
        font-size: 1.3rem;
    }
    
    .info-value {
        font-size: 1.2rem;
    }
    
    .bmi-value {
        font-size: 1.5rem;
    }
    
    /* Alert stage badges */
    .alert-stage {
        display: block;
        margin-left: 0;
        margin-top: 5px;
        width: fit-content;
    }
    
    /* No patient state */
    .no-patient {
        padding: 40px 15px;
    }
    
    .no-patient i {
        font-size: 3rem;
    }
    
    .no-patient h2 {
        font-size: 1.3rem;
    }
}

/* Very small devices (360px and down) */
@media (max-width: 360px) {
    .nav-links {
        gap: 10px;
    }
    
    .nav-links a {
        padding: 5px 8px;
        font-size: 0.8rem;
    }
    
    .user-info {
        gap: 10px;
    }
    
    .profile-pic {
        width: 35px;
        height: 35px;
    }
    
    .vital-card,
    .info-card {
        padding: 12px;
    }
    
    .action-btn {
        padding: 8px 12px;
        font-size: 0.8rem;
    }
}

/*location map*/
:root {
            --primary-color: #6A2C70;
            --primary-light: #8B4E91;
            --primary-dark: #4D1E53;
            --secondary-color: #2E8B57;
            --accent-color: #FF6B6B;
            --text-primary: #2D3748;
            --text-secondary: #4A5568;
            --bg-primary: #FFFFFF;
            --bg-secondary: #F7FAFC;
            --bg-card: #FFFFFF;
            --border-color: #E2E8F0;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        [data-theme="dark"] {
            --primary-color: #8B4E91;
            --primary-light: #9C5FA3;
            --primary-dark: #6A2C70;
            --text-primary: #F7FAFC;
            --text-secondary: #E2E8F0;
            --bg-primary: #1A202C;
            --bg-secondary: #2D3748;
            --bg-card: #2D3748;
            --border-color: #4A5568;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3), 0 2px 4px -1px rgba(0, 0, 0, 0.2);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            transition: background-color 0.3s, color 0.3s, border-color 0.3s;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .logo {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-color);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
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

        .theme-toggle {
            background: none;
            border: none;
            color: var(--text-primary);
            font-size: 1.2rem;
            cursor: pointer;
            padding: 8px;
            border-radius: 8px;
            transition: background 0.3s;
        }

        .theme-toggle:hover {
            background: var(--bg-secondary);
        }

        /* ======== removing .map-container from location-map breaks the positioning ============ */
        .map-container {
            display: grid;
            grid-template-columns: <?php echo $current_user['role'] === 'doctor' ? '350px 1fr' : '1fr'; ?>;
            gap: 30px;
            padding: 30px 0;
            min-height: calc(100vh - 80px);
        }

        .patients-sidebar {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 25px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            max-height: calc(100vh - 140px);
            overflow-y: auto;
        }

        .patients-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--border-color);
        }

        .patient-card {
            background: var(--bg-secondary);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
        }

        .patient-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
            border-color: var(--primary-light);
        }

        .patient-card.active {
            border-color: var(--primary-color);
        }

        .patient-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
        }

        .patient-info {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .map-content {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .content-header {
            margin-bottom: 10px;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .map-wrapper {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 25px;
            height: 700px;
        }

        #map {
            height: 100%;
            border-radius: 12px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
        }

        .map-sidebar {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 25px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            gap: 20px;
            height: 100%;
            overflow-y: auto;
        }

        .patient-info-card {
            background: var(--bg-secondary);
            padding: 20px;
            border-radius: 10px;
            border: 1px solid var(--border-color);
        }

        .patient-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .patient-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary-color);
        }

        .controls {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .control-btn {
            flex: 1;
            padding: 12px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-width: 120px;
        }

        .control-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .control-btn.tracking {
            background: var(--secondary-color);
        }

        .control-btn.tracking:hover {
            background: #228B22;
        }

        .location-details {
            margin-top: 15px;
            font-size: 0.9rem;
            color: var(--text-secondary);
        }

        .location-details p {
            margin-bottom: 5px;
            display: flex;
            justify-content: space-between;
        }

        .location-details span {
            color: var(--text-primary);
            font-weight: 600;
        }

        /* Hospitals list styles - more spacious */
        .hospitals-list {
            flex: 1;
            overflow-y: auto;
            min-height: 300px;
            max-height: 400px;
        }

        .hospital-item {
            background: var(--bg-secondary);
            padding: 18px;
            border-radius: 10px;
            margin-bottom: 15px;
            border: 1px solid var(--border-color);
            cursor: pointer;
            transition: all 0.3s;
        }

        .hospital-item:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
            border-color: var(--primary-light);
        }

        .hospital-item.active {
            border-color: var(--primary-color);
            background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--primary-light) 15%);
        }

        .hospital-name {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
            font-size: 1rem;
            line-height: 1.3;
        }

        .hospital-details {
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-bottom: 12px;
            line-height: 1.4;
        }

        .hospital-details div {
            margin-bottom: 4px;
        }

        .hospital-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s;
            min-height: 36px;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-outline {
            background: transparent;
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }

        .btn-outline:hover {
            background: var(--bg-secondary);
        }

        /* Route info styles - more readable */
        .route-info {
            background: var(--bg-secondary);
            padding: 20px;
            border-radius: 10px;
            margin-top: 15px;
            border: 1px solid var(--border-color);
            display: none;
            line-height: 1.5;
        }

        .route-info.active {
            display: block;
        }

        .route-info h4 {
            color: var(--text-primary);
            margin-bottom: 12px;
            font-size: 1.1rem;
        }

        #routeDetails div {
            margin-bottom: 8px;
            font-size: 0.95rem;
        }

        /* Leaflet map theme adjustments */
        .leaflet-popup-content {
            color: var(--text-primary);
        }

        .leaflet-control {
            background: var(--bg-card) !important;
            color: var(--text-primary) !important;
        }

        .leaflet-routing-container {
            background: var(--bg-card) !important;
            color: var(--text-primary) !important;
            border: 1px solid var(--border-color) !important;
        }

        .leaflet-routing-alt {
            background: var(--bg-card) !important;
            color: var(--text-primary) !important;
        }

        .leaflet-routing-alt h2 {
            color: var(--text-primary) !important;
        }

        @media (max-width: 1024px) {
            .map-container {
                grid-template-columns: 1fr;
            }
            
            .patients-sidebar {
                max-height: 300px;
            }
            
            .map-wrapper {
                grid-template-columns: 1fr;
                height: auto;
            }
            
            .map-sidebar {
                height: 400px;
            }
        }

        @media (max-width: 768px) {
            .controls {
                flex-direction: column;
            }
            
            .control-btn {
                min-width: auto;
            }
        }

        /*profile*/

        :root {
            --primary-color: #6A2C70;
            --primary-light: #8B4E91;
            --primary-dark: #4D1E53;
            --secondary-color: #2E8B57;
            --accent-color: #FF6B6B;
            --text-primary: #2D3748;
            --text-secondary: #4A5568;
            --bg-primary: #FFFFFF;
            --bg-secondary: #F7FAFC;
            --bg-card: #FFFFFF;
            --border-color: #E2E8F0;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        [data-theme="dark"] {
            --primary-color: #8B4E91;
            --primary-light: #9C5FA3;
            --primary-dark: #6A2C70;
            --text-primary: #F7FAFC;
            --text-secondary: #E2E8F0;
            --bg-primary: #1A202C;
            --bg-secondary: #2D3748;
            --bg-card: #2D3748;
            --border-color: #4A5568;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3), 0 2px 4px -1px rgba(0, 0, 0, 0.2);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            transition: background-color 0.3s, color 0.3s, border-color 0.3s;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
        }

        .navbar {
            background-color: var(--bg-card);
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 100;
            border-bottom: 1px solid var(--border-color);
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .navbar-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
        }

        .logo {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-color);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
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

        .theme-toggle {
            background: none;
            border: none;
            color: var(--text-primary);
            font-size: 1.2rem;
            cursor: pointer;
            padding: 8px;
            border-radius: 8px;
            transition: background 0.3s;
        }

        .theme-toggle:hover {
            background: var(--bg-secondary);
        }

        .profile-container {
            display: grid;
            grid-template-columns: <?php echo $current_user['role'] === 'doctor' ? '350px 1fr' : '1fr'; ?>;
            gap: 30px;
            padding: 30px 0;
            min-height: calc(100vh - 80px);
        }

        .patients-sidebar {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 25px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            max-height: calc(100vh - 140px);
            overflow-y: auto;
        }

        .patients-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--border-color);
        }

        .patient-card {
            background: var(--bg-secondary);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
        }

        .patient-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
            border-color: var(--primary-light);
        }

        .patient-card.active {
            border-color: var(--primary-color);
        }

        .patient-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
        }

        .patient-info {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .profile-content {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .content-header {
            margin-bottom: 10px;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .profile-main {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 30px;
        }

        .profile-sidebar, .profile-details {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 30px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            align-items: center;
            height: fit-content;
            top: 100px;
        }

        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--primary-color);
            margin: 0 auto 20px;
            display: block;
        }

        .profile-name {
            font-size: 24px;
            text-align: center;
            margin-bottom: 5px;
            color: var(--primary-color);
        }

        .profile-email {
            text-align: center;
            color: var(--text-secondary);
            margin-bottom: 20px;
        }

        .profile-role {
            text-align: center;
            padding: 8px 16px;
            background: var(--primary-color);
            color: white;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            display: block;
            margin: 0 auto 20px;
            width: auto;
            min-width: 120px;
        }

        .bmi-display {
            text-align: center;
            padding: 20px;
            background: var(--bg-secondary);
            border-radius: 10px;
            margin-top: 20px;
        }

        .bmi-value {
            font-size: 36px;
            font-weight: bold;
            color: var(--primary-color);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 16px;
            background: var(--bg-primary);
            color: var(--text-primary);
            transition: border-color 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .btn {
            background: var(--primary-color);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            font-size: 14px;
        }

        .btn-back {
            background: var(--primary-color);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            font-size: 14px;
            float: right;
        }

        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-full {
            width: 100%;
        }

        .success {
            background: #27AE60;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .error {
            background: #E74C3C;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 20px;
            color: var(--primary-color);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--border-color);
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .info-item {
            background: var(--bg-secondary);
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid var(--primary-color);
        }

        .info-label {
            font-size: 0.8rem;
            color: var(--text-secondary);
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .medical-info {
            background: var(--bg-secondary);
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }

        .medical-info h4 {
            margin-bottom: 15px;
            color: var(--primary-color);
        }

        .medical-list {
            list-style: none;
        }

        .medical-list li {
            padding: 8px 0;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
        }

        .medical-list li:last-child {
            border-bottom: none;
        }

        @media (max-width: 1024px) {
            .profile-container {
                grid-template-columns: 1fr;
            }
            
            .patients-sidebar {
                max-height: 300px;
            }
            
            .profile-main {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
        }

        /*statistics*/

        :root {
            --primary-color: #6A2C70;
            --primary-light: #8B4E91;
            --primary-dark: #4D1E53;
            --secondary-color: #2E8B57;
            --accent-color: #FF6B6B;
            --text-primary: #2D3748;
            --text-secondary: #4A5568;
            --bg-primary: #FFFFFF;
            --bg-secondary: #F7FAFC;
            --bg-card: #FFFFFF;
            --border-color: #E2E8F0;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        [data-theme="dark"] {
            --primary-color: #8B4E91;
            --primary-light: #9C5FA3;
            --primary-dark: #6A2C70;
            --text-primary: #F7FAFC;
            --text-secondary: #E2E8F0;
            --bg-primary: #1A202C;
            --bg-secondary: #2D3748;
            --bg-card: #2D3748;
            --border-color: #4A5568;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3), 0 2px 4px -1px rgba(0, 0, 0, 0.2);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            transition: background-color 0.3s, color 0.3s, border-color 0.3s;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .theme-toggle {
            background: none;
            border: none;
            color: var(--text-primary);
            font-size: 1.2rem;
            cursor: pointer;
            padding: 8px;
            border-radius: 8px;
            transition: background 0.3s;
        }

        .theme-toggle:hover {
            background: var(--bg-secondary);
        }

        /* ======== removing .stats-container from statistics breaks the positioning ============ */
        .stats-container {
            display: grid;
            grid-template-columns: <?php echo $current_user['role'] === 'doctor' ? '350px 1fr' : '1fr'; ?>;
            gap: 30px;
            padding: 30px 0;
            min-height: calc(100vh - 80px);
        }

        .patients-sidebar {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 25px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            max-height: calc(100vh - 140px);
            overflow-y: auto;
        }

        .patients-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--border-color);
        }

        .patient-card {
            background: var(--bg-secondary);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
        }

        .patient-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
            border-color: var(--primary-light);
        }

        .patient-card.active {
            border-color: var(--primary-color);
        }

        .patient-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
        }

        .patient-info {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .stats-content {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .content-header {
            margin-bottom: 10px;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        /* Advanced Filters Styles */
        .advanced-filters {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            margin-bottom: 25px;
        }
        
        .filter-row {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            align-items: end;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .filter-select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: var(--bg-primary);
            color: var(--text-primary);
            font-size: 14px;
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 16px;
        }
        
        .filter-select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(106, 44, 112, 0.1);
        }
        
        .apply-filters-btn {
            padding: 10px 20px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s;
            white-space: nowrap;
        }
        
        .apply-filters-btn:hover {
            background: var(--primary-dark);
        }
        
        /* Status Badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.80rem;
            font-weight: 800;
        }
        
        .status-normal { background: #C6F6D5; color: #22543D; }
        .status-warning { background: #FEFCBF; color: #954d04ff; }
        .status-critical { background: #FED7D7; color: #742A2A; }
        .stat-normal { color: #C6F6D5; }
        .stat-warning { color: #FEFCBF; }
        .stat-critical { color: #c15b5bff; }
        
        /* Stats Overview */
        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-mini-card {
            background: var(--bg-card);
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            border-left: 4px solid var(--primary-color);
            box-shadow: var(--shadow);
        }
        
        .stat-mini-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color); 
            margin-bottom: 5px;
        }
        
        .stat-mini-label {
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        /* Main Statistics Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: var(--bg-card);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin: 10px 0;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        /* Charts Container */
        .charts-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }

        .chart-card {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            height: 400px;
            display: flex;
            flex-direction: column;
        }

        .chart-card h3 {
            margin-bottom: 15px;
            color: var(--text-primary);
            font-size: 1.1rem;
        }

        .chart-container {
            flex: 1;
            position: relative;
            min-height: 0; /* Allow chart to shrink properly */
        }

        .chart-container canvas {
            width: 100% !important;
            height: 100% !important;
        }

        /* History Table */
        .history-table {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 25px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            background: var(--bg-secondary);
            font-weight: 600;
            color: var(--text-primary);
        }

        tr:hover {
            background: var(--bg-secondary);
        }

        .last-updated {
            text-align: right;
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: 15px;
        }

        .btn {
            padding: 8px 16px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
        }

        .btn:hover {
            background: var(--primary-dark);
        }

        @media (max-width: 1024px) {
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .patients-sidebar {
                max-height: 300px;
            }
            
            .charts-container {
                grid-template-columns: 1fr;
            }
            
            .stats-overview {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .chart-card {
                height: 300px;
            }
        }

        @media (max-width: 768px) {
            .stats-overview {
                grid-template-columns: 1fr;
            }
            
            .filter-row {
                flex-direction: column;
                gap: 15px;
            }
            
            .filter-group {
                min-width: 100%;
            }
            
            .chart-card {
                height: 250px;
            }
        }
    </style>