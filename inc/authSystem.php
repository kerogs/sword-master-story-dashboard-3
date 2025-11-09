<?php

/**
 * @file authSystem.php
 * @author kerogs'
 * @version 3.0
 * @date 2025-11-06
 * @update 2025-11-08
 * 
 * @description Système d'authentification sécurisé avec gestion des sessions et tokens
 * 
 */

use Dotenv\Dotenv;

class AuthSystem
{
    private $pdo;
    private $cookie_name = 'auth_token';
    private $cookie_duration = 30 * 24 * 60 * 60; // 30 jours en secondes

    public function __construct()
    {
        $this->loadEnv();
        $this->connect();
        $this->createTables();
    }

    /**
     * Charge les variables d'environnement à partir du fichier .env
     * Vérifie que les variables essentielles (KAS_DB_HOST, KAS_DB_NAME, KAS_DB_USER, KAS_DB_PASS, KAS_DB_PREFIX) existent
     * @throws \Exception si une variable essentielle est manquante
     */
    private function loadEnv()
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->load();

        // Vérifie que les variables essentielles existent
        foreach (['KAS_DB_HOST', 'KAS_DB_NAME', 'KAS_DB_USER', 'KAS_DB_PASS', 'KAS_DB_PREFIX'] as $var) {
            if (!isset($_ENV[$var])) {
                throw new Exception("Variable d'environnement manquante : $var");
            }
        }
    }

    /**
     * Établit une connexion à la base de données en utilisant les informations de connexion stockées dans les variables d'environnement
     * 
     * @throws \Exception si la connexion à la base de données échoue
     */
    private function connect()
    {
        $host   = $_ENV['KAS_DB_HOST'];
        $db     = $_ENV['KAS_DB_NAME'];
        $user   = $_ENV['KAS_DB_USER'];
        $pass   = $_ENV['KAS_DB_PASS'];

        try {
            $this->pdo = new PDO(
                "mysql:host=$host;dbname=$db;charset=utf8mb4",
                $user,
                $pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            error_log('Erreur de connexion DB: ' . $e->getMessage());
            throw new Exception('Erreur de connexion à la base de données');
        }
    }

    /**
     * Création des tables si elles n'existent pas
     */
    private function createTables()
    {
        $prefix = $_ENV['KAS_DB_PREFIX'];

        // Table des utilisateurs - CORRIGÉE
        $this->pdo->exec("
        CREATE TABLE IF NOT EXISTS {$prefix}users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(100),
            picture VARCHAR(255) DEFAULT NULL,
            role_level INT DEFAULT 0,
            email_confirm BOOLEAN DEFAULT FALSE,
            titles JSON,  -- SUPPRIMÉ: DEFAULT '[]'
            level INT DEFAULT 0,
            json_data JSON,  -- SUPPRIMÉ: DEFAULT '{}'
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_login DATETIME NULL,
            is_active BOOLEAN DEFAULT TRUE,
            failed_attempts INT DEFAULT 0,
            locked_until DATETIME NULL,
            api_key VARCHAR(255) DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

        // Table des tokens de session
        $this->pdo->exec("
        CREATE TABLE IF NOT EXISTS {$prefix}user_sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token VARCHAR(64) NOT NULL UNIQUE,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            expires_at DATETIME NOT NULL,
            user_agent VARCHAR(255),
            ip_address VARCHAR(45),
            FOREIGN KEY (user_id) REFERENCES {$prefix}users(id) ON DELETE CASCADE,
            INDEX idx_token (token),
            INDEX idx_expires (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    }


    /**
     * Enregistre un nouvel utilisateur avec un mot de passe hashé.
     *
     * Vérifie si les données sont valides avant de procéder à l'enregistrement.
     * Vérifie également si l'utilisateur existe déjà.
     *
     * @param string $username Le nom d'utilisateur.
     * @param string $password Le mot de passe.
     * @param string $email L'email de l'utilisateur (facultatif).
     * @return array Un tableau contenant un booléen 'success' et un message d'erreur.
     */
    public function register($username, $password, $email = null)
    {
        if (!$this->validateUsername($username) || !$this->validatePassword($password)) {
            return ['success' => false, 'message' => 'Données invalides'];
        }

        if ($email && !$this->validateEmail($email)) {
            return ['success' => false, 'message' => 'Email invalide'];
        }

        try {
            $this->pdo->beginTransaction();

            $prefix = $_ENV['KAS_DB_PREFIX'];

            // Vérifie si l'utilisateur existe déjà
            $stmt = $this->pdo->prepare("SELECT id FROM {$prefix}users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);

            if ($stmt->fetch()) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'Nom d\'utilisateur ou email déjà utilisé'];
            }

            // Hash du mot de passe
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            // Valeurs par défaut
            $default_picture = "https://api.dicebear.com/9.x/thumbs/svg?seed=" . urlencode($username);

            // Insertion avec toutes les colonnes
            $stmt = $this->pdo->prepare("
            INSERT INTO {$prefix}users (
                username, password, email, picture, role_level, email_confirm,
                titles, level, json_data
            ) VALUES (?, ?, ?, ?, ?, ?, NULL, ?, NULL)
        ");

            $result = $stmt->execute([
                $username,
                $password_hash,
                $email,
                $default_picture,
                0,    // role_level
                0, // email_confirm
                0     // level
            ]);

            if ($result) {
                $this->pdo->commit();
                return ['success' => true, 'message' => 'Compte créé avec succès'];
            } else {
                $this->pdo->rollBack();
                $errorInfo = $stmt->errorInfo();
                error_log("Erreur SQL détaillée: " . print_r($errorInfo, true));
                return ['success' => false, 'message' => 'Erreur technique: ' . $errorInfo[2]];
            }
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log('Erreur PDO inscription: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la création du compte: ' . $e->getMessage()];
        }
    }

    /**
     * Connexion de l'utilisateur
     */
    public function login($username, $password, $remember = false)
    {
        // Protection contre les attaques brute force
        if ($this->isAccountLocked($username)) {
            return ['success' => false, 'message' => 'Compte temporairement verrouillé'];
        }

        try {
            $prefix = $_ENV['KAS_DB_PREFIX'];

            // Récupération des informations de l'utilisateur
            $stmt = $this->pdo->prepare("
                SELECT id, username, password, is_active, failed_attempts, locked_until 
                FROM {$prefix}users 
                WHERE username = ? OR email = ?
            ");

            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();

            if (!$user) {
                $this->logFailedAttempt($username);
                return ['success' => false, 'message' => 'Identifiants incorrects'];
            }

            // Vérification du statut du compte
            if (!$user['is_active']) {
                return ['success' => false, 'message' => 'Compte désactivé'];
            }

            // Vérification du mot de passe
            if (!password_verify($password, $user['password'])) {
                $this->incrementFailedAttempts($user['id']);
                return ['success' => false, 'message' => 'Identifiants incorrects'];
            }

            // Réinitialisation des tentatives échouées
            $this->resetFailedAttempts($user['id']);

            // Mise à jour de la dernière connexion
            $this->updateLastLogin($user['id']);

            // Création de la session
            $this->createSession($user['id'], $remember);

            return ['success' => true, 'message' => 'Connexion réussie'];
        } catch (PDOException $e) {
            error_log('Erreur connexion: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la connexion'];
        }
    }

    /**
     * Vérification si l'utilisateur est connecté
     */
    public function isLoggedIn()
    {
        if (!isset($_COOKIE[$this->cookie_name])) {
            return false;
        }

        $token = $_COOKIE[$this->cookie_name];

        try {
            $prefix = $_ENV['KAS_DB_PREFIX'];

            // Vérification du token en base de données
            $stmt = $this->pdo->prepare("
                SELECT us.*, u.username, u.is_active 
                FROM {$prefix}user_sessions us 
                JOIN {$prefix}users u ON us.user_id = u.id 
                WHERE us.token = ? AND us.expires_at > NOW() AND u.is_active = TRUE
            ");

            $stmt->execute([$token]);
            $session = $stmt->fetch();

            if (!$session) {
                $this->logout();
                return false;
            }

            // Vérification de l'empreinte de l'user-agent (optionnel mais recommandé)
            if ($session['user_agent'] !== hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '')) {
                $this->deleteSession($token);
                $this->logout();
                return false;
            }

            // Renouvellement du cookie si nécessaire (moins de 7 jours restants)
            $expires = strtotime($session['expires_at']);
            if ($expires - time() < 7 * 24 * 60 * 60) {
                $this->renewSession($token);
            }

            return true;
        } catch (PDOException $e) {
            error_log('Erreur vérification session: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupération des informations de l'utilisateur connecté
     */
    public function getCurrentUser()
    {
        if (!$this->isLoggedIn()) {
            return null;
        }

        $token = $_COOKIE[$this->cookie_name];

        try {
            $prefix = $_ENV['KAS_DB_PREFIX'];

            $stmt = $this->pdo->prepare("
                SELECT * 
                FROM {$prefix}user_sessions us 
                JOIN {$prefix}users u ON us.user_id = u.id 
                WHERE us.token = ?
            ");

            $stmt->execute([$token]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log('Erreur récupération utilisateur: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Déconnexion
     */
    public function logout()
    {
        if (isset($_COOKIE[$this->cookie_name])) {
            $token = $_COOKIE[$this->cookie_name];
            $this->deleteSession($token);
        }

        setcookie($this->cookie_name, '', time() - 3600, '/', '', true, true);
        unset($_COOKIE[$this->cookie_name]);
    }

    /**
     * Méthodes privées pour la gestion interne
     */
    private function createSession($user_id, $remember = false)
    {
        $prefix = $_ENV['KAS_DB_PREFIX'];
        $token = bin2hex(random_bytes(32)); // Token sécurisé
        $duration = $remember ? $this->cookie_duration : (24 * 60 * 60); // 30 jours ou 1 jour
        $expires = date('Y-m-d H:i:s', time() + $duration);

        $user_agent = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';

        $stmt = $this->pdo->prepare("
            INSERT INTO {$prefix}user_sessions (user_id, token, expires_at, user_agent, ip_address) 
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->execute([$user_id, $token, $expires, $user_agent, $ip_address]);

        // Création du cookie sécurisé
        setcookie(
            $this->cookie_name,
            $token,
            time() + $duration,
            '/',
            '',
            false,  // HTTPS seulement en production
            true   // HttpOnly
        );
    }

    private function deleteSession($token)
    {
        try {
            $prefix = $_ENV['KAS_DB_PREFIX'];
            $stmt = $this->pdo->prepare("DELETE FROM {$prefix}user_sessions WHERE token = ?");
            $stmt->execute([$token]);
        } catch (PDOException $e) {
            error_log('Erreur suppression session: ' . $e->getMessage());
        }
    }

    private function renewSession($token)
    {
        $prefix = $_ENV['KAS_DB_PREFIX'];
        $new_expires = date('Y-m-d H:i:s', time() + $this->cookie_duration);

        $stmt = $this->pdo->prepare("
            UPDATE {$prefix}user_sessions 
            SET expires_at = ? 
            WHERE token = ?
        ");

        $stmt->execute([$new_expires, $token]);

        setcookie(
            $this->cookie_name,
            $token,
            time() + $this->cookie_duration,
            '/',
            '',
            false,
            true
        );
    }

    private function isAccountLocked($username)
    {
        $prefix = $_ENV['KAS_DB_PREFIX'];
        $stmt = $this->pdo->prepare("
            SELECT locked_until 
            FROM {$prefix}users 
            WHERE (username = ? OR email = ?) AND locked_until > NOW()
        ");

        $stmt->execute([$username, $username]);
        return (bool) $stmt->fetch();
    }

    private function logFailedAttempt($username)
    {
        $prefix = $_ENV['KAS_DB_PREFIX'];
        $stmt = $this->pdo->prepare("
            UPDATE {$prefix}users 
            SET failed_attempts = failed_attempts + 1,
                locked_until = CASE 
                    WHEN failed_attempts + 1 >= 5 THEN DATE_ADD(NOW(), INTERVAL 30 MINUTE)
                    ELSE locked_until 
                END
            WHERE username = ? OR email = ?
        ");

        $stmt->execute([$username, $username]);
    }

    private function incrementFailedAttempts($user_id)
    {
        $prefix = $_ENV['KAS_DB_PREFIX'];
        $stmt = $this->pdo->prepare("
            UPDATE {$prefix}users 
            SET failed_attempts = failed_attempts + 1,
                locked_until = CASE 
                    WHEN failed_attempts + 1 >= 5 THEN DATE_ADD(NOW(), INTERVAL 30 MINUTE)
                    ELSE locked_until 
                END
            WHERE id = ?
        ");

        $stmt->execute([$user_id]);
    }

    private function resetFailedAttempts($user_id)
    {
        $prefix = $_ENV['KAS_DB_PREFIX'];
        $stmt = $this->pdo->prepare("
            UPDATE {$prefix}users 
            SET failed_attempts = 0, locked_until = NULL 
            WHERE id = ?
        ");

        $stmt->execute([$user_id]);
    }

    private function updateLastLogin($user_id)
    {
        $prefix = $_ENV['KAS_DB_PREFIX'];
        $stmt = $this->pdo->prepare("
            UPDATE {$prefix}users 
            SET last_login = NOW() 
            WHERE id = ?
        ");

        $stmt->execute([$user_id]);
    }

    private function validateUsername($username)
    {
        return preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username);
    }

    private function validatePassword($password)
    {
        return strlen($password) >= 8;
    }

    private function validateEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

// Initialisation du système d'authentification
try {
    $auth = new AuthSystem();
} catch (Exception $e) {
    var_dump('Erreur initialisation AuthSystem: ' . $e->getMessage());
}
