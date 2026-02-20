<?php
/**
 * Page de gestion des challenges (Admin)
 */

require_once __DIR__ . '/../includes/challenge_functions.php';

requireAdmin();

$page_title = 'Gestion des Challenges';

// Traitement de l'activation/désactivation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_active'])) {
    $challenge_id = intval($_POST['challenge_id'] ?? 0);
    $is_active = intval($_POST['is_active'] ?? 0);
    
    if ($challenge_id > 0) {
        $result = updateChallenge($challenge_id, ['is_active' => $is_active], getUserId());
        if ($result['success']) {
            $status_text = $is_active ? 'activé' : 'désactivé';
            setFlashMessage('success', 'Challenge ' . $status_text . ' avec succès.');
        } else {
            setFlashMessage('error', $result['message']);
        }
    }
    
    redirect('/admin/challenges');
}

// Traitement de la suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_challenge'])) {
    $challenge_id = intval($_POST['challenge_id'] ?? 0);
    
    if ($challenge_id > 0) {
        $result = deleteChallenge($challenge_id, getUserId());
        if ($result['success']) {
            setFlashMessage('success', $result['message']);
        } else {
            setFlashMessage('error', $result['message']);
        }
    }
    
    redirect('/admin/challenges');
}

// Récupération de tous les challenges (actifs et inactifs)
$db = getDBConnection();
$stmt = $db->prepare("
    SELECT c.*, 
           u.username as author_name,
           COUNT(DISTINCT pc.id) as purchase_count
    FROM Challenge c
    JOIN User u ON c.author_id = u.id
    LEFT JOIN PurchasedChallenge pc ON c.id = pc.challenge_id
    GROUP BY c.id
    ORDER BY c.created_at DESC
");
$stmt->execute();
$result = $stmt->get_result();
$challenges = [];
while ($row = $result->fetch_assoc()) {
    $challenges[] = $row;
}
$stmt->close();

include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div style="margin-bottom: 1.5rem;">
        <a href="<?php echo url('/admin'); ?>" style="color: var(--primary-color); text-decoration: none;">
            ← Retour au tableau de bord
        </a>
    </div>
    
    <h1 style="color: var(--primary-color); margin-bottom: 2rem;">Gestion des Challenges</h1>
    
    <div class="card">
        <div class="table">
            <table style="width: 100%;">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Titre</th>
                        <th>Auteur</th>
                        <th>Catégorie</th>
                        <th>Difficulté</th>
                        <th>Prix</th>
                        <th>Achats</th>
                        <th>Résolutions</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($challenges as $challenge): ?>
                        <tr>
                            <td><?php echo $challenge['id']; ?></td>
                            <td>
                                <a href="<?php echo url('/detail?id=' . $challenge['id']); ?>" 
                                   style="color: var(--text-primary); text-decoration: none;">
                                    <?php echo h($challenge['title']); ?>
                                </a>
                            </td>
                            <td style="color: var(--text-secondary); font-size: 0.9rem;">
                                <a href="<?php echo url('/account?id=' . $challenge['author_id']); ?>" 
                                   style="color: var(--text-secondary); text-decoration: none;">
                                    <?php echo h($challenge['author_name']); ?>
                                </a>
                            </td>
                            <td>
                                <span class="badge badge-category">
                                    <?php echo h(CHALLENGE_CATEGORIES[$challenge['category']]); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-difficulty badge-<?php echo h($challenge['difficulty']); ?>">
                                    <?php echo h(DIFFICULTY_LEVELS[$challenge['difficulty']]); ?>
                                </span>
                            </td>
                            <td><?php echo formatPrice($challenge['price']); ?></td>
                            <td style="text-align: center;"><?php echo $challenge['purchase_count']; ?></td>
                            <td style="text-align: center; color: var(--success-color); font-weight: bold;">
                                <?php echo $challenge['solved_count']; ?>
                            </td>
                            <td>
                                <?php if ($challenge['is_active']): ?>
                                    <span class="badge" style="background: var(--success-color); color: var(--dark-bg);">
                                        Actif
                                    </span>
                                <?php else: ?>
                                    <span class="badge" style="background: var(--error-color); color: white;">
                                        Inactif
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.5rem;">
                                    <a href="<?php echo url('/edit?id=' . $challenge['id']); ?>" 
                                       class="btn btn-warning" 
                                       style="font-size: 0.85rem; padding: 0.25rem 0.75rem;">
                                        Éditer
                                    </a>
                                    
                                    <form method="POST" action="<?php echo url('/admin/challenges'); ?>" style="margin: 0;">
                                        <input type="hidden" name="challenge_id" value="<?php echo $challenge['id']; ?>">
                                        <input type="hidden" name="is_active" value="<?php echo $challenge['is_active'] ? 0 : 1; ?>">
                                        <button type="submit" 
                                                name="toggle_active" 
                                                class="btn <?php echo $challenge['is_active'] ? 'btn-secondary' : 'btn-success'; ?>" 
                                                style="font-size: 0.85rem; padding: 0.25rem 0.75rem;">
                                            <?php echo $challenge['is_active'] ? 'Désactiver' : 'Activer'; ?>
                                        </button>
                                    </form>
                                    
                                    <form method="POST" action="<?php echo url('/admin/challenges'); ?>" style="margin: 0;">
                                        <input type="hidden" name="challenge_id" value="<?php echo $challenge['id']; ?>">
                                        <button type="submit" 
                                                name="delete_challenge" 
                                                class="btn btn-danger" 
                                                style="font-size: 0.85rem; padding: 0.25rem 0.75rem;"
                                                onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce challenge ?');">
                                            Supprimer
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>