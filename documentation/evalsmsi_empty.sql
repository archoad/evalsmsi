-- phpMyAdmin SQL Dump
-- version 4.8.5
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost
-- Généré le :  jeu. 02 mai 2019 à 19:39
-- Version du serveur :  10.1.38-MariaDB-0+deb9u1
-- Version de PHP :  7.0.33-0+deb9u3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données :  `evalsmsi`
--
CREATE DATABASE IF NOT EXISTS `evalsmsi` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `evalsmsi`;

-- --------------------------------------------------------

--
-- Structure de la table `assess`
--

DROP TABLE IF EXISTS `assess`;
CREATE TABLE `assess` (
  `id` int(10) UNSIGNED NOT NULL,
  `etablissement` int(11) UNSIGNED NOT NULL,
  `annee` int(4) NOT NULL,
  `reponses` mediumtext NOT NULL,
  `comments` text NOT NULL,
  `comment_graph_par` text NOT NULL,
  `evaluateur` text NOT NULL,
  `valide` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Stocke les résultats d''une évaluation par étab et par année' ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Structure de la table `etablissement`
--

DROP TABLE IF EXISTS `etablissement`;
CREATE TABLE `etablissement` (
  `id` int(10) UNSIGNED NOT NULL,
  `nom` varchar(65) NOT NULL,
  `abrege` varchar(20) NOT NULL,
  `adresse` varchar(80) NOT NULL,
  `ville` varchar(20) NOT NULL,
  `code_postal` int(5) UNSIGNED NOT NULL,
  `regroupement` text NOT NULL,
  `objectifs` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `journal`
--

DROP TABLE IF EXISTS `journal`;
CREATE TABLE `journal` (
  `id` int(10) UNSIGNED NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip` varchar(30) NOT NULL,
  `etablissement` int(10) UNSIGNED NOT NULL,
  `navigateur` varchar(150) NOT NULL,
  `user` varchar(20) NOT NULL,
  `action` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `lexique`
--

DROP TABLE IF EXISTS `lexique`;
CREATE TABLE `lexique` (
  `id` int(10) UNSIGNED NOT NULL,
  `term_en` varchar(50) NOT NULL,
  `term_fr` varchar(50) NOT NULL,
  `definition_en` text NOT NULL,
  `definition_fr` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `lexique`
--

INSERT INTO `lexique` (`id`, `term_en`, `term_fr`, `definition_en`, `definition_fr`) VALUES
(1, 'Asset', 'Ressource, Actif', 'Anything that has value to the organization.', 'Tout élément représentant de la valeur pour l\'organisme.'),
(2, 'Availability', 'Disponibilité', 'The property of being accessible and usable upon demand by an authorized entity.', 'Propriété d\'être accessible et utilisable, à la demande, par une entité autorisée.'),
(3, 'Confidentiality', 'Confidentialité', 'The property that information is not available or disclosed to unauthorized individuals, entities, or processes.', 'Propriété selon laquelle l\'information n\'est pas disponible ou divulguée à des individus, entités ou processus non autorisés.'),
(4, 'Information security', 'Sécurité de l\'information', 'Preservation of confidentiality, integrity and availability of information. In addition, other properties such as authenticity, accountability, non-repudiation and reliability can also be involved.', 'Préservation de la confidentialité, de l\'intégrité et de la disponibilité de l\'information. En outre, d\'autres propriétés telles que l\'authenticité, la responsabilité, la non-répudiation et la fiabilité peuvent aussi être prises en compte.'),
(5, 'Information security event', 'Evénement en sécurité de l\'information', 'An identified occurrence of a system, service or network state indicating a possible breach of information security policy or failure of safeguards, or a previously unknown situation that may be security relevant.', 'Occurence identifiée d\'un état d\'un système, d\'un service ou d\'un réseau, indiquant une possible violation de la politique de sécurité de l\'information ou d\'un échec des garanties, ou d\'une situation inconnue précédemment qui peut relever de la sécurité.'),
(6, 'Information security incident', 'Incident en sécurité de l\'information', 'A single or a series of unwanted or unexpected information security events that have a signicant probability of compromising business operations and threatening information security.', 'Un événement ou une série d\'évènements en sécurité de l\'information, indésirables ou inattendues qui ont une probabilité significative de compromettre l\'activité de l\'entreprise et de menacer la sécurité  de l\'information.'),
(7, 'Information security management system', 'Système de management de la sécurité de l\'informat', 'That part of the overall management system, based on a business risk approach, to establish, implement, operate, monitor, review, maintain and improve information security.', 'Partie de l\'ensemble du système de management de l\'entreprise, fondée sur l\'analyse des risques, en vue d\'établir, de mettre en œuvre, d\'exploiter, de surveiller, de rééexaminer, de maintenir et d\'améliorer la sécurité de l\'information.'),
(8, 'Integrity', 'Intégrité', 'The property of safeguarding the accuracy and completeness of assets.', 'Propriété de protection de l\'exactitude et de l\'exhaustivité des ressources.'),
(9, 'Residual risk', 'Risque résiduel', 'The risk remaining after the risk treatment.', 'Le risque restant après le traitement des risques.'),
(10, 'Risk acceptance', 'Risque acceptable', 'Decision to accept a risk.', 'Décision d\'accepter un risque.'),
(11, 'Risk analysis', 'Analyse du risque', 'Systematic use of information to identify sources and to estimate the risk.', 'Usage systématique d\'informations pour identifier les sources et pour estimer le risque.'),
(12, 'Risk assessment', 'Appréciation du risque', 'Overall process of risk analysis and risk evaluation.', 'Processus global de l\'analyse du risque et de l\'évaluation du risque.'),
(13, 'Risk evaluation', 'Evaluation du risque', 'Process of comparing the estimated risk against given risk criteria to determine the significance of the risk.', 'Processus de comparaison du risque estimé avec des critères de risque donnés afin d\'en déterminer l\'importance.'),
(14, 'Risk management', 'Management du risque', 'Coordinated activities to direct and control an organization with regard to risk.', 'Activités coordonnées de direction et de contrôle d\'un organisme en prenant en compte la notion de risque.'),
(15, 'Risk treatment', 'Traitement du risque', 'Process of selection and implementation of measures to modify risk.', 'Processus de sélection et de mise en œuvre de mesures visant à modifier le risque.'),
(16, 'Statement of applicability', 'Déclaration d\'applicabilité', 'Documented statement describing the control objectives and controls that are relevant and applicable to the organization\'s ISMS.', 'Documents de déclaration décrivant les objectifs de sécurité et les contrôles qui sont pertinents et applicables au SMSI d\'un organisme.');

-- --------------------------------------------------------

--
-- Structure de la table `paragraphe`
--

DROP TABLE IF EXISTS `paragraphe`;
CREATE TABLE `paragraphe` (
  `id` int(10) UNSIGNED NOT NULL,
  `numero` tinyint(3) UNSIGNED NOT NULL,
  `libelle` varchar(70) NOT NULL,
  `abrege` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `question`
--

DROP TABLE IF EXISTS `question`;
CREATE TABLE `question` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_paragraphe` int(11) UNSIGNED NOT NULL,
  `id_sub_paragraphe` int(11) UNSIGNED NOT NULL,
  `numero` tinyint(3) UNSIGNED NOT NULL,
  `libelle` text NOT NULL,
  `mesure` text NOT NULL,
  `poids` tinyint(3) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Structure de la table `role`
--

DROP TABLE IF EXISTS `role`;
CREATE TABLE `role` (
  `id` int(10) UNSIGNED NOT NULL,
  `intitule` varchar(20) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `role`
--

INSERT INTO `role` (`id`, `intitule`) VALUES
(1, 'Administrateur'),
(2, 'Auditeur'),
(3, 'Directeur'),
(4, 'RSSI');

-- --------------------------------------------------------

--
-- Structure de la table `sub_paragraphe`
--

DROP TABLE IF EXISTS `sub_paragraphe`;
CREATE TABLE `sub_paragraphe` (
  `id` int(10) UNSIGNED NOT NULL,
  `numero` tinyint(3) UNSIGNED NOT NULL,
  `id_paragraphe` int(10) UNSIGNED NOT NULL,
  `libelle` varchar(100) NOT NULL,
  `comment` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `role` int(11) UNSIGNED NOT NULL,
  `nom` varchar(20) NOT NULL,
  `prenom` varchar(20) NOT NULL,
  `etablissement` text NOT NULL,
  `login` varchar(50) NOT NULL,
  `password` varchar(60) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `role`, `nom`, `prenom`, `etablissement`, `login`, `password`) VALUES
(1, 1, 'Dubois', 'Michel', '0', 'admin', '$2y$10$LtUv6TONg5AZyQ3E.YxhlexuAjk0nlZDmXE9107qo/VMplcIcKfyi');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `assess`
--
ALTER TABLE `assess`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `etablissement`
--
ALTER TABLE `etablissement`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `journal`
--
ALTER TABLE `journal`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `lexique`
--
ALTER TABLE `lexique`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `paragraphe`
--
ALTER TABLE `paragraphe`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `question`
--
ALTER TABLE `question`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `role`
--
ALTER TABLE `role`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `sub_paragraphe`
--
ALTER TABLE `sub_paragraphe`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `assess`
--
ALTER TABLE `assess`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `etablissement`
--
ALTER TABLE `etablissement`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `journal`
--
ALTER TABLE `journal`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `lexique`
--
ALTER TABLE `lexique`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT pour la table `paragraphe`
--
ALTER TABLE `paragraphe`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `question`
--
ALTER TABLE `question`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=184;

--
-- AUTO_INCREMENT pour la table `role`
--
ALTER TABLE `role`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT pour la table `sub_paragraphe`
--
ALTER TABLE `sub_paragraphe`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
