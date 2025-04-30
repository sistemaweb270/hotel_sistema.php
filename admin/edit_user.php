<?php
// admin/edit_user.php
include '_admin_header.php'; // Includes config.php, db.php, and require_admin()

$user_id = $_GET['id'] ?? null;
$user = null;

if ($user_id && is_numeric($user_id)) {
    // Fetch user details (excluding password for security)
    $stmt = $conn->prepare("SELECT id, username, role, status FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
    } else {
        set_message('danger', 'Usuario no encontrado.');
        redirect('manage_users.php');
    }
    $stmt->close();
} else {
    set_message('danger', 'ID de usuario no válido.');
    redirect('manage_users.php');
}

// If user is not found after checks, redirect
if (!$user) {
     redirect('manage_users.php');
}

// Prevent editing own role/status via this page to avoid locking self out
$disable_role_edit = ($user['id'] == $_SESSION['user_id']);
$disable_status_edit = ($user['id'] == $_SESSION['user_id']);

?>

<h2>Editar Usuario: <?php echo htmlspecialchars($user['username']); ?></h2>

<form action="process_edit_user.php" method="post" style="margin-bottom: 30px;">
    <h3>Actualizar Detalles</h3>
    <input type="hidden" name="action" value="update_details">
    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['id']); ?>">

     <div>
        <label for="role">Rol:</label>
        <select id="role" name="role" required <?php echo $disable_role_edit ? 'disabled' : ''; ?>>
            <option value="admin" <?php echo ($user['role'] === 'admin') ? 'selected' : ''; ?>>Administrador</option>
            <option value="employee" <?php echo ($user['role'] === 'employee') ? 'selected' : ''; ?>>Empleado</option>
        </select>
        <?php if ($disable_role_edit): ?>
             <p><small style="color: #999;">(No puedes cambiar tu propio rol)</small></p>
        <?php endif; ?>
    </div>

    <div>
        <label for="status">Estado:</label>
        <select id="status" name="status" required <?php echo $disable_status_edit ? 'disabled' : ''; ?>>
            <option value="active" <?php echo ($user['status'] === 'active') ? 'selected' : ''; ?>>Activo</option>
            <option value="inactive" <?php echo ($user['status'] === 'inactive') ? 'selected' : ''; ?>>Inactivo</option>
        </select>
         <?php if ($disable_status_edit): ?>
             <p><small style="color: #999;">(No puedes cambiar tu propio estado aquí; usa "Dar de Baja" en la lista)</small></p>
        <?php endif; ?>
    </div>

    <div>
        <button type="submit" <?php echo ($disable_role_edit && $disable_status_edit) ? 'disabled' : ''; ?>>Actualizar Detalles</button>
    </div>
</form>

<form action="process_edit_user.php" method="post">
    <h3>Actualizar Contraseña</h3>
    <input type="hidden" name="action" value="update_password">
    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['id']); ?>">

    <div>
        <label for="new_password">Nueva Contraseña:</label>
        <input type="password" id="new_password" name="new_password" required>
    </div>
     <div>
        <label for="confirm_password">Confirmar Nueva Contraseña:</label>
        <input type="password" id="confirm_password" name="confirm_password" required>
    </div>

    <div>
        <button type="submit">Actualizar Contraseña</button>
    </div>
</form>


<?php
include '_admin_footer.php'; // Closes DB connection
?>