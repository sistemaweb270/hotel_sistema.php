<?php
// employee/view_receipt.php
// This page displays a single receipt for printing/viewing.
// It does NOT include the standard header/footer/sidebar.

require_once '../includes/config.php';
require_once '../includes/db.php';

// Ensure user is logged in (either admin or employee can view)
if (!is_logged_in()) {
     set_message('danger', 'Acceso denegado. Inicie sesión.');
     redirect('../index.php');
}

$receipt_id = $_GET['receipt_id'] ?? null;
$hotel_id_from_url = $_GET['hotel_id'] ?? null; // Get hotel_id from URL if available
$receipt = null;
$hotel_id_for_back_link = null; // Variable to store hotel_id for the "Volver" link


if ($receipt_id && is_numeric($receipt_id)) {
    // Fetch detailed receipt data
    $sql = "SELECT
                rec.id AS receipt_id,
                rec.receipt_number,
                rec.issue_date,
                rec.amount AS receipt_amount, -- Amount from receipt itself
                rec.type AS receipt_type,
                rec.is_voided,
                rec.voided_at,
                r.id AS rental_id,
                r.customer_name,
                r.customer_id_doc,
                r.check_in_date,
                r.check_out_date,
                r.total_price AS rental_total_price, -- Total from rental record
                rm.room_number,
                rm.room_type,
                rm.price_per_night,
                rm.hotel_id, -- Select room's hotel_id
                h.name AS hotel_name,
                h.address AS hotel_address,
                u_issued.username AS issued_by_employee,
                u_voided.username AS voided_by_admin,
                pm.method_name AS payment_method
            FROM receipts rec
            JOIN rentals r ON rec.rental_id = r.id
            JOIN rooms rm ON r.room_id = rm.id
            JOIN hotels h ON rm.hotel_id = h.id
            JOIN users u_issued ON r.user_id = u_issued.id -- Employee who issued it
            JOIN payment_methods pm ON r.payment_method_id = pm.id
            LEFT JOIN users u_voided ON rec.voided_by_user_id = u_voided.id -- Admin who voided it (can be NULL)
            WHERE rec.id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $receipt_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $receipt = $result->fetch_assoc();

        // Get hotel_id from the fetched receipt data for the back link
        $hotel_id_for_back_link = $receipt['hotel_id'];


        // Calculate number of nights for display
         $check_in_timestamp = strtotime($receipt['check_in_date']);
         $check_out_timestamp = strtotime($receipt['check_out_date']);
         $num_nights = floor(abs($check_out_timestamp - $check_in_timestamp) / (60 * 60 * 24));


    } else {
        set_message('danger', 'Comprobante no encontrado.');
        // Redirect based on role? Or just to dashboard? Let's go to employee dashboard
        redirect('employee_dashboard.php');
    }
    $stmt->close();

} else {
    set_message('danger', 'ID de comprobante no especificado.');
    redirect('employee_dashboard.php'); // Redirect to employee dashboard
}

// If receipt not found after validation/fetch
if (!$receipt) {
    exit(); // Stop execution after redirect
}

// Close DB connection early as the rest is just display
$conn->close();

// Determine receipt type title
$receipt_title = ($receipt['receipt_type'] === 'factura') ? 'FACTURA' : 'BOLETA';


