-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 29/11/2024 às 19:18
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `shopping_list`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `default_products`
--

CREATE TABLE `default_products` (
  `id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `price` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `default_products`
--

INSERT INTO `default_products` (`id`, `product_name`, `category`, `created_at`, `price`) VALUES
(1, 'Alface', 'Hortifruti', '2024-11-29 17:52:28', 3.50),
(2, 'Tomate', 'Hortifruti', '2024-11-29 17:52:28', 6.00),
(3, 'Cebola', 'Hortifruti', '2024-11-29 17:52:28', 4.50),
(4, 'Batata', 'Hortifruti', '2024-11-29 17:52:28', 5.00),
(5, 'Cenoura', 'Hortifruti', '2024-11-29 17:52:28', 4.00),
(6, 'Banana', 'Hortifruti', '2024-11-29 17:52:28', 5.50),
(7, 'Maçã', 'Hortifruti', '2024-11-29 17:52:28', 8.00),
(8, 'Laranja', 'Hortifruti', '2024-11-29 17:52:28', 4.50),
(9, 'Limão', 'Hortifruti', '2024-11-29 17:52:28', 3.50),
(10, 'Alho', 'Hortifruti', '2024-11-29 17:52:28', 3.00),
(11, 'Pimentão', 'Hortifruti', '2024-11-29 17:52:28', 4.50),
(12, 'Repolho', 'Hortifruti', '2024-11-29 17:52:28', 5.00),
(13, 'Frango', 'Carnes', '2024-11-29 17:52:28', 15.00),
(14, 'Carne Moída', 'Carnes', '2024-11-29 17:52:28', 30.00),
(15, 'Filé de Frango', 'Carnes', '2024-11-29 17:52:28', 20.00),
(16, 'Costela', 'Carnes', '2024-11-29 17:52:28', 35.00),
(17, 'Linguiça', 'Carnes', '2024-11-29 17:52:28', 18.00),
(18, 'Peito de Frango', 'Carnes', '2024-11-29 17:52:28', 22.00),
(19, 'Carne de Porco', 'Carnes', '2024-11-29 17:52:28', 25.00),
(20, 'Peixe', 'Carnes', '2024-11-29 17:52:28', 30.00),
(21, 'Leite', 'Laticínios', '2024-11-29 17:52:28', 5.50),
(22, 'Queijo', 'Laticínios', '2024-11-29 17:52:28', 25.00),
(23, 'Iogurte', 'Laticínios', '2024-11-29 17:52:28', 7.00),
(24, 'Manteiga', 'Laticínios', '2024-11-29 17:52:29', 12.00),
(25, 'Requeijão', 'Laticínios', '2024-11-29 17:52:29', 8.00),
(26, 'Cream Cheese', 'Laticínios', '2024-11-29 17:52:29', 10.00),
(27, 'Leite Condensado', 'Laticínios', '2024-11-29 17:52:29', 7.50),
(28, 'Creme de Leite', 'Laticínios', '2024-11-29 17:52:29', 4.50),
(29, 'Arroz', 'Mercearia', '2024-11-29 17:52:29', 20.00),
(30, 'Feijão', 'Mercearia', '2024-11-29 17:52:29', 8.00),
(31, 'Macarrão', 'Mercearia', '2024-11-29 17:52:29', 5.00),
(32, 'Óleo', 'Mercearia', '2024-11-29 17:52:29', 8.50),
(33, 'Sal', 'Mercearia', '2024-11-29 17:52:29', 3.00),
(34, 'Açúcar', 'Mercearia', '2024-11-29 17:52:29', 4.50),
(35, 'Café', 'Mercearia', '2024-11-29 17:52:29', 15.00),
(36, 'Farinha de Trigo', 'Mercearia', '2024-11-29 17:52:29', 5.00),
(37, 'Molho de Tomate', 'Mercearia', '2024-11-29 17:52:29', 4.00),
(38, 'Água', 'Bebidas', '2024-11-29 17:52:29', 2.50),
(39, 'Refrigerante', 'Bebidas', '2024-11-29 17:52:29', 8.00),
(40, 'Suco', 'Bebidas', '2024-11-29 17:52:29', 6.00),
(41, 'Cerveja', 'Bebidas', '2024-11-29 17:52:29', 4.50),
(42, 'Vinho', 'Bebidas', '2024-11-29 17:52:29', 35.00),
(43, 'Água de Coco', 'Bebidas', '2024-11-29 17:52:29', 5.00),
(44, 'Pão Francês', 'Padaria', '2024-11-29 17:52:29', 15.00),
(45, 'Pão de Forma', 'Padaria', '2024-11-29 17:52:29', 8.00),
(46, 'Bolo', 'Padaria', '2024-11-29 17:52:29', 20.00),
(47, 'Biscoito', 'Padaria', '2024-11-29 17:52:29', 5.00),
(48, 'Torrada', 'Padaria', '2024-11-29 17:52:29', 6.00),
(49, 'Pão de Queijo', 'Padaria', '2024-11-29 17:52:29', 25.00),
(50, 'Detergente', 'Limpeza', '2024-11-29 17:52:29', 3.50),
(51, 'Sabão em Pó', 'Limpeza', '2024-11-29 17:52:29', 15.00),
(52, 'Desinfetante', 'Limpeza', '2024-11-29 17:52:29', 8.00),
(53, 'Papel Higiênico', 'Limpeza', '2024-11-29 17:52:29', 18.00),
(54, 'Água Sanitária', 'Limpeza', '2024-11-29 17:52:29', 6.00),
(55, 'Amaciante', 'Limpeza', '2024-11-29 17:52:29', 12.00),
(56, 'Esponja', 'Limpeza', '2024-11-29 17:52:29', 2.50),
(57, 'Sabonete', 'Higiene', '2024-11-29 17:52:29', 3.00),
(58, 'Shampoo', 'Higiene', '2024-11-29 17:52:29', 15.00),
(59, 'Condicionador', 'Higiene', '2024-11-29 17:52:29', 15.00),
(60, 'Pasta de Dente', 'Higiene', '2024-11-29 17:52:29', 8.00),
(61, 'Escova de Dente', 'Higiene', '2024-11-29 17:52:29', 5.00),
(62, 'Desodorante', 'Higiene', '2024-11-29 17:52:29', 12.00),
(63, 'Papel Toalha', 'Higiene', '2024-11-29 17:52:29', 6.00),
(64, 'Cotonete', 'Higiene', '2024-11-29 18:03:21', 8.00),
(65, 'Absorvente', 'Higiene', '2024-11-29 18:17:44', 8.00);

-- --------------------------------------------------------

--
-- Estrutura para tabela `shopping_history`
--

CREATE TABLE `shopping_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `purchase_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `shopping_history_items`
--

CREATE TABLE `shopping_history_items` (
  `id` int(11) NOT NULL,
  `history_id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `shopping_items`
--

CREATE TABLE `shopping_items` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `price` decimal(10,2) DEFAULT 0.00,
  `is_purchased` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `email` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `created_at`, `email`) VALUES
