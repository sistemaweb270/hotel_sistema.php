<?php
// admin/process_edit_user.php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_admin(); // Ensure user is logged in and is admin

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = $_POST['user_id'] ?? null;

    // Validate user_id first
    if (empty($user_id) || !is_numeric($user_id)) {
        set_message('danger', 'ID de usuario no válido.');
        redirect('manage_users.php');
    }

    // --- Action: Update User Details (Role/Status) ---
    if ($action === 'update_details') {
        $role = $_POST['role'] ?? null;
        $status = $_POST['status'] ?? null;

        // Validate inputs
        if (empty($role) || empty($status)) {
             set_message('danger', 'Rol y estado son obligatorios.');
             redirect('edit_user.php?id=' . htmlspecialchars($user_id));
        }
        if (!in_array($role, ['admin', 'employee']) || !in_array($status, ['active', 'inactive'])) {
             set_message('danger', 'Rol o estado no válido.');
             redirect('edit_user.php?id=' . htmlspecialchars($user_id));
        }

        // Prevent changing own role or status via this form
        if ($user_id == $_SESSION['user_id']) {
             set_message('warning', 'No puedes cambiar tu propio rol o estado mediante esta página.');
             redirect('edit_user.php?id=' . htmlspecialchars($user_id));
        }


        // Update user details
        $stmt = $conn->prepare("UPDATE users SET role = ?, status = ? WHERE id = ?");
        $stmt->bind_param("ssi", $role, $status, $user_id);

        if ($stmt->execute()) {
            set_message('success', 'Detalles del usuario actualizados con éxito.');
        } else {
            set_message('danger', 'Error al actualizar los detalles del usuario: ' . $stmt->error);
        }
        $stmt->close();
        redirect('manage_users.php'); // Redirect back to list after update
    }

    // --- Action: Update User Password ---
    elseif ($action === 'update_password') {
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Validate inputs
        if (empty($new_password) || empty($confirm_password)) {
             set_message('danger', 'Nueva contraseña y confirmación son obligatorias.');
             redirect('edit_user.php?id=' . htmlspecialchars($user_id));
        }
        if ($new_password !== $confirm_password) {
             set_message('danger', 'La nueva contraseña y la confirmación no coinciden.');
             redirect('edit_user.php?id=' . htmlspecialchars($user_id));
        }

        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
         if ($hashed_password === false) {
             set_message('danger', 'Error al hashear la nueva contraseña.');
             redirect('edit_user.php?id=' . htmlspecialchars($user_id));
         }


        // Update password
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $user_id);

        if ($stmt->execute()) {
             // If updating own password, update session might be needed, but role/status are primary session data
            set_message('success', 'Contraseña del usuario actualizada con éxito.');
        } else {
            set_message('danger', 'Error al actualizar la contraseña: ' . $stmt->error);
        }
        $stmt->close();
        redirect('manage_users.php'); // Redirect back to list after update
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