<?php
// employee/_employee_sidebar.php
// This is included by _employee_header.php, which already requires employee role (or admin).
?>
<div class="sidebar">
    <h2>Plan Principal sistema</h2>
    <ul>
        <li><a href="employee_dashboard.php">Dashboard</a></li>
        <li><a href="view_hotels.php">Alquilar Habitación</a></li> <!-- LINK MODIFICADO -->
        <li><a href="view_rentals.php">Ver Mis Alquileres/Reportes</a></li>
         <li><a href="add_payment_method.php">Añadir Método de Pago</a></li>
        <li><a href="../logout.php">Cerrar Sesión</a></li>
    </ul>
</div>