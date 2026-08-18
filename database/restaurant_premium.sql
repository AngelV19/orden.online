-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 18, 2026 at 07:42 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `restaurant_premium`
--

-- --------------------------------------------------------

--
-- Table structure for table `categorias`
--

CREATE TABLE `categorias` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `icono` varchar(50) DEFAULT 'bi-grid',
  `orden` tinyint(3) UNSIGNED DEFAULT 0,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categorias`
--

INSERT INTO `categorias` (`id`, `nombre`, `descripcion`, `icono`, `orden`, `activo`, `created_at`) VALUES
(1, 'Entradas', 'Deliciosos aperitivos para comenzar la velada', 'bi-egg-fried', 1, 1, '2026-06-15 18:59:23'),
(2, 'Platos Fuertes', 'Nuestras especialidades de la casa con ingredientes premium', 'bi-award', 2, 1, '2026-06-15 18:59:23'),
(3, 'Bebidas', 'Cócteles artesanales, vinos y bebidas especiales', 'bi-cup-straw', 3, 1, '2026-06-15 18:59:23'),
(4, 'Postres', 'Dulces creaciones para culminar la experiencia', 'bi-balloon-heart', 4, 1, '2026-06-15 18:59:23');

-- --------------------------------------------------------

--
-- Table structure for table `configuracion`
--

CREATE TABLE `configuracion` (
  `clave` varchar(100) NOT NULL,
  `valor` text DEFAULT NULL,
  `tipo` enum('texto','color','imagen','select','boolean','textarea') DEFAULT 'texto',
  `grupo` varchar(50) DEFAULT 'general',
  `etiqueta` varchar(150) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `configuracion`
--

INSERT INTO `configuracion` (`clave`, `valor`, `tipo`, `grupo`, `etiqueta`, `updated_at`) VALUES
('color_cards', '#0f0f1f', 'color', 'colores', 'Color de fondo de tarjetas', '2026-07-03 23:40:54'),
('color_navbar', '#0a0a1a', 'color', 'colores', 'Color del navbar al hacer scroll', '2026-07-03 23:40:54'),
('color_primario', '#dbdbdb', 'color', 'colores', 'Color dorado / acento principal', '2026-07-03 23:44:35'),
('color_secundario', '#0a0a1a', 'color', 'colores', 'Color de fondo principal', '2026-07-03 23:40:54'),
('color_texto', '#74a4e2', 'color', 'colores', 'Color de texto principal', '2026-07-03 23:44:35'),
('contacto_direccion', 'Av. Presidente Masaryk 123, Polanco, CDMX', 'textarea', 'contacto', 'Dirección', '2026-06-18 20:23:01'),
('contacto_email', 'reservaciones@restaurantpremium.com', 'texto', 'contacto', 'Email de contacto', '2026-06-18 20:23:01'),
('contacto_horarios', 'Mar–Vie: 13:00–23:00\nSáb: 12:00–24:00\nDom: 12:00–22:00', 'textarea', 'contacto', 'Horarios', '2026-06-18 20:23:01'),
('contacto_telefono', '+52 55 1234 5678', 'texto', 'contacto', 'Teléfono', '2026-06-18 20:23:01'),
('fuente_body', 'Inter', 'select', 'tipografia', 'Fuente de texto', '2026-06-23 22:51:29'),
('fuente_display', 'Josefin Sans', 'select', 'tipografia', 'Fuente de títulos', '2026-06-23 22:51:29'),
('impresora_activa', '1', 'boolean', 'impresora', 'Impresión automática activada', '2026-06-24 19:22:53'),
('impresora_copias', '1', 'texto', 'impresora', 'Número de copias por pedido', '2026-06-24 19:22:53'),
('impresora_ip', '10.10.100.163', 'texto', 'impresora', 'IP de la impresora en la red', '2026-08-04 18:20:37'),
('impresora_lineas', '3', 'texto', 'impresora', 'Líneas en blanco al final (para corte)', '2026-06-24 19:22:53'),
('impresora_logo', '0', 'boolean', 'impresora', 'Imprimir nombre del restaurante', '2026-06-24 23:05:37'),
('impresora_puerto', '9100', 'texto', 'impresora', 'Puerto (Epson TM = 9100)', '2026-06-24 19:22:53'),
('impuesto_activo', '1', 'boolean', 'impuestos', 'Cobrar impuesto', '2026-07-03 22:50:52'),
('impuesto_incluido', '0', 'boolean', 'impuestos', 'Impuesto incluido en precios (no suma extra)', '2026-06-22 18:32:31'),
('impuesto_nombre', 'TAX', 'texto', 'impuestos', 'Nombre del impuesto', '2026-06-22 18:27:55'),
('impuesto_porcentaje', '8', 'texto', 'impuestos', 'Porcentaje (%)', '2026-06-23 00:16:58'),
('maps_embed_url', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3762.661936286583!2d-99.16869492394!3d19.427024981884!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x85d1ff35f5bd1563%3A0x6c366f0e2de02ff7!2sEl+%C3%81ngel+de+la+Independencia!5e0!3m2!1ses-419!2smx!4v1700000000000!5m2!1ses-419!2smx', 'textarea', 'maps', 'Google Maps Embed URL', '2026-06-18 20:23:01'),
('moneda_codigo', 'USD', 'select', 'moneda', 'Moneda', '2026-06-30 20:34:18'),
('moneda_decimales', '2', 'select', 'moneda', 'Decimales a mostrar', '2026-06-30 20:31:28'),
('moneda_posicion', 'antes', 'select', 'moneda', 'Posición del símbolo', '2026-06-30 20:31:28'),
('moneda_separador_decimal', '.', 'select', 'moneda', 'Separador decimal', '2026-06-30 20:31:28'),
('moneda_separador_miles', ',', 'select', 'moneda', 'Separador de miles', '2026-06-30 20:31:28'),
('moneda_simbolo', '$', 'texto', 'moneda', 'Símbolo', '2026-06-30 20:31:28'),
('nosotros_imagen', 'config/6a139359e8630875.jpg', 'imagen', 'nosotros', 'Imagen sección Nosotros', '2026-06-18 22:21:39'),
('nosotros_texto1', 'Fundado en 2012 en el corazón de Polanco, nació de la visión del Chef Ejecutivo de demostrar que la gastronomía mexicana puede dialogar con las grandes cocinas del mundo.', 'textarea', 'nosotros', 'Párrafo 1', '2026-06-18 20:23:01'),
('nosotros_texto2', 'Trabajamos directamente con productores locales, seleccionamos vinos de pequeñas bodegas y actualizamos nuestro menú cada temporada.', 'textarea', 'nosotros', 'Párrafo 2', '2026-06-18 20:23:01'),
('nosotros_titulo', 'Pasión por la <em class=\"rp-display--italic text-gold\">excelencia</em>', 'textarea', 'nosotros', 'Título sección Nosotros', '2026-06-18 20:23:01'),
('pago_banco', 'BECU', 'texto', 'pagos', 'Nombre del banco', '2026-06-24 16:53:03'),
('pago_clabe', '325081403', 'texto', 'pagos', 'CLABE interbancaria', '2026-06-24 16:53:03'),
('pago_concepto', 'Pago de pedido', 'texto', 'pagos', 'Concepto de transferencia', '2026-06-24 16:48:25'),
('pago_cuenta', '', 'texto', 'pagos', 'Número de cuenta (opcional)', '2026-06-24 16:48:25'),
('pago_instrucciones', 'Envía tu comprobante por WhatsApp para confirmar tu pedido.', 'textarea', 'pagos', 'Instrucciones adicionales', '2026-06-24 16:48:25'),
('pago_titular', 'Angel Villalobos', 'texto', 'pagos', 'Titular de la cuenta', '2026-06-24 16:53:03'),
('printnode_activo', '0', 'boolean', 'impresora', 'Usar PrintNode (impresión en la nube)', '2026-06-24 20:19:37'),
('printnode_apikey', '', 'texto', 'impresora', 'API Key de PrintNode', '2026-06-24 20:00:34'),
('printnode_printer_id', '', 'texto', 'impresora', 'ID de la impresora en PrintNode', '2026-06-24 20:00:34'),
('seo_keywords', 'restaurante premium, alta cocina, reservaciones', 'texto', 'seo', 'Keywords SEO', '2026-06-18 20:23:01'),
('seo_og_image', '', 'imagen', 'seo', 'Imagen Open Graph (redes sociales)', '2026-06-18 20:23:01'),
('site_descripcion', 'Experiencia gastronómica de lujo en el corazón de la ciudad.', 'textarea', 'general', 'Descripción (SEO)', '2026-06-18 20:23:01'),
('site_favicon', '', 'imagen', 'general', 'Favicon', '2026-06-18 20:23:01'),
('site_hero_imagen', 'config/292730d01e09f48e.jpg', 'imagen', 'general', 'Imagen del Hero principal', '2026-07-03 23:40:26'),
('site_hero_subtitulo', 'Una experiencia gastronómica que trasciende los sentidos.', 'textarea', 'general', 'Subtítulo del Hero', '2026-06-18 20:23:01'),
('site_hero_titulo', 'Donde cada plato\r\ncuenta una <em>historia</em>', 'textarea', 'general', 'Título del Hero (HTML permitido)', '2026-06-18 22:26:52'),
('site_logo', 'config/e574782dbf1f6453.png', 'imagen', 'general', 'Logo del restaurante', '2026-07-03 23:35:25'),
('site_nombre', 'Restaurant Premium', 'texto', 'general', 'Nombre del restaurante', '2026-06-18 20:23:01'),
('site_slogan', 'Donde cada plato cuenta una historia', 'texto', 'general', 'Slogan', '2026-06-18 20:23:01'),
('social_facebook', 'https://www.facebook.com/LaTortuga.Everett', 'texto', 'redes', 'Facebook URL', '2026-06-18 20:58:19'),
('social_instagram', '#', 'texto', 'redes', 'Instagram URL', '2026-06-18 20:23:01'),
('social_tripadvisor', '#', 'texto', 'redes', 'TripAdvisor URL', '2026-06-18 20:23:01'),
('social_youtube', '#', 'texto', 'redes', 'YouTube URL', '2026-06-18 20:23:01'),
('tema_border_radius', '8', 'select', 'tema', 'Redondez de bordes', '2026-07-03 23:42:13'),
('tema_preset', 'azul-plata', 'select', 'tema', 'Tema de color predefinido', '2026-07-03 23:40:54'),
('whatsapp_mensaje', 'Hola, me gustaría hacer una reservación.', 'texto', 'whatsapp', 'Mensaje predefinido', '2026-06-18 20:23:01'),
('whatsapp_numero', '3608739332', 'texto', 'whatsapp', 'Número WhatsApp (sin + ni espacios)', '2026-06-23 21:00:11');

-- --------------------------------------------------------

--
-- Table structure for table `galeria`
--

CREATE TABLE `galeria` (
  `id` int(10) UNSIGNED NOT NULL,
  `titulo` varchar(150) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `imagen` varchar(255) NOT NULL,
  `orden` tinyint(3) UNSIGNED DEFAULT 0,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `galeria`
--

INSERT INTO `galeria` (`id`, `titulo`, `descripcion`, `imagen`, `orden`, `activo`, `created_at`) VALUES
(1, 'Salón Principal', 'Nuestro elegante salón con capacidad para 60 comensales', 'galeria/e9b1dc176246c628.jpg', 1, 1, '2026-06-15 18:59:23'),
(2, 'Cocina Abierta', 'Nuestros chefs en acción en la cocina show', 'galeria/48c86869cc5b5099.jpg', 2, 1, '2026-06-15 18:59:23');

-- --------------------------------------------------------

--
-- Table structure for table `opciones`
--

CREATE TABLE `opciones` (
  `id` int(10) UNSIGNED NOT NULL,
  `grupo_id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `precio` decimal(8,2) NOT NULL DEFAULT 0.00,
  `disponible` tinyint(1) DEFAULT 1,
  `orden` tinyint(3) UNSIGNED DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `opciones`
