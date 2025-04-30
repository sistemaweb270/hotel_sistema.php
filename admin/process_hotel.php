<?php
// admin/process_hotel.php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if (empty($name)) {
            set_message('danger', 'El nombre del hotel es obligatorio.');
            redirect('add_hotel.php');
        }

        // Prevent SQL injection
        $stmt = $conn->prepare("INSERT INTO hotels (name, address, phone, email) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $address, $phone, $email);

        if ($stmt->execute()) {
            set_message('success', 'Hotel añadido con éxito.');
            redirect('manage_hotels.php');
        } else {
            set_message('danger', 'Error al añadir el hotel: ' . $stmt->error);
            redirect('add_hotel.php');
        }

        $stmt->close();

    }
    // Add update/delete actions here if needed later
} else {
    set_message('warning', 'Método de solicitud no válido.');
    redirect('manage_hotels.php'); // Or a default admin page
}

$conn->close();
?>