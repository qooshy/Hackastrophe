<?php
/**
 * Page de validation du panier
 */

require_once __DIR__ . '/../includes/cart_functions.php';
require_once __DIR__ . '/../includes/user_functions.php';

// Vérification de la connexion
requireLogin();

$page_title = 'Valider ma commande';

// Récupération du contenu du panier
$cart_items = getCartContents(getUserId());
$cart_total = getCartTotal(getUserId());

// Vérification que le panier n'est pas vide
if (empty($cart_items)) {
    setFlashMessage('error', 'Votre panier est vide.');
    redirect('/cart');
}

// Récupération de l'utilisateur
$user = getUserById(getUserId());

// Vérification du solde
if ($user['balance'] < $cart_total) {
    setFlashMessage('error', 'Solde insuffisant pour finaliser la commande.');
    redirect('/cart');
}

$errors = [];

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $billing_info = [
        'address' => trim($_POST['address'] ?? ''),
        'city' => trim($_POST['city'] ?? ''),
        'zip' => trim($_POST['zip'] ?? '')
    ];
    
    // Validation
    if (empty($billing_info['address'])) {
        $errors[] = 'L\'adresse est requise.';
    }
    
    if (empty($billing_info['city'])) {
        $errors[] = 'La ville est requise.';
    }
    
    if (empty($billing_info['zip'])) {
        $errors[] = 'Le code postal est requis.';
    }
    
    if (empty($errors)) {
        $result = validateCart(getUserId(), $billing_info);
        
        if ($result['success']) {
            setFlashMessage('success', 'Commande validée avec succès ! Vous avez maintenant accès aux challenges.');
            redirect('/invoice?id=' . $result['invoice_id']);
        } else {
            $errors[] = $result['message'];
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div style="max-width: 800px; margin: 0 auto;">
        <h1 style="color: var(--primary-color); margin-bottom: 2rem;">Finaliser ma commande</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul style="list-style: none;">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo h($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="card" style="margin-bottom: 2rem;">
            <h2 style="color: var(--primary-color); margin-bottom: 1.5rem;">Récapitulatif de la commande</h2>
            
            <?php foreach ($cart_items as $item): ?>
                <div style="padding: 1rem; background: var(--darker-bg); border-radius: 6px; margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <strong><?php echo h($item['title']); ?></strong>
                        <div style="color: var(--text-secondary); font-size: 0.9rem; margin-top: 0.25rem;">
                            <?php echo h(CHALLENGE_CATEGORIES[$item['category']]); ?> - 
                            <?php echo h(DIFFICULTY_LEVELS[$item['difficulty']]); ?>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-weight: bold; color: var(--primary-color);">
                            <?php echo formatPrice($item['price']); ?>
                        </div>
                        <div style="color: var(--text-secondary); font-size: 0.85rem;">
                            <?php echo $item['points']; ?> points
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <hr style="border: none; border-top: 2px solid var(--border-color); margin: 1.5rem 0;">
            
            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 1.5rem;">
                <strong>Total</strong>
                <strong style="color: var(--primary-color);"><?php echo formatPrice($cart_total); ?></strong>
            </div>
            
            <div style="margin-top: 1rem; padding: 1rem; background: var(--darker-bg); border-radius: 6px;">
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--text-secondary);">Solde actuel</span>
                    <span style="font-weight: bold;"><?php echo formatPrice($user['balance']); ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-top: 0.5rem;">
                    <span style="color: var(--text-secondary);">Solde après achat</span>
                    <span style="font-weight: bold; color: var(--success-color);">
                        <?php echo formatPrice($user['balance'] - $cart_total); ?>
                    </span>
                </div>
            </div>
        </div>
        
        <div class="card">
            <h2 style="color: var(--primary-color); margin-bottom: 1.5rem;">Informations de Facturation</h2>
            
            <form method="POST" action="<?php echo url('/cart/validate'); ?>">
                <div class="form-group">
                    <label for="address">Adresse</label>
                    <input type="text" 
                           id="address" 
                           name="address" 
                           class="form-control" 
                           value="<?php echo h($_POST['address'] ?? ''); ?>" 
                           required 
                           autofocus>
                </div>
                
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="city">Ville</label>
                        <input type="text" 
                               id="city" 
                               name="city" 
                               class="form-control" 
                               value="<?php echo h($_POST['city'] ?? ''); ?>" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="zip">Code Postal</label>
                        <input type="text" 
                               id="zip" 
                               name="zip" 
                               class="form-control" 
                               value="<?php echo h($_POST['zip'] ?? ''); ?>" 
                               required>
                    </div>
                </div>
                
                <hr style="border: none; border-top: 1px solid var(--border-color); margin: 2rem 0;">
                
                <div style="display: flex; gap: 1rem;">
                    <button type="submit" class="btn btn-success" style="flex: 1; font-size: 1.1rem; padding: 1rem;">
                        Confirmer et Payer
                    </button>
                    <a href="<?php echo url('/cart'); ?>" class="btn btn-secondary" style="padding: 1rem;">
                        Retour au panier
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>