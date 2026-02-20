<?php
/**
 * Page de création d'un challenge
 */

require_once __DIR__ . '/../includes/challenge_functions.php';

// Vérification de la connexion
requireLogin();

$page_title = 'Créer un Challenge';
$errors = [];

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'title' => trim($_POST['title'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'category' => $_POST['category'] ?? '',
        'difficulty' => $_POST['difficulty'] ?? '',
        'price' => floatval($_POST['price'] ?? 0),
        'image_url' => trim($_POST['image_url'] ?? ''),
        'access_url' => trim($_POST['access_url'] ?? ''),
        'flag' => trim($_POST['flag'] ?? ''),
        'author_id' => getUserId()
    ];
    
    // Validation
    if (empty($data['title'])) {
        $errors[] = 'Le titre est requis.';
    }
    
    if (empty($data['description'])) {
        $errors[] = 'La description est requise.';
    }
    
    if (empty($data['category']) || !array_key_exists($data['category'], CHALLENGE_CATEGORIES)) {
        $errors[] = 'Catégorie invalide.';
    }
    
    if (empty($data['difficulty']) || !array_key_exists($data['difficulty'], DIFFICULTY_LEVELS)) {
        $errors[] = 'Difficulté invalide.';
    }
    
    if ($data['price'] < 0) {
        $errors[] = 'Le prix doit être positif.';
    }
    
    if (empty($data['flag'])) {
        $errors[] = 'Le flag est requis.';
    }
    
    if (empty($errors)) {
        $result = createChallenge($data);
        
        if ($result['success']) {
            setFlashMessage('success', 'Challenge créé avec succès !');
            redirect('/detail?id=' . $result['challenge_id']);
        } else {
            $errors[] = $result['message'];
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="card" style="max-width: 800px; margin: 0 auto;">
        <h1 style="color: var(--primary-color); margin-bottom: 0.5rem;">Créer un Challenge</h1>
        <p style="color: var(--text-secondary); margin-bottom: 2rem;">
            Partagez vos compétences en créant un challenge pour la communauté.
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
        
        <form method="POST" action="<?php echo url('/sell'); ?>">
            <div class="form-group">
                <label for="title">Titre du Challenge</label>
                <input type="text" 
                       id="title" 
                       name="title" 
                       class="form-control" 
                       value="<?php echo h($_POST['title'] ?? ''); ?>" 
                       required 
                       autofocus>
            </div>
            
            <div class="form-group">
                <label for="description">Description Détaillée</label>
                <textarea id="description" 
                          name="description" 
                          class="form-control" 
                          rows="6" 
                          required 
                          placeholder="Décrivez le challenge, les objectifs, les techniques requises..."><?php echo h($_POST['description'] ?? ''); ?></textarea>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label for="category">Catégorie</label>
                    <select id="category" name="category" class="form-control" required>
                        <option value="">Sélectionnez une catégorie</option>
                        <?php foreach (CHALLENGE_CATEGORIES as $key => $name): ?>
                            <option value="<?php echo h($key); ?>" 
                                    <?php echo (isset($_POST['category']) && $_POST['category'] === $key) ? 'selected' : ''; ?>>
                                <?php echo h($name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="difficulty">Difficulté</label>
                    <select id="difficulty" name="difficulty" class="form-control" required>
                        <option value="">Sélectionnez une difficulté</option>
                        <?php foreach (DIFFICULTY_LEVELS as $key => $name): ?>
                            <option value="<?php echo h($key); ?>" 
                                    <?php echo (isset($_POST['difficulty']) && $_POST['difficulty'] === $key) ? 'selected' : ''; ?>>
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
                       value="<?php echo h($_POST['price'] ?? '50'); ?>" 
                       min="0" 
                       step="0.01" 
                       required>
                <small style="color: var(--text-secondary); display: block; margin-top: 0.25rem;">
                    Prix que les utilisateurs devront payer pour accéder au challenge.
                </small>
            </div>
            
            <div class="form-group">
                <label for="flag">Flag</label>
                <input type="text" 
                       id="flag" 
                       name="flag" 
                       class="form-control" 
                       value="<?php echo h($_POST['flag'] ?? ''); ?>" 
                       placeholder="FLAG{votre_flag_secret}" 
                       required>
                <small style="color: var(--text-secondary); display: block; margin-top: 0.25rem;">
                    Le flag que les utilisateurs devront trouver. Il sera stocké de manière sécurisée.
                </small>
            </div>
            
            <div class="form-group">
                <label for="access_url">URL d'Accès au Challenge (optionnel)</label>
                <input type="text" 
                       id="access_url" 
                       name="access_url" 
                       class="form-control" 
                       value="<?php echo h($_POST['access_url'] ?? ''); ?>" 
                       placeholder="http://challenge.hackastrophe.fr/votre-challenge ou nc IP PORT">
                <small style="color: var(--text-secondary); display: block; margin-top: 0.25rem;">
                    URL, adresse IP:PORT, ou instructions pour accéder au challenge.
                </small>
            </div>
            
            <div class="form-group">
                <label for="image_url">URL de l'Image (optionnel)</label>
                <input type="url" 
                       id="image_url" 
                       name="image_url" 
                       class="form-control" 
                       value="<?php echo h($_POST['image_url'] ?? ''); ?>" 
                       placeholder="https://example.com/image.png">
                <small style="color: var(--text-secondary); display: block; margin-top: 0.25rem;">
                    URL d'une image représentative du challenge (max 2 Mo recommandé).
                </small>
            </div>
            
            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">
                    Créer le Challenge
                </button>
                <a href="<?php echo url('/'); ?>" class="btn btn-secondary">
                    Annuler
                </a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>