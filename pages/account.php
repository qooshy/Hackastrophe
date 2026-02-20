<?php
/**
 * Page de compte utilisateur
 */

require_once __DIR__ . '/../includes/user_functions.php';
require_once __DIR__ . '/../includes/challenge_functions.php';

// Déterminer quel compte afficher
$view_user_id = isset($_GET['id']) ? intval($_GET['id']) : (isLoggedIn() ? getUserId() : 0);

if ($view_user_id <= 0) {
    setFlashMessage('error', 'Utilisateur introuvable.');
    redirect('/');
}

$is_own_account = isLoggedIn() && $view_user_id === getUserId();

// Récupération des infos utilisateur
if ($is_own_account) {
    $user = getUserById($view_user_id, false);
} else {
    $user = getUserById($view_user_id, true);
}

if (!$user) {
    setFlashMessage('error', 'Utilisateur introuvable.');
    redirect('/');
}

$page_title = $user['username'];
$errors = [];

// Traitement de l'ajout de crédits (simulation)
if ($is_own_account && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_credits'])) {
    $amount = floatval($_POST['amount'] ?? 0);
    
    if ($amount > 0 && $amount <= 1000) {
        if (addBalance(getUserId(), $amount)) {
            setFlashMessage('success', 'Crédits ajoutés avec succès !');
            redirect('/account');
        } else {
            $errors[] = 'Erreur lors de l\'ajout des crédits.';
        }
    } else {
        $errors[] = 'Montant invalide (max 1000€ par transaction).';
    }
}

// Traitement de la modification du profil
if ($is_own_account && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $data = [
        'email' => trim($_POST['email'] ?? ''),
        'bio' => trim($_POST['bio'] ?? ''),
        'skill_level' => $_POST['skill_level'] ?? 'junior',
        'profile_picture' => trim($_POST['profile_picture'] ?? '')
    ];
    
    if (updateUser(getUserId(), $data)) {
        setFlashMessage('success', 'Profil mis à jour avec succès !');
        redirect('/account');
    } else {
        $errors[] = 'Erreur lors de la mise à jour du profil.';
    }
}

// Traitement du changement de mot de passe
if ($is_own_account && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $new_password_confirm = $_POST['new_password_confirm'] ?? '';
    
    if ($new_password !== $new_password_confirm) {
        $errors[] = 'Les nouveaux mots de passe ne correspondent pas.';
    } else {
        $result = changePassword(getUserId(), $old_password, $new_password);
        if ($result['success']) {
            setFlashMessage('success', $result['message']);
            redirect('/account');
        } else {
            $errors[] = $result['message'];
        }
    }
}

// Récupération des statistiques
$stats = getUserStats($view_user_id);
$created_challenges = getUserCreatedChallenges($view_user_id);
$purchased_challenges = getUserPurchasedChallenges($view_user_id);

