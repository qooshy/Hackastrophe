<?php
/**
 * Page d'édition d'un challenge
 */

require_once __DIR__ . '/../includes/challenge_functions.php';

// Vérification de la connexion
requireLogin();

$challenge_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($challenge_id <= 0) {
    setFlashMessage('error', 'Challenge introuvable.');
    redirect('/');
}

$challenge = getChallengeById($challenge_id);

if (!$challenge) {
    setFlashMessage('error', 'Challenge introuvable.');
    redirect('/');
}

// Vérification des permissions
$user = getUserById(getUserId());
if ($challenge['author_id'] != getUserId() && $user['role'] !== ROLE_ADMIN) {
    setFlashMessage('error', 'Vous n\'avez pas la permission de modifier ce challenge.');
    redirect('/detail?id=' . $challenge_id);
}

$page_title = 'Modifier ' . $challenge['title'];
$errors = [];

// Traitement de la suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_challenge'])) {
    $result = deleteChallenge($challenge_id, getUserId());
    
    if ($result['success']) {
        setFlashMessage('success', $result['message']);
        redirect('/');
    } else {
        $errors[] = $result['message'];
    }
}

// Traitement de la modification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_challenge'])) {
    $data = [
        'title' => trim($_POST['title'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'category' => $_POST['category'] ?? '',
        'difficulty' => $_POST['difficulty'] ?? '',
        'price' => floatval($_POST['price'] ?? 0),
        'image_url' => trim($_POST['image_url'] ?? ''),
        'access_url' => trim($_POST['access_url'] ?? ''),
        'is_active' => isset($_POST['is_active']) ? 1 : 0
    ];
    
    $result = updateChallenge($challenge_id, $data, getUserId());
    
    if ($result['success']) {
        setFlashMessage('success', $result['message']);
        redirect('/detail?id=' . $challenge_id);
    } else {
        $errors[] = $result['message'];
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="card" style="max-width: 800px; margin: 0 auto;">
        <h1 style="color: var(--primary-color); margin-bottom: 0.5rem;">Modifier le Challenge</h1>
        <p style="color: var(--text-secondary); margin-bottom: 2rem;">
            Modifiez les informations de votre challenge.
        </p>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul style="list-style: none;">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo h($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="<?php echo url('/edit?id=' . $challenge_id); ?>">
            <div class="form-group">
                <label for="title">Titre du Challenge</label>
                <input type="text" 
                       id="title" 
                       name="title" 
                       class="form-control" 
                       value="<?php echo h($challenge['title']); ?>" 
                       required>
            </div>
            
            <div class="form-group">
                <label for="description">Description Détaillée</label>
                <textarea id="description" 
                          name="description" 
                          class="form-control" 
                          rows="6" 
                          required><?php echo h($challenge['description']); ?></textarea>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label for="category">Catégorie</label>
                    <select id="category" name="category" class="form-control" required>
                        <?php foreach (CHALLENGE_CATEGORIES as $key => $name): ?>
                            <option value="<?php echo h($key); ?>" 
                                    <?php echo $challenge['category'] === $key ? 'selected' : ''; ?>>
                                <?php echo h($name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="difficulty">Difficulté</label>
                    <select id="difficulty" name="difficulty" class="form-control" required>
                        <?php foreach (DIFFICULTY_LEVELS as $key => $name): ?>
                            <option value="<?php echo h($key); ?>" 
                                    <?php echo $challenge['difficulty'] === $key ? 'selected' : ''; ?>>
                                <?php echo h($name); ?> (<?php echo DIFFICULTY_POINTS[$key]; ?> points)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="price">Prix (en €)</label>
                <input type="number" 
                       id="price" 
                       name="price" 
                       class="form-control" 
                       value="<?php echo h($challenge['price']); ?>" 
                       min="0" 
                       step="0.01" 
                       required>
            </div>
            
            <div class="form-group">
                <label for="access_url">URL d'Accès au Challenge</label>
                <input type="text" 
                       id="access_url" 
                       name="access_url" 
                       class="form-control" 
                       value="<?php echo h($challenge['access_url']); ?>" 
                       placeholder="http://challenge.hackastrophe.fr/votre-challenge">
            </div>
            
            <div class="form-group">
                <label for="image_url">URL de l'Image</label>
                <input type="url" 
                       id="image_url" 
                       name="image_url" 
                       class="form-control" 
                       value="<?php echo h($challenge['image_url']); ?>" 
                       placeholder="https://example.com/image.png">
            </div>
            
            <div class="form-group">
                <label style="display: flex; align-items: center; cursor: pointer;">
                    <input type="checkbox" 
                           name="is_active" 
                           <?php echo $challenge['is_active'] ? 'checked' : ''; ?> 
                           style="margin-right: 0.5rem; width: auto;">
                    Challenge actif (visible par les utilisateurs)
                </label>
            </div>
            
            <hr style="border: none; border-top: 1px solid var(--border-color); margin: 2rem 0;">
            
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <button type="submit" name="update_challenge" class="btn btn-primary" style="flex: 1;">
                    Enregistrer les modifications
                </button>
                <a href="<?php echo url('/detail?id=' . $challenge_id); ?>" class="btn btn-secondary">
                    Annuler
                </a>
            </div>
        </form>
        
        <hr style="border: none; border-top: 1px solid var(--border-color); margin: 2rem 0;">
        
        <div style="background: rgba(255, 0, 85, 0.1); border: 1px solid var(--error-color); border-radius: 6px; padding: 1.5rem;">
            <h3 style="color: var(--error-color); margin-bottom: 1rem;">Zone Dangereuse</h3>
            <p style="color: var(--text-secondary); margin-bottom: 1rem;">
                La suppression du challenge est définitive et irréversible. Toutes les données associées seront perdues.
            </p>
            <form method="POST" action="<?php echo url('/edit?id=' . $challenge_id); ?>" onsubmit="return confirm('Êtes-vous absolument certain de vouloir supprimer ce challenge ? Cette action est irréversible.');">
                <button type="submit" name="delete_challenge" class="btn btn-danger">
                    Supprimer définitivement ce challenge
                </button>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>