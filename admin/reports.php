<?php
// admin/reports.php
include '_admin_header.php';

$rentals = [];
// Fetch detailed rental data for reports
$sql = "SELECT
            r.id AS rental_id,
            h.name AS hotel_name,
            rm.room_number,
            rm.hotel_id, -- Select room's hotel_id
            u.username AS employee_name,
            r.customer_name,
            r.check_in_date,
            r.check_out_date,
            r.total_price,
            pm.method_name AS payment_method,
            r.rental_date,
            r.status AS rental_status,
            rec.id AS receipt_id, -- Select receipt ID
            rec.receipt_number,
            rec.type AS receipt_type,
            rec.issue_date AS receipt_date,
            rec.is_voided
        FROM rentals r
        JOIN rooms rm ON r.room_id = rm.id
        JOIN hotels h ON rm.hotel_id = h.id
        JOIN users u ON r.user_id = u.id
        JOIN payment_methods pm ON r.payment_method_id = pm.id
        LEFT JOIN receipts rec ON r.id = rec.rental_id -- LEFT JOIN in case a rental somehow has no receipt (shouldn't happen with current logic)
        ORDER BY r.rental_date DESC"; // Order by most recent rentals first

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $rentals[] = $row;
    }
}
?>

<h2>Reporte General de Alquileres y Comprobantes</h2>

<?php if (empty($rentals)): ?>
    <p>No hay registros de alquileres.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Rental ID</th>
                <th>Hotel</th>
                <th>Habitación</th>
                <th>Empleado</th>
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
                 <th>Comprobante</th> </tr>
        </thead>
        <tbody>
            <?php foreach ($rentals as $rental): ?>
                <tr>
                    <td><?php echo htmlspecialchars($rental['rental_id']); ?></td>
                    <td><?php echo htmlspecialchars($rental['hotel_name']); ?></td>
                    <td><?php echo htmlspecialchars($rental['room_number']); ?></td>
                     <td><?php echo htmlspecialchars($rental['employee_name']); ?></td>
                    <td><?php echo htmlspecialchars($rental['customer_name']); ?></td>
                    <td><?php echo htmlspecialchars($rental['check_in_date']); ?></td>
                    <td><?php echo htmlspecialchars($rental['check_out_date']); ?></td>
                    <td><?php echo htmlspecialchars(number_format($rental['total_price'], 2)); ?></td>
                    <td><?php echo htmlspecialchars($rental['payment_method']); ?></td>
                    <td><?php echo htmlspecialchars($rental['rental_date']); ?></td>
                     <td><?php echo htmlspecialchars($rental['rental_status']); ?></td>
                    <td><?php echo htmlspecialchars($rental['receipt_number']); ?></td>
                    <td><?php echo htmlspecialchars($rental['receipt_type']); ?></td>
                    <td><?php echo htmlspecialchars($rental['receipt_date']); ?></td>
                    <td>
                         <?php if ($rental['is_voided']): ?>
                             <span style="color: red;">Sí</span>
                         <?php else: ?>
                             No
                         <?php endif; ?>
                    </td>
                     <td>
                         <?php if ($rental['receipt_id']): // Only show link if receipt exists ?>
                             <a href="../employee/view_receipt.php?receipt_id=<?php echo htmlspecialchars($rental['receipt_id']); ?>&hotel_id=<?php echo htmlspecialchars($rental['hotel_id']); ?>" class="btn btn-info btn-sm" target="_blank">Ver/Imprimir</a>
                         <?php else: ?>
                             N/A
                         <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php
include '_admin_footer.php';
?>