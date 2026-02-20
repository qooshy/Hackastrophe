<?php
/**
 * Page de connexion
 */

require_once __DIR__ . '/../includes/user_functions.php';

// Si déjà connecté, rediriger vers l'accueil
if (isLoggedIn()) {
    redirect('/');
}

$page_title = 'Connexion';
$errors = [];

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($login)) {
        $errors[] = 'Le nom d\'utilisateur ou l\'email est requis.';
    }
    
    if (empty($password)) {
        $errors[] = 'Le mot de passe est requis.';
    }
    
    if (empty($errors)) {
        $result = authenticateUser($login, $password);
        
        if ($result['success']) {
            // Création de la session
            $_SESSION['user_id'] = $result['user']['id'];
            $_SESSION['username'] = $result['user']['username'];
            $_SESSION['user_role'] = $result['user']['role'];
            
            // Régénération de l'ID de session pour sécurité
            session_regenerate_id(true);
            
            // Redirection vers la page demandée initialement ou l'accueil
            $redirect_to = $_SESSION['redirect_after_login'] ?? '/';
            unset($_SESSION['redirect_after_login']);
            
            setFlashMessage('success', 'Connexion réussie. Bon retour ' . $result['user']['username'] . ' !');
            redirect($redirect_to);
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
            <h1>Connexion</h1>
            <p style="color: var(--text-secondary);">Accédez à votre espace pentester</p>
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
        
        <form method="POST" action="<?php echo url('/login'); ?>">
            <div class="form-group">
                <label for="login">Nom d'utilisateur ou Email</label>
                <input type="text" 
                       id="login" 
                       name="login" 
                       class="form-control" 
                       value="<?php echo h($_POST['login'] ?? ''); ?>" 
                       required 
                       autofocus>
            </div>
            
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" 
                       id="password" 
                       name="password" 
                       class="form-control" 
                       required>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">
                Se connecter
            </button>
        </form>
        
        <div class="auth-footer">
            Pas encore de compte ? 
            <a href="<?php echo url('/register'); ?>">S'inscrire</a>
        </div>
        
        <div style="margin-top: 2rem; padding: 1rem; background: var(--darker-bg); border-radius: 6px;">
            <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 0.5rem;">
                Comptes de test disponibles :
            </p>
            <ul style="list-style: none; color: var(--text-secondary); font-size: 0.85rem;">
                <li>Admin: admin / password123</li>
                <li>Creator: pentester_pro / password123</li>
                <li>User: john_doe / password123</li>
            </ul>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>