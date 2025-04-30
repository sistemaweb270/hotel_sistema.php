<?php
// employee/process_payment_method.php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_employee();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $method_name = trim($_POST['method_name'] ?? '');

        if (empty($method_name)) {
             set_message('danger', 'El nombre del método de pago es obligatorio.');
             redirect('add_payment_method.php');
        }

        // Check if method already exists
        $check_stmt = $conn->prepare("SELECT id FROM payment_methods WHERE method_name = ?");
        $check_stmt->bind_param("s", $method_name);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            set_message('danger', 'Este método de pago ya existe.');
            $check_stmt->close();
            redirect('add_payment_method.php');
        }
        $check_stmt->close();

        // Prevent SQL injection
        $stmt = $conn->prepare("INSERT INTO payment_methods (method_name) VALUES (?)");
        $stmt->bind_param("s", $method_name);

        if ($stmt->execute()) {
            set_message('success', 'Método de pago añadido con éxito.');
            redirect('add_payment_method.php'); // Stay on the same page or redirect to a list
        } else {
            set_message('danger', 'Error al añadir el método de pago: ' . $stmt->error);
             redirect('add_payment_method.php');
        }

        $stmt->close();
    }
     // Add update/delete actions here if needed later
} else {
    set_message('warning', 'Método de solicitud no válido.');
    redirect('employee_dashboard.php'); // Or a default employee page
}

$conn->close();
?>