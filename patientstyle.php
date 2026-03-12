<style>
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
            padding: 30px 0;
            min-height: calc(100vh - 80px);
        }

        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .profile-content {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 30px;
        }

        .profile-sidebar {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 30px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            text-align: center;
        }

        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--primary-color);
            margin: 0 auto 20px;
        }

        .profile-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 10px;
        }

        .profile-role {
            background: var(--primary-color);
            color: white;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 20px;
        }

        .profile-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 25px 0;
        }

        .stat-item {
            background: var(--bg-secondary);
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }

        .stat-value {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        .bmi-display {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }

        .bmi-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .bmi-category {
            font-size: 1rem;
            opacity: 0.9;
        }

        .profile-form-container {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 30px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
        }

        .form-section {
            margin-bottom: 30px;
        }

        .form-section h3 {
            color: var(--primary-color);
            margin-bottom: 20px;
            font-size: 1.3rem;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--border-color);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
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

        .form-textarea {
            min-height: 100px;
            resize: vertical;
        }

        .file-upload {
            position: relative;
            display: inline-block;
            width: 100%;
        }

        .file-upload-input {
            position: absolute;
            left: -9999px;
        }

        .file-upload-label {
            display: block;
            padding: 12px;
            border: 2px dashed var(--border-color);
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .file-upload-label:hover {
            border-color: var(--primary-color);
            background: var(--bg-secondary);
        }

        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid var(--border-color);
        }

        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            font-size: 16px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
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

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .alert-success {
            background: #C6F6D5;
            color: #22543D;
            border: 1px solid #9AE6B4;
        }

        .alert-error {
            background: #FED7D7;
            color: #742A2A;
            border: 1px solid #FEB2B2;
        }

        .device-info {
            background: var(--bg-secondary);
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
            border-left: 4px solid var(--primary-color);
        }

        .device-info p {
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .device-info small {
            color: var(--text-secondary);
        }

        @media (max-width: 1024px) {
            .profile-content {
                grid-template-columns: 1fr;
            }
            
            .profile-sidebar {
                order: 2;
            }
            
            .profile-form-container {
                order: 1;
            }
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .profile-stats {
                grid-template-columns: 1fr;
            }
        }

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

        .dashboard-container {
            padding: 30px 0;
            min-height: calc(100vh - 80px);
        }

        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        /* Live Status Indicator */
        .live-status {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            padding: 12px 20px;
            background: var(--bg-secondary);
            border-radius: 10px;
            border-left: 4px solid var(--border-color);
        }

        .status-indicator {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .status-indicator.live {
            background: #27ae60;
            color: white;
            border-left: 4px solid #27ae60;
        }

        .status-indicator.offline {
            background: #e74c3c;
            color: white;
            border-left: 4px solid #e74c3c;
        }

        .status-indicator i {
            font-size: 0.7rem;
        }

        .device-info {
            display: flex;
            gap: 20px;
            font-size: 0.9rem;
            color: var(--text-secondary);
        }

        .device-info span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 25px;
        }

        .card {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 25px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
        }

        .card-full {
            grid-column: 1 / -1;
        }

        .card h3 {
            color: var(--primary-color);
            margin-bottom: 20px;
            font-size: 1.3rem;
        }

        /* Alert Stage Badges */
        .alert-stage {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-left: 10px;
        }

        .stage-normal { background: #27ae60; color: white; }
        .stage-moderate { background: #f39c12; color: white; }
        .stage-critical { background: #e74c3c; color: white; }

        .vitals-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .vital-card {
            background: var(--bg-secondary);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            border: 1px solid var(--border-color);
        }

        .vital-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .vital-title {
            font-size: 0.9rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .vital-icon {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 14px;
        }

        .vital-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 5px;
        }

        .vital-status {
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-normal { color: #27AE60; }
        .status-warning { color: #F39C12; }
        .status-critical { color: #E74C3C; }

        .contact-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .contact-item {
            background: var(--bg-secondary);
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid var(--primary-color);
        }

        .contact-name {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 5px;
        }

        .contact-details {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: var(--bg-secondary);
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        .personal-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .info-item {
            background: var(--bg-secondary);
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }

        .info-value {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 5px;
        }

        .info-label {
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        .bmi-display {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            color: white;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            margin-top: 15px;
        }

        .bmi-value {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .bmi-category {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        /* Location Map Styles */
        .location-map-container {
            margin-top: 15px;
            height: 300px;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid var(--border-color);
            position: relative;
        }

        #locationMap {
            height: 100%;
            width: 100%;
        }

        .map-controls {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .map-btn {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 5px;
            padding: 8px 12px;
            cursor: pointer;
            color: var(--text-primary);
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
        }

        .map-btn:hover {
            background: var(--bg-secondary);
        }

        .map-btn.tracking {
            background: var(--secondary-color);
            color: white;
        }

        .map-btn.tracking:hover {
            background: #228B22;
        }

        .location-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 10px;
        }

        .location-item { text-align: center; padding: 10px; background: var(--bg-primary); border-radius: 6px; }

        .location-value { font-weight: 600; color: var(--primary-color); }

        .location-label { font-size: 0.8rem; color: var(--text-secondary); }

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

        .btn-danger { background: #E74C3C; color: white; }

        .btn-danger:hover { background: #C0392B; }

        /* Statistics Filters */
         .stats-filters {
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
            height: 450px;
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

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background-color: var(--bg-card);
            margin: 5% auto;
            padding: 0;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            border: 1px solid var(--border-color);
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 25px 30px;
            border-bottom: 1px solid var(--border-color);
        }

        .modal-header h2 {
            color: var(--primary-color);
            margin: 0;
            font-size: 1.5rem;
        }

        .close-modal {
            color: var(--text-secondary);
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s;
        }

        .close-modal:hover {
            color: var(--primary-color);
        }

        .modal-body {
            padding: 30px;
            max-height: 70vh;
            overflow-y: auto;
        }

        .add-contact-form {
            background: var(--bg-secondary);
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 20px;
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

        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 25px;
        }

        .btn-outline {
            background: transparent;
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }

        .btn-outline:hover {
            background: var(--bg-secondary);
        }

        .existing-contacts {
            margin-top: 25px;
        }

        .existing-contacts h3 {
            color: var(--primary-color);
            margin-bottom: 15px;
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

        .status-normal { color: #27AE60; }
        .status-warning { color: #F39C12; }
        .status-danger { color: #E74C3C; }

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
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .charts-container {
                grid-template-columns: 1fr;
            }
            
            .location-details {
                grid-template-columns: 1fr;
            }

            .modal-content {
                width: 95%;
                margin: 10% auto;
            }
        }

        @media (max-width: 768px) {
            .filter-options {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .filter-option {
                width: 100%;
                text-align: center;
            }

            .form-actions {
                flex-direction: column;
            }

            .modal-body {
                padding: 20px;
            }

            .device-info {
                flex-direction: column;
                gap: 10px;
            }
        }
</style>