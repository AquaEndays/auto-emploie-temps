-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : lun. 16 juin 2025 à 17:14
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
-- Structure de la table `classes`
--

DROP TABLE IF EXISTS `classes`;
CREATE TABLE IF NOT EXISTS `classes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `effectif` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `deuxieme_emploi`
--

DROP TABLE IF EXISTS `deuxieme_emploi`;
CREATE TABLE IF NOT EXISTS `deuxieme_emploi` (
  `id` int NOT NULL AUTO_INCREMENT,
  `enseignant_id` int DEFAULT NULL,
  `jour` varchar(20) NOT NULL,
  `classe` varchar(20) NOT NULL,
  `salle` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `matiere` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `duree` int(10) UNSIGNED ZEROFILL DEFAULT NULL,
  `type_evenement` varchar(50) DEFAULT NULL,
  `creneau` enum('Matin','Midi','Soir') NOT NULL,
  `salle_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `enseignant_id` (`enseignant_id`),
  KEY `salle_id` (`salle_id`)
) ENGINE=InnoDB AUTO_INCREMENT=266 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `deuxieme_emploi`
--

INSERT INTO `deuxieme_emploi` (`id`, `enseignant_id`, `jour`, `classe`, `salle`, `matiere`, `duree`, `type_evenement`, `creneau`, `salle_id`) VALUES
(261, 3, 'Mercredi', '', NULL, 'Chimie', NULL, NULL, 'Midi', 2),
(262, 4, 'Lundi', '', NULL, 'Mathematics', NULL, NULL, 'Matin', 2),
(263, 4, 'Lundi', '', NULL, 'Mathematics', NULL, NULL, 'Midi', 2),
(264, 3, 'Mercredi', '', NULL, 'Chimie', NULL, NULL, 'Matin', 2),
(265, 4, 'Lundi', '', NULL, 'Mathematics', NULL, NULL, 'Soir', 4);

-- --------------------------------------------------------

--
-- Structure de la table `disponibilites`
--

