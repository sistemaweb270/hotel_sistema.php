<?php
// employee/add_payment_method.php
include '_employee_header.php';
?>

<h2>Añadir Nuevo Método de Pago</h2>

<form action="process_payment_method.php" method="post">
     <input type="hidden" name="action" value="add">
    <div>
        <label for="method_name">Nombre del Método de Pago:</label>
        <input type="text" id="method_name" name="method_name" required>
    </div>
    <div>
        <button type="submit">Añadir Método de Pago</button>
    </div>
</form>

<?php
include '_employee_footer.php';
?>