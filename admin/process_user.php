<?php
// admin/process_user.php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_admin(); // Ensure user is logged in and is admin

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // --- Action: Add New User ---
    if ($action === 'add') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? ''; // Do not trim password
        $role = $_POST['role'] ?? '';

        // Basic Validation
        if (empty($username) || empty($password) || empty($role)) {
             set_message('danger', 'Usuario, contraseña y rol son obligatorios.');
             redirect('add_user.php');
        }
        if (!in_array($role, ['admin', 'employee'])) {
             set_message('danger', 'Rol no válido.');
             redirect('add_user.php');
        }

        // Check if username already exists
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();
        $check_stmt->store_result();
        if ($check_stmt->num_rows > 0) {
            set_message('danger', 'El nombre de usuario ya existe.');
            $check_stmt->close();
            redirect('add_user.php');
        }
        $check_stmt->close();

        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        if ($hashed_password === false) {
             set_message('danger', 'Error al hashear la contraseña.');
             redirect('add_user.php');
        }

        // Insert user (status defaults to 'active' in DB schema)
        $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $hashed_password, $role);

        if ($stmt->execute()) {
            set_message('success', 'Usuario añadido con éxito.');
            redirect('manage_users.php');
        } else {
            set_message('danger', 'Error al añadir el usuario: ' . $stmt->error);
            redirect('add_user.php');
        }
        $stmt->close();
    }

    // --- Action: Change User Status (Activate/Deactivate) ---
    elseif ($action === 'change_status') {
        $user_id = $_POST['user_id'] ?? null;
        $new_status = $_POST['new_status'] ?? null;

        // Validate inputs
        if (empty($user_id) || !is_numeric($user_id) || !in_array($new_status, ['active', 'inactive'])) {
             set_message('danger', 'Datos de estado de usuario no válidos.');
             redirect('manage_users.php');
        }

        // Prevent changing status of the currently logged-in user
        if ($user_id == $_SESSION['user_id']) {
             set_message('warning', 'No puedes cambiar tu propio estado.');
             redirect('manage_users.php');
        }

        // Update status
        $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $user_id);

        if ($stmt->execute()) {
            $message = ($new_status === 'active') ? 'Usuario activado con éxito.' : 'Usuario dado de baja con éxito.';
            set_message('success', $message);
        } else {
            set_message('danger', 'Error al actualizar el estado del usuario: ' . $stmt->error);
        }
        $stmt->close();
        redirect('manage_users.php');
    }

    // --- Action: Delete User ---
    elseif ($action === 'delete') {
        $user_id = $_POST['user_id'] ?? null;

        // Validate input
        if (empty($user_id) || !is_numeric($user_id)) {
             set_message('danger', 'ID de usuario no válido para eliminar.');
             redirect('manage_users.php');
        }

         // Prevent deleting the currently logged-in user
        if ($user_id == $_SESSION['user_id']) {
             set_message('warning', 'No puedes eliminar tu propia cuenta.');
             redirect('manage_users.php');
        }

        // Check if the user has associated rentals (Optional: prevents deletion if linked)
        // For simplicity here, we just delete. If FOREIGN KEY ON DELETE is not SET NULL/CASCADE,
        // deleting a user with rentals will cause a foreign key constraint error.
        // A better approach is often soft delete (status = 'inactive').
        // If you want to PREVENT deletion, uncomment this block:
        /*
        $check_rentals_stmt = $conn->prepare("SELECT COUNT(*) FROM rentals WHERE user_id = ?");
        $check_rentals_stmt->bind_param("i", $user_id);
        $check_rentals_stmt->execute();
        $check_rentals_stmt->bind_result($rental_count);
        $check_rentals_stmt->fetch();
        $check_rentals_stmt->close();

        if ($rental_count > 0) {
            set_message('danger', 'No se puede eliminar el usuario porque tiene alquileres asociados. Considere darlo de baja en su lugar.');
            redirect('manage_users.php');
        }
        */

        // Delete user
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);

        if ($stmt->execute()) {
            // Check if any row was actually deleted
            if ($stmt->affected_rows > 0) {
                 set_message('success', 'Usuario eliminado con éxito.');
            } else {
                 set_message('warning', 'Usuario no encontrado o ya eliminado.');
            }
        } else {
            // This is where a foreign key error might occur if rentals exist and ON DELETE is not set
            set_message('danger', 'Error al eliminar el usuario: ' . $stmt->error);
        }
        $stmt->close();
        redirect('manage_users.php');
    }

    // Handle unknown action
    else {
        set_message('danger', 'Acción no válida.');
        redirect('manage_users.php');
    }

} else {
    set_message('warning', 'Método de solicitud no válido.');
    redirect('manage_users.php');
}

$conn->close();
?>