--

INSERT INTO `opciones` (`id`, `grupo_id`, `nombre`, `descripcion`, `precio`, `disponible`, `orden`, `created_at`) VALUES
(1, 1, 'Camarón a la plancha', NULL, 45.00, 1, 1, '2026-06-19 20:19:47'),
(2, 1, 'Pollo a las finas hierbas', NULL, 35.00, 1, 2, '2026-06-19 20:19:47'),
(3, 1, 'Langostinos al ajillo', NULL, 65.00, 1, 3, '2026-06-19 20:19:47'),
(4, 1, 'Costilla de res', NULL, 55.00, 1, 4, '2026-06-19 20:19:47'),
(5, 2, 'Salsa trufada', NULL, 25.00, 1, 1, '2026-06-19 20:19:47'),
(6, 2, 'Chimichurri artesanal', NULL, 15.00, 1, 2, '2026-06-19 20:19:47'),
(7, 2, 'Reducción de vino tinto', NULL, 20.00, 1, 3, '2026-06-19 20:19:47'),
(8, 2, 'Mantequilla de hierbas', NULL, 15.00, 1, 4, '2026-06-19 20:19:47'),
(9, 2, 'Salsa de ostión', NULL, 20.00, 1, 5, '2026-06-19 20:19:47'),
(10, 3, 'Papas fritas con trufa', NULL, 35.00, 1, 1, '2026-06-19 20:19:47'),
(11, 3, 'Risotto cremoso', NULL, 45.00, 1, 2, '2026-06-19 20:19:47'),
(12, 3, 'Vegetales salteados', NULL, 25.00, 1, 3, '2026-06-19 20:19:47'),
(13, 3, 'Puré de papa trufado', NULL, 30.00, 1, 4, '2026-06-19 20:19:47'),
(14, 3, 'Ensalada mixta', NULL, 25.00, 1, 5, '2026-06-19 20:19:47'),
(15, 3, 'Arroz pilaf', NULL, 20.00, 1, 6, '2026-06-19 20:19:47'),
(16, 4, 'Crudo (Blue)', NULL, 0.00, 1, 1, '2026-06-19 20:19:47'),
(17, 4, 'Medio crudo (Rare)', NULL, 0.00, 1, 2, '2026-06-19 20:19:47'),
(18, 4, 'Tres cuartos (Medium)', NULL, 0.00, 1, 3, '2026-06-19 20:19:47'),
(19, 4, 'Bien cocido (Well done)', NULL, 0.00, 1, 4, '2026-06-19 20:19:47'),
(20, 5, 'Sin cebolla', NULL, 0.00, 1, 1, '2026-06-19 20:19:47'),
(21, 5, 'Sin ajo', NULL, 0.00, 1, 2, '2026-06-19 20:19:47'),
(22, 5, 'Sin gluten', NULL, 0.00, 1, 3, '2026-06-19 20:19:47'),
(23, 5, 'Sin lácteos', NULL, 0.00, 1, 4, '2026-06-19 20:19:47'),
(24, 5, 'Vegetariano', NULL, 0.00, 1, 5, '2026-06-19 20:19:47'),
(25, 5, 'Sin mariscos', NULL, 0.00, 1, 6, '2026-06-19 20:19:47'),
(26, 6, 'Sin picante', NULL, 0.00, 1, 1, '2026-06-19 20:19:47'),
(27, 6, 'Poco picante', NULL, 0.00, 1, 2, '2026-06-19 20:19:47'),
(28, 6, 'Medio', NULL, 0.00, 1, 3, '2026-06-19 20:19:47'),
(29, 6, 'Picante', NULL, 0.00, 1, 4, '2026-06-19 20:19:47'),
(30, 6, 'Extra picante', NULL, 0.00, 1, 5, '2026-06-19 20:19:47'),
(31, 7, 'Aguacate extra', NULL, 20.00, 1, 1, '2026-06-19 20:19:47'),
(32, 7, 'Queso extra', NULL, 18.00, 1, 2, '2026-06-19 20:19:47'),
(33, 7, 'Tocino crujiente', NULL, 22.00, 1, 3, '2026-06-19 20:19:47'),
(34, 7, 'Huevo estrellado', NULL, 15.00, 1, 4, '2026-06-19 20:19:47'),
(35, 7, 'Champiñones salteados', NULL, 20.00, 1, 5, '2026-06-19 20:19:47'),
(36, 7, 'Jalapeños', NULL, 10.00, 1, 6, '2026-06-19 20:19:47'),
(37, 8, 'asada', 'agregar extra asada', 2.00, 1, 1, '2026-06-19 20:26:36'),
(38, 8, 'pastor', 'agregar extra pastor', 2.00, 1, 0, '2026-06-19 20:27:50');

