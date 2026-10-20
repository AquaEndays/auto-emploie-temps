-- --------------------------------------------------------
-- Hôte:                         127.0.0.1
-- Version du serveur:           8.3.0 - MySQL Community Server - GPL
-- SE du serveur:                Win64
-- HeidiSQL Version:             12.1.0.6537
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- Listage de la structure de table gilles_le_magnifique. classes
CREATE TABLE IF NOT EXISTS `classes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `effectif` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Listage des données de la table gilles_le_magnifique.classes : ~1 rows (environ)
INSERT INTO `classes` (`id`, `nom`, `effectif`) VALUES
	(1, 'B1B', 50);

-- Listage de la structure de table gilles_le_magnifique. deuxieme_emploi
CREATE TABLE IF NOT EXISTS `deuxieme_emploi` (
  `id` int NOT NULL AUTO_INCREMENT,
  `enseignant_id` int DEFAULT NULL,
  `jour` varchar(20) NOT NULL,
  `classe` varchar(20) NOT NULL,
  `salle` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `matiere` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `duree` int(10) unsigned zerofill DEFAULT NULL,
  `type_evenement` varchar(50) DEFAULT NULL,
  `creneau` enum('Matin','Midi','Soir') NOT NULL,
  `salle_id` int DEFAULT NULL,
  `heure_debut` time NOT NULL,
  `heure_fin` time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `enseignant_id` (`enseignant_id`),
  KEY `salle_id` (`salle_id`),
  CONSTRAINT `deuxieme_emploi_ibfk_1` FOREIGN KEY (`enseignant_id`) REFERENCES `enseignants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `deuxieme_emploi_ibfk_2` FOREIGN KEY (`salle_id`) REFERENCES `salles` (`id`),
  CONSTRAINT `deuxieme_emploi_ibfk_3` FOREIGN KEY (`salle_id`) REFERENCES `salles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=266 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Listage des données de la table gilles_le_magnifique.deuxieme_emploi : ~5 rows (environ)
INSERT INTO `deuxieme_emploi` (`id`, `enseignant_id`, `jour`, `classe`, `salle`, `matiere`, `duree`, `type_evenement`, `creneau`, `salle_id`, `heure_debut`, `heure_fin`) VALUES
	(261, 3, 'Mercredi', '', NULL, 'Chimie', NULL, NULL, 'Midi', 2, '00:00:00', '00:00:00'),
	(262, 4, 'Lundi', '', NULL, 'Mathematics', NULL, NULL, 'Matin', 2, '00:00:00', '00:00:00'),
	(263, 4, 'Lundi', '', NULL, 'Mathematics', NULL, NULL, 'Midi', 2, '00:00:00', '00:00:00'),
	(264, 3, 'Mercredi', '', NULL, 'Chimie', NULL, NULL, 'Matin', 2, '00:00:00', '00:00:00'),
	(265, 4, 'Lundi', '', NULL, 'Mathematics', NULL, NULL, 'Soir', 4, '00:00:00', '00:00:00');

-- Listage de la structure de table gilles_le_magnifique. disponibilites
CREATE TABLE IF NOT EXISTS `disponibilites` (
  `id` int NOT NULL AUTO_INCREMENT,
  `enseignant_id` int NOT NULL,
  `jour` varchar(20) NOT NULL,
  `nom_enseignant` varchar(50) NOT NULL DEFAULT '',
  `creneau` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `enseignant_id` (`enseignant_id`),
  CONSTRAINT `disponibilites_ibfk_1` FOREIGN KEY (`enseignant_id`) REFERENCES `enseignants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Listage des données de la table gilles_le_magnifique.disponibilites : ~15 rows (environ)
INSERT INTO `disponibilites` (`id`, `enseignant_id`, `jour`, `nom_enseignant`, `creneau`) VALUES
	(18, 4, 'Jeudi', '', 'matin'),
	(23, 2, 'Samedi', '', 'matin'),
	(24, 2, 'Vendredi', '', 'soir'),
	(27, 7, 'Mardi', '', 'soir'),
	(28, 7, 'Mardi', '', 'matin'),
	(31, 2, 'Lundi', '', 'midi'),
	(32, 3, 'Lundi', '', '13h30-17h30'),
	(36, 1, 'Jeudi', '', '13h30-17h30'),
	(37, 1, 'Samedi', '', '13h30-17h30'),
	(38, 9, 'Lundi', '', '13h30-17h30'),
	(39, 9, 'Mardi', '', '13h30-17h30'),
	(40, 9, 'Mercredi', '', '13h30-17h30'),
	(41, 9, 'Jeudi', '', '13h30-17h30'),
	(42, 9, 'Vendredi', '', '13h30-17h30'),
	(43, 9, 'Samedi', '', '08h00-12h00');

