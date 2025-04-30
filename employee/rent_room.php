<?php
// employee/rent_room.php (MODIFICADO - Estilo de botón Volver)
include '_employee_header.php'; // Includes config.php, db.php, and require_employee()

$room_id = $_GET['room_id'] ?? null;
$room = null; // Details of the selected room
$hotel = null; // Details of the room's hotel
$hotel_id_for_back_link = null; // To store hotel_id for the back link


// --- Validate room_id and Fetch Room/Hotel Details ---
if ($room_id && is_numeric($room_id)) {
    // Fetch room and its hotel details
    $stmt = $conn->prepare("SELECT r.id, r.hotel_id, r.room_number, r.room_type, r.capacity, r.price_per_night, r.status,
                            h.name AS hotel_name, h.address AS hotel_address
                            FROM rooms r
                            JOIN hotels h ON r.hotel_id = h.id
                            WHERE r.id = ?");
    if ($stmt === false) {
         error_log("RENT_ROOM: Error preparing room/hotel fetch: " . $conn->error);
          set_message('danger', 'Error interno al verificar habitación.');
         redirect('view_hotels.php');
    }
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $room = $result->fetch_assoc();
        // Check if the room is actually available before showing the form
        if ($room['status'] !== 'available') {
            set_message('warning', 'La habitación seleccionada no está disponible para alquilar.');
            // Redirect back to the room list for that hotel
            redirect('view_rooms_by_hotel.php?hotel_id=' . htmlspecialchars($room['hotel_id']));
        }
         // Set hotel details from the join result
        $hotel = ['id' => $room['hotel_id'], 'name' => $room['hotel_name'], 'address' => $room['hotel_address']];
        // Store hotel_id for the back link
        $hotel_id_for_back_link = $room['hotel_id'];


    } else {
        set_message('danger', 'Habitación no encontrada.');
        redirect('view_hotels.php'); // Redirect back to hotel list
    }
    $stmt->close();
} else {
    set_message('danger', 'ID de habitación no especificado.');
    redirect('view_hotels.php'); // Redirect back to hotel list
}

// If room/hotel not found or not available after validation, stop processing
if (!$room || !$hotel) {
    // Redirect already handled above
    exit(); // Stop execution after redirecting
}


// Fetch payment methods (same as before)
$payment_methods = [];
$sql_payments = "SELECT id, method_name FROM payment_methods ORDER BY method_name";
$result_payments = $conn->query($sql_payments);
if ($result_payments && $result_payments->num_rows > 0) {
    while($row = $result_payments->fetch_assoc()) {
        $payment_methods[] = $row;
    }
} else if (!$result_payments) {
     error_log("RENT_ROOM: Error fetching payment methods: " . $conn->error);
     set_message('warning', 'Error al cargar métodos de pago.');
}

?>

<h2>Alquilar Habitación: <?php echo htmlspecialchars($room['room_number'] . ' en ' . $hotel['name']); ?></h2>

<?php display_message(); // Display flash messages ?>

<p>
     <?php if ($hotel_id_for_back_link): // Only show link if hotel_id is known ?>
        <a href="view_rooms_by_hotel.php?hotel_id=<?php echo htmlspecialchars($hotel_id_for_back_link); ?>" style="cursor: pointer;"     class="btn btn-secondary" >&laquo; Volver a Habitaciones de este Hotel</a>
    <?php else: // Fallback link ?>
         <a href="view_hotels.php" class="btn btn-secondary" style="cursor: pointer;"   >&laquo; Volver a Hoteles</a>
    <?php endif; ?>
</p>


<form action="process_rental.php" method="post">
    <input type="hidden" name="room_id" value="<?php echo htmlspecialchars($room['id']); ?>">
    <input type="hidden" name="action" value="add_rental"> <div style="border: 1px solid #ddd; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
         <h4>Detalles de la Habitación Seleccionada:</h4>
         <p><strong>Hotel:</strong> <?php echo htmlspecialchars($hotel['name']); ?></p>
         <p><strong>Número:</strong> <?php echo htmlspecialchars($room['room_number']); ?></p>
         <p><strong>Tipo:</strong> <?php echo htmlspecialchars($room['room_type']); ?></p>
         <p><strong>Precio por Noche:</strong> <?php echo htmlspecialchars(number_format($room['price_per_night'], 2)); ?></p>
         </div>


    <div>
        <label for="customer_name">Nombre del Cliente:</label>
        <input type="text" id="customer_name" name="customer_name" required>
    </div>
    <div>
        <label for="customer_id_doc">Documento de Identidad del Cliente:</label>
        <input type="text" id="customer_id_doc" name="customer_id_doc">
    </div>

    <div>
        <label for="check_in_date">Fecha de Ingreso:</label>
        <input type="date" id="check_in_date" name="check_in_date" required>
    </div>
    <div>
        <label for="check_out_date">Fecha de Salida:</label>
        <input type="date" id="check_out_date" name="check_out_date" required>
    </div>

     <div>
        <label for="payment_method_id">Método de Pago:</label>
        <?php if (empty($payment_methods)): ?>
             <p class="alert alert-warning">No hay métodos de pago registrados. <a href="add_payment_method.php">Añada uno aquí</a>.</p>
        <?php else: ?>
        <select id="payment_method_id" name="payment_method_id" required>
            <option value="">Seleccione un método de pago</option>
            <?php foreach ($payment_methods as $method): ?>
                <option value="<?php echo htmlspecialchars($method['id']); ?>"><?php echo htmlspecialchars($method['method_name']); ?></option>
            <?php endforeach; ?>
        </select>
         <?php endif; ?>
    </div>

    <div>
        <label for="receipt_type">Emitir:</label>
        <select id="receipt_type" name="receipt_type" required>
            <option value="">Seleccione tipo de comprobante</option>
            <option value="boleta">Boleta</option>
            <option value="factura">Factura</option>
        </select>
    </div>

    <div>
        <button type="submit">Registrar Alquiler y Emitir Comprobante</button>
    </div>
</form>

<?php
include '_employee_footer.php'; // Closes DB connection
?>