<?php
/**
 * Page d'inscription
 */

require_once __DIR__ . '/../includes/user_functions.php';

// Si déjà connecté, rediriger vers l'accueil
if (isLoggedIn()) {
    redirect('/');
}

$page_title = 'Inscription';
$errors = [];
$success = false;

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $bio = trim($_POST['bio'] ?? '');
    $skill_level = $_POST['skill_level'] ?? 'junior';
    
    // Validation
    if (empty($username)) {
        $errors[] = 'Le nom d\'utilisateur est requis.';
    }
    
    if (empty($email)) {
        $errors[] = 'L\'email est requis.';
    }
    
    if (empty($password)) {
        $errors[] = 'Le mot de passe est requis.';
    }
    
    if ($password !== $password_confirm) {
        $errors[] = 'Les mots de passe ne correspondent pas.';
    }
    
    if (empty($errors)) {
        $result = createUser($username, $email, $password, $bio, $skill_level);
        
        if ($result['success']) {
            // Connexion automatique
            $_SESSION['user_id'] = $result['user_id'];
            $_SESSION['username'] = $username;
            
            // Récupération du rôle pour la session
            $user = getUserById($result['user_id']);
            $_SESSION['user_role'] = $user['role'];
            
            // Régénération de l'ID de session pour sécurité
            session_regenerate_id(true);
            
            setFlashMessage('success', 'Compte créé avec succès ! Bienvenue sur Hackastrophe.');
            redirect('/');
        } else {
            $errors[] = $result['message'];
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h1>Créer un compte</h1>
            <p style="color: var(--text-secondary);">Rejoignez la communauté des pentesters</p>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul style="list-style: none;">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo h($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="<?php echo url('/register'); ?>">
            <div class="form-group">
                <label for="username">Nom d'utilisateur</label>
                <input type="text" 
                       id="username" 
                       name="username" 
                       class="form-control" 
                       value="<?php echo h($_POST['username'] ?? ''); ?>" 
                       required 
                       autofocus>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" 
                       id="email" 
                       name="email" 
                       class="form-control" 
                       value="<?php echo h($_POST['email'] ?? ''); ?>" 
                       required>
            </div>
            
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" 
                       id="password" 
                       name="password" 
                       class="form-control" 
                       required>
                <small style="color: var(--text-secondary); display: block; margin-top: 0.25rem;">
                    Minimum 8 caractères
                </small>
            </div>
            
            <div class="form-group">
                <label for="password_confirm">Confirmer le mot de passe</label>
                <input type="password" 
                       id="password_confirm" 
                       name="password_confirm" 
                       class="form-control" 
                       required>
            </div>
            
            <div class="form-group">
                <label for="skill_level">Niveau de compétence</label>
                <select id="skill_level" name="skill_level" class="form-control">
                    <?php foreach (SKILL_LEVELS as $key => $name): ?>
                        <option value="<?php echo h($key); ?>" 
                                <?php echo (isset($_POST['skill_level']) && $_POST['skill_level'] === $key) ? 'selected' : ''; ?>>
                            <?php echo h($name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="bio">Biographie (optionnel)</label>
                <textarea id="bio" 
                          name="bio" 
                          class="form-control" 
                          placeholder="Parlez-nous de vous et de vos compétences..."><?php echo h($_POST['bio'] ?? ''); ?></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">
                Créer mon compte
            </button>
        </form>
        
        <div class="auth-footer">
            Vous avez déjà un compte ? 
            <a href="<?php echo url('/login'); ?>">Se connecter</a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>