<?php
// admin/edit_room.php
include '_admin_header.php'; // Includes config.php, db.php, and require_admin()

$room_id = $_GET['id'] ?? null;
$room = null;
$room_images = [];
$hotels = []; // For the dropdown

if ($room_id && is_numeric($room_id)) {
    // Fetch room details
    $stmt = $conn->prepare("SELECT id, hotel_id, room_number, room_type, capacity, price_per_night, status FROM rooms WHERE id = ?");
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $room = $result->fetch_assoc();

        // Fetch room images
        $stmt_img = $conn->prepare("SELECT id, image_path FROM room_images WHERE room_id = ? ORDER BY uploaded_at");
        $stmt_img->bind_param("i", $room_id);
        $stmt_img->execute();
        $result_img = $stmt_img->get_result();
        while($row_img = $result_img->fetch_assoc()) {
            $room_images[] = $row_img;
        }
        $stmt_img->close();

        // Get list of hotels for the dropdown
        $sql_hotels = "SELECT id, name FROM hotels ORDER BY name";
        $result_hotels = $conn->query($sql_hotels);
        while($row_hotels = $result_hotels->fetch_assoc()) {
            $hotels[] = $row_hotels;
        }


    } else {
        set_message('danger', 'Habitación no encontrada.');
        redirect('manage_rooms.php');
    }
    $stmt->close();
} else {
    set_message('danger', 'ID de habitación no válido.');
    redirect('manage_rooms.php');
}

// If room is not found after checks, redirect
if (!$room) {
     redirect('manage_rooms.php');
}

?>

<h2>Editar Habitación: <?php echo htmlspecialchars($room['room_number'] . ' (' . $room['room_type'] . ')'); ?></h2>

<!-- Form to Update Room Details -->
<form action="process_edit_room.php" method="post" style="margin-bottom: 30px;">
    <h3>Actualizar Detalles de la Habitación</h3>
    <input type="hidden" name="action" value="update_details">
    <input type="hidden" name="room_id" value="<?php echo htmlspecialchars($room['id']); ?>">

    <div>
        <label for="hotel_id">Hotel:</label>
        <select id="hotel_id" name="hotel_id" required>
            <option value="">Seleccione un hotel</option>
            <?php foreach ($hotels as $hotel): ?>
                <option value="<?php echo htmlspecialchars($hotel['id']); ?>" <?php echo ($hotel['id'] == $room['hotel_id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($hotel['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label for="room_number">Número de Habitación:</label>
        <input type="text" id="room_number" name="room_number" value="<?php echo htmlspecialchars($room['room_number']); ?>" required>
    </div>
    <div>
        <label for="room_type">Tipo de Habitación:</label>
        <input type="text" id="room_type" name="room_type" value="<?php echo htmlspecialchars($room['room_type']); ?>">
    </div>
    <div>
        <label for="capacity">Capacidad:</label>
        <input type="number" id="capacity" name="capacity" value="<?php echo htmlspecialchars($room['capacity']); ?>" min="1">
    </div>
     <div>
        <label for="price_per_night">Precio por Noche:</label>
        <input type="number" id="price_per_night" name="price_per_night" value="<?php echo htmlspecialchars($room['price_per_night']); ?>" step="0.01" min="0" required>
    </div>
     <div>
        <label for="status">Estado:</label>
        <select id="status" name="status" required>
             <option value="available" <?php echo ($room['status'] === 'available') ? 'selected' : ''; ?>>Disponible</option>
             <option value="occupied" <?php echo ($room['status'] === 'occupied') ? 'selected' : ''; ?>>Ocupada</option>
             <option value="maintenance" <?php echo ($room['status'] === 'maintenance') ? 'selected' : ''; ?>>Mantenimiento</option>
        </select>
    </div>

    <div>
        <button type="submit">Actualizar Detalles</button>
    </div>
</form>

<!-- Section to Manage Images -->
<h3 style="margin-top: 30px;">Imágenes de la Habitación</h3>

<?php if (empty($room_images)): ?>
    <p>No hay imágenes para esta habitación.</p>
<?php else: ?>
    <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px;">
        <?php foreach ($room_images as $image): ?>
            <div style="border: 1px solid #ddd; padding: 5px; border-radius: 4px; text-align: center;">
                 <!-- Display Image (Adjust path if needed) -->
                <img src="<?php echo htmlspecialchars($image['image_path']); ?>" alt="Imagen Habitación" style="width: 100px; height: auto; display: block; margin-bottom: 5px;">
                 <!-- Delete Image Button -->
                 <form action="process_edit_room.php" method="post" onsubmit="return confirm('¿Está seguro de eliminar esta imagen?');">
                    <input type="hidden" name="action" value="delete_image">
                    <input type="hidden" name="image_id" value="<?php echo htmlspecialchars($image['id']); ?>">
                     <input type="hidden" name="room_id" value="<?php echo htmlspecialchars($room['id']); ?>"> <!-- Keep room ID for redirect -->
                    <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                 </form>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Form to Upload New Images -->
<form action="process_edit_room.php" method="post" enctype="multipart/form-data">
    <h3>Subir Nueva Imagen</h3>
    <input type="hidden" name="action" value="add_image">
    <input type="hidden" name="room_id" value="<?php echo htmlspecialchars($room['id']); ?>">

    <div>
        <label for="new_room_image">Seleccionar Imagen:</label>
        <input type="file" id="new_room_image" name="new_room_image" accept="image/*" required>
    </div>
    <div>
        <button type="submit">Subir Imagen</button>
    </div>
</form>


<?php
include '_admin_footer.php'; // Closes DB connection
?>