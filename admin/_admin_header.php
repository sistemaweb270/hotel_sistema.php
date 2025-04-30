<?php
// admin/_admin_header.php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_admin(); // Ensure user is logged in and is admin
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CP_Administrador</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="dashboard-layout">
        <?php include '_admin_sidebar.php'; ?>
        <div class="main-content">
            <h1>Panel de Administrador</h1>
             <?php display_message(); // Display flash messages ?>