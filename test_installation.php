<?php
/**
 * Script de Test Automatique - Hackastrophe
 * 
 * Place ce fichier à la racine du projet et accède à :
 * http://localhost/hackastrophe/test_installation.php
 * 
 * Il va vérifier que tout est correctement configuré
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Style CSS pour l'affichage
echo '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Test Installation - Hackastrophe</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            max-width: 800px; 
            margin: 50px auto; 
            background: #0a0e27; 
            color: #fff; 
            padding: 20px;
        }
        h1 { color: #00d9ff; }
        .test { 
            background: #1a1f3a; 
            border: 1px solid #2a3050; 
            padding: 15px; 
            margin: 10px 0; 
            border-radius: 8px;
        }
        .success { border-left: 4px solid #00ff88; }
        .error { border-left: 4px solid #ff0055; }
        .warning { border-left: 4px solid #ffaa00; }
        .icon { font-size: 24px; margin-right: 10px; }
    </style>
</head>
<body>
    <h1>🔍 Test d\'Installation - Hackastrophe</h1>
    <p>Ce script vérifie automatiquement votre installation.</p>
';

$errors = [];
$warnings = [];
$success = [];

// TEST 1 : Version PHP
echo '<div class="test">';
echo '<h2>Test 1 : Version PHP</h2>';
$php_version = phpversion();
if (version_compare($php_version, '8.0', '>=')) {
    echo '<p class="success">✅ PHP ' . $php_version . ' (OK - version 8.0+ requise)</p>';
    $success[] = 'Version PHP compatible';
} else {
    echo '<p class="error">❌ PHP ' . $php_version . ' (ERREUR - version 8.0+ requise)</p>';
    $errors[] = 'Version PHP trop ancienne';
}
echo '</div>';

// TEST 2 : Extension MySQLi
echo '<div class="test">';
echo '<h2>Test 2 : Extension MySQLi</h2>';
if (extension_loaded('mysqli')) {
    echo '<p class="success">✅ Extension MySQLi chargée</p>';
    $success[] = 'Extension MySQLi OK';
} else {
    echo '<p class="error">❌ Extension MySQLi non chargée</p>';
    echo '<p>Solution : Activez l\'extension mysqli dans php.ini</p>';
    $errors[] = 'Extension MySQLi manquante';
}
echo '</div>';

// TEST 3 : Fichiers de configuration
echo '<div class="test">';
echo '<h2>Test 3 : Fichiers de Configuration</h2>';

$config_files = [
    'config/config.php' => 'Configuration générale',
    'config/database.php' => 'Configuration base de données'
];

foreach ($config_files as $file => $description) {
    if (file_exists($file)) {
        echo '<p class="success">✅ ' . $description . ' (' . $file . ')</p>';
        $success[] = $description . ' présent';
    } else {
        echo '<p class="error">❌ ' . $description . ' (' . $file . ') manquant</p>';
        $errors[] = $description . ' manquant';
    }
}
echo '</div>';

// TEST 4 : Connexion à la base de données
echo '<div class="test">';
echo '<h2>Test 4 : Connexion Base de Données</h2>';

if (file_exists('config/database.php')) {
    require_once 'config/database.php';
    
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            echo '<p class="error">❌ Erreur de connexion : ' . $conn->connect_error . '</p>';
            echo '<p>Vérifiez les identifiants dans config/database.php</p>';
            $errors[] = 'Connexion BDD impossible';
        } else {
            echo '<p class="success">✅ Connexion à MySQL réussie</p>';
            echo '<p>Serveur : ' . DB_HOST . ' | Base : ' . DB_NAME . '</p>';
            $success[] = 'Connexion MySQL OK';
            
            // Test des tables
            $tables = ['User', 'Challenge', 'Cart', 'Invoice', 'Submission', 'PurchasedChallenge', 'ChallengeInstance', 'InvoiceItem'];
            $tables_found = 0;
            
            echo '<h3>Tables de la Base de Données :</h3>';
            foreach ($tables as $table) {
                $result = $conn->query("SHOW TABLES LIKE '$table'");
                if ($result && $result->num_rows > 0) {
                    echo '<p class="success">✅ Table ' . $table . '</p>';
                    $tables_found++;
                } else {
                    echo '<p class="error">❌ Table ' . $table . ' manquante</p>';
                    $errors[] = 'Table ' . $table . ' manquante';
                }
            }
            
            if ($tables_found === count($tables)) {
                echo '<p class="success">✅ Toutes les tables sont présentes (' . $tables_found . '/8)</p>';
                $success[] = 'Toutes les tables présentes';
            } else {
                echo '<p class="error">❌ Certaines tables manquent (' . $tables_found . '/8)</p>';
                echo '<p>Importez le fichier hackastrophe_db.sql dans PhpMyAdmin</p>';
                $errors[] = 'Tables manquantes';
            }
            
            // Test des utilisateurs
            $user_result = $conn->query("SELECT COUNT(*) as count FROM User");
            if ($user_result) {
                $user_count = $user_result->fetch_assoc()['count'];
                echo '<p class="success">✅ ' . $user_count . ' utilisateur(s) dans la base</p>';
                
                if ($user_count >= 3) {
                    $success[] = 'Utilisateurs de test présents';
                } else {
                    $warnings[] = 'Peu d\'utilisateurs de test';
                }
            }
            
            // Test des challenges
            $challenge_result = $conn->query("SELECT COUNT(*) as count FROM Challenge");
            if ($challenge_result) {
                $challenge_count = $challenge_result->fetch_assoc()['count'];
                echo '<p class="success">✅ ' . $challenge_count . ' challenge(s) dans la base</p>';
                
                if ($challenge_count >= 5) {
                    $success[] = 'Challenges de test présents';
                } else {
                    $warnings[] = 'Peu de challenges de test';
                }
            }
            
            $conn->close();
        }
    } catch (Exception $e) {
        echo '<p class="error">❌ Exception : ' . $e->getMessage() . '</p>';
        $errors[] = 'Exception MySQL';
    }
} else {
    echo '<p class="error">❌ Fichier config/database.php introuvable</p>';
    $errors[] = 'Configuration BDD manquante';
}
echo '</div>';

// TEST 5 : Structure des dossiers
echo '<div class="test">';
echo '<h2>Test 5 : Structure des Dossiers</h2>';

$folders = [
    'admin' => 'Pages d\'administration',
    'assets' => 'Ressources statiques',
    'assets/css' => 'Fichiers CSS',
    'assets/js' => 'Fichiers JavaScript',
    'config' => 'Configuration',
    'includes' => 'Fonctions PHP',
    'pages' => 'Pages publiques'
];

foreach ($folders as $folder => $description) {
    if (is_dir($folder)) {
        echo '<p class="success">✅ ' . $description . ' (/' . $folder . ')</p>';
    } else {
        echo '<p class="error">❌ ' . $description . ' (/' . $folder . ') manquant</p>';
        $errors[] = 'Dossier ' . $folder . ' manquant';
    }
}
echo '</div>';

// TEST 6 : Fichiers critiques
echo '<div class="test">';
echo '<h2>Test 6 : Fichiers Critiques</h2>';

$critical_files = [
    'index.php' => 'Point d\'entrée',
    'hackastrophe_db.sql' => 'Fichier SQL',
    'includes/user_functions.php' => 'Fonctions utilisateurs',
    'includes/challenge_functions.php' => 'Fonctions challenges',
    'includes/cart_functions.php' => 'Fonctions panier',
    'pages/home.php' => 'Page d\'accueil',
    'pages/login.php' => 'Page de connexion',
    'admin/index.php' => 'Panneau admin'
];

foreach ($critical_files as $file => $description) {
    if (file_exists($file)) {
        echo '<p class="success">✅ ' . $description . ' (' . $file . ')</p>';
    } else {
        echo '<p class="error">❌ ' . $description . ' (' . $file . ') manquant</p>';
        $errors[] = $description . ' manquant';
    }
}
echo '</div>';

// TEST 7 : Permissions d'écriture
echo '<div class="test">';
echo '<h2>Test 7 : Permissions</h2>';

$writable_dirs = [
    'assets/images' => 'Upload d\'images'
];

foreach ($writable_dirs as $dir => $description) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    if (is_writable($dir)) {
        echo '<p class="success">✅ ' . $description . ' (/' . $dir . ') accessible en écriture</p>';
    } else {
        echo '<p class="warning">⚠️ ' . $description . ' (/' . $dir . ') non accessible en écriture</p>';
        $warnings[] = 'Permissions ' . $dir;
    }
}
echo '</div>';

// TEST 8 : Configuration BASE_URL
echo '<div class="test">';
echo '<h2>Test 8 : Configuration BASE_URL</h2>';

if (file_exists('config/config.php')) {
    require_once 'config/config.php';
    
    $current_path = dirname($_SERVER['PHP_SELF']);
    echo '<p>Chemin actuel détecté : <code>' . $current_path . '</code></p>';
    echo '<p>BASE_URL configuré : <code>' . BASE_URL . '</code></p>';
    
    if ($current_path === BASE_URL) {
        echo '<p class="success">✅ BASE_URL correspond au chemin actuel</p>';
        $success[] = 'BASE_URL correct';
    } else {
        echo '<p class="warning">⚠️ BASE_URL ne correspond pas exactement</p>';
        echo '<p>Conseil : Si le site ne fonctionne pas, changez BASE_URL en : <code>' . $current_path . '</code></p>';
        $warnings[] = 'BASE_URL à vérifier';
    }
}
echo '</div>';

// RÉSUMÉ FINAL
echo '<div class="test" style="background: #2a3050; border: 2px solid #00d9ff;">';
echo '<h2>📊 Résumé Final</h2>';

echo '<h3 style="color: #00ff88;">Réussites (' . count($success) . ')</h3>';
if (count($success) > 0) {
    echo '<ul>';
    foreach ($success as $s) {
        echo '<li>✅ ' . $s . '</li>';
    }
    echo '</ul>';
}

if (count($warnings) > 0) {
    echo '<h3 style="color: #ffaa00;">Avertissements (' . count($warnings) . ')</h3>';
    echo '<ul>';
    foreach ($warnings as $w) {
        echo '<li>⚠️ ' . $w . '</li>';
    }
    echo '</ul>';
}

if (count($errors) > 0) {
    echo '<h3 style="color: #ff0055;">Erreurs (' . count($errors) . ')</h3>';
    echo '<ul>';
    foreach ($errors as $e) {
        echo '<li>❌ ' . $e . '</li>';
    }
    echo '</ul>';
    echo '<p style="color: #ff0055;"> L\'application ne fonctionnera pas correctement tant que ces erreurs ne sont pas résolues.</p>';
} else {
    echo '<h2 style="color: #00ff88;"> Installation Complète et Fonctionnelle !</h2>';
    echo '<p>Votre installation de Hackastrophe est correcte. Vous pouvez maintenant :</p>';
    echo '<ul>';
    echo '<li> <a href="' . (defined('BASE_URL') ? BASE_URL : '') . '/" style="color: #00d9ff;">Accéder à la page d\'accueil</a></li>';
    echo '<li> <a href="' . (defined('BASE_URL') ? BASE_URL : '') . '/login" style="color: #00d9ff;">Se connecter</a> (admin / password123)</li>';
    echo '<li> <a href="' . (defined('BASE_URL') ? BASE_URL : '') . '/admin" style="color: #00d9ff;">Panneau admin</a></li>';
    echo '</ul>';
}

echo '</div>';

// Informations système
echo '<div class="test">';
echo '<h2> Informations Système</h2>';
echo '<ul>';
echo '<li>PHP Version : ' . phpversion() . '</li>';
echo '<li>Serveur : ' . $_SERVER['SERVER_SOFTWARE'] . '</li>';
echo '<li>Document Root : ' . $_SERVER['DOCUMENT_ROOT'] . '</li>';
echo '<li>Script : ' . $_SERVER['SCRIPT_FILENAME'] . '</li>';
echo '<li>Extensions chargées : ' . implode(', ', get_loaded_extensions()) . '</li>';
echo '</ul>';
echo '</div>';

echo '</body></html>';
?>