-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : jeu. 12 juin 2025 à 02:15
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `kora_tickets`
--

-- --------------------------------------------------------

--
-- Structure de la table `carts`
--

CREATE TABLE `carts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(11) NOT NULL,
  `cart_id` int(11) NOT NULL,
  `ticket_category_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `matches`
--

CREATE TABLE `matches` (
  `id` int(11) NOT NULL,
  `home_team` varchar(100) NOT NULL,
  `away_team` varchar(100) NOT NULL,
  `match_date` datetime NOT NULL,
  `stadium_id` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `matches`
--

INSERT INTO `matches` (`id`, `home_team`, `away_team`, `match_date`, `stadium_id`, `description`, `image_url`, `created_at`, `updated_at`) VALUES
(1, 'Wydad AC', 'Raja CA', '2024-07-15 20:00:00', 1, 'Derby de Casablanca - Classico marocain', NULL, '2025-06-12 00:12:10', '2025-06-12 00:12:10'),
(2, 'FAR Rabat', 'AS FAR', '2024-07-20 19:00:00', 2, 'Derby de Rabat', NULL, '2025-06-12 00:12:10', '2025-06-12 00:12:10'),
(3, 'Moghreb Tétouan', 'IRT Tanger', '2024-07-27 18:00:00', 5, 'Derby du Nord', NULL, '2025-06-12 00:12:10', '2025-06-12 00:12:10'),
(4, 'Raja CA', 'AS FAR', '2024-08-03 20:00:00', 1, 'Match de gala', NULL, '2025-06-12 00:12:10', '2025-06-12 00:12:10'),
(5, 'Wydad AC', 'FUS Rabat', '2024-08-10 19:30:00', 1, 'Match de championnat', NULL, '2025-06-12 00:12:10', '2025-06-12 00:12:10'),
(6, 'OC Khouribga', 'Hassania Agadir', '2024-08-17 17:00:00', 3, 'Match de milieu de tableau', NULL, '2025-06-12 00:12:10', '2025-06-12 00:12:10'),
(7, 'RS Berkane', 'MAS Fès', '2024-08-24 18:30:00', 7, 'Match à enjeu', NULL, '2025-06-12 00:12:10', '2025-06-12 00:12:10'),
(8, 'Difaa El Jadida', 'Olympic Safi', '2024-08-31 19:00:00', 1, 'Match de la côte atlantique', NULL, '2025-06-12 00:12:10', '2025-06-12 00:12:10');

-- --------------------------------------------------------

--
-- Structure de la table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `paypal_order_id` varchar(100) DEFAULT NULL,
  `status` enum('pending','completed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `order_details`
--

CREATE TABLE `order_details` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `ticket_category_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price_per_ticket` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `stadiums`
--

CREATE TABLE `stadiums` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `location` varchar(255) NOT NULL,
  `capacity` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `stadiums`
--

INSERT INTO `stadiums` (`id`, `name`, `location`, `capacity`, `created_at`) VALUES
(1, 'Stade Mohammed V', 'Casablanca', 45000, '2025-06-12 00:12:10'),
(2, 'Stade Moulay Abdellah', 'Rabat', 53000, '2025-06-12 00:12:10'),
(3, 'Stade Adrar', 'Agadir', 45380, '2025-06-12 00:12:10'),
(4, 'Grand Stade de Marrakech', 'Marrakech', 45340, '2025-06-12 00:12:10'),
(5, 'Stade Ibn Batouta', 'Tanger', 45000, '2025-06-12 00:12:10'),
(6, 'Stade de Fès', 'Fès', 45000, '2025-06-12 00:12:10'),
(7, 'Stade Municipal de Berkane', 'Berkane', 15000, '2025-06-12 00:12:10'),
(8, 'Stade El Harti', 'Marrakech', 25000, '2025-06-12 00:12:10');

-- --------------------------------------------------------

--
-- Structure de la table `ticket_categories`
--

CREATE TABLE `ticket_categories` (
  `id` int(11) NOT NULL,
  `match_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `total_quantity` int(11) NOT NULL,
  `available_quantity` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `ticket_categories`
--

INSERT INTO `ticket_categories` (`id`, `match_id`, `name`, `price`, `total_quantity`, `available_quantity`, `created_at`) VALUES
(1, 1, 'Tribune Honneur', 300.00, 5000, 5000, '2025-06-12 00:12:10'),
(2, 1, 'Tribune Couvert', 200.00, 8000, 8000, '2025-06-12 00:12:10'),
(3, 1, 'Virage Sud', 100.00, 10000, 10000, '2025-06-12 00:12:10'),
(4, 1, 'Virage Nord', 100.00, 10000, 10000, '2025-06-12 00:12:10'),
(5, 1, 'Catégorie VIP', 500.00, 1000, 1000, '2025-06-12 00:12:10'),
(6, 2, 'Tribune Principale', 250.00, 4000, 4000, '2025-06-12 00:12:10'),
(7, 2, 'Tribune Secondaire', 150.00, 7000, 7000, '2025-06-12 00:12:10'),
(8, 2, 'Virage Est', 80.00, 9000, 9000, '2025-06-12 00:12:10'),
(9, 2, 'Virage Ouest', 80.00, 9000, 9000, '2025-06-12 00:12:10'),
(10, 2, 'Catégorie VIP', 400.00, 800, 800, '2025-06-12 00:12:10'),
(11, 3, 'Tribune Centrale', 200.00, 3000, 3000, '2025-06-12 00:12:10'),
(12, 3, 'Tribune Latérale', 120.00, 5000, 5000, '2025-06-12 00:12:10'),
(13, 3, 'Virage Maroc', 60.00, 7000, 7000, '2025-06-12 00:12:10'),
(14, 3, 'Virage Méditerranée', 60.00, 7000, 7000, '2025-06-12 00:12:10'),
(15, 3, 'Catégorie VIP', 350.00, 600, 600, '2025-06-12 00:12:10'),
(16, 4, 'Tribune Honneur', 280.00, 5000, 5000, '2025-06-12 00:12:10'),
(17, 4, 'Tribune Couvert', 180.00, 8000, 8000, '2025-06-12 00:12:10'),
(18, 4, 'Virage Sud', 90.00, 10000, 10000, '2025-06-12 00:12:10'),
(19, 4, 'Virage Nord', 90.00, 10000, 10000, '2025-06-12 00:12:10'),
(20, 4, 'Catégorie VIP', 450.00, 1000, 1000, '2025-06-12 00:12:10'),
(21, 5, 'Tribune Honneur', 250.00, 5000, 5000, '2025-06-12 00:12:10'),
(22, 5, 'Tribune Couvert', 150.00, 8000, 8000, '2025-06-12 00:12:10'),
(23, 5, 'Virage Sud', 80.00, 10000, 10000, '2025-06-12 00:12:10'),
(24, 5, 'Virage Nord', 80.00, 10000, 10000, '2025-06-12 00:12:10'),
(25, 5, 'Catégorie VIP', 400.00, 1000, 1000, '2025-06-12 00:12:10'),
(26, 6, 'Tribune Principale', 150.00, 3000, 3000, '2025-06-12 00:12:10'),
(27, 6, 'Tribune Secondaire', 100.00, 5000, 5000, '2025-06-12 00:12:10'),
(28, 6, 'Virage Atlas', 50.00, 7000, 7000, '2025-06-12 00:12:10'),
(29, 6, 'Virage Souss', 50.00, 7000, 7000, '2025-06-12 00:12:10'),
(30, 6, 'Catégorie VIP', 300.00, 500, 500, '2025-06-12 00:12:10'),
(31, 7, 'Tribune Centrale', 120.00, 2000, 2000, '2025-06-12 00:12:10'),
(32, 7, 'Tribune Latérale', 80.00, 4000, 4000, '2025-06-12 00:12:10'),
(33, 7, 'Virage Oriental', 40.00, 5000, 5000, '2025-06-12 00:12:10'),
(34, 7, 'Virage Rif', 40.00, 5000, 5000, '2025-06-12 00:12:10'),
(35, 7, 'Catégorie VIP', 250.00, 400, 400, '2025-06-12 00:12:10'),
(36, 8, 'Tribune Principale', 180.00, 3000, 3000, '2025-06-12 00:12:10'),
(37, 8, 'Tribune Secondaire', 120.00, 5000, 5000, '2025-06-12 00:12:10'),
(38, 8, 'Virage Atlantique', 70.00, 7000, 7000, '2025-06-12 00:12:10'),
(39, 8, 'Virage Doukkala', 70.00, 7000, 7000, '2025-06-12 00:12:10'),
(40, 8, 'Catégorie VIP', 350.00, 600, 600, '2025-06-12 00:12:10');

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@kora-tickets.ma', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '2025-06-12 00:12:10', '2025-06-12 00:12:10'),
(2, 'karim.benz', 'karim.benz@example.ma', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', '2025-06-12 00:12:10', '2025-06-12 00:12:10'),
(3, 'fatima.zahra', 'fatima.zahra@example.ma', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', '2025-06-12 00:12:10', '2025-06-12 00:12:10'),
(4, 'youssef.alami', 'youssef.alami@example.ma', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', '2025-06-12 00:12:10', '2025-06-12 00:12:10'),
(5, 'houda.saadi', 'houda.saadi@example.ma', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', '2025-06-12 00:12:10', '2025-06-12 00:12:10');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cart_id` (`cart_id`),
  ADD KEY `ticket_category_id` (`ticket_category_id`);

--
-- Index pour la table `matches`
--
ALTER TABLE `matches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `stadium_id` (`stadium_id`);

--
-- Index pour la table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `order_details`
--
ALTER TABLE `order_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `ticket_category_id` (`ticket_category_id`);

--
-- Index pour la table `stadiums`
--
ALTER TABLE `stadiums`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `ticket_categories`
--
ALTER TABLE `ticket_categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `match_id` (`match_id`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `carts`
--
ALTER TABLE `carts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `matches`
--
ALTER TABLE `matches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `order_details`
--
ALTER TABLE `order_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `stadiums`
--
ALTER TABLE `stadiums`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `ticket_categories`
--
ALTER TABLE `ticket_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `carts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`),
  ADD CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`ticket_category_id`) REFERENCES `ticket_categories` (`id`);

--
-- Contraintes pour la table `matches`
--
ALTER TABLE `matches`
  ADD CONSTRAINT `matches_ibfk_1` FOREIGN KEY (`stadium_id`) REFERENCES `stadiums` (`id`);

--
-- Contraintes pour la table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `order_details`
--
ALTER TABLE `order_details`
  ADD CONSTRAINT `order_details_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `order_details_ibfk_2` FOREIGN KEY (`ticket_category_id`) REFERENCES `ticket_categories` (`id`);

--
-- Contraintes pour la table `ticket_categories`
--
ALTER TABLE `ticket_categories`
  ADD CONSTRAINT `ticket_categories_ibfk_1` FOREIGN KEY (`match_id`) REFERENCES `matches` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
