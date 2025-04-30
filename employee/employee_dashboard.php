<?php
// employee/employee_dashboard.php
include '_employee_header.php';
?>

<h2>Bienvenido, <?php echo htmlspecialchars($_SESSION['username']); ?> (Empleado)</h2>
<p>Seleccione una opción del menú lateral para gestionar alquileres.</p>

<?php
include '_employee_footer.php';
?>