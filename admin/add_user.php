<?php
// admin/add_user.php
include '_admin_header.php'; // Includes config.php, db.php, and require_admin()
?>

<h2>Añadir Nuevo Usuario</h2>

<form action="process_user.php" method="post">
    <input type="hidden" name="action" value="add">
    <div>
        <label for="username">Nombre de Usuario:</label>
        <input type="text" id="username" name="username" required>
    </div>
    <div>
        <label for="password">Contraseña:</label>
        <input type="password" id="password" name="password" required>
    </div>
     <div>
        <label for="role">Rol:</label>
        <select id="role" name="role" required>
            <option value="">Seleccione un rol</option>
            <option value="admin">Administrador</option>
            <option value="employee">Empleado</option>
        </select>
    </div>
    <div>
        <button type="submit">Crear Usuario</button>
    </div>
</form>

<?php
include '_admin_footer.php'; // Closes DB connection
?>