// --- Function to format date in Spanish (DD de NombreDelMes de YYYY) ---
function format_date_spanish($date_string) {
    if (empty($date_string) || $date_string == '0000-00-00 00:00:00') { // Handle empty or zero dates
        return 'N/A';
    }
    $timestamp = strtotime($date_string);
    if ($timestamp === false) {
        return $date_string; // Return original if invalid
    }

    $day = date('d', $timestamp);
    $month_num = date('n', $timestamp); // Month without leading zeros
    $year = date('Y', $timestamp);
    $hour = date('H', $timestamp);
    $minute = date('i', $timestamp);


    $spanish_months = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
    ];

     // Check if time part is present (for issue_date and voided_at)
     $has_time = (date('H:i:s', $timestamp) !== '00:00:00');


    $formatted_date = $day . ' de ' . $spanish_months[$month_num] . ' de ' . $year;

     if ($has_time) {
         $formatted_date .= ' ' . $hour . ':' . $minute;
     }

    return $formatted_date;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($receipt_title); ?> Nº <?php echo htmlspecialchars($receipt['receipt_number']); ?></title>
     <!-- Basic Print Styles -->
    <style>
        body {
            font-family: sans-serif;
            line-height: 1.6;
            margin: 0; /* Adjusted margin */
            padding: 20px; /* Added padding */
            color: #333;
            background-color: #f4f4f4; /* Added background for visual centering */
            display: flex; /* Use flexbox for centering */
            justify-content: center; /* Center horizontally */
            align-items: flex-start; /* Align to top vertically */
            min-height: 100vh; /* Minimum height to center vertically */
             box-sizing: border-box; /* Include padding in body size */
        }
        .receipt-container {
            width: 100%; /* Use percentage for responsiveness */
            max-width: 400px; /* Max width for typical receipt size */
            margin: 20px auto; /* Keep auto margins for centering older browsers */
            border: 1px solid #ccc;
            padding: 15mm; /* Increased padding */
             box-shadow: 0 0 10px rgba(0,0,0,0.1); /* Stronger shadow */
             background-color: #fff; /* White background for the receipt */
             box-sizing: border-box; /* Include padding/border in width */
        }
        .header, .footer {
            text-align: center;
            margin-bottom: 15mm; /* Increased margin */
        }
        .details, .items, .totals {
            margin-bottom: 15mm; /* Increased margin */
        }
        .details p, .totals p {
            margin: 3px 0; /* Adjusted margin */
        }
        .items table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5mm; /* Added margin */
        }
        .items th, .items td {
            border: 1px solid #eee;
            padding: 8px; /* Increased padding */
            text-align: left;
        }
        .items th {
            background-color: #f9f9f9;
        }
         .items td:last-child, /* Align last column (Subtotal) right */
         .items th:last-child {
             text-align: right;
         }
        .totals p {
            font-weight: bold;
            text-align: right; /* Align totals to the right */
        }
         .voided-stamp {
             color: red;
             border: 2px dashed red;
             padding: 5px;
             transform: rotate(-15deg);
             position: absolute; /* Position relative to nearest positioned ancestor or body */
             top: 100px; /* Adjust position */
             left: 50%;
             margin-left: -50px; /* Center horizontally */
             width: 100px;
             text-align: center;
             opacity: 0.7;
             font-size: 1.5em;
             font-weight: bold;
             pointer-events: none; /* Ignore mouse events on the stamp */
         }

        /* Button Styling - Reusing .btn classes */
        .button-container {
            text-align: center;
            margin-top: 20px;
        }
        .button-container .btn {
            display: inline-block; /* Ensure buttons are inline */
            margin: 0 10px; /* Space between buttons */
             /* Inherit styles from style.css */
             padding: 8px 15px;
             border-radius: 4px;
             text-decoration: none;
             color: white;
             cursor: pointer;
             border: none; /* Remove default button border */
        }
         .button-container .btn-info { /* Use btn-info for print button */
             background-color: #17a2b8;
         }
          .button-container .btn-info:hover {
             background-color: #138496;
          }
         .button-container .btn-secondary { /* Use btn-secondary for back button */
             background-color: #6c757d;
         }
          .button-container .btn-secondary:hover {
             background-color: #5a6268;
          }


        @media print {
            body {
                margin: 0;
                 padding: 0;
                 background-color: none; /* No background on print */
                 display: block; /* No flexbox on print */
            }
             .receipt-container {
                 border: none;
                 box-shadow: none;
                 width: 100%; /* Use full width on print */
                 margin: 0 auto; /* Keep auto margin */
                 padding: 10mm; /* Adjust padding for print */
             }
             .voided-stamp {
                position: fixed; /* Use fixed for print */
                top: 40%;
                left: 30%;
                transform: rotate(-15deg);
                opacity: 0.7;
             }
             .button-container { /* Hide button container on print */
                 display: none !important;
             }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="header">
            <h2><?php echo htmlspecialchars($receipt_title); ?></h2>
            <p>Nº: <?php echo htmlspecialchars($receipt['receipt_number']); ?></p>
             <!-- Apply date formatting -->
            <p>Fecha Emisión: <?php echo htmlspecialchars(format_date_spanish($receipt['issue_date'])); ?></p>
             <?php if ($receipt['is_voided']): ?>
                 <div class="voided-stamp">ANULADO</div>
             <?php endif; ?>
        </div>

        <div class="details">
            <p>Hotel: <?php echo htmlspecialchars($receipt['hotel_name']); ?></p>
            <p>Dirección: <?php echo htmlspecialchars($receipt['hotel_address']); ?></p>
            <p>Cliente: <?php echo htmlspecialchars($receipt['customer_name']); ?></p>
            <p>Documento: <?php echo htmlspecialchars($receipt['customer_id_doc'] ?? 'N/A'); ?></p>
            <p>Emitido por: <?php echo htmlspecialchars($receipt['issued_by_employee']); ?></p>
        </div>

        <div class="items">
            <h4>Detalles del Alquiler:</h4>
            <table>
                <thead>
                    <tr>
                        <th>Habitación</th>
                        <th>Tipo</th>
                        <th>Precio/Noche</th>
                        <th>Noches</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo htmlspecialchars($receipt['room_number']); ?></td>
                        <td><?php echo htmlspecialchars($receipt['room_type']); ?></td>
                        <td><?php echo htmlspecialchars(number_format($receipt['price_per_night'], 2)); ?></td>
                         <td><?php echo htmlspecialchars($num_nights); ?></td>
                         <td><?php echo htmlspecialchars(number_format($receipt['price_per_night'] * $num_nights, 2)); ?></td>
                    </tr>
                </tbody>
            </table>
             <!-- Apply date formatting -->
             <p style="margin-top: 10px;">Fecha de Ingreso: <?php echo htmlspecialchars(format_date_spanish($receipt['check_in_date'])); ?></p>
             <p>Fecha de Salida: <?php echo htmlspecialchars(format_date_spanish($receipt['check_out_date'])); ?></p>
             <p>Método de Pago: <?php echo htmlspecialchars($receipt['payment_method']); ?></p>
        </div>

        <div class="totals">
            <p>Total Comprobante: <?php echo htmlspecialchars(number_format($receipt['receipt_amount'], 2)); ?></p>
             <?php if ($receipt['is_voided']): ?>
                 <!-- Apply date formatting -->
                 <p style="color: red;">Anulado en: <?php echo htmlspecialchars(format_date_spanish($receipt['voided_at'])); ?></p>
                  <p style="color: red;">Anulado por: <?php echo htmlspecialchars($receipt['voided_by_admin'] ?? 'Admin Desconocido'); ?></p>
             <?php endif; ?>
        </div>

        <div class="footer">
            <!-- Add footer text here if needed -->
            <p>¡Gracias por su preferencia!</p>
        </div>
    </div>

     <!-- Print and Back Buttons -->
    <div class="button-container">
         <!-- Imprimir Button -->
        <button onclick="window.print()" class="btn btn-info">Imprimir Comprobante</button>
         <!-- Volver Button -->
        
    </div>

</body>
</html>
<?php
// No need to close connection here as it was closed above
// $conn->close();
?>