-- --------------------------------------------------------

--
-- Table structure for table `opcion_grupos`
--

CREATE TABLE `opcion_grupos` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `tipo` enum('extra','complemento','modificador') NOT NULL DEFAULT 'extra',
  `descripcion` varchar(255) DEFAULT NULL,
  `requerido` tinyint(1) DEFAULT 0,
  `multiple` tinyint(1) DEFAULT 1,
  `min_sel` tinyint(3) UNSIGNED DEFAULT 0,
  `max_sel` tinyint(3) UNSIGNED DEFAULT 0,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `opcion_grupos`
--

INSERT INTO `opcion_grupos` (`id`, `nombre`, `tipo`, `descripcion`, `requerido`, `multiple`, `min_sel`, `max_sel`, `activo`, `created_at`) VALUES
(1, 'Extras de Proteína', 'extra', 'Agrega más proteína a tu plato', 0, 1, 0, 3, 1, '2026-06-19 20:19:47'),
(2, 'Salsas y Aderezos', 'extra', 'Salsas adicionales', 0, 1, 0, 2, 1, '2026-06-19 20:19:47'),
(3, 'Guarniciones', 'complemento', 'Elige tu acompañamiento', 0, 1, 0, 2, 1, '2026-06-19 20:19:47'),
(4, 'Término de la Carne', 'modificador', 'Indica el término de cocción', 1, 0, 1, 1, 1, '2026-06-19 20:19:47'),
(5, 'Restricciones', 'modificador', 'Alergias o preferencias sin costo adicional', 0, 1, 0, 5, 1, '2026-06-19 20:19:47'),
(6, 'Nivel de Picante', 'modificador', 'Nivel de picante deseado', 0, 0, 0, 1, 1, '2026-06-19 20:19:47'),
(7, 'Ingredientes extras', 'extra', 'Ingredientes adicionales para tu platillo', 0, 1, 0, 4, 1, '2026-06-19 20:19:47'),
(8, 'Extra carne', 'extra', 'Agregar extra carne a los tacos', 0, 1, 0, 0, 1, '2026-06-19 20:24:48');

