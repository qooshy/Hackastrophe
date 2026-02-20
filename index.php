<?php
/**
 * Point d'entrée principal de l'application Hackastrophe
 * 
 * Gère le routage simple basé sur l'URL
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

// Récupération du chemin de la requête
$request_uri = $_SERVER['REQUEST_URI'];
$base_path = str_replace('/index.php', '', $_SERVER['SCRIPT_NAME']);
$path = str_replace($base_path, '', $request_uri);

// Extraction du chemin sans les paramètres GET
$path = strtok($path, '?');
$path = rtrim($path, '/');

// Si le chemin est vide, on est à la racine
if (empty($path)) {
    $path = '/';
}

// Routage simple
switch ($path) {
    case '/':
        require_once __DIR__ . '/pages/home.php';
        break;
        
    case '/register':
        require_once __DIR__ . '/pages/register.php';
        break;
        
    case '/login':
        require_once __DIR__ . '/pages/login.php';
        break;
        
    case '/logout':
        require_once __DIR__ . '/pages/logout.php';
        break;
        
    case '/detail':
        require_once __DIR__ . '/pages/detail.php';
        break;
        
    case '/sell':
        require_once __DIR__ . '/pages/sell.php';
        break;
        
    case '/cart':
        require_once __DIR__ . '/pages/cart.php';
        break;
        
    case '/cart/validate':
        require_once __DIR__ . '/pages/cart_validate.php';
        break;
        
    case '/edit':
        require_once __DIR__ . '/pages/edit.php';
        break;
        
    case '/account':
        require_once __DIR__ . '/pages/account.php';
        break;
        
    case '/admin':
        require_once __DIR__ . '/admin/index.php';
        break;
        
    case '/admin/users':
        require_once __DIR__ . '/admin/users.php';
        break;
        
    case '/admin/challenges':
        require_once __DIR__ . '/admin/challenges.php';
        break;
        
    case '/invoice':
        require_once __DIR__ . '/pages/invoice.php';
        break;
        
    default:
        http_response_code(404);
        $page_title = 'Page non trouvée';
        include __DIR__ . '/includes/header.php';
        echo '<div class="container">';
        echo '<div class="card" style="margin-top: 3rem; text-align: center;">';
        echo '<h1>404 - Page non trouvée</h1>';
        echo '<p style="margin-top: 1rem;">La page que vous recherchez n\'existe pas.</p>';
        echo '<a href="' . url('/') . '" class="btn btn-primary" style="margin-top: 1.5rem;">Retour à l\'accueil</a>';
        echo '</div>';
        echo '</div>';
        include __DIR__ . '/includes/footer.php';
        break;
}