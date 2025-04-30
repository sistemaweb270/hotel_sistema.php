<?php
// admin/manage_rooms.php
include '_admin_header.php';

$rooms = [];

// Fetch room data, joining with hotels to get hotel name
$sql = "SELECT
            rm.id,
            rm.hotel_id,
            h.name AS hotel_name,
            rm.room_number,
            rm.room_type,
            rm.capacity,
            rm.price_per_night,
            rm.status, -- Room status
            (SELECT COUNT(id) FROM room_images WHERE room_id = rm.id) AS image_count -- Count images for the room
        FROM rooms rm
        JOIN hotels h ON rm.hotel_id = h.id
        ORDER BY h.name, rm.room_number";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $rooms[] = $row;
    }
}

// --- Function to translate room status for Admin ---
function translate_room_status_admin($status) {
    switch ($status) {
        case 'available':
            return 'Disponible';
        case 'occupied':
            return 'Ocupada';
        case 'maintenance':
            return 'Mantenimiento';
        default:
            return $status; // Return original if unknown
    }
}
?>

<h2>Gestionar Habitaciones Y Hoteles</h2>
<a href="add_hotel.php" class="btn">Añadir Nuevo Hotel</a> <!-- Este es el enlace -->
<?php display_message(); // Display flash messages ?>

<a href="add_room.php" class="btn">Añadir Nueva Habitación</a>

<?php if (empty($rooms)): ?>
    <p>No hay habitaciones registradas.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Hotel</th>
                <th>Número</th>
                <th>Tipo</th>
                <th>Capacidad</th>
                <th>Precio por Noche</th>
                <th>Estado</th>
                <th>Imágenes</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rooms as $room): ?>
                <tr>
                    <td><?php echo htmlspecialchars($room['id']); ?></td>
                    <td><?php echo htmlspecialchars($room['hotel_name']); ?></td>
                    <td><?php echo htmlspecialchars($room['room_number']); ?></td>
                    <td><?php echo htmlspecialchars($room['room_type']); ?></td>
                    <td><?php echo htmlspecialchars($room['capacity']); ?></td>
                    <td><?php echo htmlspecialchars(number_format($room['price_per_night'], 2)); ?></td>
                    <!-- Apply translation function here -->
                    <td><?php echo htmlspecialchars(translate_room_status_admin($room['status'])); ?></td>
                    <td><?php echo htmlspecialchars($room['image_count']); ?></td>
                    <td>
                         <!-- Edit Room Details Button -->
                         <a href="edit_room.php?id=<?php echo htmlspecialchars($room['id']); ?>" class="btn btn-warning btn-sm">Editar</a>
                         <!-- Manage Images Button (Optional - link to a new page) -->
                         <!-- <a href="manage_room_images.php?room_id=<?php echo htmlspecialchars($room['id']); ?>" class="btn btn-info btn-sm">Imágenes</a> -->
                        <!-- Delete Room Button (Optional) -->
                         <!-- <a href="delete_room.php?id=<?php echo htmlspecialchars($room['id']); ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Está seguro de que desea eliminar esta habitación y todas sus imágenes?');">Eliminar</a> -->
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php
include '_admin_footer.php';
?>