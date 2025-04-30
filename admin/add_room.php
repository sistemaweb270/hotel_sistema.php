<?php
// admin/add_room.php
include '_admin_header.php';

// Get list of hotels for the dropdown
$hotels = [];
$sql = "SELECT id, name FROM hotels ORDER BY name";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $hotels[] = $row;
    }
}
?>

<h2>Añadir Nueva Habitación</h2>

<?php if (empty($hotels)): ?>
    <p class="alert alert-warning">No hay hoteles registrados. Añada un hotel primero antes de añadir habitaciones.</p>
    <a href="add_hotel.php" class="btn">Añadir Hotel</a>
<?php else: ?>

<!-- ADDED enctype for file upload -->
<form action="process_room.php" method="post" enctype="multipart/form-data">
    <input type="hidden" name="action" value="add">
    <div>
        <label for="hotel_id">Hotel:</label>
        <select id="hotel_id" name="hotel_id" required>
            <option value="">Seleccione un hotel</option>
            <?php foreach ($hotels as $hotel): ?>
                <option value="<?php echo htmlspecialchars($hotel['id']); ?>"><?php echo htmlspecialchars($hotel['name']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label for="room_number">Número de Habitación:</label>
        <input type="text" id="room_number" name="room_number" required>
    </div>
    <div>
        <label for="room_type">Tipo de Habitación:</label>
        <input type="text" id="room_type" name="room_type">
    </div>
    <div>
        <label for="capacity">Capacidad:</label>
        <input type="number" id="capacity" name="capacity" min="1">
    </div>
     <div>
        <label for="price_per_night">Precio por Noche:</label>
        <input type="number" id="price_per_night" name="price_per_night" step="0.01" min="0" required>
    </div>
     <div>
        <label for="status">Estado Inicial:</label>
        <select id="status" name="status" required>
             <option value="available">Disponible</option>
             <option value="occupied">Ocupada</option>
             <option value="maintenance">Mantenimiento</option>
        </select>
    </div>
    <!-- ADDED File Upload Input -->
    <div>
        <label for="room_image">Imagen de la Habitación (Opcional):</label>
        <input type="file" id="room_image" name="room_image" accept="image/*">
    </div>
    <div>
        <button type="submit">Añadir Habitación</button>
    </div>
</form>

<?php endif; ?>

<?php
include '_admin_footer.php';
?>