DROP TABLE IF EXISTS `disponibilites`;
CREATE TABLE IF NOT EXISTS `disponibilites` (
  `id` int NOT NULL AUTO_INCREMENT,
  `enseignant_id` int NOT NULL,
  `jour` varchar(20) NOT NULL,
  `creneau` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `enseignant_id` (`enseignant_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `disponibilites`
--

INSERT INTO `disponibilites` (`id`, `enseignant_id`, `jour`, `creneau`) VALUES
(1, 4, 'Mercredi', '08h00-12h00'),
(2, 3, 'Lundi', '08h00-12h00'),
(3, 3, 'Mardi', '13h30-17h30'),
(5, 3, 'Jeudi', '08h00-12h00'),
(6, 3, 'Vendredi', '13h30-17h30'),
(8, 8, 'Lundi', '08h00-12h00');

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
  `salle_physique_id` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_pre_affectation` (`enseignant_id`,`matiere_id`,`salle_physique_id`,`salle_id`),
  UNIQUE KEY `unique_enseignant_matiere_classe` (`enseignant_id`,`matiere_id`,`salle_id`),
  KEY `fk_matiere_edt` (`matiere_id`),
  KEY `fk_salle_edt` (`salle_id`),
  KEY `fk_salle_physique_edt` (`salle_physique_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `donnees_emploi`
--

INSERT INTO `donnees_emploi` (`id`, `enseignant_id`, `matiere_id`, `salle_id`, `salle_physique_id`) VALUES
(9, 4, 1, 5, 1),
(7, 4, 3, 4, 1),
(1, 4, 3, 5, 1);

-- --------------------------------------------------------

--
-- Structure de la table `donnees_emploi2`
--

DROP TABLE IF EXISTS `donnees_emploi2`;
CREATE TABLE IF NOT EXISTS `donnees_emploi2` (
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
-- Déchargement des données de la table `donnees_emploi2`
--

INSERT INTO `donnees_emploi2` (`id`, `enseignant_id`, `matiere_id`, `salle_id`, `jour`, `creneau`) VALUES
(1, 4, 2, 2, 'Mercredi', 'matin');

-- --------------------------------------------------------

--
-- Structure de la table `emploi`
--

DROP TABLE IF EXISTS `emploi`;
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
  KEY `niveau_id` (`niveau_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `emplois_generes`
--

DROP TABLE IF EXISTS `emplois_generes`;
CREATE TABLE IF NOT EXISTS `emplois_generes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `enseignant_id` int NOT NULL,
  `matiere_id` int NOT NULL,
  `salle_id` int NOT NULL,
  `jour` varchar(50) NOT NULL,
  `creneau` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `enseignant_id` (`enseignant_id`),
  KEY `matiere_id` (`matiere_id`),
  KEY `salle_id` (`salle_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `emploi_du_temps`
--

DROP TABLE IF EXISTS `emploi_du_temps`;
CREATE TABLE IF NOT EXISTS `emploi_du_temps` (
  `id` int NOT NULL AUTO_INCREMENT,
  `enseignant_id` int NOT NULL,
  `matiere_id` int NOT NULL,
  `salle_physique_id` int NOT NULL,
  `classe_id` int NOT NULL,
  `jour` varchar(20) NOT NULL,
  `creneau` varchar(50) NOT NULL,
  `heure_debut` time NOT NULL,
  `heure_fin` time NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_slot_per_physical_salle` (`jour`,`creneau`,`salle_physique_id`),
  UNIQUE KEY `unique_enseignant_slot` (`jour`,`creneau`,`enseignant_id`),
  UNIQUE KEY `unique_class_slot` (`jour`,`creneau`,`classe_id`),
  KEY `enseignant_id` (`enseignant_id`),
  KEY `matiere_id` (`matiere_id`),
  KEY `salle_physique_id` (`salle_physique_id`),
  KEY `classe_id` (`classe_id`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `emploi_du_temps`
--

INSERT INTO `emploi_du_temps` (`id`, `enseignant_id`, `matiere_id`, `salle_physique_id`, `classe_id`, `jour`, `creneau`, `heure_debut`, `heure_fin`) VALUES
(40, 4, 1, 1, 5, 'Mercredi', '08h00-12h00', '08:00:00', '12:00:00');

-- --------------------------------------------------------

--
-- Structure de la table `enseignants`
--

DROP TABLE IF EXISTS `enseignants`;
CREATE TABLE IF NOT EXISTS `enseignants` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `classe` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `volume_horaire` int DEFAULT NULL,
  `mot_de_passe` varchar(255) DEFAULT NULL,
  `type_evenement` varchar(50) DEFAULT NULL,
  `salle_id` int DEFAULT NULL,
  `matiere` varchar(255) DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `enseignants`
--

INSERT INTO `enseignants` (`id`, `nom`, `classe`, `volume_horaire`, `mot_de_passe`, `type_evenement`, `salle_id`, `matiere`, `email`) VALUES
(0, 'N/A', 'N/A', 4, 'Bonsoir1234', 'Enseignant', NULL, NULL, 'juniorkengne861@gmail.com'),
(1, 'Administrateur Principal', NULL, 0, 'admin24', 'admin', NULL, NULL, 'admin@votredomaine.com'),
(3, 'P', 'B12', 4, '1234', 'enseignant', 2, NULL, 'jeanpierre@gmail.com'),
(4, 'junior', 'B12', 4, '4321', 'enseignant', 4, NULL, 'paulsatre@gmail.com');

-- --------------------------------------------------------

--
-- Structure de la table `etudiants`
--

DROP TABLE IF EXISTS `etudiants`;
CREATE TABLE IF NOT EXISTS `etudiants` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) NOT NULL,
  `prenom` varchar(255) DEFAULT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `classe_id` int DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nom` (`nom`),
  KEY `classe_id` (`classe_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `etudiants`
--

INSERT INTO `etudiants` (`id`, `nom`, `prenom`, `mot_de_passe`, `classe_id`, `email`) VALUES
(4, 'JeanDupond2', 'Pierre', 'mdp1', 5, 'jeanpierre12@gmail.com'),
(5, 'MarieCurie', 'Jason ', 'mdp2', 2, 'marie@gmail.com');

-- --------------------------------------------------------

--
-- Structure de la table `matieres`
--

DROP TABLE IF EXISTS `matieres`;
CREATE TABLE IF NOT EXISTS `matieres` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `duree` int DEFAULT '4',
  `id_enseignant` int DEFAULT NULL,
  `nom_enseignant` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_matiere_enseignant` (`id_enseignant`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `matieres`
--

INSERT INTO `matieres` (`id`, `nom`, `duree`, `id_enseignant`, `nom_enseignant`) VALUES
(1, 'Mathematique', 2, 3, 'P'),
(3, 'Histoire', 4, 4, 'junior'),
(4, 'TPE', 4, NULL, NULL),
(5, 'PAUSE', 2, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `messages_chat`
--

DROP TABLE IF EXISTS `messages_chat`;
CREATE TABLE IF NOT EXISTS `messages_chat` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sender_id` int NOT NULL,
  `sender_type` enum('admin','enseignant','etudiant') NOT NULL,
  `message` text NOT NULL,
  `timestamp` datetime DEFAULT CURRENT_TIMESTAMP,
  `reply_to_message_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reply_to_message_id` (`reply_to_message_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `messages_chat`
--

INSERT INTO `messages_chat` (`id`, `sender_id`, `sender_type`, `message`, `timestamp`, `reply_to_message_id`) VALUES
(1, 3, 'admin', 'hello les gars', '2025-06-16 02:02:26', NULL),
(2, 1, 'admin', 'au fait l\'emploi de temps est disponible', '2025-06-16 02:02:56', NULL),
(3, 3, 'admin', 'merci gar', '2025-06-16 09:12:27', NULL),
(4, 3, 'admin', 'ok boss', '2025-06-16 09:13:03', NULL),
(5, 1, 'admin', 'hello', '2025-06-16 09:19:18', NULL),
(6, 3, 'admin', 'hello gar', '2025-06-16 09:19:51', NULL),
(7, 1, 'admin', 'chacun a vu son emploi de temps perso ?', '2025-06-16 10:31:44', NULL),
(8, 3, 'admin', 'bonjour', '2025-06-16 16:56:07', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `niveaux`
--

DROP TABLE IF EXISTS `niveaux`;
CREATE TABLE IF NOT EXISTS `niveaux` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(50) DEFAULT NULL,
  `effectif` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `salles`
--

DROP TABLE IF EXISTS `salles`;
CREATE TABLE IF NOT EXISTS `salles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `capacite` int DEFAULT NULL,
  `est_indisponible` tinyint DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `salles`
--

INSERT INTO `salles` (`id`, `nom`, `capacite`, `est_indisponible`) VALUES
(0, 'TPE', NULL, 0),
(2, 'Gilles', 212, 0),
(3, 'B12', 210, 0),
(4, 'B1B', 200, 1),
(5, 'B2', 150, 1);

-- --------------------------------------------------------

--
-- Structure de la table `salles_physiques`
--

DROP TABLE IF EXISTS `salles_physiques`;
CREATE TABLE IF NOT EXISTS `salles_physiques` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `capacite` int DEFAULT NULL,
  `est_indisponible` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `nom` (`nom`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `salles_physiques`
--

INSERT INTO `salles_physiques` (`id`, `nom`, `capacite`, `est_indisponible`) VALUES
(1, 'Amphi 1', 100, 0),
(2, 'Amphi 2', 60, 0);

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

DROP TABLE IF EXISTS `utilisateurs`;
CREATE TABLE IF NOT EXISTS `utilisateurs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `type_utilisateur` enum('admin','enseignant','etudiant') NOT NULL DEFAULT 'enseignant',
  PRIMARY KEY (`id`),
  UNIQUE KEY `nom` (`nom`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id`, `nom`, `mot_de_passe`, `type_utilisateur`) VALUES
(1, 'Administrateur', 'adminpass', 'admin'),
(2, 'P', 'enseignantpass', 'enseignant'),
(3, 'EleveTest', 'etudiantpass', 'etudiant');

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `donnees_emploi`
--
ALTER TABLE `donnees_emploi`
  ADD CONSTRAINT `fk_enseignant_edt` FOREIGN KEY (`enseignant_id`) REFERENCES `enseignants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_matiere_edt` FOREIGN KEY (`matiere_id`) REFERENCES `matieres` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_salle_edt` FOREIGN KEY (`salle_id`) REFERENCES `salles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_salle_physique_edt` FOREIGN KEY (`salle_physique_id`) REFERENCES `salles_physiques` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `emplois_generes`
--
ALTER TABLE `emplois_generes`
  ADD CONSTRAINT `emplois_generes_ibfk_1` FOREIGN KEY (`enseignant_id`) REFERENCES `enseignants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `emplois_generes_ibfk_2` FOREIGN KEY (`matiere_id`) REFERENCES `matieres` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `emplois_generes_ibfk_3` FOREIGN KEY (`salle_id`) REFERENCES `salles` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `emploi_du_temps`
--
ALTER TABLE `emploi_du_temps`
  ADD CONSTRAINT `emploi_du_temps_ibfk_1` FOREIGN KEY (`enseignant_id`) REFERENCES `enseignants` (`id`),
  ADD CONSTRAINT `emploi_du_temps_ibfk_3` FOREIGN KEY (`salle_physique_id`) REFERENCES `salles_physiques` (`id`),
  ADD CONSTRAINT `emploi_du_temps_ibfk_4` FOREIGN KEY (`classe_id`) REFERENCES `salles` (`id`);

--
-- Contraintes pour la table `etudiants`
--
ALTER TABLE `etudiants`
  ADD CONSTRAINT `etudiants_ibfk_1` FOREIGN KEY (`classe_id`) REFERENCES `salles` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `matieres`
--
ALTER TABLE `matieres`
  ADD CONSTRAINT `fk_matiere_enseignant` FOREIGN KEY (`id_enseignant`) REFERENCES `enseignants` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `messages_chat`
--
ALTER TABLE `messages_chat`
  ADD CONSTRAINT `messages_chat_ibfk_1` FOREIGN KEY (`reply_to_message_id`) REFERENCES `messages_chat` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
