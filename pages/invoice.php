<?php
/**
 * Page de visualisation d'une facture
 */

require_once __DIR__ . '/../includes/cart_functions.php';

requireLogin();

$invoice_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($invoice_id <= 0) {
    setFlashMessage('error', 'Facture introuvable.');
    redirect('/account');
}

// Récupération de la facture (vérification que c'est bien la facture de l'utilisateur)
$invoice = getInvoiceById($invoice_id, getUserId());

if (!$invoice) {
    setFlashMessage('error', 'Facture introuvable ou accès refusé.');
    redirect('/account');
}

$page_title = 'Facture #' . str_pad($invoice_id, 6, '0', STR_PAD_LEFT);

include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div style="max-width: 800px; margin: 0 auto;">
        <div style="margin-bottom: 1.5rem;">
            <a href="<?php echo url('/account'); ?>" style="color: var(--primary-color); text-decoration: none;">
                ← Retour à mon compte
            </a>
        </div>
        
        <div class="card">
            <div style="text-align: center; margin-bottom: 2rem;">
                <h1 style="color: var(--primary-color); font-size: 2.5rem; margin-bottom: 0.5rem;">
                    FACTURE
                </h1>
                <div style="font-size: 1.2rem; color: var(--text-secondary);">
                    #<?php echo str_pad($invoice['id'], 6, '0', STR_PAD_LEFT); ?>
                </div>
            </div>
            
            <hr style="border: none; border-top: 2px solid var(--border-color); margin: 2rem 0;">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                <div>
                    <h3 style="color: var(--primary-color); margin-bottom: 1rem;">Client</h3>
                    <div style="color: var(--text-secondary);">
                        <strong style="color: var(--text-primary); display: block; margin-bottom: 0.25rem;">
                            <?php echo h($invoice['username']); ?>
                        </strong>
                        <?php echo h($invoice['email']); ?><br>
                        <?php echo h($invoice['billing_address']); ?><br>
                        <?php echo h($invoice['billing_zip']); ?> <?php echo h($invoice['billing_city']); ?>
                    </div>
                </div>
                
                <div>
                    <h3 style="color: var(--primary-color); margin-bottom: 1rem;">Informations</h3>
                    <div style="color: var(--text-secondary);">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span>Date:</span>
                            <strong style="color: var(--text-primary);">
                                <?php echo formatDate($invoice['date']); ?>
                            </strong>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span>Statut:</span>
                            <strong style="color: var(--success-color);">
                                <?php echo ucfirst($invoice['status']); ?>
                            </strong>
                        </div>
                    </div>
                </div>
            </div>
            
            <hr style="border: none; border-top: 1px solid var(--border-color); margin: 2rem 0;">
            
            <h3 style="color: var(--primary-color); margin-bottom: 1.5rem;">Articles</h3>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>Challenge</th>
                        <th style="text-align: center;">Quantité</th>
                        <th style="text-align: right;">Prix Unitaire</th>
                        <th style="text-align: right;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invoice['items'] as $item): ?>
                        <tr>
                            <td><?php echo h($item['challenge_title']); ?></td>
                            <td style="text-align: center;"><?php echo $item['quantity']; ?></td>
                            <td style="text-align: right;"><?php echo formatPrice($item['price']); ?></td>
                            <td style="text-align: right; font-weight: bold;">
                                <?php echo formatPrice($item['price'] * $item['quantity']); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <hr style="border: none; border-top: 2px solid var(--border-color); margin: 2rem 0;">
            
            <div style="display: flex; justify-content: flex-end;">
                <div style="min-width: 300px;">
                    <div style="display: flex; justify-content: space-between; font-size: 1.5rem; font-weight: bold; color: var(--primary-color);">
                        <span>TOTAL</span>
                        <span><?php echo formatPrice($invoice['amount']); ?></span>
                    </div>
                </div>
            </div>
            
            <hr style="border: none; border-top: 1px solid var(--border-color); margin: 2rem 0;">
            
            <div style="text-align: center; color: var(--text-secondary); font-size: 0.9rem;">
                <p>Merci pour votre achat sur Hackastrophe !</p>
                <p>Cette facture a été générée automatiquement.</p>
            </div>
        </div>
        
        <div style="margin-top: 2rem; text-align: center;">
            <button onclick="window.print()" class="btn btn-primary">
                Imprimer la Facture
            </button>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>