<?php
// admin/manage_rooms.php
include '_admin_header.php';

$rooms = [];
// Join with hotels table to show hotel name
$sql = "SELECT r.id, h.name AS hotel_name, r.room_number, r.room_type, r.capacity, r.price_per_night, r.status
        FROM rooms r
        JOIN hotels h ON r.hotel_id = h.id
        ORDER BY h.name, r.room_number";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $rooms[] = $row;
    }
}
?>

<h2>Gestionar Habitaciones</h2>

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
                    <td><?php echo htmlspecialchars($room['status']); ?></td>
                    </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php
include '_admin_footer.php';
?>