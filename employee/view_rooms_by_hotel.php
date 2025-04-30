<?php
// employee/view_rooms_by_hotel.php
include '_employee_header.php'; // Includes config.php, db.php, and require_employee()

$hotel_id = $_GET['hotel_id'] ?? null;
$hotel = null;
$rooms = [];
$room_images = []; // To store images grouped by room_id

// --- Validate hotel_id and Fetch Hotel Details ---
if ($hotel_id && is_numeric($hotel_id)) {
    $stmt_hotel = $conn->prepare("SELECT id, name, address FROM hotels WHERE id = ?");
    if ($stmt_hotel === false) {
         error_log("VIEW_ROOMS_BY_HOTEL: Error preparing hotel fetch: " . $conn->error);
          set_message('danger', 'Error interno al buscar hotel.');
         redirect('view_hotels.php'); // Redirect back to hotel list
    }
    $stmt_hotel->bind_param("i", $hotel_id);
    $stmt_hotel->execute();
    $result_hotel = $stmt_hotel->get_result();

    if ($result_hotel->num_rows === 1) {
        $hotel = $result_hotel->fetch_assoc();
    } else {
        set_message('danger', 'Hotel no encontrado.');
        redirect('view_hotels.php'); // Redirect back to hotel list
    }
    $stmt_hotel->close();
} else {
    set_message('danger', 'ID de hotel no válido.');
    redirect('view_hotels.php'); // Redirect back to hotel list
}

// If hotel not found after validation, stop processing
if (!$hotel) {
    // Redirect already handled above
    exit(); // Stop execution after redirecting
}

// --- Fetch Rooms for this Hotel ---
$sql_rooms = "SELECT id, room_number, room_type, capacity, price_per_night, status
              FROM rooms
              WHERE hotel_id = ?
              ORDER BY room_number";
$stmt_rooms = $conn->prepare($sql_rooms);
if ($stmt_rooms === false) {
     error_log("VIEW_ROOMS_BY_HOTEL: Error preparing rooms fetch: " . $conn->error);
      set_message('danger', 'Error interno al cargar habitaciones.');
     // rooms list will be empty
} else {
    $stmt_rooms->bind_param("i", $hotel_id);
    if ($stmt_rooms->execute() === false) {
         error_log("VIEW_ROOMS_BY_HOTEL: Error executing rooms fetch: " . $stmt_rooms->error);
         set_message('danger', 'Error interno al cargar habitaciones.');
         // rooms list will be empty
    } else {
        $result_rooms = $stmt_rooms->get_result();
        while($room = $result_rooms->fetch_assoc()) {
            $rooms[] = $room;
        }
    }
    $stmt_rooms->close();
}


// --- Fetch Images for these Rooms ---
$room_images = []; // Reset or initialize for this scope
if (!empty($rooms)) {
    $room_ids = array_column($rooms, 'id');
    // Handle case where $room_ids might be empty if no rooms were fetched
    if (!empty($room_ids)) {
        $placeholders = implode(',', array_fill(0, count($room_ids), '?'));
        $param_types = str_repeat('i', count($room_ids));

        $sql_images = "SELECT room_id, image_path FROM room_images WHERE room_id IN ($placeholders) ORDER BY room_id, uploaded_at";
        $stmt_images = $conn->prepare($sql_images);
        if ($stmt_images === false) {
             error_log("VIEW_ROOMS_BY_HOTEL: Error preparing images fetch: " . $conn->error);
             // room_images list will be empty
        } else {
            $stmt_images->bind_param($param_types, ...$room_ids);
             if ($stmt_images->execute() === false) {
                 error_log("VIEW_ROOMS_BY_HOTEL: Error executing images fetch: " . $stmt_images->error);
                 // room_images list will be empty
             } else {
                $result_images = $stmt_images->get_result();
                while($row_img = $result_images->fetch_assoc()) {
                    $room_images[$row_img['room_id']][] = $row_img['image_path']; // Store images grouped by room_id
                }
             }
            $stmt_images->close();
        }
    }
}

// --- Function to translate room status (can reuse from admin if in includes, or define here) ---
function translate_room_status_emp($status) { // Renamed to avoid conflict if admin function included
    switch ($status) {
        case 'available':
            return 'Disponible';
        case 'occupied':
            return 'Ocupada';
        case 'maintenance':
            return 'Mantenimiento';
        default:
            return $status; // Return original if unknown
    }
}

// Default placeholder image if no image is found for a room
$placeholder_image_room = '../uploads/placeholder_room.png'; // Create a default image file here
?>

<h2>Habitaciones en <?php echo htmlspecialchars($hotel['name']); ?></h2>

<?php display_message(); // Display flash messages ?>

<p><a href="view_hotels.php" class="btn btn-secondary" style="cursor: pointer;"   >&laquo; Volver a Hoteles</a></p>


<?php if (empty($rooms)): ?>
    <p class="alert alert-warning">No hay habitaciones registradas para este hotel o hubo un error al cargar.</p>
<?php else: ?>
    <div class="room-list">
        <?php foreach ($rooms as $room): ?>
            <div class="room-card">
                <div class="room-images">
                    <?php if (!empty($room_images[$room['id']])): ?>
                        <img src="<?php echo htmlspecialchars($room_images[$room['id']][0]); ?>" alt="Imagen Habitación <?php echo htmlspecialchars($room['room_number']); ?>" class="room-image-thumb">
                         <?php else: ?>
                        <img src="<?php echo htmlspecialchars($placeholder_image_room); ?>" alt="Sin imagen" class="room-image-thumb">
                    <?php endif; ?>
                </div>
                <div class="room-details">
                    <h3>Habitación <?php echo htmlspecialchars($room['room_number']); ?></h3>
                    <p><strong>Tipo:</strong> <?php echo htmlspecialchars($room['room_type']); ?></p>
                    <p><strong>Capacidad:</strong> <?php echo htmlspecialchars($room['capacity']); ?></p>
                    <p><strong>Precio por Noche:</strong> <?php echo htmlspecialchars(number_format($room['price_per_night'], 2)); ?></p>
                    <p><strong>Estado:</strong> <?php echo htmlspecialchars(translate_room_status_emp($room['status'])); ?></p>
                     <?php if (!empty($room_images[$room['id']]) && count($room_images[$room['id']]) > 1): ?>
                         <p><small><a href="#">Ver todas las imágenes (<?php echo count($room_images[$room['id']]); ?>)</a></small></p>
                     <?php endif; ?>

                    <?php if ($room['status'] === 'available'): ?>
                        <a href="rent_room.php?room_id=<?php echo htmlspecialchars($room['id']); ?>" class="btn btn-success"  style="width: 200px;margin-left: 20px;" >Alquilar esta Habitación</a>
                    <?php else: ?>
                        <button class="btn btn-secondary" disabled>No Disponible</button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
include '_employee_footer.php'; // Closes DB connection
?>