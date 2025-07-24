CREATE DATABASE freelancehub_v2;
USE freelancehub_v2;

-- Users Table
CREATE TABLE users (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('client','freelancer','admin') NOT NULL DEFAULT 'freelancer',
  `avatar` varchar(255) DEFAULT 'default.jpg',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Jobs Table
CREATE TABLE jobs (
  id int(11) NOT NULL AUTO_INCREMENT,
  client_id int(11) NOT NULL,
  title varchar(100) NOT NULL,
  description text NOT NULL,
  requirements text DEFAULT NULL,
  budget decimal(10,2) NOT NULL,
  post_date timestamp NOT NULL DEFAULT current_timestamp(),
  status enum('open','in_progress','completed') DEFAULT 'open',
  PRIMARY KEY (id),
  FOREIGN KEY (client_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Applications Table
CREATE TABLE `applications` (
  id int(11) NOT NULL AUTO_INCREMENT,
  job_id int(11) NOT NULL,
  freelancer_id int(11) NOT NULL,
  proposal text NOT NULL,
  bid_amount decimal(10,2) NOT NULL,
  status enum('pending','accepted','rejected') DEFAULT 'pending',
  applied_at timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id),
  FOREIGN KEY (job_id) REFERENCES jobs (id),
  FOREIGN KEY (freelancer_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO users(username, email, password_hash, role)
VALUES ('Admin', 'yisihakabraham51@gmail.com', '$2a$12$PXqx9pqBYyymGDkftLdhDeQFQJ/tKWgePSzuHs9URAjgRVnODynje', 'admin');