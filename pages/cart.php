<?php
/**
 * Page du panier
 */

require_once __DIR__ . '/../includes/cart_functions.php';
require_once __DIR__ . '/../includes/user_functions.php';

// Vérification de la connexion
requireLogin();

$page_title = 'Mon Panier';

// Traitement de la suppression d'un article
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_item'])) {
    $challenge_id = intval($_POST['challenge_id'] ?? 0);
    
    if ($challenge_id > 0) {
        if (removeFromCart(getUserId(), $challenge_id)) {
            setFlashMessage('success', 'Article supprimé du panier.');
        } else {
            setFlashMessage('error', 'Erreur lors de la suppression.');
        }
    }
    
    redirect('/cart');
}

// Récupération du contenu du panier
$cart_items = getCartContents(getUserId());
$cart_total = getCartTotal(getUserId());

// Récupération du solde de l'utilisateur
$user = getUserById(getUserId());

include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <h1 style="color: var(--primary-color); margin-bottom: 2rem;">Mon Panier</h1>
    
    <?php if (empty($cart_items)): ?>
        <div class="card" style="text-align: center; padding: 3rem;">
            <h2>Votre panier est vide</h2>
            <p style="color: var(--text-secondary); margin-top: 1rem;">
                Parcourez nos challenges et ajoutez-en à votre panier pour commencer votre aventure de pentesting.
            </p>
            <a href="<?php echo url('/'); ?>" class="btn btn-primary" style="margin-top: 1.5rem;">
                Découvrir les challenges
            </a>
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: 1fr 400px; gap: 2rem;">
            <div>
                <div class="card" style="margin-bottom: 1.5rem;">
                    <h2 style="color: var(--primary-color); margin-bottom: 1.5rem;">
                        Articles (<?php echo count($cart_items); ?>)
                    </h2>
                    
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item">
                            <div class="cart-item-info" style="flex: 1;">
                                <h3 style="margin-bottom: 0.5rem;">
                                    <a href="<?php echo url('/detail?id=' . $item['id']); ?>" 
                                       style="color: var(--text-primary); text-decoration: none;">
                                        <?php echo h($item['title']); ?>
                                    </a>
                                </h3>
                                <div style="display: flex; gap: 0.75rem; margin-bottom: 0.5rem;">
                                    <span class="badge badge-category">
                                        <?php echo h(CHALLENGE_CATEGORIES[$item['category']]); ?>
                                    </span>
                                    <span class="badge badge-difficulty badge-<?php echo h($item['difficulty']); ?>">
                                        <?php echo h(DIFFICULTY_LEVELS[$item['difficulty']]); ?>
                                    </span>
                                </div>
                                <p style="color: var(--text-secondary); font-size: 0.9rem;">
                                    par <?php echo h($item['author_name']); ?>
                                </p>
                            </div>
                            
                            <div class="cart-item-actions">
                                <div style="text-align: right;">
                                    <div style="font-size: 1.5rem; font-weight: bold; color: var(--primary-color);">
                                        <?php echo formatPrice($item['price']); ?>
                                    </div>
                                    <div style="color: var(--text-secondary); font-size: 0.85rem;">
                                        <?php echo $item['points']; ?> points
                                    </div>
                                </div>
                                
                                <form method="POST" action="<?php echo url('/cart'); ?>" style="margin: 0;">
                                    <input type="hidden" name="challenge_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" 
                                            name="remove_item" 
                                            class="btn btn-danger" 
                                            onclick="return confirm('Êtes-vous sûr de vouloir retirer ce challenge du panier ?');">
                                        Retirer
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div>
                <div class="cart-summary">
                    <h2 style="color: var(--primary-color); margin-bottom: 1.5rem;">Récapitulatif</h2>
                    
                    <div class="cart-summary-line">
                        <span>Articles (<?php echo count($cart_items); ?>)</span>
                        <span><?php echo formatPrice($cart_total); ?></span>
                    </div>
                    
                    <div class="cart-summary-total">
                        <div>Total</div>
                        <div><?php echo formatPrice($cart_total); ?></div>
                    </div>
                    
                    <hr style="border: none; border-top: 1px solid var(--border-color); margin: 1.5rem 0;">
                    
                    <div style="background: var(--darker-bg); padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span style="color: var(--text-secondary);">Votre solde</span>
                            <span style="font-weight: bold; color: <?php echo $user['balance'] >= $cart_total ? 'var(--success-color)' : 'var(--error-color)'; ?>">
                                <?php echo formatPrice($user['balance']); ?>
                            </span>
                        </div>
                        
                        <?php if ($user['balance'] < $cart_total): ?>
                            <div style="color: var(--error-color); font-size: 0.85rem; margin-top: 0.5rem;">
                                Solde insuffisant. Il vous manque <?php echo formatPrice($cart_total - $user['balance']); ?>.
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($user['balance'] >= $cart_total): ?>
                        <a href="<?php echo url('/cart/validate'); ?>" class="btn btn-success btn-block" style="font-size: 1.1rem; padding: 1rem;">
                            Procéder au paiement
                        </a>
                    <?php else: ?>
                        <button class="btn btn-secondary btn-block disabled" style="font-size: 1.1rem; padding: 1rem;" disabled>
                            Solde insuffisant
                        </button>
                        <a href="<?php echo url('/account'); ?>" class="btn btn-primary btn-block" style="margin-top: 0.5rem;">
                            Ajouter des crédits
                        </a>
                    <?php endif; ?>
                    
                    <a href="<?php echo url('/'); ?>" style="display: block; text-align: center; margin-top: 1rem; color: var(--text-secondary); text-decoration: none;">
                        Continuer mes achats
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>