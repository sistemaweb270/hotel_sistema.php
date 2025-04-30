<?php
// admin/manage_prices.php
include '_admin_header.php';

$rooms = [];
// Join with hotels table to show hotel name
$sql = "SELECT r.id, h.name AS hotel_name, r.room_number, r.room_type, r.price_per_night
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

<h2>Modificar Precios de Habitaciones</h2>

<?php if (empty($rooms)): ?>
    <p>No hay habitaciones registradas para modificar precios.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Hotel</th>
                <th>Número</th>
                <th>Tipo</th>
                <th>Precio Actual por Noche</th>
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
                    <td><?php echo htmlspecialchars(number_format($room['price_per_night'], 2)); ?></td>
                    <td>
                        <a href="edit_price.php?id=<?php echo htmlspecialchars($room['id']); ?>" class="btn btn-warning">Modificar Precio</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php
include '_admin_footer.php';
?>