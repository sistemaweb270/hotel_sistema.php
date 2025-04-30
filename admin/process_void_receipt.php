<?php
// admin/process_void_receipt.php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $receipt_id = $_POST['receipt_id'] ?? null;
    $admin_user_id = $_SESSION['user_id']; // Get the ID of the admin performing the action

    if ($receipt_id && is_numeric($receipt_id)) {

        // Check if the receipt is already voided
        $check_stmt = $conn->prepare("SELECT is_voided FROM receipts WHERE id = ?");
        $check_stmt->bind_param("i", $receipt_id);
        $check_stmt->execute();
        $check_stmt->bind_result($is_voided);
        $check_stmt->fetch();
        $check_stmt->close();

        if ($is_voided) {
             set_message('warning', 'Este comprobante ya ha sido anulado.');
             redirect('void_receipts.php');
        } else {
            // Anulate the receipt
            $stmt = $conn->prepare("UPDATE receipts SET is_voided = TRUE, voided_by_user_id = ?, voided_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->bind_param("ii", $admin_user_id, $receipt_id);

            if ($stmt->execute()) {
                set_message('success', 'Comprobante anulado con éxito.');
            } else {
                set_message('danger', 'Error al anular el comprobante: ' . $stmt->error);
            }
            $stmt->close();
        }

    } else {
        set_message('danger', 'ID de comprobante no válido.');
    }

    redirect('void_receipts.php');

} else {
    set_message('warning', 'Método de solicitud no válido.');
    redirect('void_receipts.php');
}

$conn->close();
?>