-- Listage de la structure de table gilles_le_magnifique. donnees_emploi
CREATE TABLE IF NOT EXISTS `donnees_emploi` (
  `id` int NOT NULL AUTO_INCREMENT,
  `enseignant_id` int NOT NULL,
  `matiere_id` int NOT NULL,
  `salle_id` int NOT NULL,
  `jour` varchar(20) NOT NULL,
  `creneau` varchar(50) NOT NULL,
  `date_generation` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_emploi` (`enseignant_id`,`matiere_id`,`salle_id`,`jour`,`creneau`),
  KEY `matiere_id` (`matiere_id`),
  KEY `salle_id` (`salle_id`),
  CONSTRAINT `donnees_emploi_ibfk_1` FOREIGN KEY (`enseignant_id`) REFERENCES `enseignants` (`id`),
  CONSTRAINT `donnees_emploi_ibfk_2` FOREIGN KEY (`matiere_id`) REFERENCES `matieres` (`id`),
  CONSTRAINT `donnees_emploi_ibfk_3` FOREIGN KEY (`salle_id`) REFERENCES `salles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Listage des données de la table gilles_le_magnifique.donnees_emploi : ~12 rows (environ)
INSERT INTO `donnees_emploi` (`id`, `enseignant_id`, `matiere_id`, `salle_id`, `jour`, `creneau`, `date_generation`) VALUES
	(22, 4, 13, 4, 'Jeudi', 'matin', '2025-06-12 10:32:44'),
	(23, 7, 14, 4, 'Mardi', 'matin', '2025-06-12 10:32:44'),
	(24, 3, 11, 4, 'Lundi', 'soir', '2025-06-12 10:32:44'),
	(25, 3, 9, 4, 'Mercredi', 'soir', '2025-06-12 10:32:44'),
	(27, 2, 15, 4, 'Lundi', 'midi', '2025-06-12 10:32:44'),
	(28, 3, 12, 4, 'Mercredi', 'midi', '2025-06-12 10:32:44'),
	(29, 2, 4, 4, 'Samedi', 'matin', '2025-06-12 10:32:44'),
	(30, 2, 9, 4, 'Vendredi', 'soir', '2025-06-12 10:32:44'),
	(32, 1, 13, 4, 'Jeudi', '13h30-17h30', '2025-06-12 10:32:44'),
	(33, 1, 13, 4, 'Samedi', '13h30-17h30', '2025-06-12 10:32:44'),
	(34, 3, 9, 4, 'Lundi', '13h30-17h30', '2025-06-12 13:26:01'),
	(35, 7, 11, 4, 'Mardi', 'soir', '2025-06-12 13:47:40');

-- Listage de la structure de table gilles_le_magnifique. emploi
CREATE TABLE IF NOT EXISTS `emploi` (
  `id` int NOT NULL AUTO_INCREMENT,
  `jour` varchar(20) DEFAULT NULL,
  `heure_debut` time DEFAULT NULL,
  `heure_fin` time DEFAULT NULL,
  `salle_id` int DEFAULT NULL,
  `enseignant_id` int DEFAULT NULL,
  `matiere_id` int DEFAULT NULL,
  `niveau_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `salle_id` (`salle_id`),
  KEY `enseignant_id` (`enseignant_id`),
  KEY `matiere_id` (`matiere_id`),
  KEY `niveau_id` (`niveau_id`),
  CONSTRAINT `emploi_ibfk_1` FOREIGN KEY (`salle_id`) REFERENCES `salles` (`id`),
  CONSTRAINT `emploi_ibfk_2` FOREIGN KEY (`enseignant_id`) REFERENCES `enseignants` (`id`),
  CONSTRAINT `emploi_ibfk_3` FOREIGN KEY (`matiere_id`) REFERENCES `matieres` (`id`),
  CONSTRAINT `emploi_ibfk_4` FOREIGN KEY (`niveau_id`) REFERENCES `niveaux` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Listage des données de la table gilles_le_magnifique.emploi : ~0 rows (environ)

-- Listage de la structure de table gilles_le_magnifique. emploi_du_temps
CREATE TABLE IF NOT EXISTS `emploi_du_temps` (
  `id` int NOT NULL AUTO_INCREMENT,
  `enseignant_id` int NOT NULL,
  `matiere_id` int NOT NULL,
  `salle_id` int NOT NULL,
  `jour` varchar(50) NOT NULL,
  `creneau` varchar(50) NOT NULL,
  `date_generation` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `enseignant_id` (`enseignant_id`,`jour`,`creneau`),
  UNIQUE KEY `salle_id` (`salle_id`,`jour`,`creneau`),
  KEY `fk_edt_matiere` (`matiere_id`),
  CONSTRAINT `fk_edt_enseignant` FOREIGN KEY (`enseignant_id`) REFERENCES `enseignants` (`id`),
  CONSTRAINT `fk_edt_matiere` FOREIGN KEY (`matiere_id`) REFERENCES `matieres` (`id`),
  CONSTRAINT `fk_edt_salle` FOREIGN KEY (`salle_id`) REFERENCES `salles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Listage des données de la table gilles_le_magnifique.emploi_du_temps : ~24 rows (environ)
INSERT INTO `emploi_du_temps` (`id`, `enseignant_id`, `matiere_id`, `salle_id`, `jour`, `creneau`, `date_generation`) VALUES
	(1, 1, 13, 4, 'Jeudi', '13h30-17h30', '2025-06-12 15:36:26'),
	(2, 1, 13, 4, 'Samedi', '13h30-17h30', '2025-06-12 15:36:26'),
	(3, 2, 4, 4, 'Samedi', '08h00-12h00', '2025-06-12 15:36:26'),
	(4, 2, 9, 4, 'Vendredi', '18h00-21h00', '2025-06-12 15:36:26'),
	(5, 2, 15, 4, 'Lundi', '12h00-13h30', '2025-06-12 15:36:26'),
	(6, 3, 9, 4, 'Lundi', '13h30-17h30', '2025-06-12 15:36:26'),
	(7, 3, 9, 4, 'Mercredi', '18h00-21h00', '2025-06-12 15:36:26'),
	(8, 3, 11, 4, 'Lundi', '18h00-21h00', '2025-06-12 15:36:26'),
	(9, 3, 12, 4, 'Mercredi', '12h00-13h30', '2025-06-12 15:36:26'),
	(10, 4, 13, 4, 'Jeudi', '08h00-12h00', '2025-06-12 15:36:26'),
	(11, 7, 11, 4, 'Mardi', '18h00-21h00', '2025-06-12 15:36:26'),
	(12, 7, 14, 4, 'Mardi', '08h00-12h00', '2025-06-12 15:36:26'),
	(13, 0, 4, 0, 'Lundi', '08h00-12h00', '2025-06-12 15:36:26'),
	(14, 0, 5, 0, 'Mardi', '12h00-13h30', '2025-06-12 15:36:26'),
	(15, 9, 1, 4, 'Mardi', '13h30-17h30', '2025-06-12 15:36:26'),
	(16, 0, 4, 0, 'Mercredi', '08h00-12h00', '2025-06-12 15:36:26'),
	(17, 9, 7, 4, 'Mercredi', '13h30-17h30', '2025-06-12 15:36:26'),
	(18, 0, 5, 0, 'Jeudi', '12h00-13h30', '2025-06-12 15:36:26'),
	(19, 0, 4, 0, 'Jeudi', '18h00-21h00', '2025-06-12 15:36:26'),
	(20, 0, 4, 0, 'Vendredi', '08h00-12h00', '2025-06-12 15:36:26'),
	(21, 0, 5, 0, 'Vendredi', '12h00-13h30', '2025-06-12 15:36:26'),
	(22, 9, 3, 4, 'Vendredi', '13h30-17h30', '2025-06-12 15:36:26'),
	(23, 0, 5, 0, 'Samedi', '12h00-13h30', '2025-06-12 15:36:26'),
	(24, 0, 4, 0, 'Samedi', '18h00-21h00', '2025-06-12 15:36:26');

-- Listage de la structure de table gilles_le_magnifique. enseignants
CREATE TABLE IF NOT EXISTS `enseignants` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) NOT NULL,
  `matiere` varchar(255) DEFAULT NULL,
  `classe` varchar(255) DEFAULT NULL,
  `volume_horaire` int DEFAULT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `type_evenement` varchar(255) DEFAULT NULL,
  `salle` varchar(255) DEFAULT NULL,
  `salle_id` int DEFAULT NULL,
  `matiere_id` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Listage des données de la table gilles_le_magnifique.enseignants : ~8 rows (environ)
INSERT INTO `enseignants` (`id`, `nom`, `matiere`, `classe`, `volume_horaire`, `mot_de_passe`, `type_evenement`, `salle`, `salle_id`, `matiere_id`) VALUES
	(0, 'TPE', 'TPE', 'TPE', 4, '1234', NULL, NULL, 1, NULL),
	(1, 'JUNIOr', 'cyber securite', NULL, 4, '125', NULL, NULL, 3, NULL),
	(2, 'P', 'Algorithme', NULL, 4, '123', NULL, NULL, 5, NULL),
	(3, 'komplin', 'developement web', NULL, 123, '123', NULL, NULL, 5, NULL),
	(4, 'Endays', 'developement python', NULL, 4, '1234', NULL, NULL, 5, NULL),
	(7, 'alexia', 'cyber securite', NULL, 12, '2222', NULL, NULL, 4, NULL),
	(9, 'kimi', 'VBexcel', NULL, 4, '4321', NULL, NULL, 5, NULL),
	(10, 'Halimata', 'developement web', NULL, 15, '123456', NULL, NULL, 4, NULL);

-- Listage de la structure de table gilles_le_magnifique. enseignant_matiere_classe
CREATE TABLE IF NOT EXISTS `enseignant_matiere_classe` (
  `id` int NOT NULL AUTO_INCREMENT,
  `enseignant_id` int NOT NULL,
  `matiere_id` int NOT NULL,
  `classe_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `enseignant_id` (`enseignant_id`),
  KEY `matiere_id` (`matiere_id`),
  KEY `classe_id` (`classe_id`),
  CONSTRAINT `enseignant_matiere_classe_ibfk_1` FOREIGN KEY (`enseignant_id`) REFERENCES `enseignants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `enseignant_matiere_classe_ibfk_2` FOREIGN KEY (`matiere_id`) REFERENCES `matieres` (`id`) ON DELETE CASCADE,
  CONSTRAINT `enseignant_matiere_classe_ibfk_3` FOREIGN KEY (`classe_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Listage des données de la table gilles_le_magnifique.enseignant_matiere_classe : ~0 rows (environ)

-- Listage de la structure de table gilles_le_magnifique. enseignements
CREATE TABLE IF NOT EXISTS `enseignements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `enseignant_id` int DEFAULT NULL,
  `classe` varchar(50) DEFAULT NULL,
  `matiere` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `enseignant_id` (`enseignant_id`),
  CONSTRAINT `enseignements_ibfk_1` FOREIGN KEY (`enseignant_id`) REFERENCES `enseignants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Listage des données de la table gilles_le_magnifique.enseignements : ~0 rows (environ)

-- Listage de la structure de table gilles_le_magnifique. matieres
CREATE TABLE IF NOT EXISTS `matieres` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `duree` int DEFAULT '4',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Listage des données de la table gilles_le_magnifique.matieres : ~15 rows (environ)
INSERT INTO `matieres` (`id`, `nom`, `duree`) VALUES
	(1, 'Gilles', 4),
	(2, 'chinois', 4),
	(3, 'italien', 4),
	(4, 'TPE', 4),
	(5, 'PAUSE', 1),
	(6, 'francais', 4),
	(7, 'anglais', 4),
	(8, 'cantonais', 4),
	(9, 'developement web', 4),
	(10, 'japonais', 4),
	(11, 'cyber securite', 4),
	(12, 'developement python', 4),
	(13, 'VBexcel', 4),
	(14, 'C++', 6),
	(15, 'Algorithme', 4);

-- Listage de la structure de table gilles_le_magnifique. niveaux
CREATE TABLE IF NOT EXISTS `niveaux` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(50) DEFAULT NULL,
  `effectif` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nom` (`nom`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Listage des données de la table gilles_le_magnifique.niveaux : ~1 rows (environ)
INSERT INTO `niveaux` (`id`, `nom`, `effectif`) VALUES
	(4, 'M2', 30);

-- Listage de la structure de table gilles_le_magnifique. salles
CREATE TABLE IF NOT EXISTS `salles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `capacite` int NOT NULL,
  `est_indisponible` tinyint DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Listage des données de la table gilles_le_magnifique.salles : ~6 rows (environ)
INSERT INTO `salles` (`id`, `nom`, `capacite`, `est_indisponible`) VALUES
	(0, 'N/A', 0, 1),
	(2, 'Gilles', 212, 1),
	(3, 'B12', 210, 1),
	(4, 'B1B', 200, 0),
	(5, 'B2', 150, 0),
	(7, 'amphi2', 50, 1);

-- Listage de la structure de table gilles_le_magnifique. utilisateurs
CREATE TABLE IF NOT EXISTS `utilisateurs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `role` enum('admin','enseignant') NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Listage des données de la table gilles_le_magnifique.utilisateurs : ~0 rows (environ)

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
