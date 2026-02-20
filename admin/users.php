<?php
/**
 * Page de gestion des utilisateurs (Admin)
 */

require_once __DIR__ . '/../includes/user_functions.php';

requireAdmin();

$page_title = 'Gestion des Utilisateurs';

// Traitement du changement de rôle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_role'])) {
    $user_id = intval($_POST['user_id'] ?? 0);
    $role = $_POST['role'] ?? '';
    
    if ($user_id > 0 && in_array($role, [ROLE_USER, ROLE_CREATOR, ROLE_ADMIN])) {
        if (changeUserRole($user_id, $role)) {
            setFlashMessage('success', 'Rôle modifié avec succès.');
        } else {
            setFlashMessage('error', 'Erreur lors de la modification du rôle.');
        }
    }
    
    redirect('/admin/users');
}

// Traitement du changement de statut
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $user_id = intval($_POST['user_id'] ?? 0);
    $is_active = intval($_POST['is_active'] ?? 0);
    
    if ($user_id > 0 && $user_id !== getUserId()) {
        if (toggleUserStatus($user_id, $is_active)) {
            $status_text = $is_active ? 'activé' : 'désactivé';
            setFlashMessage('success', 'Utilisateur ' . $status_text . ' avec succès.');
        } else {
            setFlashMessage('error', 'Erreur lors de la modification du statut.');
        }
    } elseif ($user_id === getUserId()) {
        setFlashMessage('error', 'Vous ne pouvez pas modifier votre propre statut.');
    }
    
    redirect('/admin/users');
}

// Récupération de tous les utilisateurs
$users = getAllUsers();

include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div style="margin-bottom: 1.5rem;">
        <a href="<?php echo url('/admin'); ?>" style="color: var(--primary-color); text-decoration: none;">
            ← Retour au tableau de bord
        </a>
    </div>
    
    <h1 style="color: var(--primary-color); margin-bottom: 2rem;">Gestion des Utilisateurs</h1>
    
    <div class="card">
        <div class="table">
            <table style="width: 100%;">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Niveau</th>
                        <th>Solde</th>
                        <th>Score</th>
                        <th>Challenges</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td>
                                <a href="<?php echo url('/account?id=' . $user['id']); ?>" 
                                   style="color: var(--text-primary); text-decoration: none;">
                                    <?php echo h($user['username']); ?>
                                </a>
                            </td>
                            <td style="color: var(--text-secondary); font-size: 0.9rem;">
                                <?php echo h($user['email']); ?>
                            </td>
                            <td>
                                <form method="POST" action="<?php echo url('/admin/users'); ?>" style="margin: 0;">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <select name="role" 
                                            class="form-control" 
                                            style="padding: 0.25rem 0.5rem; font-size: 0.9rem;"
                                            onchange="this.form.submit()">
                                        <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                        <option value="creator" <?php echo $user['role'] === 'creator' ? 'selected' : ''; ?>>Creator</option>
                                        <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                    <button type="submit" name="change_role" style="display: none;"></button>
                                </form>
                            </td>
                            <td>
                                <span class="badge badge-category">
                                    <?php echo h(SKILL_LEVELS[$user['skill_level']]); ?>
                                </span>
                            </td>
                            <td><?php echo formatPrice($user['balance']); ?></td>
                            <td style="font-weight: bold; color: var(--primary-color);">
                                <?php echo $user['score']; ?>
                            </td>
                            <td style="font-size: 0.85rem; color: var(--text-secondary);">
                                <?php echo $user['challenges_created']; ?> créés<br>
                                <?php echo $user['challenges_purchased']; ?> achetés
                            </td>
                            <td>
                                <?php if ($user['is_active']): ?>
                                    <span class="badge" style="background: var(--success-color); color: var(--dark-bg);">
                                        Actif
                                    </span>
                                <?php else: ?>
                                    <span class="badge" style="background: var(--error-color); color: white;">
                                        Banni
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['id'] !== getUserId()): ?>
                                    <form method="POST" action="<?php echo url('/admin/users'); ?>" style="margin: 0;">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="is_active" value="<?php echo $user['is_active'] ? 0 : 1; ?>">
                                        <button type="submit" 
                                                name="toggle_status" 
                                                class="btn <?php echo $user['is_active'] ? 'btn-danger' : 'btn-success'; ?>" 
                                                style="font-size: 0.85rem; padding: 0.25rem 0.75rem;">
                                            <?php echo $user['is_active'] ? 'Bannir' : 'Activer'; ?>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>