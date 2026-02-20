<?php
/**
 * Header commun à toutes les pages
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Récupération du nombre d'articles dans le panier si connecté
$cart_count = 0;
if (isLoggedIn()) {
    require_once __DIR__ . '/../includes/cart_functions.php';
    $cart_count = getCartItemCount(getUserId());
}

// Récupération des messages flash
$flash_messages = getFlashMessages();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? h($page_title) . ' - ' : ''; ?>Hackastrophe</title>
    <link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand">
                <a href="<?php echo url('/'); ?>">Hackastrophe</a>
            </div>
            
            <ul class="nav-menu">
                <li><a href="<?php echo url('/'); ?>">Challenges</a></li>
                
                <?php if (isLoggedIn()): ?>
                    <li><a href="<?php echo url('/sell'); ?>">Créer un Challenge</a></li>
                    <li>
                        <a href="<?php echo url('/cart'); ?>" class="cart-link">
                            Panier
                            <?php if ($cart_count > 0): ?>
                                <span class="cart-badge"><?php echo $cart_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li><a href="<?php echo url('/account'); ?>">Mon Compte</a></li>
                    
                    <?php if (isAdmin()): ?>
                        <li><a href="<?php echo url('/admin'); ?>" class="admin-link">Admin</a></li>
                    <?php endif; ?>
                    
                    <li><a href="<?php echo url('/logout'); ?>">Déconnexion</a></li>
                <?php else: ?>
                    <li><a href="<?php echo url('/login'); ?>">Connexion</a></li>
                    <li><a href="<?php echo url('/register'); ?>" class="btn-primary">Inscription</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
    
    <main class="main-content">
        <?php if (!empty($flash_messages)): ?>
            <div class="container">
                <?php foreach ($flash_messages as $type => $message): ?>
                    <div class="alert alert-<?php echo h($type); ?>">
                        <?php echo h($message); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>