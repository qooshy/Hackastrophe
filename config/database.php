<?php
/**
 * Configuration de la connexion à la base de données
 * 
 * Ce fichier centralise la connexion à MySQL/MariaDB
 * Adaptez les paramètres selon votre environnement local
 */

// Constantes de configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // ou '' pour XAMPP/MAMP par défaut
define('DB_NAME', 'hackastrophe_db');
define('DB_CHARSET', 'utf8mb4');

/**
 * Établit une connexion MySQLi avec gestion d'erreur
 * 
 * @return mysqli Instance de connexion
 * @throws Exception si la connexion échoue
 */
function getDBConnection() {
    static $connection = null;
    
    if ($connection === null) {
        // Désactivation du rapport d'erreur MySQLi pour éviter l'exposition d'infos sensibles
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        
        try {
            $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            $connection->set_charset(DB_CHARSET);
        } catch (Exception $e) {
            error_log("Erreur de connexion à la base de données: " . $e->getMessage());
            die("Erreur de connexion à la base de données. Veuillez contacter l'administrateur.");
        }
    }
    
    return $connection;
}

/**
 * Ferme proprement la connexion à la base de données
 */
function closeDBConnection() {
    $connection = getDBConnection();
    if ($connection) {
        $connection->close();
    }
}