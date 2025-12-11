CREATE DATABASE IF NOT EXISTS network_panel CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE network_panel;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(64) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL
);
INSERT INTO `users` (`id`, `username`, `password_hash`) VALUES
(1, 'admin', '$2y$10$NhW7YOhja9O7x3rqu9VYeuV6ohAq2cHPoKCl0T.2OwgPfaXiFJd6K');

CREATE TABLE devices (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  ip VARCHAR(64) NOT NULL,
  username VARCHAR(64) NOT NULL,
  password VARCHAR(128) NOT NULL,
  category ENUM('router','switch','ap','ptp') NOT NULL,
  brand VARCHAR(64) NOT NULL,
  model VARCHAR(64) NOT NULL,
  interface VARCHAR(64) NOT NULL DEFAULT 'ether1',
  show_on_dashboard TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
