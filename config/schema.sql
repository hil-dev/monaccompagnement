-- Schéma de la base de données : OrientaSup
CREATE DATABASE IF NOT EXISTS apresbac CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE apresbac;

-- Utilisateurs (auth Google OU email/mdp)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    google_id VARCHAR(255) NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NULL, -- NULL si connexion Google uniquement
    auth_provider ENUM('google', 'email') NOT NULL DEFAULT 'email',
    email_verified TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Formules d'accompagnement
CREATE TABLE IF NOT EXISTS formules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code ENUM('premium', 'vip', 'gold') NOT NULL UNIQUE,
    nom VARCHAR(50) NOT NULL,
    prix INT NOT NULL, -- en FCFA
    avantages JSON NOT NULL, -- liste des avantages
    places_totales INT NOT NULL,
    places_restantes INT NOT NULL,
    actif TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- Profils bacheliers (infos du formulaire)
CREATE TABLE IF NOT EXISTS candidats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    serie VARCHAR(20) NOT NULL,
    telephone VARCHAR(20) NOT NULL,
    mention ENUM('Passable', 'Assez Bien', 'Bien', 'Très Bien', 'Excellent') NOT NULL,
    formule_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (formule_id) REFERENCES formules(id)
) ENGINE=InnoDB;

-- Paiements FedaPay
CREATE TABLE IF NOT EXISTS paiements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    candidat_id INT NOT NULL,
    fedapay_transaction_id VARCHAR(100) NULL,
    montant INT NOT NULL,
    statut ENUM('en_attente', 'reussi', 'echoue', 'annule') NOT NULL DEFAULT 'en_attente',
    reference VARCHAR(100) NOT NULL UNIQUE, -- référence interne
    pdf_genere TINYINT(1) NOT NULL DEFAULT 0,
    pdf_path VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (candidat_id) REFERENCES candidats(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Données initiales des formules (prix à ajuster avec Jeicke)
INSERT INTO formules (code, nom, prix, avantages, places_totales, places_restantes) VALUES
('premium', 'Premium', 15000, JSON_ARRAY(
    'Bilan d’orientation personnalisé',
    'Accès au guide des filières',
    'Support par email'
), 100, 100),
('vip', 'VIP', 30000, JSON_ARRAY(
    'Tout Premium',
    'Session live avec un conseiller',
    'Simulation de dossier Parcoursup/APB',
    'Support prioritaire WhatsApp'
), 50, 50),
('gold', 'Gold', 50000, JSON_ARRAY(
    'Tout VIP',
    'Accompagnement individuel 1-to-1',
    'Suivi jusqu’à l’inscription définitive',
    'Accès à vie à la communauté'
), 25, 25)
ON DUPLICATE KEY UPDATE nom = VALUES(nom);
