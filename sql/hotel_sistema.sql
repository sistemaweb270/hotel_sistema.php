-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3308
-- Tiempo de generación: 21-04-2025 a las 03:53:28
-- Versión del servidor: 9.1.0
-- Versión de PHP: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `hotel_sistema`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `hotels`
--

DROP TABLE IF EXISTS `hotels`;
CREATE TABLE IF NOT EXISTS `hotels` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `hotels`
--

INSERT INTO `hotels` (`id`, `name`, `address`, `phone`, `email`, `created_at`) VALUES
(2, 'HOTEL EL FARAON', 'AV PEREZ DE CUELLAR MZ G LT 15', '955555232', 'mail@mail.com', '2025-04-21 02:36:37'),
(3, 'FARAHON 4', 'AV EJERCITO 128', '955555232', 'mail@mail.com', '2025-04-21 03:41:47'),
(4, 'FARAHON 6', 'Huamanga, Ayacucho', '955555232', 'mail@mail.com', '2025-04-21 03:49:40');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `payment_methods`
--

DROP TABLE IF EXISTS `payment_methods`;
CREATE TABLE IF NOT EXISTS `payment_methods` (
  `id` int NOT NULL AUTO_INCREMENT,
  `method_name` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `method_name` (`method_name`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `payment_methods`
--

INSERT INTO `payment_methods` (`id`, `method_name`, `created_at`) VALUES
(8, 'Tranferencia', '2025-04-21 02:16:45'),
(7, 'Yape / Plim', '2025-04-21 02:16:37'),
(6, 'Efectivo', '2025-04-21 02:16:27');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `receipts`
--

DROP TABLE IF EXISTS `receipts`;
CREATE TABLE IF NOT EXISTS `receipts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `rental_id` int DEFAULT NULL,
  `receipt_number` varchar(50) NOT NULL,
  `issue_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `amount` decimal(10,2) NOT NULL,
  `type` enum('factura','boleta') NOT NULL,
  `is_voided` tinyint(1) DEFAULT '0',
  `voided_by_user_id` int DEFAULT NULL,
  `voided_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `receipt_number` (`receipt_number`),
  KEY `rental_id` (`rental_id`),
  KEY `voided_by_user_id` (`voided_by_user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `receipts`
--

INSERT INTO `receipts` (`id`, `rental_id`, `receipt_number`, `issue_date`, `amount`, `type`, `is_voided`, `voided_by_user_id`, `voided_at`) VALUES
(1, 8, '202504210225108', '2025-04-21 02:25:10', 150.00, 'factura', 0, NULL, NULL),
(2, 9, '202504210226549', '2025-04-21 02:26:54', 300.00, 'boleta', 0, NULL, NULL),
(3, 10, '2025042102402010', '2025-04-21 02:40:20', 40.00, 'boleta', 1, 3, '2025-04-21 03:23:58'),
(4, 11, '2025042102445811', '2025-04-21 02:44:58', 40.00, 'factura', 1, 1, '2025-04-21 02:45:16'),
(5, 12, '2025042022435812', '2025-04-21 03:43:58', 50.00, 'factura', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rentals`
--

DROP TABLE IF EXISTS `rentals`;
CREATE TABLE IF NOT EXISTS `rentals` (
  `id` int NOT NULL AUTO_INCREMENT,
  `room_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_id_doc` varchar(50) DEFAULT NULL,
  `check_in_date` date NOT NULL,
  `check_out_date` date NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `payment_method_id` int DEFAULT NULL,
  `rental_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('active','completed','cancelled') DEFAULT 'active',
  PRIMARY KEY (`id`),
  KEY `room_id` (`room_id`),
  KEY `user_id` (`user_id`),
  KEY `payment_method_id` (`payment_method_id`)
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `rentals`
--

INSERT INTO `rentals` (`id`, `room_id`, `user_id`, `customer_name`, `customer_id_doc`, `check_in_date`, `check_out_date`, `total_price`, `payment_method_id`, `rental_date`, `status`) VALUES
(12, 7, 3, 'RAUL', '70444337', '2025-04-20', '2025-04-21', 50.00, 6, '2025-04-21 03:43:58', 'active'),
(11, 4, 3, 'PEDRITO', '70444332', '2025-04-20', '2025-04-21', 40.00, 6, '2025-04-21 02:44:58', 'cancelled'),
(10, 3, 3, 'CRISMER', '73456794', '2025-04-20', '2025-04-21', 40.00, 6, '2025-04-21 02:40:20', 'cancelled');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rooms`
--

DROP TABLE IF EXISTS `rooms`;
CREATE TABLE IF NOT EXISTS `rooms` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int DEFAULT NULL,
  `room_number` varchar(20) NOT NULL,
  `room_type` varchar(50) DEFAULT NULL,
  `capacity` int DEFAULT NULL,
  `price_per_night` decimal(10,2) NOT NULL,
  `status` enum('available','occupied','maintenance') DEFAULT 'available',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_room` (`hotel_id`,`room_number`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `rooms`
--

INSERT INTO `rooms` (`id`, `hotel_id`, `room_number`, `room_type`, `capacity`, `price_per_night`, `status`, `created_at`) VALUES
(3, 2, '100', 'INDIVIDUAL', 2, 40.00, 'available', '2025-04-21 02:37:17'),
(4, 2, '101', 'INDIVIDUAL', 2, 40.00, 'available', '2025-04-21 02:38:39'),
(5, 2, '102', 'DUPLEX', 2, 40.00, 'available', '2025-04-21 02:38:56'),
(6, 2, '103', 'Matrimonial', 2, 40.00, 'available', '2025-04-21 02:39:32'),
(7, 3, '10', 'DUPLEX', 2, 50.00, 'occupied', '2025-04-21 03:42:05'),
(8, 4, '305', 'DUPLEX', 2, 120.00, 'available', '2025-04-21 03:49:58');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `room_images`
--

DROP TABLE IF EXISTS `room_images`;
CREATE TABLE IF NOT EXISTS `room_images` (
  `id` int NOT NULL AUTO_INCREMENT,
  `room_id` int DEFAULT NULL,
  `image_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `room_id` (`room_id`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `room_images`
--

INSERT INTO `room_images` (`id`, `room_id`, `image_path`, `uploaded_at`) VALUES
(1, 1, '../uploads/rooms/room_6805a96deff8d3.66769278.jpg', '2025-04-21 02:11:57'),
(2, 2, '../uploads/rooms/room_6805aa05c1e493.66821993.jpg', '2025-04-21 02:14:29'),
(3, 3, '../uploads/rooms/room_6805af5d9f4b50.43547758.jpg', '2025-04-21 02:37:17'),
(4, 4, '../uploads/rooms/room_6805afaf8f8148.31901225.jpg', '2025-04-21 02:38:39'),
(5, 5, '../uploads/rooms/room_6805afc07849b7.67001883.jpg', '2025-04-21 02:38:56'),
(6, 6, '../uploads/rooms/room_6805afe49cfa14.68020730.jpg', '2025-04-21 02:39:32'),
(7, 7, '../uploads/rooms/room_6805be8dbb81e0.75883902.jpg', '2025-04-21 03:42:05'),
(8, 8, '../uploads/rooms/room_6805c066d36157.93501805.jpg', '2025-04-21 03:49:58');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','employee') NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`, `status`) VALUES
(1, 'admin', '$2y$10$dYlCYvYfTp7bGZC6NSe4vuC7dA.UKfiOWb84ArrwwnOpNAVY6sgzC', 'admin', '2025-04-21 01:10:44', 'active'),
(3, 'empleado', '$2y$10$XxDrA1BzSBKe96giYea/De17YP5Ri9glPNpxz1U8JKBdVzEKr92QS', 'employee', '2025-04-21 01:37:44', 'active');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
