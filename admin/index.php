<?php
/**
 * Page principale d'administration
 */

require_once __DIR__ . '/../includes/user_functions.php';
require_once __DIR__ . '/../includes/challenge_functions.php';
require_once __DIR__ . '/../includes/cart_functions.php';

requireAdmin();

$page_title = 'Administration';

// Statistiques globales
$db = getDBConnection();

$total_users = $db->query("SELECT COUNT(*) as count FROM User")->fetch_assoc()['count'];
$total_challenges = $db->query("SELECT COUNT(*) as count FROM Challenge")->fetch_assoc()['count'];
$total_purchases = $db->query("SELECT COUNT(*) as count FROM PurchasedChallenge")->fetch_assoc()['count'];
$total_revenue = $db->query("SELECT COALESCE(SUM(amount), 0) as total FROM Invoice")->fetch_assoc()['total'];

// Dernières activités
$recent_users = $db->query("SELECT id, username, email, created_at FROM User ORDER BY created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
$recent_challenges = $db->query("
    SELECT c.id, c.title, u.username as author, c.created_at 
    FROM Challenge c 
    JOIN User u ON c.author_id = u.id 
    ORDER BY c.created_at DESC LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <h1 style="color: var(--primary-color); margin-bottom: 0.5rem;">Tableau de Bord Admin</h1>
    <p style="color: var(--text-secondary); margin-bottom: 2rem;">
        Vue d'ensemble de la plateforme Hackastrophe
    </p>
    
    <div class="account-stats">
        <div class="stat-card">
            <div class="stat-value"><?php echo $total_users; ?></div>
            <div class="stat-label">Utilisateurs</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $total_challenges; ?></div>
            <div class="stat-label">Challenges</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $total_purchases; ?></div>
            <div class="stat-label">Achats Totaux</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo formatPrice($total_revenue); ?></div>
            <div class="stat-label">Revenus Totaux</div>
        </div>
    </div>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin-bottom: 2rem;">
        <div class="card">
            <h2 style="color: var(--primary-color); margin-bottom: 1rem;">Actions Rapides</h2>
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                <a href="<?php echo url('/admin/users'); ?>" class="btn btn-primary">
                    Gérer les Utilisateurs
                </a>
                <a href="<?php echo url('/admin/challenges'); ?>" class="btn btn-primary">
                    Gérer les Challenges
                </a>
                <a href="<?php echo url('/sell'); ?>" class="btn btn-secondary">
                    Créer un Challenge
                </a>
            </div>
        </div>
        
        <div class="card">
            <h2 style="color: var(--primary-color); margin-bottom: 1rem;">Derniers Utilisateurs</h2>
            <?php if (empty($recent_users)): ?>
                <p style="color: var(--text-secondary);">Aucun utilisateur.</p>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <?php foreach ($recent_users as $user): ?>
                        <div style="padding: 0.5rem; background: var(--darker-bg); border-radius: 4px;">
                            <div style="font-weight: bold;">
                                <a href="<?php echo url('/account?id=' . $user['id']); ?>" 
                                   style="color: var(--text-primary); text-decoration: none;">
                                    <?php echo h($user['username']); ?>
                                </a>
                            </div>
                            <div style="color: var(--text-secondary); font-size: 0.85rem;">
                                <?php echo formatDate($user['created_at']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2 style="color: var(--primary-color); margin-bottom: 1rem;">Derniers Challenges</h2>
            <?php if (empty($recent_challenges)): ?>
                <p style="color: var(--text-secondary);">Aucun challenge.</p>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <?php foreach ($recent_challenges as $challenge): ?>
                        <div style="padding: 0.5rem; background: var(--darker-bg); border-radius: 4px;">
                            <div style="font-weight: bold;">
                                <a href="<?php echo url('/detail?id=' . $challenge['id']); ?>" 
                                   style="color: var(--text-primary); text-decoration: none;">
                                    <?php echo h($challenge['title']); ?>
                                </a>
                            </div>
                            <div style="color: var(--text-secondary); font-size: 0.85rem;">
                                par <?php echo h($challenge['author']); ?> - <?php echo formatDate($challenge['created_at']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>