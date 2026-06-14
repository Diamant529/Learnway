-- =====================================================================
-- Script d'initialisation de la base de données de Learn Way
-- Nom de la base : learnway_db
-- DBMS : MySQL / MariaDB
-- =====================================================================

-- (La base de données est gérée et sélectionnée automatiquement par l'application/Railway)

-- Désactivation temporaire des contraintes de clé étrangère pour la réinitialisation
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- 1. Suppression des tables si elles existent
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `certifications`;
DROP TABLE IF EXISTS `evaluation_attempts`;
DROP TABLE IF EXISTS `enrollments`;
DROP TABLE IF EXISTS `module_teachers`;
DROP TABLE IF EXISTS `options`;
DROP TABLE IF EXISTS `questions`;
DROP TABLE IF EXISTS `evaluations`;
DROP TABLE IF EXISTS `lessons`;
DROP TABLE IF EXISTS `courses`;
DROP TABLE IF EXISTS `modules`;
DROP TABLE IF EXISTS `users`;

-- Réactivation des contraintes de clé étrangère
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- 2. Création des tables
-- ---------------------------------------------------------------------

-- Table: users (Utilisateurs de la plateforme)
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(180) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `role` ENUM('promoteur', 'enseignant', 'etudiant') NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: modules (Modules de formation créés par le Promoteur)
CREATE TABLE `modules` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `created_by` INT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_modules_creator` FOREIGN KEY (`created_by`)
        REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: courses (Cours associés à un module)
CREATE TABLE `courses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `module_id` INT NOT NULL,
    `created_by` INT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_courses_module` FOREIGN KEY (`module_id`)
        REFERENCES `modules` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_courses_creator` FOREIGN KEY (`created_by`)
        REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: lessons (Leçons constituant un cours)
CREATE TABLE `lessons` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `pdf_path` VARCHAR(255) NULL,
    `video_path` VARCHAR(255) NULL,
    `position` INT NOT NULL DEFAULT 1,
    `course_id` INT NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_lessons_course` FOREIGN KEY (`course_id`)
        REFERENCES `courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: evaluations (Une évaluation de type QCM associée à une leçon - 1:1)
CREATE TABLE `evaluations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `lesson_id` INT NOT NULL UNIQUE,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_evaluations_lesson` FOREIGN KEY (`lesson_id`)
        REFERENCES `lessons` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: questions (Questions de l'évaluation QCM)
CREATE TABLE `questions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `question_text` TEXT NOT NULL,
    `points` INT NOT NULL DEFAULT 1,
    `evaluation_id` INT NOT NULL,
    CONSTRAINT `fk_questions_evaluation` FOREIGN KEY (`evaluation_id`)
        REFERENCES `evaluations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: options (Options de réponse pour chaque question)
CREATE TABLE `options` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `option_text` VARCHAR(255) NOT NULL,
    `is_correct` TINYINT(1) NOT NULL DEFAULT 0,
    `question_id` INT NOT NULL,
    CONSTRAINT `fk_options_question` FOREIGN KEY (`question_id`)
        REFERENCES `questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table d'association: module_teachers (Attribution des enseignants aux modules)
CREATE TABLE `module_teachers` (
    `module_id` INT NOT NULL,
    `teacher_id` INT NOT NULL,
    `assigned_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`module_id`, `teacher_id`),
    CONSTRAINT `fk_mt_module` FOREIGN KEY (`module_id`)
        REFERENCES `modules` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_mt_teacher` FOREIGN KEY (`teacher_id`)
        REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table d'association: enrollments (Inscriptions des étudiants aux modules)
CREATE TABLE `enrollments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `module_id` INT NOT NULL,
    `enrolled_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_student_module` (`student_id`, `module_id`),
    CONSTRAINT `fk_enrollments_student` FOREIGN KEY (`student_id`)
        REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_enrollments_module` FOREIGN KEY (`module_id`)
        REFERENCES `modules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: evaluation_attempts (Tentatives de QCM et scores par leçon/évaluation)
CREATE TABLE `evaluation_attempts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `evaluation_id` INT NOT NULL,
    `score_obtained` INT NOT NULL,
    `max_score` INT NOT NULL,
    `percentage` FLOAT NOT NULL, -- Utilisé directement pour le calcul de progression de la leçon
    `passed_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_attempts_student` FOREIGN KEY (`student_id`)
        REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_attempts_evaluation` FOREIGN KEY (`evaluation_id`)
        REFERENCES `evaluations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: certifications (Certificats générés pour les étudiants)
CREATE TABLE `certifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `certificate_number` VARCHAR(100) NOT NULL UNIQUE,
    `student_id` INT NOT NULL,
    `module_id` INT NOT NULL,
    `issue_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `signature_hash` VARCHAR(64) NOT NULL,
    UNIQUE KEY `uk_certif_student_module` (`student_id`, `module_id`),
    CONSTRAINT `fk_certifications_student` FOREIGN KEY (`student_id`)
        REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_certifications_module` FOREIGN KEY (`module_id`)
        REFERENCES `modules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3. Insertion de données de test (Seeds)
--    Note : Le mot de passe pour tous les comptes de test est 'password123'
--           Le hash standard bcrypt est $2y$10$w8gZ457.lYdK32aJ.D/7tOux0iV2U4C2gR9vM.RkS5j67Y94N4s06
--           ($2y$10$... est le hash bcrypt généré par password_hash('password123', PASSWORD_DEFAULT))
-- ---------------------------------------------------------------------

-- Utilisateurs (Promoteur, Enseignants)
INSERT INTO `users` (`id`, `email`, `password`, `first_name`, `last_name`, `role`) VALUES
(1, 'erwin.fudjing@facsciences-uy1.cm', '$2y$12$3KTCYgEfmkB7/HtaxebSoumRtcpAZWItW/ZidpkFvE9O0dLNtihI2', 'Erwin', 'Fudjing', 'promoteur'),
(2, 'prof.martin@learnway.com', '$2y$12$2y2mamJSYHCRJC//2DbYYuyLt7eR4mwtQEsAFU1kcqYKhBcx5RCFS', 'Alice', 'Martin', 'enseignant');

-- Modules de formation
INSERT INTO `modules` (`id`, `title`, `description`, `created_by`) VALUES
(1, 'Introduction au Développement Web', 'Apprenez les bases du Web : HTML, CSS et JavaScript pour concevoir des sites responsives et interactifs.', 1),
(2, 'Algorithmique et Programmation en PHP', 'Découvrez les bases de l\'algorithme et de la programmation avec le langage PHP et la base de données MySQL.', 1);

-- Attribution des enseignants aux modules
INSERT INTO `module_teachers` (`module_id`, `teacher_id`) VALUES
(1, 2), -- Prof Martin sur le module Développement Web
(2, 2); -- Prof Martin sur le module PHP

-- Inscriptions (aucune par défaut car aucun étudiant pré-configuré)

-- Cours pour le module 1 (Développement Web)
INSERT INTO `courses` (`id`, `title`, `description`, `module_id`, `created_by`) VALUES
(1, 'Bases du HTML5', 'Comprendre la structure logique d\'une page web à l\'aide des balises sémantiques.', 1, 2),
(2, 'Stylisation avec CSS3', 'Mettre en forme vos pages web avec les feuilles de style CSS.', 1, 2);

-- Leçons pour le cours 1 (Bases du HTML5)
INSERT INTO `lessons` (`id`, `title`, `description`, `pdf_path`, `video_path`, `position`, `course_id`) VALUES
(1, 'Introduction aux balises sémantiques', 'Découvrir la structure de base d\'un fichier HTML5 et les balises article, section, header, footer.', NULL, NULL, 1, 1),
(2, 'Création de formulaires modernes', 'Construire des formulaires interactifs avec les types d\'entrées avancés de HTML5.', NULL, NULL, 2, 1);

-- Leçons pour le cours 2 (Stylisation avec CSS3)
INSERT INTO `lessons` (`id`, `title`, `description`, `pdf_path`, `video_path`, `position`, `course_id`) VALUES
(3, 'Le modèle de boîte et Flexbox', 'Comprendre le Box Model et apprendre à aligner les éléments de manière moderne.', NULL, NULL, 1, 2);

-- Évaluations (QCM) pour les leçons
INSERT INTO `evaluations` (`id`, `title`, `lesson_id`) VALUES
(1, 'Évaluation sur les Balises Sémantiques', 1),
(2, 'Évaluation sur les Formulaires HTML5', 2),
(3, 'Évaluation sur Flexbox et modèle de boîte', 3);

-- Questions pour l'évaluation 1 (Balises Sémantiques)
INSERT INTO `questions` (`id`, `question_text`, `points`, `evaluation_id`) VALUES
(1, 'Quelle balise HTML5 est la plus appropriée pour un contenu autonome pouvant être distribué indépendamment ?', 5, 1),
(2, 'Laquelle de ces balises n\'est pas une nouveauté sémantique de HTML5 ?', 5, 1);

-- Options pour la question 1
INSERT INTO `options` (`id`, `option_text`, `is_correct`, `question_id`) VALUES
(1, '<section>', 0, 1),
(2, '<article>', 1, 1),
(3, '<div>', 0, 1),
(4, '<aside>', 0, 1);

-- Options pour la question 2
INSERT INTO `options` (`id`, `option_text`, `is_correct`, `question_id`) VALUES
(5, '<header>', 0, 2),
(6, '<nav>', 0, 2),
(7, '<table>', 1, 2),
(8, '<main>', 0, 2);

-- Questions pour l'évaluation 2 (Formulaires HTML5)
INSERT INTO `questions` (`id`, `question_text`, `points`, `evaluation_id`) VALUES
(3, 'Quel type d\'input HTML5 permet de sélectionner une couleur à l\'aide d\'un sélecteur natif ?', 10, 2);

-- Options pour la question 3
INSERT INTO `options` (`id`, `option_text`, `is_correct`, `question_id`) VALUES
(9, 'type="palette"', 0, 3),
(10, 'type="rgb"', 0, 3),
(11, 'type="color"', 1, 3),
(12, 'type="hex"', 0, 3);

-- Questions pour l'évaluation 3 (Flexbox et modèle de boîte)
INSERT INTO `questions` (`id`, `question_text`, `points`, `evaluation_id`) VALUES
(4, 'Quelle propriété CSS permet de définir l\'alignement des éléments le long de l\'axe principal d\'un conteneur Flexbox ?', 10, 3);

-- Options pour la question 4
INSERT INTO `options` (`id`, `option_text`, `is_correct`, `question_id`) VALUES
(13, 'align-items', 0, 4),
(14, 'justify-content', 1, 4),
(15, 'flex-direction', 0, 4),
(16, 'grid-gap', 0, 4);
