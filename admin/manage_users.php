<?php
// admin/manage_users.php
include '_admin_header.php';

$users = [];
// Fetch all users
$sql = "SELECT id, username, role, status, created_at FROM users ORDER BY created_at DESC";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

// --- Function to translate user role ---
function translate_user_role($role) {
    switch ($role) {
        case 'admin':
            return 'Administrador';
        case 'employee':
            return 'Empleado';
        default:
            return $role; // Return original if unknown
    }
}

// --- Function to translate user status (reusing if available or define here) ---
// Let's define it here for clarity for this file, assuming it's not in includes
function translate_user_status($status) {
    switch ($status) {
        case 'active':
            return 'Activo';
        case 'inactive':
            return 'Inactivo';
        default:
            return $status; // Return original if unknown
    }
}

?>

<h2>Gestionar Usuarios</h2>

<?php display_message(); // Display flash messages ?>

<a href="add_user.php" class="btn">Añadir Nuevo Usuario</a>

<?php if (empty($users)): ?>
    <p>No hay usuarios registrados.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Usuario</th>
                <th>Rol</th>
                <th>Estado</th>
                <th>Fecha Creación</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo htmlspecialchars($user['id']); ?></td>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    <!-- Apply translation function here -->
                    <td><?php echo htmlspecialchars(translate_user_role($user['role'])); ?></td>
                     <!-- Apply translation function here -->
                    <td><?php echo htmlspecialchars(translate_user_status($user['status'])); ?></td>
                    <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                    <td>
                        <!-- Edit/Change Password Button -->
                         <a href="edit_user.php?id=<?php echo htmlspecialchars($user['id']); ?>" class="btn btn-warning btn-sm">Editar / Contraseña</a>

                        <?php if ($user['status'] === 'active'): ?>
                            <!-- Deactivate Button (using a form for POST) -->
                            <form action="process_user.php" method="post" style="display:inline-block;" onsubmit="return confirm('¿Está seguro de que desea dar de BAJA a este usuario?');">
                                <input type="hidden" name="action" value="deactivate">
                                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['id']); ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Dar de Baja</button>
                            </form>
                        <?php else: ?>
                            <!-- Activate Button (using a form for POST) -->
                            <form action="process_user.php" method="post" style="display:inline-block;" onsubmit="return confirm('¿Está seguro de que desea ACTIVAR a este usuario?');">
                                <input type="hidden" name="action" value="activate">
                                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['id']); ?>">
                                <button type="submit" class="btn btn-success btn-sm">Activar</button>
                            </form>
                        <?php endif; ?>

                        <!-- Delete Button (Use with caution!) -->
                         <?php if ($user['id'] != $_SESSION['user_id']): // Prevent deleting the currently logged-in user ?>
                            <form action="process_user.php" method="post" style="display:inline-block;" onsubmit="return confirm('¿Está seguro de que desea ELIMINAR a este usuario de forma permanente? Esta acción no se puede deshacer.');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['id']); ?>">
                                 <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                            </form>
                         <?php endif; ?>

                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php
include '_admin_footer.php';
?>