(1, 'Fábio', '$2y$10$7Q40LKQ0doUeeU8/u0342eQTT73XHwN4sm/J5p92pqes9diIIWxLO', '2024-11-28 22:13:30', 'fabiollregis@gmail.com');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `default_products`
--
ALTER TABLE `default_products`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `shopping_history`
--
ALTER TABLE `shopping_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Índices de tabela `shopping_history_items`
--
ALTER TABLE `shopping_history_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `history_id` (`history_id`);

--
-- Índices de tabela `shopping_items`
--
ALTER TABLE `shopping_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Índices de tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `default_products`
--
ALTER TABLE `default_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT de tabela `shopping_history`
--
ALTER TABLE `shopping_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT de tabela `shopping_history_items`
--
ALTER TABLE `shopping_history_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=125;

--
-- AUTO_INCREMENT de tabela `shopping_items`
--
ALTER TABLE `shopping_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=208;

--
-- AUTO_INCREMENT de tabela `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `shopping_history`
--
ALTER TABLE `shopping_history`
  ADD CONSTRAINT `shopping_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `shopping_history_items`
--
ALTER TABLE `shopping_history_items`
  ADD CONSTRAINT `shopping_history_items_ibfk_1` FOREIGN KEY (`history_id`) REFERENCES `shopping_history` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `shopping_items`
--
ALTER TABLE `shopping_items`
  ADD CONSTRAINT `shopping_items_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
