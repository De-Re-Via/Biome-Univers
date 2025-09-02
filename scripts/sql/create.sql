-- Créer/ouvrir la base biome_univers
CREATE DATABASE IF NOT EXISTS biome_univers
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE biome_univers;

-- Table utilisateurs avec rôles > création
DROP TABLE IF EXISTS users;
CREATE TABLE users (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name           VARCHAR(100)                 NOT NULL,
  email          VARCHAR(190)                 NOT NULL,
  password_hash  VARCHAR(255)                 NOT NULL,
  role           ENUM('explorer','explorer_master','admin')
                                               NOT NULL DEFAULT 'explorer',
  is_active      TINYINT(1)                   NOT NULL DEFAULT 1,
  last_login     DATETIME                     NULL,
  created_at     TIMESTAMP                    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP                    NOT NULL DEFAULT CURRENT_TIMESTAMP
                                               ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT uq_users_email UNIQUE (email),
  INDEX idx_users_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



-- Création / gestion utilisateurs avec rôle
USE biome_univers;

-- Mot de passe = demo123 (hash bcrypt réutilisable pour les trois)
-- Tu pourras changer les mdp plus tard via l’interface.
INSERT INTO users (name, email, password_hash, role) VALUES
('Admin',           'admin@biome.local',  '$2y$10$uSaBz4J6mK2iUxbSM9U3lO2wSg7t8E6q7ZxQ0T2ha0h3tB2gk7m7W', 'admin'),
('ExplorerMaster1','em1@biome.local',     '$2y$10$uSaBz4J6mK2iUxbSM9U3lO2wSg7t8E6q7ZxQ0T2ha0h3tB2gk7m7W', 'explorer_master'),
('Explorer1',     'exp1@biome.local', '$2y$10$uSaBz4J6mK2iUxbSM9U3lO2wSg7t8E6q7ZxQ0T2ha0h3tB2gk7m7W', 'explorer');




-- Promouvoir un Explorer en ExplorerMaster
UPDATE users SET role = 'explorer_master' WHERE id = 3;  -- remplace 3 par l'id visé

-- Redescendre un ExplorerMaster en Explorer
UPDATE users SET role = 'explorer' WHERE id = 2;

-- Nommer/déposer un Admin
UPDATE users SET role = 'admin' WHERE id = 3;
UPDATE users SET role = 'explorer' WHERE id = 1;         -- prudence : ne pas te détruire l’accès admin !

-- Activer/Désactiver un compte sans le supprimer
UPDATE users SET is_active = 0 WHERE id = 5;  -- désactive
UPDATE users SET is_active = 1 WHERE id = 5;  -- réactive

-- Lister les rôles rapidement
SELECT id, name, email, role, is_active FROM users ORDER BY role, id;



-- Problème de Hash des mdp créé, mettre le même mot de passe que Explorer2 à Admin + ExplorerMaster + Explorer1
UPDATE users u_target
JOIN users u_src ON u_src.email = 'exp2@biome.local'
SET u_target.password_hash = u_src.password_hash
WHERE u_target.email IN ('admin@biome.local','em1@biome.local', 'exp1@biome.local');
