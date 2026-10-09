-- ============================================================
-- Merged SQL: gestionlesall (production schema & data merged into local dump)
-- Source: production dump + local phpMyAdmin dump
-- Merged on: 2026-10-09
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- --------------------------------------------------------
-- Table : authentification
-- Notes: production uses bcrypt-hashed passwords. Using hashed values from production for safety.
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `authentification` (
  `id`           int(11)                        NOT NULL AUTO_INCREMENT,
  `nom`          varchar(100)                   NOT NULL,
  `email`        varchar(150)                   NOT NULL,
  `mot_de_passe` varchar(255)                   NOT NULL,
  `role`         enum('admin','user')           NOT NULL DEFAULT 'user',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=4;

-- Données — mots de passe bcrypt (production)
INSERT INTO `authentification` (`id`, `nom`, `email`, `mot_de_passe`, `role`) VALUES
(1, 'mohamed',  'mohasbt77@gmail.com',      '$2b$12$c/6Dxb9wsMu0Ef3mt7Z6v.Oq0R2SKF5MqUim5pQLaDW.7QpFNa3TG', 'admin'),
(2, 'Sara',     'sara@email.com',            '$2b$12$kv9RbwqlNj1JmWE.4Z01H.Ipn.h3RMkG0wfthdZUTQO2/airwb9zm', 'user'),
(3, 'ljljm',    'djebiriabdrazak@gmail.com', '$2b$12$c/6Dxb9wsMu0Ef3mt7Z6v.Oq0R2SKF5MqUim5pQLaDW.7QpFNa3TG', 'admin');

-- --------------------------------------------------------
-- Table : gestion
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `gestion` (
  `id`          int(11)      NOT NULL AUTO_INCREMENT,
  `nom`         varchar(100) NOT NULL,
  `capacite`    int(11)      NOT NULL,
  `description` text         DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AUTO_INCREMENT=2;

INSERT INTO `gestion` (`id`, `nom`, `capacite`, `description`) VALUES
(1, 'salle', 20, 'exemple');

-- --------------------------------------------------------
-- Table : reservation
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `reservation` (
  `id`          int(11) NOT NULL AUTO_INCREMENT,
  `id_user`     int(11) NOT NULL,
  `id_salle`    int(11) NOT NULL,
  `date`        date    NOT NULL,
  `heure_debut` time    NOT NULL,
  `heure_fin`   time    NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_user`  (`id_user`),
  KEY `id_salle` (`id_salle`),
  CONSTRAINT `reservation_ibfk_1` FOREIGN KEY (`id_user`)  REFERENCES `authentification` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reservation_ibfk_2` FOREIGN KEY (`id_salle`) REFERENCES `gestion` (`id`)          ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AUTO_INCREMENT=2;

INSERT INTO `reservation` (`id`, `id_user`, `id_salle`, `date`, `heure_debut`, `heure_fin`) VALUES
(1, 1, 1, '2026-05-17', '05:26:00', '17:02:00');

COMMIT;

-- Notes and rationale:
-- 1) The merged file uses the production-safe bcrypt password hashes instead of cleartext values present in the phpMyAdmin dump.
-- 2) CREATE TABLE IF NOT EXISTS and inline FK constraints (with ON DELETE CASCADE) are kept from production to ensure consistent behavior across environments.
-- 3) Character set and collations set to utf8mb4 for compatibility with production.
-- If any local-only differences are required (e.g., cleartext passwords for dev), consider keeping a separate dev-only dump instead of committing them here.
