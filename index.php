<?php
// index.php - Login Page
require_once 'includes/config.php';
require_once 'includes/db.php';

// Redirect if already logged in AND active
if (is_logged_in()) {
    if ($_SESSION['role'] === 'admin') {
        redirect('admin/admin_dashboard.php');
    } else {
        redirect('employee/employee_dashboard.php');
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Prevent SQL injection using prepared statements
    // Select status as well
    $stmt = $conn->prepare("SELECT id, username, password, role, status FROM users WHERE username = ?");
    if ($stmt === false) {
         // Log error if prepare fails
         error_log("Login prepare failed: " . $conn->error);
         $error = 'Error interno del servidor.';
    } else {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            // Bind status variable
            $stmt->bind_result($user_id, $db_username, $hashed_password, $role, $status);
            $stmt->fetch();

            // Verify password and check if user is active
            if (password_verify($password, $hashed_password) && $status === 'active') {
                // Password is correct and user is active, start session
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $db_username;
                $_SESSION['role'] = $role;
                $_SESSION['status'] = $status; // Store status in session

                // Redirect based on role
                if ($role === 'admin') {
                    redirect('admin/admin_dashboard.php');
                } else {
                    // Employees redirect to view hotels page initially
                    redirect('employee/view_hotels.php'); // MODIFICADO: Redirect employees to hotel catalog
                }
            } else {
                // Password incorrect OR user is inactive
                // Provide a generic error message for security
                $error = 'Invalid username or password.';
            }
        } else {
            // User not found
            $error = 'Invalid username or password.';
        }

        $stmt->close();
    }

    // Close connection here if you don't need it further in this script
    // $conn->close(); // Connection will be closed by _footer files anyway
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión de Hoteles - Login</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f4f4f4;
        }
        .login-container {
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            width: 300px;
            text-align: center;
        }
        .login-container h2 {
            margin-bottom: 20px;
            color: #333;
        }
        .login-container input[type="text"],
        .login-container input[type="password"] {
            width: calc(100% - 20px);
            margin-bottom: 15px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .login-container button {
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
        }
        .login-container button:hover {
            background-color: #0056b3;
        }
        .error {
            color: #d9534f;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>SISTEMA DE GESTION DE HOTELES</h2>
        <?php if ($error): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
         <?php display_message(); // Display flash messages ?>
        <form action="index.php" method="post">
            <div>
                <input type="text" name="username" placeholder="Usuario" required>
            </div>
            <div>
                <input type="password" name="password" placeholder="Contraseña" required>
            </div>
            <div>
                <button type="submit">Ingresar</button>
            </div>
        </form>
    </div>
</body>
</html>