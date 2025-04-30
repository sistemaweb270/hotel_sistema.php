<?php
// admin/process_edit_room.php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_admin();

// Define upload directory (relative to this script)
$upload_dir = '../uploads/rooms/'; // Relative to admin/

// Ensure upload directory exists and is writable
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true); // Create directory recursively, 0777 permissions (adjust as needed for security)
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $room_id = $_POST['room_id'] ?? null;
    $image_id = $_POST['image_id'] ?? null; // Used for delete_image action

    // Validate room_id (used by update_details and add_image)
    if (($action === 'update_details' || $action === 'add_image') && (empty($room_id) || !is_numeric($room_id))) {
         set_message('danger', 'ID de habitación no válido para esta acción.');
         redirect('manage_rooms.php');
    }
    // Validate image_id (used by delete_image)
     if ($action === 'delete_image' && (empty($image_id) || !is_numeric($image_id))) {
         set_message('danger', 'ID de imagen no válido para eliminar.');
         // If room_id is present, redirect back to edit page, otherwise to list
         if (!empty($room_id) && is_numeric($room_id)) {
              redirect('edit_room.php?id=' . htmlspecialchars($room_id));
         } else {
              redirect('manage_rooms.php');
         }
    }


    // --- Action: Update Room Details ---
    if ($action === 'update_details') {
        $hotel_id = $_POST['hotel_id'] ?? null;
        $room_number = trim($_POST['room_number'] ?? '');
        $room_type = trim($_POST['room_type'] ?? '');
        $capacity = $_POST['capacity'] ?? null;
        $price_per_night = $_POST['price_per_night'] ?? null;
        $status = $_POST['status'] ?? null;


        // Basic Validation
        if (empty($hotel_id) || empty($room_number) || empty($price_per_night) || !is_numeric($price_per_night) || empty($status)) {
             set_message('danger', 'Hotel, número, precio y estado son obligatorios, y el precio debe ser un número.');
             redirect('edit_room.php?id=' . htmlspecialchars($room_id));
        }
         if (!in_array($status, ['available', 'occupied', 'maintenance'])) {
             set_message('danger', 'Estado de habitación no válido.');
              redirect('edit_room.php?id=' . htmlspecialchars($room_id));
        }

        // Check for duplicate room number within the selected hotel (excluding the current room)
        $check_stmt = $conn->prepare("SELECT id FROM rooms WHERE hotel_id = ? AND room_number = ? AND id != ?");
        $check_stmt->bind_param("isi", $hotel_id, $room_number, $room_id);
        $check_stmt->execute();
        $check_stmt->store_result();
        if ($check_stmt->num_rows > 0) {
            set_message('danger', 'Ya existe otra habitación con este número en este hotel.');
            $check_stmt->close();
             redirect('edit_room.php?id=' . htmlspecialchars($room_id));
        }
        $check_stmt->close();


        // Update room details
        $stmt = $conn->prepare("UPDATE rooms SET hotel_id = ?, room_number = ?, room_type = ?, capacity = ?, price_per_night = ?, status = ? WHERE id = ?");
        $stmt->bind_param("issidsi", $hotel_id, $room_number, $room_type, $capacity, $price_per_night, $status, $room_id);

        if ($stmt->execute()) {
            set_message('success', 'Detalles de la habitación actualizados con éxito.');
        } else {
            set_message('danger', 'Error al actualizar los detalles de la habitación: ' . $stmt->error);
        }
        $stmt->close();
        redirect('edit_room.php?id=' . htmlspecialchars($room_id)); // Redirect back to the edit page


    }

    // --- Action: Add New Image ---
    elseif ($action === 'add_image') {
         $uploaded_file = $_FILES['new_room_image'] ?? null;

        if (!$uploaded_file || $uploaded_file['error'] !== UPLOAD_ERR_OK) {
             $error_message = 'Error al subir el archivo: ';
             switch ($uploaded_file['error']) {
                 case UPLOAD_ERR_INI_SIZE:
                 case UPLOAD_ERR_FORM_SIZE:
                     $error_message .= 'Archivo demasiado grande.';
                     break;
                 case UPLOAD_ERR_PARTIAL:
                     $error_message .= 'Archivo subido parcialmente.';
                     break;
                 case UPLOAD_ERR_NO_FILE:
                     $error_message .= 'No se seleccionó ningún archivo.';
                     break;
                 case UPLOAD_ERR_NO_TMP_DIR:
                     $error_message .= 'Falta carpeta temporal.';
                     break;
                 case UPLOAD_ERR_CANT_WRITE:
                     $error_message .= 'Error al escribir en disco.';
                     break;
                 case UPLOAD_ERR_EXTENSION:
                     $error_message .= 'Una extensión de PHP detuvo la subida.';
                     break;
                 default:
                     $error_message .= 'Error desconocido.';
                     break;
             }
             set_message('danger', $error_message);
             redirect('edit_room.php?id=' . htmlspecialchars($room_id));
        } else {
            $file_name = basename($uploaded_file['name']);
            $file_tmp_name = $uploaded_file['tmp_name'];
            $file_size = $uploaded_file['size'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            // Allowed file extensions
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($file_ext, $allowed_ext)) {
                set_message('danger', 'Solo se permiten archivos JPG, JPEG, PNG y GIF.');
                 redirect('edit_room.php?id=' . htmlspecialchars($room_id));
            }

            // Validate file size (e.g., max 5MB)
            $max_file_size = 5 * 1024 * 1024; // 5MB
            if ($file_size > $max_file_size) {
                set_message('danger', 'El archivo es demasiado grande (máx. 5MB).');
                 redirect('edit_room.php?id=' . htmlspecialchars($room_id));
            }

            // Generate a unique filename
            $unique_file_name = uniqid('room_', true) . '.' . $file_ext;
            $target_file_path = $upload_dir . $unique_file_name;

            // Attempt to move the uploaded file
            if (move_uploaded_file($file_tmp_name, $target_file_path)) {
                // Insert image path into database
                $stmt_img = $conn->prepare("INSERT INTO room_images (room_id, image_path) VALUES (?, ?)");
                $stmt_img->bind_param("is", $room_id, $target_file_path);
                if ($stmt_img->execute()) {
                    set_message('success', 'Imagen subida y guardada con éxito.');
                } else {
                    // If DB insert fails, delete the uploaded file
                    if (file_exists($target_file_path)) {
                        unlink($target_file_path);
                    }
                    set_message('danger', 'Error al guardar la ruta de la imagen en la base de datos: ' . $stmt_img->error);
                }
                 $stmt_img->close();
            } else {
                set_message('danger', 'Error al mover el archivo subido.');
            }
             redirect('edit_room.php?id=' . htmlspecialchars($room_id)); // Redirect back to the edit page
        }
    }

     // --- Action: Delete Image ---
    elseif ($action === 'delete_image') {
        // Get the image path before deleting the database record
        $stmt_get_path = $conn->prepare("SELECT image_path FROM room_images WHERE id = ?");
        $stmt_get_path->bind_param("i", $image_id);
        $stmt_get_path->execute();
        $stmt_get_path->bind_result($image_path_to_delete);
        $stmt_get_path->fetch();
        $stmt_get_path->close();

        // Delete image record from database
        $stmt_del_db = $conn->prepare("DELETE FROM room_images WHERE id = ?");
        $stmt_del_db->bind_param("i", $image_id);

        if ($stmt_del_db->execute()) {
            // If database record deleted, try to delete the file
            if ($image_path_to_delete && file_exists($image_path_to_delete)) {
                if (unlink($image_path_to_delete)) {
                    set_message('success', 'Imagen eliminada con éxito.');
                } else {
                     // Log file deletion error, but DB record is gone
                     error_log("Error deleting image file: " . $image_path_to_delete);
                     set_message('warning', 'Imagen eliminada de la base de datos, pero hubo un error al eliminar el archivo físico.');
                }
            } else {
                 set_message('warning', 'Imagen eliminada de la base de datos, pero el archivo físico no se encontró.');
            }
        } else {
            set_message('danger', 'Error al eliminar la imagen de la base de datos: ' . $stmt_del_db->error);
        }
        $stmt_del_db->close();

        // Redirect back to the edit page
         if (!empty($room_id) && is_numeric($room_id)) {
              redirect('edit_room.php?id=' . htmlspecialchars($room_id));
         } else {
              redirect('manage_rooms.php'); // Fallback redirect
         }
    }


    // Handle unknown action
    else {
        set_message('danger', 'Acción no válida.');
         if (!empty($room_id) && is_numeric($room_id)) {
              redirect('edit_room.php?id=' . htmlspecialchars($room_id));
         } else {
              redirect('manage_rooms.php');
         }
    }

} else {
    set_message('warning', 'Método de solicitud no válido.');
    redirect('manage_rooms.php');
}

$conn->close();
?>