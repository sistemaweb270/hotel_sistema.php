<?php
// admin/admin_dashboard.php
include '_admin_header.php';
?>

<h2>Bienvenido, <?php echo htmlspecialchars($_SESSION['username']); ?> (Administrador)</h2>
<p>Seleccione una opción del menú lateral para gestionar el sistema.</p>

<?php
include '_admin_footer.php';
?>