<?php
// admin/void_receipts.php
include '_admin_header.php';

$receipts = [];
// Fetch receipts, joining with rentals and users to show relevant info
$sql = "SELECT
            rec.id AS receipt_id,
            rec.receipt_number,
            rec.issue_date,
            rec.amount,
            rec.type,
            rec.is_voided,
            r.customer_name,
            rm.room_number,
            h.name AS hotel_name,
            u_issued.username AS issued_by_employee,
            u_voided.username AS voided_by_admin,
            rec.voided_at
        FROM receipts rec
        JOIN rentals r ON rec.rental_id = r.id
        JOIN rooms rm ON r.room_id = rm.id
        JOIN hotels h ON rm.hotel_id = h.id
        JOIN users u_issued ON r.user_id = u_issued.id -- Employee who issued it
        LEFT JOIN users u_voided ON rec.voided_by_user_id = u_voided.id -- Admin who voided it (can be NULL)
        ORDER BY rec.issue_date DESC";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $receipts[] = $row;
    }
}
?>

<h2>Anular Comprobantes</h2>

<?php if (empty($receipts)): ?>
    <p>No hay comprobantes emitidos.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nº Comprobante</th>
                <th>Fecha Emisión</th>
                <th>Monto</th>
                <th>Tipo</th>
                <th>Cliente</th>
                <th>Habitación</th>
                <th>Hotel</th>
                 <th>Emitido Por</th>
                <th>Estado</th>
                <th>Anulado Por</th>
                <th>Fecha Anulación</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($receipts as $receipt): ?>
                <tr>
                    <td><?php echo htmlspecialchars($receipt['receipt_id']); ?></td>
                    <td><?php echo htmlspecialchars($receipt['receipt_number']); ?></td>
                    <td><?php echo htmlspecialchars($receipt['issue_date']); ?></td>
                    <td><?php echo htmlspecialchars(number_format($receipt['amount'], 2)); ?></td>
                    <td><?php echo htmlspecialchars($receipt['type']); ?></td>
                    <td><?php echo htmlspecialchars($receipt['customer_name']); ?></td>
                     <td><?php echo htmlspecialchars($receipt['room_number']); ?></td>
                    <td><?php echo htmlspecialchars($receipt['hotel_name']); ?></td>
                    <td><?php echo htmlspecialchars($receipt['issued_by_employee']); ?></td>
                    <td>
                        <?php if ($receipt['is_voided']): ?>
                            <span style="color: red;">Anulado</span>
                        <?php else: ?>
                            Válido
                        <?php endif; ?>
                    </td>
                     <td><?php echo htmlspecialchars($receipt['voided_by_admin'] ?? 'N/A'); ?></td>
                     <td><?php echo htmlspecialchars($receipt['voided_at'] ?? 'N/A'); ?></td>
                    <td>
                        <?php if (!$receipt['is_voided']): ?>
                            <form action="process_void_receipt.php" method="post" onsubmit="return confirm('¿Está seguro de que desea anular este comprobante?');">
                                <input type="hidden" name="receipt_id" value="<?php echo htmlspecialchars($receipt['receipt_id']); ?>">
                                <button type="submit" class="btn btn-danger">Anular</button>
                            </form>
                        <?php else: ?>
                            -
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