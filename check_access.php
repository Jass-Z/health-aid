<?php
// check_access.php - Access control functions

function checkPatientAccess() {
    if ($_SESSION['user']['role'] === 'patient') {
        header("Location: patient_dashboard.php");
        exit();
    }
}

function checkDoctorAccess() {
    if ($_SESSION['user']['role'] === 'doctor') {
        // Doctors can access all pages, no redirect needed
        return true;
    }
}

function getRedirectPage() {
    if ($_SESSION['user']['role'] === 'patient') {
        return 'patient_dashboard.php';
    } else {
        return 'dashboard.php';
    }
}
?>