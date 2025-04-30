<?php
// admin/process_room.php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_admin();

// Define upload directory (relative to this script)
$upload_dir = '../uploads/rooms/';

// Ensure upload directory exists and is writable
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true); // Create directory recursively, 0777 permissions (adjust as needed for security)
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $hotel_id = $_POST['hotel_id'] ?? null;
        $room_number = trim($_POST['room_number'] ?? '');
        $room_type = trim($_POST['room_type'] ?? '');
        $capacity = $_POST['capacity'] ?? null;
        $price_per_night = $_POST['price_per_night'] ?? null;
        $status = $_POST['status'] ?? 'available';

        // File upload details
        $file_upload_ok = false;
        $file_error = '';
        $uploaded_file = $_FILES['room_image'] ?? null;


        if (empty($hotel_id) || empty($room_number) || empty($price_per_night) || !is_numeric($price_per_night)) {
             set_message('danger', 'Hotel, número de habitación y precio son obligatorios, y el precio debe ser un número.');
             redirect('add_room.php');
        }

        // Check if room number already exists for this hotel
        $check_stmt = $conn->prepare("SELECT id FROM rooms WHERE hotel_id = ? AND room_number = ?");
        $check_stmt->bind_param("is", $hotel_id, $room_number);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            set_message('danger', 'Ya existe una habitación con este número en este hotel.');
            $check_stmt->close();
            redirect('add_room.php');
        }
        $check_stmt->close();

        // --- Handle File Upload (Basic) ---
        $image_path = null; // Default to null
        if ($uploaded_file && $uploaded_file['error'] === UPLOAD_ERR_OK) {
            $file_name = basename($uploaded_file['name']);
            $file_tmp_name = $uploaded_file['tmp_name'];
            $file_size = $uploaded_file['size'];
            $file_type = $uploaded_file['type'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            // Allowed file extensions
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

            // Validate file extension
            if (!in_array($file_ext, $allowed_ext)) {
                $file_error = 'Solo se permiten archivos JPG, JPEG, PNG y GIF.';
            }

            // Validate file size (e.g., max 5MB)
            $max_file_size = 5 * 1024 * 1024; // 5MB
            if ($file_size > $max_file_size) {
                $file_error = 'El archivo es demasiado grande (máx. 5MB).';
            }

            // Generate a unique filename to prevent overwriting
            $unique_file_name = uniqid('room_', true) . '.' . $file_ext;
            $target_file_path = $upload_dir . $unique_file_name;

            // Attempt to move the uploaded file
            if ($file_error === '') {
                if (move_uploaded_file($file_tmp_name, $target_file_path)) {
                    $file_upload_ok = true;
                    $image_path = $target_file_path; // Store the relative path
                } else {
                    $file_error = 'Error al subir el archivo.';
                }
            }
        } elseif ($uploaded_file && $uploaded_file['error'] !== UPLOAD_ERR_NO_FILE) {
             // Handle other upload errors
            $file_error = 'Error de subida: Código ' . $uploaded_file['error'];
        }

        // --- Insert Room (Start Transaction) ---
        $conn->begin_transaction();
        $room_inserted = false;
        $room_id = null;

        try {
            $stmt = $conn->prepare("INSERT INTO rooms (hotel_id, room_number, room_type, capacity, price_per_night, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issids", $hotel_id, $room_number, $room_type, $capacity, $price_per_night, $status);

            if ($stmt->execute()) {
                $room_inserted = true;
                $room_id = $stmt->insert_id;
            } else {
                 throw new Exception('Error al añadir la habitación: ' . $stmt->error);
            }
            $stmt->close();

            // --- Insert Image Path if Upload was Successful ---
            if ($file_upload_ok && $room_id) {
                $stmt_img = $conn->prepare("INSERT INTO room_images (room_id, image_path) VALUES (?, ?)");
                $stmt_img->bind_param("is", $room_id, $image_path);
                if (!$stmt_img->execute()) {
                     // Log image insert error, but don't necessarily roll back the room insert
                     error_log("Error inserting room image path for room ID " . $room_id . ": " . $stmt_img->error);
                     // Optionally, try to delete the uploaded file if DB insert failed
                     if (file_exists($target_file_path)) {
                         unlink($target_file_path);
                     }
                }
                 $stmt_img->close();
            }

            // Commit Transaction if room inserted successfully
            $conn->commit();

            $success_message = 'Habitación añadida con éxito.';
            if ($file_error) {
                $success_message .= " Advertencia: " . $file_error; // Report file error even if room added
                set_message('warning', $success_message);
            } else {
                 set_message('success', $success_message);
            }

            redirect('manage_rooms.php');

        } catch (Exception $e) {
             // Rollback transaction on error
             $conn->rollback();
             // If room was inserted but image failed and caused rollback (unlikely with logic above)
             // or if room insert failed after file upload, clean up the uploaded file.
             if ($file_upload_ok && file_exists($target_file_path)) {
                unlink($target_file_path);
             }
             set_message('danger', 'Error en el proceso de añadir habitación: ' . $e->getMessage());
             redirect('add_room.php');
        }

    }
    // Add update/delete actions here if needed later (moved to process_edit_room.php for edits)
    // Delete action could be added here or in process_edit_room.php
    // For simplicity now, delete is not implemented, status change is the soft delete.

} else {
    set_message('warning', 'Método de solicitud no válido.');
    redirect('manage_rooms.php'); // Or a default admin page
}

$conn->close();
?>