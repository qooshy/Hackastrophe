<?php
/**
 * Configuration générale de l'application Hackastrophe
 * 
 * Définit les constantes et paramètres globaux de la plateforme
 */

// Démarrage de session sécurisé
if (session_status() === PHP_SESSION_NONE) {
    // Configuration sécurisée de session
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Mettre à 1 en production HTTPS
    ini_set('session.cookie_samesite', 'Strict');
    
    session_start();
}

// Configuration de l'affichage des erreurs (désactiver en production)
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Définition du fuseau horaire
date_default_timezone_set('Europe/Paris');

// URL de base de l'application
define('BASE_URL', '/hackastrophe');

// Chemins absolus
define('ROOT_PATH', dirname(__DIR__));
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('PAGES_PATH', ROOT_PATH . '/pages');
define('ASSETS_PATH', ROOT_PATH . '/assets');

// Configuration uploads
define('UPLOAD_MAX_SIZE', 2 * 1024 * 1024); // 2 MB
define('UPLOAD_ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('UPLOAD_PATH', ASSETS_PATH . '/images/uploads');

// Rôles utilisateur
define('ROLE_USER', 'user');
define('ROLE_CREATOR', 'creator');
define('ROLE_ADMIN', 'admin');

// Niveaux de compétence
define('SKILL_LEVELS', [
    'junior' => 'Junior',
    'intermediate' => 'Intermédiaire',
    'senior' => 'Senior',
    'expert' => 'Expert'
]);

// Catégories de challenges
define('CHALLENGE_CATEGORIES', [
    'web' => 'Web',
    'pwn' => 'Pwn',
    'crypto' => 'Crypto',
    'forensic' => 'Forensic',
    'reverse' => 'Reverse Engineering',
    'steganography' => 'Steganography',
    'osint' => 'OSINT',
    'misc' => 'Misc'
]);

// Niveaux de difficulté
define('DIFFICULTY_LEVELS', [
    'noob' => 'Noob',
    'mid' => 'Mid',
    'ardu' => 'Ca commence à être ardu',
    'fou' => 'Je vais devenir Fou',
    'cybersec' => 'Give Me CYBERSECURITY title'
]);

// Points par niveau de difficulté
define('DIFFICULTY_POINTS', [
    'noob' => 10,
    'mid' => 25,
    'ardu' => 50,
    'fou' => 100,
    'cybersec' => 200
]);

// Paramètres de pagination
define('ITEMS_PER_PAGE', 12);

// Solde initial pour nouveaux utilisateurs
define('INITIAL_BALANCE', 1000);

/**
 * Fonction de redirection sécurisée
 * 
 * @param string $path Chemin relatif ou absolu
 */
function redirect($path) {
    if (strpos($path, 'http') === 0) {
        header('Location: ' . $path);
    } else {
        header('Location: ' . BASE_URL . $path);
    }
    exit();
}

/**
 * Fonction pour sécuriser l'affichage HTML (protection XSS)
 * 
 * @param string $string Chaîne à sécuriser
 * @return string Chaîne sécurisée
 */
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Vérifie si l'utilisateur est connecté
 * 
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Récupère l'ID de l'utilisateur connecté
 * 
 * @return int|null
 */
function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Vérifie si l'utilisateur a un rôle spécifique
 * 
 * @param string $role Rôle à vérifier
 * @return bool
 */
function hasRole($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

/**
 * Vérifie si l'utilisateur est admin
 * 
 * @return bool
 */
function isAdmin() {
    return hasRole(ROLE_ADMIN);
}

/**
 * Force la connexion (redirige vers login si non connecté)
 */
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        redirect('/login');
    }
}

/**
 * Force le rôle admin (redirige si non admin)
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        $_SESSION['error'] = "Accès refusé. Vous devez être administrateur.";
        redirect('/');
    }
}

/**
 * Génère un token CSRF
 * 
 * @return string
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie un token CSRF
 * 
 * @param string $token Token à vérifier
 * @return bool
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Affiche un message flash (success, error, info)
 * 
 * @param string $type Type de message
 * @param string $message Message à afficher
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash'][$type] = $message;
}

/**
 * Récupère et supprime les messages flash
 * 
 * @return array
 */
function getFlashMessages() {
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

/**
 * Formate un montant en euros
 * 
 * @param float $amount Montant
 * @return string
 */
function formatPrice($amount) {
    return number_format($amount, 2, ',', ' ') . ' €';
}

/**
 * Formate une date
 * 
 * @param string $date Date
 * @return string
 */
function formatDate($date) {
    return date('d/m/Y à H:i', strtotime($date));
}

/**
 * Génère une URL absolue
 * 
 * @param string $path Chemin
 * @return string
 */
function url($path = '') {
    return BASE_URL . $path;
}

/**
 * Génère une URL pour un asset
 * 
 * @param string $path Chemin de l'asset
 * @return string
 */
function asset($path) {
    return BASE_URL . '/assets/' . $path;
}