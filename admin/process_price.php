<?php
// admin/process_price.php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update') {
        $room_id = $_POST['room_id'] ?? null;
        $new_price = $_POST['new_price'] ?? null;

        if (empty($room_id) || !is_numeric($room_id) || !is_numeric($new_price) || $new_price < 0) {
            set_message('danger', 'Datos de precio no válidos.');
            redirect('manage_prices.php'); // Or redirect back to edit page with error
        }

        // Prevent SQL injection
        $stmt = $conn->prepare("UPDATE rooms SET price_per_night = ? WHERE id = ?");
        $stmt->bind_param("di", $new_price, $room_id); // 'd' for decimal/double

        if ($stmt->execute()) {
            set_message('success', 'Precio actualizado con éxito.');
            redirect('manage_prices.php');
        } else {
            set_message('danger', 'Error al actualizar el precio: ' . $stmt->error);
            // Redirect back to the edit page for this room
            redirect('edit_price.php?id=' . htmlspecialchars($room_id));
        }

        $stmt->close();

    }
} else {
    set_message('warning', 'Método de solicitud no válido.');
    redirect('manage_prices.php');
}

$conn->close();
?>