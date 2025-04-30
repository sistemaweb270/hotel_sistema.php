<?php
// employee/process_cancellation.php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_employee(); // Ensure user is logged in and is employee or admin

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rental_id = $_POST['rental_id'] ?? null;
    $room_id = $_POST['room_id'] ?? null; // Get room_id from the form
    $cancelling_user_id = $_SESSION['user_id']; // Get the ID of the user performing the action

    // Validate inputs
    if (empty($rental_id) || !is_numeric($rental_id) || empty($room_id) || !is_numeric($room_id)) {
         set_message('danger', 'Datos de alquiler no válidos para cancelar.');
         redirect('view_rentals.php');
    }

    // --- Fetch Rental Details and Validate Status/Ownership ---
    $stmt_check = $conn->prepare("SELECT status, user_id FROM rentals WHERE id = ?");
    if ($stmt_check === false) {
         error_log("PROCESS_CANCELLATION: Error preparing rental check: " . $conn->error);
          set_message('danger', 'Error interno al verificar el alquiler.');
         redirect('view_rentals.php');
    }
    $stmt_check->bind_param("i", $rental_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows === 0) {
         set_message('danger', 'Alquiler no encontrado.');
         $stmt_check->close();
         redirect('view_rentals.php');
    }

    $rental_data = $result_check->fetch_assoc();
    $stmt_check->close();

    // Check if rental is already cancelled or completed
    if ($rental_data['status'] !== 'active') {
         set_message('warning', 'Este alquiler ya no está activo y no se puede cancelar.');
         redirect('view_rentals.php');
    }

    // Optional: Check if the cancelling user is the one who made the rental
    // if ($rental_data['user_id'] !== $cancelling_user_id && $_SESSION['role'] !== 'admin') {
    //     set_message('danger', 'No tienes permiso para cancelar este alquiler.');
    //     redirect('view_rentals.php');
    // }


    // --- Start Transaction ---
    $conn->begin_transaction();

    try {
        // --- 1. Update Rental Status to 'cancelled' ---
        $stmt_cancel_rental = $conn->prepare("UPDATE rentals SET status = 'cancelled' WHERE id = ?");
        if ($stmt_cancel_rental === false) {
             throw new Exception('Error al preparar la cancelación del alquiler: ' . $conn->error);
        }
        $stmt_cancel_rental->bind_param("i", $rental_id);
        if ($stmt_cancel_rental->execute() === false) {
             throw new Exception('Error al cancelar el alquiler: ' . $stmt_cancel_rental->error);
        }
        $stmt_cancel_rental->close();

        // --- 2. Update Room Status back to 'available' ---
        $stmt_update_room = $conn->prepare("UPDATE rooms SET status = 'available' WHERE id = ?");
        if ($stmt_update_room === false) {
            // Log this error, but continue if possible
             error_log("PROCESS_CANCELLATION: Error al preparar la actualización de estado de habitación: " . $conn->error);
             // Decide if this should stop the transaction - probably yes if availability is key
             throw new Exception('Error interno al liberar la habitación.');
        }
        $stmt_update_room->bind_param("i", $room_id);
         if ($stmt_update_room->execute() === false) {
             error_log("PROCESS_CANCELLATION: Error updating room status for room ID " . $room_id . ": " . $stmt_update_room->error);
              throw new Exception('Error al liberar la habitación.');
         }
        $stmt_update_room->close();


        // --- 3. Void Associated Receipt (if any) ---
        // Find the receipt linked to this rental
        $stmt_receipt_id = $conn->prepare("SELECT id FROM receipts WHERE rental_id = ? AND is_voided = FALSE");
        if ($stmt_receipt_id === false) {
             error_log("PROCESS_CANCELLATION: Error preparing receipt check: " . $conn->error);
             // Log error, but don't stop transaction
        } else {
             $stmt_receipt_id->bind_param("i", $rental_id);
             $stmt_receipt_id->execute();
             $result_receipt_id = $stmt_receipt_id->get_result();

             if ($result_receipt_id->num_rows > 0) {
                 $receipt_data = $result_receipt_id->fetch_assoc();
                 $receipt_id_to_void = $receipt_data['id'];
                 $stmt_receipt_id->close(); // Close statement before preparing new one


                 // Void the receipt
                 $stmt_void_receipt = $conn->prepare("UPDATE receipts SET is_voided = TRUE, voided_by_user_id = ?, voided_at = CURRENT_TIMESTAMP WHERE id = ?");
                  if ($stmt_void_receipt === false) {
                     error_log("PROCESS_CANCELLATION: Error al preparar la anulación de comprobante: " . $conn->error);
                     // Log error, but don't necessarily stop transaction if voiding receipt isn't critical
                  } else {
                     $stmt_void_receipt->bind_param("ii", $cancelling_user_id, $receipt_id_to_void);
                      if ($stmt_void_receipt->execute() === false) {
                         error_log("PROCESS_CANCELLATION: Error al anular el comprobante " . $receipt_id_to_void . ": " . $stmt_void_receipt->error);
                          // Log error
                      }
                     $stmt_void_receipt->close(); // Close statement
                  }
             } else {
                  $stmt_receipt_id->close(); // Close statement even if no active receipt found
             }
        }


        // If everything was successful, commit the transaction
        $conn->commit();
        set_message('success', 'Alquiler cancelado con éxito y habitación liberada.');

    } catch (Exception $e) {
        // An error occurred, roll back the transaction
        $conn->rollback();
        // Clean up any prepared statements that might still be open due to the error
        if (isset($stmt_check) && $stmt_check !== false) $stmt_check->close(); // Close check statement if it wasn't already
        if (isset($stmt_cancel_rental) && $stmt_cancel_rental !== false) $stmt_cancel_rental->close();
        if (isset($stmt_update_room) && $stmt_update_room !== false) $stmt_update_room->close();
        if (isset($stmt_receipt_id) && $stmt_receipt_id !== false) $stmt_receipt_id->close();
        if (isset($stmt_void_receipt) && $stmt_void_receipt !== false) $stmt_void_receipt->close();


        set_message('danger', 'Error al cancelar el alquiler: ' . $e->getMessage());
    }

    // Always redirect back to the rentals list
    redirect('view_rentals.php');

} else {
    set_message('warning', 'Método de solicitud no válido.');
    redirect('view_rentals.php');
}

$conn->close(); // Close main connection at the end
?>