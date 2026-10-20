-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : mar. 10 juin 2025 à 23:08
-- Version du serveur : 8.3.0
-- Version de PHP : 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `gilles_le_magnifique`
--

-- --------------------------------------------------------

--
-- Structure de la table `donnees_emploi`
--

DROP TABLE IF EXISTS `donnees_emploi`;
CREATE TABLE IF NOT EXISTS `donnees_emploi` (
  `id` int NOT NULL AUTO_INCREMENT,
  `enseignant_id` int NOT NULL,
  `matiere_id` int NOT NULL,
  `salle_id` int NOT NULL,
  `jour` varchar(20) NOT NULL,
  `creneau` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_emploi` (`enseignant_id`,`matiere_id`,`salle_id`,`jour`,`creneau`),
  KEY `matiere_id` (`matiere_id`),
  KEY `salle_id` (`salle_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `donnees_emploi`
--

INSERT INTO `donnees_emploi` (`id`, `enseignant_id`, `matiere_id`, `salle_id`, `jour`, `creneau`) VALUES
(1, 4, 1, 2, 'Jeudi', 'matin');

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `donnees_emploi`
--
ALTER TABLE `donnees_emploi`
  ADD CONSTRAINT `donnees_emploi_ibfk_1` FOREIGN KEY (`enseignant_id`) REFERENCES `enseignants` (`id`),
  ADD CONSTRAINT `donnees_emploi_ibfk_2` FOREIGN KEY (`matiere_id`) REFERENCES `matieres` (`id`),
  ADD CONSTRAINT `donnees_emploi_ibfk_3` FOREIGN KEY (`salle_id`) REFERENCES `salles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
