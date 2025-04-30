<?php
// admin/add_hotel.php
include '_admin_header.php';
?>

<h2>Añadir Nuevo Hotel</h2>

<form action="process_hotel.php" method="post">
    <input type="hidden" name="action" value="add">
    <div>
        <label for="name">Nombre del Hotel:</label>
        <input type="text" id="name" name="name" required>
    </div>
     <div>
        <label for="address">Dirección:</label>
        <input type="text" id="address" name="address">
    </div>
    <div>
        <label for="phone">Teléfono:</label>
        <input type="text" id="phone" name="phone">
    </div>
    <div>
        <label for="email">Email:</label>
        <input type="email" id="email" name="email">
    </div>
    <div>
        <button type="submit">Añadir Hotel</button>
    </div>
</form>

<?php
include '_admin_footer.php';
?>