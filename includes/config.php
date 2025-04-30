<?php
// includes/config.php (VERSIÓN FINAL CORREGIDA)

// Database credentials
define('DB_SERVER', 'localhost:3308'); // e.g., 'localhost' or IP address (or 'localhost:3308' if needed) - VERIFICA ESTO
define('DB_USERNAME', 'root'); // Your database username - PON TU USUARIO REAL
define('DB_PASSWORD', 'root'); // Your database password - PON TU CONTRASEÑA REAL
define('DB_NAME', 'hotel_sistema'); // Your database name (use the name you created, e.g., 'hotel_sistema') - PON TU NOMBRE DE BD REAL

// Base URL (optional, useful for redirects and links)
// define('BASE_URL', 'http://localhost/hotel_system/');

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Error reporting (set to 0 in production, E_ALL in development)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set default timezone (important for date/time functions)
date_default_timezone_set('America/Lima'); // PON TU ZONA HORARIA si no es Lima

// --- Helper Functions ---

// Function to check if user is logged in AND active
function is_logged_in() {
    // Check if user_id is set AND status is active
    return isset($_SESSION['user_id']) && isset($_SESSION['status']) && $_SESSION['status'] === 'active';
}

// Function to check if user has a specific role
function has_role($role) {
    return is_logged_in() && $_SESSION['role'] === $role;
}

// Function to redirect
function redirect($url) {
    // Clean output buffer before redirecting
    if (ob_get_contents()) {
        ob_end_clean();
    }
    header("Location: " . $url);
    exit();
}

// Function to set a flash message
function set_message($type, $message) {
    $_SESSION['message'] = ['type' => $type, 'text' => $message];
}

// Function to display flash message
function display_message() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        // Use basic alert classes, ensure style.css has them
        echo "<div class='alert alert-" . htmlspecialchars($message['type']) . "'>" . htmlspecialchars($message['text']) . "</div>";
        unset($_SESSION['message']); // Clear the message after displaying
    }
}

// Simple authentication check for admin pages
function require_admin() {
    // Check if logged in, is active, and has admin role
    if (!has_role('admin')) {
        set_message('danger', 'Acceso denegado. Solo administradores activos.');
        // Clear session partially to avoid loop if status changed while logged in
        unset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['role'], $_SESSION['status']);
        redirect('../index.php'); // Redirect to login or main page
    }
}

// Simple authentication check for employee pages
function require_employee() {
    // Check if logged in and is active
     if (!is_logged_in()) {
        set_message('danger', 'Acceso denegado. Inicie sesión.');
         redirect('../index.php'); // Redirect to login page
     }
    // Employee role includes admin for simplicity here, adjust if needed
    if (!has_role('employee') && !has_role('admin')) {
        set_message('danger', 'Acceso denegado. Solo empleados o administradores activos.');
        redirect('../index.php'); // Redirect to login or main page
    }
}