if ($is_own_account) {
    $invoices = getUserInvoices($view_user_id);
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <ul style="list-style: none;">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo h($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <div class="account-header">
        <?php if ($user['profile_picture']): ?>
            <img src="<?php echo h($user['profile_picture']); ?>" 
                 alt="<?php echo h($user['username']); ?>" 
                 class="profile-picture">
        <?php else: ?>
            <div class="profile-picture" style="background: linear-gradient(135deg, var(--secondary-color), var(--primary-color)); display: flex; align-items: center; justify-content: center; font-size: 3rem; font-weight: bold;">
                <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
            </div>
        <?php endif; ?>
        
        <div class="account-info" style="flex: 1;">
            <h1><?php echo h($user['username']); ?></h1>
            <div style="display: flex; gap: 1rem; margin-top: 0.5rem; flex-wrap: wrap;">
                <span class="badge badge-category">
                    <?php echo h(SKILL_LEVELS[$user['skill_level']]); ?>
                </span>
                <?php if ($is_own_account): ?>
                    <span class="badge" style="background: var(--primary-color); color: var(--dark-bg);">
                        Solde: <?php echo formatPrice($user['balance']); ?>
                    </span>
                <?php endif; ?>
            </div>
            
            <?php if ($user['bio']): ?>
                <p style="color: var(--text-secondary); margin-top: 1rem; max-width: 600px;">
                    <?php echo h($user['bio']); ?>
                </p>
            <?php endif; ?>
            
            <?php if (!$is_own_account): ?>
                <p style="color: var(--text-secondary); margin-top: 0.5rem; font-size: 0.9rem;">
                    Membre depuis <?php echo formatDate($user['created_at']); ?>
                </p>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="account-stats">
        <div class="stat-card">
            <div class="stat-value"><?php echo $user['score']; ?></div>
            <div class="stat-label">Points</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $stats['challenges_created']; ?></div>
            <div class="stat-label">Challenges Créés</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $stats['challenges_solved']; ?></div>
            <div class="stat-label">Challenges Résolus</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $stats['challenges_purchased']; ?></div>
            <div class="stat-label">Challenges Achetés</div>
        </div>
    </div>
    
    <?php if ($is_own_account): ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 2rem; margin-bottom: 2rem;">
            <div class="card">
                <h2 style="color: var(--primary-color); margin-bottom: 1.5rem;">Ajouter des Crédits</h2>
                <form method="POST" action="<?php echo url('/account'); ?>">
                    <div class="form-group">
                        <label for="amount">Montant (€)</label>
                        <input type="number" 
                               id="amount" 
                               name="amount" 
                               class="form-control" 
                               min="1" 
                               max="1000" 
                               step="0.01" 
                               value="100" 
                               required>
                        <small style="color: var(--text-secondary); display: block; margin-top: 0.25rem;">
                            Simulation d'ajout de crédits (max 1000€)
                        </small>
                    </div>
                    <button type="submit" name="add_credits" class="btn btn-success btn-block">
                        Ajouter des Crédits
                    </button>
                </form>
            </div>
            
            <div class="card">
                <h2 style="color: var(--primary-color); margin-bottom: 1.5rem;">Modifier le Profil</h2>
                <form method="POST" action="<?php echo url('/account'); ?>">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               class="form-control" 
                               value="<?php echo h($user['email']); ?>" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="skill_level">Niveau</label>
                        <select id="skill_level" name="skill_level" class="form-control">
                            <?php foreach (SKILL_LEVELS as $key => $name): ?>
                                <option value="<?php echo h($key); ?>" 
                                        <?php echo $user['skill_level'] === $key ? 'selected' : ''; ?>>
                                    <?php echo h($name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="bio">Biographie</label>
                        <textarea id="bio" 
                                  name="bio" 
                                  class="form-control" 
                                  rows="3"><?php echo h($user['bio']); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="profile_picture">URL Photo de Profil</label>
                        <input type="url" 
                               id="profile_picture" 
                               name="profile_picture" 
                               class="form-control" 
                               value="<?php echo h($user['profile_picture']); ?>" 
                               placeholder="https://example.com/avatar.png">
                    </div>
                    
                    <button type="submit" name="update_profile" class="btn btn-primary btn-block">
                        Mettre à Jour
                    </button>
                </form>
            </div>
        </div>
        
        <div class="card" style="margin-bottom: 2rem;">
            <h2 style="color: var(--primary-color); margin-bottom: 1.5rem;">Changer le Mot de Passe</h2>
            <form method="POST" action="<?php echo url('/account'); ?>" style="max-width: 500px;">
                <div class="form-group">
                    <label for="old_password">Ancien mot de passe</label>
                    <input type="password" 
                           id="old_password" 
                           name="old_password" 
                           class="form-control" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="new_password">Nouveau mot de passe</label>
                    <input type="password" 
                           id="new_password" 
                           name="new_password" 
                           class="form-control" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="new_password_confirm">Confirmer le nouveau mot de passe</label>
                    <input type="password" 
                           id="new_password_confirm" 
                           name="new_password_confirm" 
                           class="form-control" 
                           required>
                </div>
                
                <button type="submit" name="change_password" class="btn btn-warning">
                    Changer le Mot de Passe
                </button>
            </form>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($created_challenges)): ?>
        <div class="card" style="margin-bottom: 2rem;">
            <h2 style="color: var(--primary-color); margin-bottom: 1.5rem;">Challenges Créés</h2>
            <div class="challenges-grid">
                <?php foreach ($created_challenges as $challenge): ?>
                    <div class="challenge-card fade-in">
                        <?php if ($challenge['image_url']): ?>
                            <img src="<?php echo h($challenge['image_url']); ?>" 
                                 alt="<?php echo h($challenge['title']); ?>" 
                                 class="challenge-image">
                        <?php else: ?>
                            <div class="challenge-image"></div>
                        <?php endif; ?>
                        
                        <div class="challenge-content">
                            <h3 class="challenge-title"><?php echo h($challenge['title']); ?></h3>
                            <div class="challenge-meta">
                                <span class="badge badge-category">
                                    <?php echo h(CHALLENGE_CATEGORIES[$challenge['category']]); ?>
                                </span>
                                <span class="badge badge-difficulty badge-<?php echo h($challenge['difficulty']); ?>">
                                    <?php echo h(DIFFICULTY_LEVELS[$challenge['difficulty']]); ?>
                                </span>
                            </div>
                            <div class="challenge-price">
                                <?php echo formatPrice($challenge['price']); ?>
                            </div>
                            <div class="challenge-footer">
                                <span style="color: var(--success-color);">
                                    <?php echo $challenge['purchase_count']; ?> achat<?php echo $challenge['purchase_count'] > 1 ? 's' : ''; ?>
                                </span>
                                <a href="<?php echo url('/detail?id=' . $challenge['id']); ?>" 
                                   class="btn btn-primary">
                                    Voir
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($is_own_account && !empty($purchased_challenges)): ?>
        <div class="card" style="margin-bottom: 2rem;">
            <h2 style="color: var(--primary-color); margin-bottom: 1.5rem;">Mes Challenges</h2>
            <div class="table">
                <table style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>Catégorie</th>
                            <th>Difficulté</th>
                            <th>Acheté le</th>
                            <th>Statut</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($purchased_challenges as $challenge): ?>
                            <tr>
                                <td><?php echo h($challenge['title']); ?></td>
                                <td><?php echo h(CHALLENGE_CATEGORIES[$challenge['category']]); ?></td>
                                <td>
                                    <span class="badge badge-difficulty badge-<?php echo h($challenge['difficulty']); ?>">
                                        <?php echo h(DIFFICULTY_LEVELS[$challenge['difficulty']]); ?>
                                    </span>
                                </td>
                                <td><?php echo formatDate($challenge['purchased_at']); ?></td>
                                <td>
                                    <?php if ($challenge['is_solved']): ?>
                                        <span class="badge" style="background: var(--success-color); color: var(--dark-bg);">
                                            Résolu
                                        </span>
                                    <?php else: ?>
                                        <span class="badge" style="background: var(--warning-color); color: var(--dark-bg);">
                                            En cours
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo url('/detail?id=' . $challenge['id']); ?>" class="btn btn-primary">
                                        Accéder
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($is_own_account && !empty($invoices)): ?>
        <div class="card">
            <h2 style="color: var(--primary-color); margin-bottom: 1.5rem;">Mes Factures</h2>
            <div class="table">
                <table style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Numéro</th>
                            <th>Date</th>
                            <th>Montant</th>
                            <th>Articles</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($invoices as $invoice): ?>
                            <tr>
                                <td>#<?php echo str_pad($invoice['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo formatDate($invoice['date']); ?></td>
                                <td><?php echo formatPrice($invoice['amount']); ?></td>
                                <td><?php echo $invoice['item_count']; ?> article<?php echo $invoice['item_count'] > 1 ? 's' : ''; ?></td>
                                <td>
                                    <a href="<?php echo url('/invoice?id=' . $invoice['id']); ?>" class="btn btn-secondary">
                                        Voir
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>