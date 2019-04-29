-- phpMyAdmin SQL Dump
-- version 4.8.5
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Erstellungszeit: 28. Apr 2019 um 18:46
-- Server-Version: 10.1.38-MariaDB
-- PHP-Version: 7.3.2

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT = @@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS = @@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION = @@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Datenbank: `db_app`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `user`
--

CREATE TABLE `user`
(
    `id`          binary(16) NOT NULL,
    `user_name`   varchar(255)    DEFAULT NULL,
    `insert_date` timestamp  NULL DEFAULT CURRENT_TIMESTAMP,
    `delete_date` datetime        DEFAULT NULL
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

--
-- Daten für Tabelle `user`
--

INSERT INTO `user` (`id`, `user_name`, `insert_date`, `delete_date`)
VALUES (0x11b0831063ac11e9a558e86a6466d8e3, 'tester 2', '2019-04-20 20:36:53', '2019-04-20 16:37:09');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `user_email`
--

CREATE TABLE `user_email`
(
    `id`          binary(16) NOT NULL,
    `user_id`     binary(16)      DEFAULT NULL,
    `email`       varchar(255)    DEFAULT NULL,
    `insert_date` timestamp  NULL DEFAULT CURRENT_TIMESTAMP,
    `delete_date` datetime        DEFAULT NULL
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

--
-- Daten für Tabelle `user_email`
--

INSERT INTO `user_email` (`id`, `user_id`, `email`, `insert_date`, `delete_date`)
VALUES (0x11b19c3f63ac11e9a558e86a6466d8e3, 0x11b0831063ac11e9a558e86a6466d8e3, 'some@email.com', '2019-04-20 20:36:53',
        NULL),
       (0x2e59317c638c11e9a558e86a6466d8e3, 0x2e592551638c11e9a558e86a6466d8e3, 'any@email.com', '2019-04-20 16:48:37',
        NULL),
       (0xf7be155463ab11e9a558e86a6466d8e3, 0xf7bd66ee63ab11e9a558e86a6466d8e3, 'some@email.com', '2019-04-20 20:36:10',
        NULL);

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `user`
--
ALTER TABLE `user`
    ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `user_email`
--
ALTER TABLE `user_email`
    ADD PRIMARY KEY (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT = @OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS = @OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION = @OLD_COLLATION_CONNECTION */;