-- --------------------------------------------------------

--
-- Table structure for table `pedidos`
--

CREATE TABLE `pedidos` (
  `id` int(10) UNSIGNED NOT NULL,
  `codigo` varchar(12) NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `telefono` varchar(20) NOT NULL,
  `email` varchar(180) NOT NULL,
  `tipo` enum('salon','llevar','domicilio') DEFAULT 'salon',
  `direccion` text DEFAULT NULL,
  `mesa` varchar(20) DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `impuesto` decimal(10,2) NOT NULL DEFAULT 0.00,
  `impuesto_pct` decimal(5,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `notas` text DEFAULT NULL,
  `metodo_pago` varchar(30) DEFAULT 'efectivo',
  `estado` enum('nuevo','preparando','listo','entregado','cancelado') DEFAULT 'nuevo',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pedidos`
--

INSERT INTO `pedidos` (`id`, `codigo`, `nombre`, `telefono`, `email`, `tipo`, `direccion`, `mesa`, `subtotal`, `impuesto`, `impuesto_pct`, `total`, `notas`, `metodo_pago`, `estado`, `created_at`, `updated_at`) VALUES
(1, 'CBD45BD9', 'Administrador', '13608739332', 'angeldavidadvg0@gmail.com', 'llevar', NULL, NULL, 1735.00, 0.00, 0.00, 1735.00, 'REMPLAZALAS POR ALGO SIMILAR', 'efectivo', 'entregado', '2026-06-18 18:53:59', '2026-06-19 19:13:50'),
(2, '414BD82E', 'Jesus', '13608739332', 'jesus0@gmail.com', 'salon', NULL, '', 845.00, 0.00, 0.00, 845.00, '', 'efectivo', 'entregado', '2026-06-19 19:13:11', '2026-06-19 19:17:26'),
(3, '9E8A3A25', 'Goku777', '32423424', 'goku@gmail.com', 'domicilio', '', NULL, 525.00, 0.00, 0.00, 525.00, 'please ring the doorbell', 'efectivo', 'entregado', '2026-06-19 19:22:13', '2026-06-19 19:23:00'),
(4, 'BAEFF33C', 'David', '13608739332', 'angeldavidadvg0@gmail.com', 'llevar', NULL, NULL, 10.00, 0.00, 0.00, 10.00, '', 'efectivo', 'entregado', '2026-06-19 21:38:19', '2026-06-19 21:38:44'),
(5, 'C1994760', 'Jesus', '13608739332', 'angeldavidadvg0@gmail.com', 'domicilio', '', NULL, 24.00, 0.00, 0.00, 24.00, '', 'efectivo', 'entregado', '2026-06-19 23:50:23', '2026-06-19 23:51:04'),
(6, '2DFB693D', 'ANGEL', '13608739332', 'angeldavidadvg0@gmail.com', 'salon', NULL, '', 1074.00, 0.00, 0.00, 1074.00, '', 'efectivo', 'entregado', '2026-06-20 00:05:01', '2026-06-20 00:08:23'),
(7, 'B52C1F86', 'CHAVA', '13608739332', 'angeldavidadvg0@gmail.com', 'salon', NULL, '', 24.00, 0.00, 0.00, 24.00, '', 'efectivo', 'entregado', '2026-06-20 00:35:56', '2026-06-20 00:43:32'),
(8, '1BEBF925', 'Jesus', '13608739332', 'angeldavidadvg0@gmail.com', 'salon', NULL, '', 24.00, 0.00, 0.00, 24.00, '', 'efectivo', 'entregado', '2026-06-20 00:44:20', '2026-06-20 00:45:48'),
(9, 'DE008C90', 'Jesus', '13608739332', 'angeldavidadvg0@gmail.com', 'salon', NULL, '', 14.00, 0.00, 0.00, 14.00, '', 'efectivo', 'entregado', '2026-06-20 00:50:01', '2026-06-20 00:51:41'),
(10, '77A150B8', 'Jesus', '13608739332', 'angeldavidadvg0@gmail.com', 'domicilio', '805 112th Street Southeast\r\nH203', NULL, 537.00, 0.00, 0.00, 537.00, 'extra picante please', 'efectivo', 'entregado', '2026-06-22 17:03:43', '2026-06-22 17:04:28'),
(12, 'F7CC4307', 'Jesus', '13608739332', 'angeldavidadvg0@gmail.com', 'llevar', NULL, NULL, 1867.00, 298.72, 16.00, 2165.72, '', 'efectivo', 'entregado', '2026-06-22 18:33:52', '2026-06-22 18:34:55'),
(13, 'E6ACDD51', 'Jesus', '13608739332', 'angeldavidadvg0@gmail.com', 'salon', NULL, '', 34.00, 5.44, 16.00, 39.44, '', 'efectivo', 'entregado', '2026-06-22 18:45:50', '2026-06-22 18:48:20'),
(14, '0EE0A016', 'Jesus', '13608739332', 'angeldavidadvg0@gmail.com', 'domicilio', '805 112th Street Southeast\r\nH203', NULL, 12.00, 1.20, 10.00, 13.20, 'DEJARLO EN LA PUERTA', 'efectivo', 'entregado', '2026-06-22 20:51:00', '2026-06-22 21:18:47'),
(15, 'E6E31C03', 'Jesus', '13608739330', 'angeldavidadvg0@gmail.com', 'salon', NULL, '', 537.00, 42.96, 8.00, 579.96, '', 'efectivo', 'entregado', '2026-06-23 20:56:31', '2026-06-23 20:57:48'),
(16, 'A936391D', 'Jesus', '13608739332', 'angeldavidadvg0@gmail.com', 'salon', NULL, '', 525.00, 42.00, 8.00, 567.00, '', 'paypal', 'entregado', '2026-06-24 16:37:12', '2026-06-24 16:37:41'),
(17, 'BD5C6827', 'Administrador', '13608739332', 'angeldavidadvg0@gmail.com', 'salon', NULL, '', 525.00, 0.00, 0.00, 525.00, '', 'efectivo', 'entregado', '2026-06-24 23:53:34', '2026-06-25 00:00:44'),
(18, 'D2C2BEB0', 'angek', '13608739332', 'angeldavidadvg0@gmail.com', 'salon', NULL, '', 297.00, 0.00, 0.00, 297.00, '', 'efectivo', 'entregado', '2026-06-30 00:28:17', '2026-06-30 16:40:26');

-- --------------------------------------------------------

--
-- Table structure for table `pedido_items`
--

CREATE TABLE `pedido_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `pedido_id` int(10) UNSIGNED NOT NULL,
  `platillo_id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `precio` decimal(8,2) NOT NULL,
  `cantidad` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `subtotal` decimal(10,2) NOT NULL,
  `notas` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pedido_items`
--

INSERT INTO `pedido_items` (`id`, `pedido_id`, `platillo_id`, `nombre`, `precio`, `cantidad`, `subtotal`, `notas`) VALUES
(1, 1, 1, 'Tartar de Atún', 285.00, 1, 285.00, NULL),
(2, 1, 2, 'Burrata Caprese', 240.00, 1, 240.00, NULL),
(3, 1, 3, 'Croquetas de Langosta', 320.00, 1, 320.00, NULL),
(4, 1, 6, 'Langosta Thermidor', 890.00, 1, 890.00, NULL),
(5, 2, 1, 'Tartar de Atún', 285.00, 1, 285.00, NULL),
(6, 2, 2, 'Burrata Caprese', 240.00, 1, 240.00, NULL),
(7, 2, 3, 'Croquetas de Langosta', 320.00, 1, 320.00, NULL),
(8, 3, 1, 'Tartar de Atún', 285.00, 1, 285.00, NULL),
(9, 3, 2, 'Burrata Caprese', 240.00, 1, 240.00, NULL),
(10, 4, 17, 'Taco', 10.00, 1, 10.00, NULL),
(11, 5, 17, 'Taco', 14.00, 1, 14.00, ''),
(12, 6, 2, 'Burrata Caprese', 240.00, 1, 240.00, ''),
(13, 6, 17, 'Taco', 14.00, 1, 14.00, ''),
(14, 6, 1, 'Tartar de Atún', 285.00, 1, 285.00, ''),
(15, 7, 17, 'Taco', 14.00, 1, 14.00, ''),
(16, 8, 17, 'Taco', 14.00, 1, 14.00, ''),
(17, 9, 17, 'Taco', 14.00, 1, 14.00, ''),
(18, 10, 1, 'Tartar de Atún', 285.00, 1, 285.00, ''),
(19, 10, 2, 'Burrata Caprese', 240.00, 1, 240.00, ''),
(20, 10, 17, 'Taco', 12.00, 1, 12.00, ''),
(26, 12, 1, 'Tartar de Atún', 285.00, 1, 285.00, ''),
(27, 12, 2, 'Burrata Caprese', 240.00, 1, 240.00, ''),
(28, 12, 5, 'Filete Wellington', 680.00, 1, 680.00, ''),
(29, 12, 11, 'Champagne Dom Pérignon', 650.00, 1, 650.00, ''),
(30, 12, 17, 'Taco', 12.00, 1, 12.00, ''),
(31, 13, 17, 'Taco', 12.00, 1, 12.00, ''),
(32, 13, 17, 'Taco', 12.00, 1, 12.00, ''),
(33, 13, 17, 'Taco', 10.00, 1, 10.00, ''),
(34, 14, 17, 'Taco', 12.00, 1, 12.00, ''),
(35, 15, 2, 'Burrata Caprese', 240.00, 1, 240.00, ''),
(36, 15, 1, 'Tartar de Atún', 285.00, 1, 285.00, ''),
(37, 15, 17, 'Taco', 12.00, 1, 12.00, ''),
(38, 16, 1, 'Tartar de Atún', 285.00, 1, 285.00, ''),
(39, 16, 2, 'Burrata Caprese', 240.00, 1, 240.00, ''),
(40, 17, 1, 'Tartar de Atún', 285.00, 1, 285.00, ''),
(41, 17, 2, 'Burrata Caprese', 240.00, 1, 240.00, ''),
(42, 18, 1, 'Tartar de Atún', 285.00, 1, 285.00, ''),
(43, 18, 17, 'Taco', 12.00, 1, 12.00, '');

-- --------------------------------------------------------

--
-- Table structure for table `pedido_item_opciones`
--

CREATE TABLE `pedido_item_opciones` (
  `id` int(10) UNSIGNED NOT NULL,
  `item_id` int(10) UNSIGNED NOT NULL,
  `opcion_id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `precio` decimal(8,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pedido_item_opciones`
--

INSERT INTO `pedido_item_opciones` (`id`, `item_id`, `opcion_id`, `nombre`, `precio`) VALUES
(1, 11, 30, 'Extra picante', 0.00),
(2, 11, 38, 'pastor', 2.00),
(3, 11, 37, 'asada', 2.00),
(4, 13, 29, 'Picante', 0.00),
(5, 13, 37, 'asada', 2.00),
(6, 13, 38, 'pastor', 2.00),
(7, 14, 30, 'Extra picante', 0.00),
(8, 15, 26, 'Sin picante', 0.00),
(9, 15, 37, 'asada', 2.00),
(10, 15, 38, 'pastor', 2.00),
(11, 16, 37, 'asada', 2.00),
(12, 16, 38, 'pastor', 2.00),
(13, 17, 37, 'asada', 2.00),
(14, 17, 38, 'pastor', 2.00),
(15, 18, 27, 'Poco picante', 0.00),
(16, 20, 30, 'Extra picante', 0.00),
(17, 20, 38, 'pastor', 2.00),
(22, 26, 30, 'Extra picante', 0.00),
(23, 28, 17, 'Medio crudo (Rare)', 0.00),
(24, 30, 30, 'Extra picante', 0.00),
(25, 30, 38, 'pastor', 2.00),
(26, 31, 30, 'Extra picante', 0.00),
(27, 31, 38, 'pastor', 2.00),
(28, 32, 37, 'asada', 2.00),
(29, 34, 30, 'Extra picante', 0.00),
(30, 34, 37, 'asada', 2.00),
(31, 36, 29, 'Picante', 0.00),
(32, 37, 37, 'asada', 2.00),
(33, 38, 29, 'Picante', 0.00),
(34, 40, 30, 'Extra picante', 0.00),
(35, 42, 29, 'Picante', 0.00),
(36, 43, 30, 'Extra picante', 0.00),
(37, 43, 37, 'asada', 2.00);

-- --------------------------------------------------------

--
-- Table structure for table `platillos`
--

CREATE TABLE `platillos` (
  `id` int(10) UNSIGNED NOT NULL,
  `categoria_id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio` decimal(8,2) NOT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `disponible` tinyint(1) DEFAULT 1,
  `destacado` tinyint(1) DEFAULT 0,
  `orden` tinyint(3) UNSIGNED DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `platillos`
--

INSERT INTO `platillos` (`id`, `categoria_id`, `nombre`, `descripcion`, `precio`, `imagen`, `disponible`, `destacado`, `orden`, `created_at`, `updated_at`) VALUES
(1, 1, 'Tartar de Atún', 'Atún fresco marinado con aguacate, jengibre, soya y ajonjolí tostado. Servido sobre tostadas de wonton.', 285.00, 'fa4b7dc1b2cb2025.png', 1, 1, 0, '2026-06-15 18:59:23', '2026-06-19 17:22:48'),
(2, 1, 'Burrata Caprese', 'Queso burrata importado con jitomates heirloom, albahaca fresca, reducción de balsámico y aceite de oliva extra virgen.', 240.00, '5e9f604c9bfdc444.png', 1, 0, 0, '2026-06-15 18:59:23', '2026-06-19 17:45:47'),
(3, 1, 'Croquetas de Langosta', 'Suaves croquetas rellenas de langosta del Caribe con mayonesa de chipotle y limón Meyer.', 320.00, 'placeholder', 1, 0, 0, '2026-06-15 18:59:23', '2026-06-15 18:59:23'),
(4, 1, 'Carpaccio de Res', 'Finas láminas de filete de res angus, alcaparras, parmesano rallado, rúcula y vinagreta de mostaza.', 265.00, 'placeholder', 1, 0, 0, '2026-06-15 18:59:23', '2026-06-15 18:59:23'),
(5, 2, 'Filete Wellington', 'Filete de res angus premium envuelto en duxelles de hongos y hojaldre dorado. Acompañado de puré trufado y vegetales de temporada.', 680.00, 'placeholder', 1, 1, 0, '2026-06-15 18:59:23', '2026-06-15 18:59:23'),
(6, 2, 'Langosta Thermidor', 'Media langosta del Atlántico gratinada con salsa thermidor de mantequilla, cognac y hierbas finas. Servida con papas Anna.', 890.00, 'placeholder', 1, 1, 0, '2026-06-15 18:59:23', '2026-06-15 18:59:23'),
(7, 2, 'Pato a la Naranja', 'Magret de pato confitado con glaseado de naranja y Grand Marnier, lentejas beluga y reducción de vino tinto.', 520.00, 'placeholder', 1, 0, 0, '2026-06-15 18:59:23', '2026-06-15 18:59:23'),
(8, 2, 'Risotto de Trufas', 'Risotto Arborio cremoso con trufa negra rallada, parmesano Reggiano 24 meses y mantequilla de chardonnay.', 450.00, 'placeholder', 1, 0, 0, '2026-06-15 18:59:23', '2026-06-15 18:59:23'),
(9, 2, 'Salmón en Costra', 'Filete de salmón Atlántico en costra de hierbas y pistache, salsa beurre blanc de azafrán y espárragos verdes.', 480.00, 'placeholder', 1, 0, 0, '2026-06-15 18:59:23', '2026-06-15 18:59:23'),
(10, 3, 'Negroni Clásico', 'Gin London Dry, Campari y vermut rojo Carpano. Servido en copa helada con twist de naranja.', 185.00, 'placeholder', 1, 0, 0, '2026-06-15 18:59:23', '2026-06-15 18:59:23'),
(11, 3, 'Champagne Dom Pérignon', 'Copa de champagne Dom Pérignon Vintage, burbujas finas y elegantes con notas de brioche y cítricos.', 650.00, 'placeholder', 1, 1, 0, '2026-06-15 18:59:23', '2026-06-15 18:59:23'),
(12, 3, 'Mezcal Sour de Mango', 'Mezcal artesanal, mango Ataulfo, lima, clara de huevo y sal de chapulín. Firma de la casa.', 210.00, 'placeholder', 1, 0, 0, '2026-06-15 18:59:23', '2026-06-15 18:59:23'),
(13, 3, 'Agua Mineral Premium', 'Acqua Panna o San Pellegrino 750ml.', 95.00, 'placeholder', 1, 0, 0, '2026-06-15 18:59:23', '2026-06-15 18:59:23'),
(14, 4, 'Soufflé de Chocolate', 'Soufflé de chocolate Valrhona 70% recién horneado con helado de vainilla Bourbon y crumble de avellana. (15 min de preparación)', 210.00, '8f7e512575137540.jpg', 1, 1, 0, '2026-06-15 18:59:23', '2026-06-24 00:21:05'),
(15, 4, 'Crème Brûlée', 'Clásica crème brûlée de vainilla Tahití con costra de azúcar caramelizada y frutas de temporada.', 165.00, 'placeholder', 1, 0, 0, '2026-06-15 18:59:23', '2026-06-15 18:59:23'),
(16, 4, 'Tarta Tatin de Pera', 'Tarta invertida de pera Williams con crema Chantilly, helado de canela y reducción de calvados.', 185.00, 'placeholder', 1, 0, 0, '2026-06-15 18:59:23', '2026-06-15 18:59:23'),
(17, 1, 'Taco', 'tacos de cualquier tipo de carnes', 10.00, 'a7d8b8353e0118b1.jpg', 1, 0, 0, '2026-06-19 20:32:27', '2026-06-19 20:32:27');

-- --------------------------------------------------------

--
-- Table structure for table `platillo_opciones`
--

CREATE TABLE `platillo_opciones` (
  `platillo_id` int(10) UNSIGNED NOT NULL,
  `grupo_id` int(10) UNSIGNED NOT NULL,
  `orden` tinyint(3) UNSIGNED DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `platillo_opciones`
--

INSERT INTO `platillo_opciones` (`platillo_id`, `grupo_id`, `orden`) VALUES
(1, 6, 8),
(4, 3, 5),
(4, 4, 6),
(5, 1, 2),
(5, 2, 4),
(5, 3, 3),
(5, 4, 1),
(5, 5, 5),
(8, 5, 2),
(8, 6, 3),
(8, 7, 1),
(9, 2, 1),
(9, 3, 2),
(9, 5, 3),
(17, 6, 8),
(17, 8, 4);

-- --------------------------------------------------------

--
-- Table structure for table `reservas`
--

CREATE TABLE `reservas` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `telefono` varchar(20) NOT NULL,
  `email` varchar(180) NOT NULL,
  `fecha` date NOT NULL,
  `hora` time NOT NULL,
  `personas` tinyint(3) UNSIGNED NOT NULL DEFAULT 2,
  `mensaje` text DEFAULT NULL,
  `estado` enum('pendiente','confirmada','cancelada') DEFAULT 'pendiente',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sugerencias`
--

CREATE TABLE `sugerencias` (
  `id` int(10) UNSIGNED NOT NULL,
  `platillo_id` int(10) UNSIGNED NOT NULL,
  `orden` tinyint(3) UNSIGNED DEFAULT 0,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sugerencias`
--

INSERT INTO `sugerencias` (`id`, `platillo_id`, `orden`, `activo`, `created_at`) VALUES
(1, 17, 0, 1, '2026-06-25 16:44:31'),
(2, 2, 1, 1, '2026-06-30 20:39:12'),
(3, 15, 2, 1, '2026-06-30 20:39:31'),
(4, 14, 3, 1, '2026-06-30 20:39:36');

-- --------------------------------------------------------

--
-- Table structure for table `testimonios`
--

CREATE TABLE `testimonios` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `cargo` varchar(100) DEFAULT NULL,
  `comentario` text NOT NULL,
  `estrellas` tinyint(3) UNSIGNED DEFAULT 5,
  `avatar` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `testimonios`
--

INSERT INTO `testimonios` (`id`, `nombre`, `cargo`, `comentario`, `estrellas`, `avatar`, `activo`, `created_at`) VALUES
(1, 'Alejandro Morales', 'Empresario', 'Una experiencia gastronómica incomparable. El Filete Wellington es, sin duda, el mejor que he probado en mi vida. El servicio es impecable y la atmósfera, única.', 5, NULL, 1, '2026-06-15 18:59:23'),
(2, 'Valentina Cruz', 'Blogger Gastronómica', 'Vine por primera vez en mi aniversario y quedé completamente enamorada. Desde el amuse-bouche hasta el postre, cada bocado cuenta una historia de dedicación y pasión.', 5, NULL, 1, '2026-06-15 18:59:23'),
(3, 'Roberto Hernández', 'Chef Ejecutivo', 'Como profesional de la cocina, puedo apreciar la calidad de los ingredientes y la técnica del equipo. La langosta thermidor es una obra de arte. Volveré sin dudarlo.', 5, NULL, 1, '2026-06-15 18:59:23'),
(4, 'Mariana Soto', 'Directora de Arte', 'El ambiente es tan hermoso como la comida. Cada detalle, desde la iluminación hasta la vajilla, ha sido pensado con cuidado. Una experiencia que recomiendo ampliamente.', 5, NULL, 1, '2026-06-15 18:59:23'),
(5, 'Carlos Mendez', 'Ejecutivo Financiero', 'El lugar ideal para cenas de negocios. Privacidad, excelente cocina y un servicio que anticipa tus necesidades. Mi restaurante favorito de la ciudad.', 5, NULL, 1, '2026-06-15 18:59:23');

-- --------------------------------------------------------

--
-- Table structure for table `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `email` varchar(180) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('admin','editor') DEFAULT 'editor',
  `activo` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `email`, `password`, `rol`, `activo`, `last_login`, `created_at`) VALUES
(1, 'Administrador', 'admin@restaurantpremium.com', '$2y$10$j9E31yuxPm0t3rHnNTGeGejxe1VwJPPah2csDbzSTzAkS4Hn8UOYi', 'admin', 1, '2026-06-15 13:13:18', '2026-06-15 18:59:23'),
(2, 'Angel', 'angel@restaurantpremium.com', '$2y$10$GXXPp0q553ZjJuhz/EL0EOhYqVP.qMZynaMt7sP4ROSbUBMRbLkOm', 'admin', 1, '2026-07-31 11:26:10', '2026-06-15 19:59:05');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `configuracion`
--
ALTER TABLE `configuracion`
  ADD PRIMARY KEY (`clave`);

--
-- Indexes for table `galeria`
--
ALTER TABLE `galeria`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `opciones`
--
ALTER TABLE `opciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_opcion_grupo` (`grupo_id`);

--
-- Indexes for table `opcion_grupos`
--
ALTER TABLE `opcion_grupos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `idx_estado` (`estado`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `pedido_items`
--
ALTER TABLE `pedido_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_item_pedido` (`pedido_id`),
  ADD KEY `fk_item_platillo` (`platillo_id`);

--
-- Indexes for table `pedido_item_opciones`
--
ALTER TABLE `pedido_item_opciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pio_item` (`item_id`);

--
-- Indexes for table `platillos`
--
ALTER TABLE `platillos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_platillo_categoria` (`categoria_id`);

--
-- Indexes for table `platillo_opciones`
--
ALTER TABLE `platillo_opciones`
  ADD PRIMARY KEY (`platillo_id`,`grupo_id`),
  ADD KEY `fk_po_grupo` (`grupo_id`);

--
-- Indexes for table `reservas`
--
ALTER TABLE `reservas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_fecha` (`fecha`),
  ADD KEY `idx_estado` (`estado`);

--
-- Indexes for table `sugerencias`
--
ALTER TABLE `sugerencias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_platillo` (`platillo_id`);

--
-- Indexes for table `testimonios`
--
ALTER TABLE `testimonios`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `galeria`
--
ALTER TABLE `galeria`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `opciones`
--
ALTER TABLE `opciones`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `opcion_grupos`
--
ALTER TABLE `opcion_grupos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `pedido_items`
--
ALTER TABLE `pedido_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `pedido_item_opciones`
--
ALTER TABLE `pedido_item_opciones`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `platillos`
--
ALTER TABLE `platillos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `reservas`
--
ALTER TABLE `reservas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sugerencias`
--
ALTER TABLE `sugerencias`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `testimonios`
--
ALTER TABLE `testimonios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `opciones`
--
ALTER TABLE `opciones`
  ADD CONSTRAINT `fk_opcion_grupo` FOREIGN KEY (`grupo_id`) REFERENCES `opcion_grupos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `pedido_items`
--
ALTER TABLE `pedido_items`
  ADD CONSTRAINT `fk_item_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_item_platillo` FOREIGN KEY (`platillo_id`) REFERENCES `platillos` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `pedido_item_opciones`
--
ALTER TABLE `pedido_item_opciones`
  ADD CONSTRAINT `fk_pio_item` FOREIGN KEY (`item_id`) REFERENCES `pedido_items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `platillos`
--
ALTER TABLE `platillos`
  ADD CONSTRAINT `fk_platillo_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `platillo_opciones`
--
ALTER TABLE `platillo_opciones`
  ADD CONSTRAINT `fk_po_grupo` FOREIGN KEY (`grupo_id`) REFERENCES `opcion_grupos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_po_platillo` FOREIGN KEY (`platillo_id`) REFERENCES `platillos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `sugerencias`
--
ALTER TABLE `sugerencias`
  ADD CONSTRAINT `fk_sug_platillo` FOREIGN KEY (`platillo_id`) REFERENCES `platillos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
