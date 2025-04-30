<?php
// admin/edit_price.php
include '_admin_header.php';

$room_id = $_GET['id'] ?? null;
$room = null;

if ($room_id && is_numeric($room_id)) {
    // Fetch room details including current price
    $stmt = $conn->prepare("SELECT r.id, h.name AS hotel_name, r.room_number, r.price_per_night
                            FROM rooms r JOIN hotels h ON r.hotel_id = h.id
                            WHERE r.id = ?");
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $room = $result->fetch_assoc();
    } else {
        set_message('danger', 'Habitación no encontrada.');
        redirect('manage_prices.php');
    }
    $stmt->close();
} else {
    set_message('danger', 'ID de habitación no válido.');
    redirect('manage_prices.php');
}

// If room is not found after checks, redirect
if (!$room) {
     redirect('manage_prices.php');
}

?>

<h2>Modificar Precio de la Habitación: <?php echo htmlspecialchars($room['room_number'] . ' (' . $room['hotel_name'] . ')'); ?></h2>

<form action="process_price.php" method="post">
    <input type="hidden" name="room_id" value="<?php echo htmlspecialchars($room['id']); ?>">
    <input type="hidden" name="action" value="update">

    <div>
        <label for="new_price">Nuevo Precio por Noche:</label>
        <input type="number" id="new_price" name="new_price" step="0.01" min="0"
               value="<?php echo htmlspecialchars($room['price_per_night']); ?>" required>
    </div>

    <div>
        <button type="submit">Actualizar Precio</button>
    </div>
</form>

<?php
include '_admin_footer.php';
?>