<?php
/**
 * Page de détail d'un challenge
 */

require_once __DIR__ . '/../includes/challenge_functions.php';
require_once __DIR__ . '/../includes/cart_functions.php';

// Récupération de l'ID du challenge
$challenge_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($challenge_id <= 0) {
    setFlashMessage('error', 'Challenge introuvable.');
    redirect('/');
}

// Récupération du challenge
$challenge = getChallengeById($challenge_id);

if (!$challenge) {
    setFlashMessage('error', 'Challenge introuvable.');
    redirect('/');
}

$page_title = $challenge['title'];

// Vérification si l'utilisateur a acheté le challenge
$has_purchased = false;
$has_solved = false;
$submissions = [];

if (isLoggedIn()) {
    $has_purchased = hasUserPurchasedChallenge(getUserId(), $challenge_id);
    $has_solved = hasUserSolvedChallenge(getUserId(), $challenge_id);
    
    if ($has_purchased) {
        $submissions = getChallengeSubmissions(getUserId(), $challenge_id);
    }
}

// Traitement de l'ajout au panier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    requireLogin();
    
    $result = addToCart(getUserId(), $challenge_id);
    if ($result['success']) {
        setFlashMessage('success', $result['message']);
    } else {
        setFlashMessage('error', $result['message']);
    }
    redirect('/detail?id=' . $challenge_id);
}

