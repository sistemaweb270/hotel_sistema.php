<?php
// employee/process_rental.php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_employee();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? ''; // Get the action (e.g., 'add_rental')

    // --- Action: Add New Rental ---
    if ($action === 'add_rental') {
        $room_id = $_POST['room_id'] ?? null; // Get room_id from the form
        $customer_name = trim($_POST['customer_name'] ?? '');
        $customer_id_doc = trim($_POST['customer_id_doc'] ?? '');
        $check_in_date = $_POST['check_in_date'] ?? null;
        $check_out_date = $_POST['check_out_date'] ?? null;
        $payment_method_id = $_POST['payment_method_id'] ?? null;
        $receipt_type = $_POST['receipt_type'] ?? null;
        $employee_user_id = $_SESSION['user_id'];

        // --- Basic Validation ---
        if (empty($room_id) || !is_numeric($room_id) || empty($customer_name) || empty($check_in_date) || empty($check_out_date) || empty($payment_method_id) || !is_numeric($payment_method_id) || empty($receipt_type)) {
             set_message('danger', 'Faltan datos obligatorios para el alquiler.');
             // Redirect back to the rent room page with the specific room_id if possible
             $redirect_url = !empty($room_id) ? 'rent_room.php?room_id=' . htmlspecialchars($room_id) : 'view_hotels.php';
             redirect($redirect_url);
        }

        // Validate dates
        $check_in_timestamp = strtotime($check_in_date);
        $check_out_timestamp = strtotime($check_out_date);

        if ($check_in_timestamp === false || $check_out_timestamp === false || $check_in_timestamp >= $check_out_timestamp) {
             set_message('danger', 'Fechas de check-in/check-out no válidas.');
              $redirect_url = !empty($room_id) ? 'rent_room.php?room_id=' . htmlspecialchars($room_id) : 'view_hotels.php';
             redirect($redirect_url);
        }

        // Calculate number of nights
        $diff = abs($check_out_timestamp - $check_in_timestamp);
        $num_nights = floor($diff / (60 * 60 * 24));
         if ($num_nights <= 0) {
             set_message('danger', 'La fecha de check-out debe ser posterior a la fecha de check-in.');
              $redirect_url = !empty($room_id) ? 'rent_room.php?room_id=' . htmlspecialchars($room_id) : 'view_hotels.php';
             redirect($redirect_url);
         }

        // --- Get Room Price and Re-validate Availability ---
        // Fetch room price and ensure it's still available for the *requested dates* (basic check)
        // A robust system would check for *conflicting rentals* in the rentals table.
        $stmt_room = $conn->prepare("SELECT price_per_night, status, hotel_id FROM rooms WHERE id = ?");
        if ($stmt_room === false) {
             // This is a prepare error, likely DB connection issue or syntax in query
             error_log("PROCESS_RENTAL: Error preparing room fetch: " . $conn->error);
              set_message('danger', 'Error interno al verificar habitación.');
             redirect('view_hotels.php');
        }
        $stmt_room->bind_param("i", $room_id);
        $stmt_room->execute();
        $result_room = $stmt_room->get_result();

        if ($result_room->num_rows === 0) {
            set_message('danger', 'La habitación ya no existe.');
            $stmt_room->close();
            redirect('view_hotels.php'); // Go back to hotel list
        }
        $room_data = $result_room->fetch_assoc();
        $stmt_room->close();

        // Store hotel_id for potential redirect later if needed
        $hotel_id_for_redirect = $room_data['hotel_id'] ?? null;


        if ($room_data['status'] !== 'available') {
             set_message('danger', 'La habitación ya no está disponible.');
             // Try to redirect back to the room list for that hotel if we can get the hotel ID
             if ($hotel_id_for_redirect) {
                  redirect('view_rooms_by_hotel.php?hotel_id=' . htmlspecialchars($hotel_id_for_redirect));
             } else {
                 redirect('view_hotels.php'); // Fallback
             }
        }

        $price_per_night = $room_data['price_per_night'];
        $total_price = $price_per_night * $num_nights;

        // --- Start Transaction (Recommended for multi-step operations) ---
        $conn->begin_transaction();

        try {
            // --- 1. Record the Rental ---
            $stmt_rental = $conn->prepare("INSERT INTO rentals (room_id, user_id, customer_name, customer_id_doc, check_in_date, check_out_date, total_price, payment_method_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt_rental === false) { // Check if prepare failed
                 throw new Exception('Error al preparar la sentencia de alquiler: ' . $conn->error);
            }
            // Corrected bind_param: "iissssdi" has 8 chars for 8 vars
            $stmt_rental->bind_param("iissssdi", $room_id, $employee_user_id, $customer_name, $customer_id_doc, $check_in_date, $check_out_date, $total_price, $payment_method_id);
             if ($stmt_rental->execute() === false) { // Check if execute failed
                 throw new Exception('Error al registrar el alquiler: ' . $stmt_rental->error);
             }
            $rental_id = $stmt_rental->insert_id;
            $stmt_rental->close(); // Close statement after execution

            // --- 2. Issue the Receipt ---
            // Generate a simple unique receipt number (Timestamp + Rental ID)
            $receipt_number = date('YmdHis') . $rental_id;

            $stmt_receipt = $conn->prepare("INSERT INTO receipts (rental_id, receipt_number, amount, type) VALUES (?, ?, ?, ?)");
             if ($stmt_receipt === false) { // Check if prepare failed
                 throw new Exception('Error al preparar la sentencia de comprobante: ' . $conn->error);
             }
            // CORRECTED bind_param: "isds" has 4 chars for 4 vars ($rental_id, $receipt_number, $total_price, $receipt_type)
            $stmt_receipt->bind_param("isds", $rental_id, $receipt_number, $total_price, $receipt_type);
             if ($stmt_receipt->execute() === false) { // Check if execute failed
                 throw new Exception('Error al emitir el comprobante: ' . $stmt_receipt->error);
             }
             $stmt_receipt->close(); // Close statement after execution

            // --- 3. Update Room Status (Optional, depending on availability logic) ---
            // Mark the room as occupied. A real system needs more complex availability based on rental dates.
             $stmt_update_room = $conn->prepare("UPDATE rooms SET status = 'occupied' WHERE id = ?");
             if ($stmt_update_room === false) { // Check if prepare failed
                 error_log("PROCESS_RENTAL: Error al preparar la sentencia de actualizar estado de habitación: " . $conn->error); // Log, but don't throw exception that rolls back rental/receipt
             } else {
                 $stmt_update_room->bind_param("i", $room_id);
                  if ($stmt_update_room->execute() === false) {
                     // Log this error, but might not need to roll back if availability is date-based
                     error_log("PROCESS_RENTAL: Error updating room status for room ID " . $room_id . ": " . $stmt_update_room->error);
                 }
                 $stmt_update_room->close(); // Close statement after execution
             }


            // If everything was successful, commit the transaction
            $conn->commit();
            set_message('success', 'Alquiler registrado y comprobante emitido con éxito. Nº Comprobante: ' . htmlspecialchars($receipt_number));
            redirect('view_rentals.php'); // Redirect to view rentals

        } catch (Exception $e) {
            // An error occurred, roll back the transaction
            $conn->rollback();
            // Clean up any prepared statements that might still be open due to the error
            if (isset($stmt_rental) && $stmt_rental !== false) $stmt_rental->close();
            if (isset($stmt_receipt) && $stmt_receipt !== false) $stmt_receipt->close();
            if (isset($stmt_update_room) && $stmt_update_room !== false) $stmt_update_room->close();


            set_message('danger', 'Error en el proceso de alquiler: ' . $e->getMessage());
            // Try to redirect back to the rent room page with the specific room_id if possible
             $redirect_url = !empty($room_id) ? 'rent_room.php?room_id=' . htmlspecialchars($room_id) : 'view_hotels.php';
            redirect($redirect_url); // Redirect back to the form or hotel list
        }

    }
    // Handle unknown action
    else {
        set_message('warning', 'Acción no válida.');
         redirect('view_hotels.php'); // Default redirect
    }


} else {
    set_message('warning', 'Método de solicitud no válido.');
    redirect('view_hotels.php'); // Or a default employee page
}

$conn->close(); // Close main connection at the end
?>