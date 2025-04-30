<?php
// employee/view_hotels.php
include '_employee_header.php'; // Includes config.php, db.php, and require_employee()

$hotels = [];
// Fetch all hotels
$sql_hotels = "SELECT id, name, address FROM hotels ORDER BY name";
$result_hotels = $conn->query($sql_hotels);

if ($result_hotels->num_rows > 0) {
    while($hotel = $result_hotels->fetch_assoc()) {
        $hotels[] = $hotel;
    }
}

// --- Get one representative image for each hotel ---
// This query fetches the first image path found for any room within each hotel
$hotel_images = [];
if (!empty($hotels)) {
     // Join hotels, rooms, and room_images to get one image path per hotel
    $sql_images = "SELECT h.id AS hotel_id, ri.image_path
                   FROM hotels h
                   JOIN rooms r ON h.id = r.hotel_id
                   JOIN room_images ri ON r.id = ri.room_id
                   GROUP BY h.id"; // Group by hotel to get one image per hotel

    $result_images = $conn->query($sql_images); // Use direct query for simplicity here
    if ($result_images) {
        while($row_img = $result_images->fetch_assoc()) {
            $hotel_images[$row_img['hotel_id']] = $row_img['image_path'];
        }
    } else {
        // Log error if needed
        error_log("Error fetching hotel images: " . $conn->error);
    }
}

// Default placeholder image if no image is found for a hotel
$placeholder_image = '../uploads/placeholder_hotel.png'; // Create a default image file here
?>

<h2>Seleccione un Hotel para Alquilar Habitación</h2>

<?php if (empty($hotels)): ?>
    <p class="alert alert-warning">No hay hoteles registrados en el sistema.</p>
<?php else: ?>
    <div class="hotel-catalog">
        <?php foreach ($hotels as $hotel): ?>
            <div class="hotel-card">
                 <!-- Link to view rooms for this hotel -->
                <a href="view_rooms_by_hotel.php?hotel_id=<?php echo htmlspecialchars($hotel['id']); ?>">
                    <!-- Use the image path or placeholder -->
                    <img src="<?php echo htmlspecialchars($hotel_images[$hotel['id']] ?? $placeholder_image); ?>" alt="<?php echo htmlspecialchars($hotel['name']); ?>" class="hotel-image">
                    <div class="hotel-info">
                        <h3><?php echo htmlspecialchars($hotel['name']); ?></h3>
                        <p><?php echo htmlspecialchars($hotel['address']); ?></p>
                         <!-- Add more hotel info if needed -->
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
include '_employee_footer.php'; // Closes DB connection
?>

<!-- Styles moved to css/style.css -->