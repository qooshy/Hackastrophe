<?php
/**
 * Page d'accueil - Liste des challenges
 */

require_once __DIR__ . '/../includes/challenge_functions.php';

$page_title = 'Challenges';

// Gestion des filtres
$filters = [];
if (!empty($_GET['category'])) {
    $filters['category'] = $_GET['category'];
}
if (!empty($_GET['difficulty'])) {
    $filters['difficulty'] = $_GET['difficulty'];
}
if (!empty($_GET['search'])) {
    $filters['search'] = $_GET['search'];
}

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * ITEMS_PER_PAGE;

// Récupération des challenges
$challenges = getAllChallenges($filters, ITEMS_PER_PAGE, $offset);
$total_challenges = countChallenges($filters);
$total_pages = ceil($total_challenges / ITEMS_PER_PAGE);

include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="page-header" style="margin-bottom: 2rem;">
        <h1 style="color: var(--primary-color); font-size: 2.5rem; margin-bottom: 0.5rem;">
            Découvrez nos Challenges
        </h1>
        <p style="color: var(--text-secondary); font-size: 1.1rem;">
            <?php echo $total_challenges; ?> challenge<?php echo $total_challenges > 1 ? 's' : ''; ?> disponible<?php echo $total_challenges > 1 ? 's' : ''; ?>
        </p>
    </div>
    
    <!-- Filtres -->
    <div class="filters">
        <form method="GET" action="<?php echo url('/'); ?>">
            <div class="filters-grid">
                <div class="form-group">
                    <label for="search">Rechercher</label>
                    <input type="text" 
                           id="search" 
                           name="search" 
                           class="form-control" 
                           placeholder="Titre ou description..." 
                           value="<?php echo h($_GET['search'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="category">Catégorie</label>
                    <select id="category" name="category" class="form-control">
                        <option value="">Toutes les catégories</option>
                        <?php foreach (CHALLENGE_CATEGORIES as $key => $name): ?>
                            <option value="<?php echo h($key); ?>" 
                                    <?php echo (isset($filters['category']) && $filters['category'] === $key) ? 'selected' : ''; ?>>
                                <?php echo h($name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="difficulty">Difficulté</label>
                    <select id="difficulty" name="difficulty" class="form-control">
                        <option value="">Toutes les difficultés</option>
                        <?php foreach (DIFFICULTY_LEVELS as $key => $name): ?>
                            <option value="<?php echo h($key); ?>" 
                                    <?php echo (isset($filters['difficulty']) && $filters['difficulty'] === $key) ? 'selected' : ''; ?>>
                                <?php echo h($name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group" style="display: flex; align-items: flex-end; gap: 0.5rem;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Filtrer</button>
                    <a href="<?php echo url('/'); ?>" class="btn btn-secondary">Reset</a>
                </div>
            </div>
        </form>
    </div>
    
    <?php if (empty($challenges)): ?>
        <div class="card" style="text-align: center; padding: 3rem;">
            <h2>Aucun challenge trouvé</h2>
            <p style="color: var(--text-secondary); margin-top: 1rem;">
                Essayez de modifier vos filtres ou revenez plus tard.
            </p>
        </div>
    <?php else: ?>
        <!-- Grille de challenges -->
        <div class="challenges-grid">
            <?php foreach ($challenges as $challenge): ?>
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
                        
                        <p style="color: var(--text-secondary); margin-bottom: 1rem; min-height: 60px;">
                            <?php 
                            $desc = strlen($challenge['description']) > 120 
                                ? substr($challenge['description'], 0, 120) . '...' 
                                : $challenge['description'];
                            echo h($desc); 
                            ?>
                        </p>
                        
                        <div class="challenge-price">
                            <?php echo formatPrice($challenge['price']); ?>
                        </div>
                        
                        <div class="challenge-footer">
                            <span class="challenge-author">
                                par <?php echo h($challenge['author_name']); ?>
                            </span>
                            <a href="<?php echo url('/detail?id=' . $challenge['id']); ?>" 
                               class="btn btn-primary">
                                Voir détails
                            </a>
                        </div>
                        
                        <?php if ($challenge['solved_count'] > 0): ?>
                            <div style="margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid var(--border-color); color: var(--success-color); font-size: 0.85rem;">
                                Résolu par <?php echo $challenge['solved_count']; ?> pentester<?php echo $challenge['solved_count'] > 1 ? 's' : ''; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="<?php echo url('/?' . http_build_query(array_merge($_GET, ['page' => $page - 1]))); ?>">
                        Précédent
                    </a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i === $page): ?>
                        <span class="active"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="<?php echo url('/?' . http_build_query(array_merge($_GET, ['page' => $i]))); ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="<?php echo url('/?' . http_build_query(array_merge($_GET, ['page' => $page + 1]))); ?>">
                        Suivant
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>