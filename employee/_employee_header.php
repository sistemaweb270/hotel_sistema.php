<?php
// employee/_employee_header.php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_employee(); // Ensure user is logged in and is employee or admin
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Principal</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="dashboard-layout">
        <?php include '_employee_sidebar.php'; ?>
        <div class="main-content">
            <h1>Panel de Empleado</h1>
            <?php display_message(); // Display flash messages ?>