// Traitement de la soumission de flag
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_flag'])) {
    requireLogin();
    
    $flag = trim($_POST['flag'] ?? '');
    
    if (empty($flag)) {
        setFlashMessage('error', 'Veuillez entrer un flag.');
    } else {
        $result = submitFlag(getUserId(), $challenge_id, $flag);
        
        if ($result['success']) {
            if ($result['valid']) {
                setFlashMessage('success', $result['message']);
                $has_solved = true;
            } else {
                setFlashMessage('error', $result['message']);
            }
        } else {
            setFlashMessage('error', $result['message']);
        }
    }
    
    redirect('/detail?id=' . $challenge_id);
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div style="margin-bottom: 1.5rem;">
        <a href="<?php echo url('/'); ?>" style="color: var(--primary-color); text-decoration: none;">
            ← Retour aux challenges
        </a>
    </div>
    
    <div class="card" style="margin-bottom: 2rem;">
        <?php if ($challenge['image_url']): ?>
            <img src="<?php echo h($challenge['image_url']); ?>" 
                 alt="<?php echo h($challenge['title']); ?>" 
                 style="width: 100%; height: 300px; object-fit: cover; border-radius: 8px; margin-bottom: 1.5rem;">
        <?php endif; ?>
        
        <h1 style="color: var(--primary-color); font-size: 2.5rem; margin-bottom: 1rem;">
            <?php echo h($challenge['title']); ?>
        </h1>
        
        <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; align-items: center;">
            <span class="badge badge-category">
                <?php echo h(CHALLENGE_CATEGORIES[$challenge['category']]); ?>
            </span>
            <span class="badge badge-difficulty badge-<?php echo h($challenge['difficulty']); ?>">
                <?php echo h(DIFFICULTY_LEVELS[$challenge['difficulty']]); ?>
            </span>
            <span style="color: var(--text-secondary);">
                par <strong><?php echo h($challenge['author_name']); ?></strong>
            </span>
            <span style="color: var(--text-secondary);">
                Publié le <?php echo formatDate($challenge['created_at']); ?>
            </span>
        </div>
        
        <div style="display: flex; gap: 2rem; margin-bottom: 2rem; flex-wrap: wrap;">
            <div>
                <div style="font-size: 2.5rem; font-weight: bold; color: var(--primary-color);">
                    <?php echo formatPrice($challenge['price']); ?>
                </div>
                <div style="color: var(--text-secondary); font-size: 0.9rem;">
                    <?php echo $challenge['points']; ?> points si résolu
                </div>
            </div>
            
            <div style="flex: 1; min-width: 200px;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
                    <div>
                        <div style="color: var(--text-secondary); font-size: 0.85rem;">Achats</div>
                        <div style="font-size: 1.5rem; font-weight: bold; color: var(--primary-color);">
                            <?php echo $challenge['purchase_count']; ?>
                        </div>
                    </div>
                    <div>
                        <div style="color: var(--text-secondary); font-size: 0.85rem;">Résolutions</div>
                        <div style="font-size: 1.5rem; font-weight: bold; color: var(--success-color);">
                            <?php echo $challenge['solved_count']; ?>
                        </div>
                    </div>
                    <div>
                        <div style="color: var(--text-secondary); font-size: 0.85rem;">Soumissions</div>
                        <div style="font-size: 1.5rem; font-weight: bold;">
                            <?php echo $challenge['total_submissions']; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <hr style="border: none; border-top: 1px solid var(--border-color); margin: 2rem 0;">
        
        <h2 style="color: var(--primary-color); margin-bottom: 1rem;">Description</h2>
        <div style="color: var(--text-secondary); line-height: 1.8; white-space: pre-wrap;">
            <?php echo h($challenge['description']); ?>
        </div>
        
        <?php if ($challenge['access_url']): ?>
            <div style="margin-top: 2rem; padding: 1.5rem; background: var(--darker-bg); border-radius: 6px;">
                <h3 style="color: var(--primary-color); margin-bottom: 0.75rem;">Accès au Challenge</h3>
                <code style="color: var(--text-primary); background: var(--card-bg); padding: 0.5rem 1rem; border-radius: 4px; display: inline-block;">
                    <?php echo h($challenge['access_url']); ?>
                </code>
            </div>
        <?php endif; ?>
        
        <?php if ($has_solved): ?>
            <div style="margin-top: 2rem; padding: 1.5rem; background: rgba(0, 255, 136, 0.1); border: 2px solid var(--success-color); border-radius: 8px; text-align: center;">
                <h2 style="color: var(--success-color); margin-bottom: 0.5rem;">Challenge Résolu !</h2>
                <p style="color: var(--text-secondary);">
                    Félicitations, vous avez gagné <?php echo $challenge['points']; ?> points.
                </p>
            </div>
        <?php elseif ($has_purchased): ?>
            <div style="margin-top: 2rem;">
                <h3 style="color: var(--primary-color); margin-bottom: 1rem;">Soumettre un Flag</h3>
                <form method="POST" action="<?php echo url('/detail?id=' . $challenge_id); ?>">
                    <div style="display: flex; gap: 1rem;">
                        <input type="text" 
                               name="flag" 
                               class="form-control" 
                               placeholder="FLAG{votre_flag_ici}" 
                               required 
                               style="flex: 1;">
                        <button type="submit" name="submit_flag" class="btn btn-success">
                            Valider
                        </button>
                    </div>
                </form>
                
                <?php if (!empty($submissions)): ?>
                    <div style="margin-top: 1.5rem;">
                        <h4 style="color: var(--text-secondary); margin-bottom: 0.75rem; font-size: 1rem;">
                            Historique de vos soumissions (<?php echo count($submissions); ?>)
                        </h4>
                        <div style="max-height: 200px; overflow-y: auto;">
                            <?php foreach ($submissions as $sub): ?>
                                <div style="padding: 0.75rem; background: var(--darker-bg); border-radius: 4px; margin-bottom: 0.5rem; display: flex; justify-content: space-between; align-items: center;">
                                    <code style="color: var(--text-secondary); font-size: 0.9rem;">
                                        <?php echo h($sub['flag_submitted']); ?>
                                    </code>
                                    <div>
                                        <?php if ($sub['is_valid']): ?>
                                            <span class="badge" style="background: var(--success-color); color: var(--dark-bg);">
                                                Valide
                                            </span>
                                        <?php else: ?>
                                            <span class="badge" style="background: var(--error-color); color: white;">
                                                Invalide
                                            </span>
                                        <?php endif; ?>
                                        <span style="color: var(--text-secondary); font-size: 0.85rem; margin-left: 0.5rem;">
                                            <?php echo formatDate($sub['submitted_at']); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div style="margin-top: 2rem;">
                <?php if (isLoggedIn()): ?>
                    <?php if (getUserId() == $challenge['author_id']): ?>
                        <div class="alert alert-info">
                            Vous êtes l'auteur de ce challenge. Vous ne pouvez pas l'acheter.
                        </div>
                    <?php else: ?>
                        <form method="POST" action="<?php echo url('/detail?id=' . $challenge_id); ?>">
                            <button type="submit" name="add_to_cart" class="btn btn-primary" style="font-size: 1.2rem; padding: 1rem 2rem;">
                                Ajouter au panier
                            </button>
                        </form>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-info">
                        Vous devez être connecté pour acheter ou résoudre ce challenge.
                        <div style="margin-top: 1rem;">
                            <a href="<?php echo url('/login'); ?>" class="btn btn-primary">Se connecter</a>
                            <a href="<?php echo url('/register'); ?>" class="btn btn-secondary" style="margin-left: 0.5rem;">S'inscrire</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isLoggedIn() && (getUserId() == $challenge['author_id'] || isAdmin())): ?>
            <hr style="border: none; border-top: 1px solid var(--border-color); margin: 2rem 0;">
            <div style="display: flex; gap: 1rem;">
                <a href="<?php echo url('/edit?id=' . $challenge_id); ?>" class="btn btn-warning">
                    Modifier ce challenge
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>