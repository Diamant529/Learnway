# Cahier des Charges - Plateforme LMS Learn Way

Ce document définit les spécifications fonctionnelles, les rôles des utilisateurs, les règles de gestion et les exigences ergonomiques pour la plateforme d'apprentissage en ligne **Learn Way**.

---

## 1. Présentation du Projet
**Learn Way** est un système de gestion de l'apprentissage (LMS - Learning Management System) conçu pour structurer et moderniser le suivi pédagogique. La plateforme permet :
* Aux **Promoteurs** de piloter l'offre de formation, de superviser les enseignants et d'attribuer des certifications.
* Aux **Enseignants** de structurer leurs cours sous forme de leçons riches (vidéos, PDF) et d'évaluations interactives (QCM).
* Aux **Étudiants** de s'inscrire à des modules, de consulter les contenus et de tester leurs connaissances pour progresser et obtenir des certificats.

---

## 2. Rôles et Droits des Utilisateurs

La plateforme repose sur trois rôles principaux, avec une gestion stricte des droits d'accès.

### 2.1. Le Promoteur Learn Way
C'est l'administrateur de la plateforme. Ses droits incluent :
* **Gestion des modules** : Création, modification et suppression des modules de formation.
* **Gestion des enseignants** : Attribution d'un ou plusieurs enseignants à des modules spécifiques.
* **Supervision globale** : Visualisation des statistiques globales (nombre d'étudiants inscrits, taux de réussite, certificats émis).
* **Suivi des étudiants** : Consultation de la progression détaillée de chaque étudiant sur chaque module.
* **Gestion des certificats** : Suivi et validation des certificats générés.

### 2.2. L'Enseignant
Il est le créateur du contenu pédagogique. Ses droits incluent :
* **Gestion des cours** : Création, modification et suppression de cours au sein des modules qui lui sont attribués.
* **Découpage en leçons** : Structuration de chaque cours en plusieurs leçons successives.
* **Enrichissement du contenu** : Téléchargement de documents PDF de support et de fichiers vidéo (MP4) pour chaque leçon.
* **Gestion des évaluations** : Pour chaque leçon, l'enseignant peut concevoir un Questionnaire à Choix Multiples (QCM) :
  * Ajout de questions.
  * Définition de plusieurs options de réponse.
  * Spécification des bonnes réponses.
  * Attribution d'un score/nombre de points spécifique à chaque question.

### 2.3. L'Étudiant
Il est l'apprenant. Ses droits incluent :
* **Consultation du catalogue** : Visualisation de l'ensemble des modules disponibles sur Learn Way.
* **Inscription** : Inscription libre à un module de formation.
* **Suivi de cours** : Consultation des cours, lecture en ligne des documents PDF et visionnage des cours vidéo.
* **Passage des évaluations** : Réponse aux QCM associés aux leçons pour évaluer ses connaissances.
* **Suivi de progression** : Visualisation en temps réel de son pourcentage de progression par leçon, par cours et par module.
* **Téléchargement des certificats** : Obtention automatique du certificat de réussite au format PDF une fois le module entièrement validé (100% de progression globale).

---

## 3. Règles de Gestion et Algorithmes

### 3.1. Calcul de la Progression
La progression d'un étudiant sur Learn Way est structurée de manière dynamique à deux niveaux principaux : la leçon et le module.

#### A. Progression d'une leçon
La progression pour une leçon dépend directement du score obtenu lors de l'évaluation (QCM) associée à cette leçon.
* **Formule** :
  $$\text{Progression Leçon (\%)} = \frac{\text{Score obtenu}}{\text{Score maximum du QCM}} \times 100$$
* **Exemple** :
  * Si l'évaluation comporte 3 questions valant au total 20 points, et que l'étudiant obtient 10 points, sa progression sur cette leçon est de **50 %**.
  * Si l'étudiant obtient 20 points, sa progression sur cette leçon est de **100 %**.
* **Remarque** : Si une leçon ne contient pas d'évaluation (QCM non encore créé par l'enseignant), sa progression est par défaut considérée comme validée à 100 % dès lors que l'étudiant a consulté l'intégralité du contenu (visionné la vidéo et ouvert le PDF).

#### B. Progression globale du module
La progression globale au sein d'un module est la moyenne arithmétique de la progression de toutes les leçons de tous les cours qui le composent.
* **Formule** :
  $$\text{Progression Module (\%)} = \frac{\sum \text{Progression de chaque leçon}}{\text{Nombre total de leçons dans le module}}$$

### 3.2. Validation du Module et Certification
* **Condition de réussite** : Un étudiant est considéré comme ayant validé un module lorsque sa progression globale sur le module atteint **100 %**. Cela signifie qu'il a obtenu 100 % de réussite à toutes les évaluations de toutes les leçons du module.
* **Génération de certificat** : Dès que la condition de réussite est remplie, le système génère instantanément un certificat PDF officiel **Learn Way** téléchargeable par l'étudiant depuis son tableau de bord.
* **Éléments obligatoires du certificat** :
  1. **Nom et prénom** de l'étudiant.
  2. **Nom du module** validé.
  3. **Date de validation** de la certification.
  4. **Signature numérique** (représentation graphique de la signature officielle de Learn Way / du Promoteur + empreinte de sécurité).
  5. **Numéro unique de certificat** généré sous la forme : `LW-YYYYMMDD-[ID_ETUDIANT]-[ID_MODULE]-[HASH_UNIQUE]`.

---

## 4. Exigences Non Fonctionnelles et Ergonomie

### 4.1. Design et Identité Visuelle
* **Nom de marque** : **Learn Way** doit apparaître sur tous les en-têtes, titres, bas de page et métadonnées.
* **Esthétique** : Interface haut de gamme, moderne et épurée. Utilisation d'une palette de couleurs harmonieuse (bleu indigo/navy pour le sérieux académique, touches de vert émeraude pour la validation et le succès, dégradés subtils).
* **Typographie** : Polices modernes (ex: *Inter* ou *Outfit* importées de Google Fonts) avec une hiérarchie visuelle stricte.
* **Micro-animations** : Transitions fluides sur les boutons, effets de survol (hover) sur les cartes de cours, barres de progression animées.

### 4.2. Ergonomie et Adaptabilité
* **Design Responsive** : Compatibilité totale sur mobile (smartphones), tablettes et ordinateurs de bureau.
* **Structure de navigation** :
  * Une barre latérale (**Sidebar**) pour l'accès rapide aux sections principales.
  * Une barre de navigation supérieure (**Navbar**) pour le profil utilisateur et les notifications.
  * Des tableaux de bord organisés sous forme de cartes d'indicateurs clés (KPIs).

### 4.3. Sécurité et Performance
* **Authentification** : Gestion des sessions PHP sécurisées avec régénération de l'ID de session à la connexion pour éviter les attaques de fixation de session.
* **Sécurisation de la base de données** : Utilisation exclusive de requêtes préparées avec PDO pour interdire les injections SQL.
* **Hachage des mots de passe** : Utilisation de l'algorithme robuste `PASSWORD_DEFAULT` en PHP (BCRYPT).
* **Validation de formulaires** : Validation côté client (HTML5, JS) et côté serveur (PHP) de tous les champs d'entrée.
