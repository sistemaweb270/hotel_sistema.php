<?php
// employee/view_rentals.php (CORREGIDO - Revisión de sintaxis y cierre de bloques)
include '_employee_header.php';

$employee_user_id = $_SESSION['user_id'];

$rentals = [];
// Fetch rental data issued by the current employee, joining with other tables
// Ensure rm.id is selected AS room_id for direct array access later
$sql = "SELECT
            r.id AS rental_id,
            h.name AS hotel_name,
            rm.room_number,
            rm.id AS room_id, -- Select room's ID AS room_id
            rm.hotel_id, -- Select room's hotel_id
            u.username AS employee_name, -- Employee who issued it
            r.customer_name,
            r.check_in_date,
            r.check_out_date,
            r.total_price,
            pm.method_name AS payment_method,
            r.rental_date,
            r.status AS rental_status, -- Rental status
             rec.id AS receipt_id, -- Select receipt ID
            rec.receipt_number,
            rec.type AS receipt_type,
            rec.issue_date AS receipt_date,
            rec.is_voided
        FROM rentals r
        JOIN rooms rm ON r.room_id = rm.id
        JOIN hotels h ON rm.hotel_id = h.id
        JOIN payment_methods pm ON r.payment_method_id = pm.id -- CORRECTED JOIN CONDITION
        JOIN users u ON r.user_id = u.id -- Join with users table
        LEFT JOIN receipts rec ON r.id = rec.rental_id -- LEFT JOIN in case a rental somehow has no receipt
        WHERE r.user_id = ? -- Filter by the current logged-in employee
        ORDER BY r.rental_date DESC"; // Order by most recent rentals first


$stmt = $conn->prepare($sql);
// --- CHECK IF prepare FAILED ---
if ($stmt === false) {
    error_log("VIEW_RENTALS: Error preparing main query: " . $conn->error);
    set_message('danger', 'Error interno al cargar alquileres.');
    // Display error and stop or return empty list
    // For now, display error and the rest of the page will show no rentals
    $rentals = []; // Ensure rentals is empty if prepare fails
} else {
    $stmt->bind_param("i", $employee_user_id);
    // --- CHECK IF execute FAILED ---
    if ($stmt->execute() === false) {
         error_log("VIEW_RENTALS: Error executing main query: " . $stmt->error);
         set_message('danger', 'Error interno al cargar alquileres.');
         $rentals = []; // Ensure rentals is empty if execute fails
    } else {
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $rentals[] = $row;
            }
        }
    }
    $stmt->close();
}


// --- Function to translate rental status ---
function translate_rental_status_emp($status) {
    switch ($status) {
        case 'active':
            return 'Activo';
        case 'completed':
            return 'Completado';
        case 'cancelled':
            return 'Cancelado';
        default:
            return $status; // Return original if unknown
    }
}
?>

<h2>Mis Alquileres Registrados</h2>

<?php display_message(); // Display flash messages ?>


<?php if (empty($rentals)): ?>
    <p>Aún no has registrado ningún alquiler o hubo un error al cargar.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Rental ID</th>
                <th>Hotel</th>
                <th>Habitación</th>
                <th>Cliente</th>
                <th>Check-in</th>
                <th>Check-out</th>
                <th>Precio Total</th>
                <th>Método Pago</th>
                <th>Fecha Alquiler</th>
                <th>Estado Rental</th>
                <th>Nº Comprobante</th>
                <th>Tipo Comprobante</th>
                <th>Fecha Comprobante</th>
                 <th>Anulado</th>
                 <th>Comprobante</th>
                 <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rentals as $rental): ?>
                <tr>
                    <td><?php echo htmlspecialchars($rental['rental_id']); ?></td>
                    <td><?php echo htmlspecialchars($rental['hotel_name']); ?></td>
                    <td><?php echo htmlspecialchars($rental['room_number']); ?></td>
                    <td><?php echo htmlspecialchars($rental['customer_name']); ?></td>
                    <td><?php echo htmlspecialchars($rental['check_in_date']); ?></td>
                    <td><?php echo htmlspecialchars($rental['check_out_date']); ?></td>
                    <td><?php echo htmlspecialchars(number_format($rental['total_price'], 2)); ?></td>
                    <td><?php echo htmlspecialchars($rental['payment_method']); ?></td>
                    <td><?php echo htmlspecialchars($rental['rental_date']); ?></td>
                     <td><?php echo htmlspecialchars(translate_rental_status_emp($rental['rental_status'])); ?></td>
                    <td><?php echo htmlspecialchars($rental['receipt_number'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($rental['receipt_type'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($rental['receipt_date'] ?? 'N/A'); ?></td>
                    <td>
                         <?php if (isset($rental['is_voided']) && $rental['is_voided']): // Check if key exists and is true ?>
                             <span style="color: red;">Sí</span>
                         <?php else: ?>
                             No
                         <?php endif; ?>
                    </td>
                     <td>
                         <?php if (isset($rental['receipt_id']) && $rental['receipt_id']): // Only show link if receipt exists and is not null ?>
                             <a href="view_receipt.php?receipt_id=<?php echo htmlspecialchars($rental['receipt_id']); ?>&hotel_id=<?php echo htmlspecialchars($rental['hotel_id'] ?? ''); ?>" class="btn btn-info btn-sm" target="_blank">Ver/Imprimir</a>
                         <?php else: ?>
                             N/A
                         <?php endif; ?>
                    </td>
                    <td>
                        <?php if (isset($rental['rental_status']) && $rental['rental_status'] === 'active'): // Check if key exists and status is active ?>
                            <form action="process_cancellation.php" method="post" style="display:inline-block;" onsubmit="return confirm('¿Está seguro de que desea CANCELAR este alquiler? Esto también anulará el comprobante asociado.');">
                                <input type="hidden" name="rental_id" value="<?php echo htmlspecialchars($rental['rental_id'] ?? ''); ?>"> <input type="hidden" name="room_id" value="<?php echo htmlspecialchars($rental['room_id'] ?? ''); ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Cancelar Alquiler</button>
                            </form>
                        <?php else: ?>
                            - <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php
include '_employee_footer.php'; // Closes DB connection
?>