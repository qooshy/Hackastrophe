<?php
/**
 * Fonctions de gestion des utilisateurs
 * 
 * Contient toutes les fonctions relatives aux opérations sur les utilisateurs
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Crée un nouveau compte utilisateur
 * 
 * @param string $username Nom d'utilisateur
 * @param string $email Email
 * @param string $password Mot de passe (sera hashé)
 * @param string $bio Biographie (optionnel)
 * @param string $skill_level Niveau de compétence
 * @return array Résultat avec 'success' et 'message' ou 'user_id'
 */
function createUser($username, $email, $password, $bio = '', $skill_level = 'junior') {
    $db = getDBConnection();
    
    // Validation des données
    if (strlen($username) < 3 || strlen($username) > 50) {
        return ['success' => false, 'message' => 'Le nom d\'utilisateur doit contenir entre 3 et 50 caractères.'];
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'L\'adresse email est invalide.'];
    }
    
    if (strlen($password) < 8) {
        return ['success' => false, 'message' => 'Le mot de passe doit contenir au moins 8 caractères.'];
    }
    
    // Vérification de l'unicité du username et email
    $stmt = $db->prepare("SELECT id FROM User WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $stmt->close();
        return ['success' => false, 'message' => 'Ce nom d\'utilisateur ou cet email est déjà utilisé.'];
    }
    $stmt->close();
    
    // Hash du mot de passe
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Insertion de l'utilisateur
    $stmt = $db->prepare("INSERT INTO User (username, email, password, bio, skill_level, balance) VALUES (?, ?, ?, ?, ?, ?)");
    $initial_balance = INITIAL_BALANCE;
    $stmt->bind_param("sssssd", $username, $email, $password_hash, $bio, $skill_level, $initial_balance);
    
    if ($stmt->execute()) {
        $user_id = $db->insert_id;
        $stmt->close();
        return ['success' => true, 'user_id' => $user_id];
    }
    
    $stmt->close();
    return ['success' => false, 'message' => 'Erreur lors de la création du compte.'];
}

/**
 * Authentifie un utilisateur
 * 
 * @param string $login Username ou email
 * @param string $password Mot de passe
 * @return array Résultat avec 'success' et 'user' ou 'message'
 */
function authenticateUser($login, $password) {
    $db = getDBConnection();
    
    // Recherche de l'utilisateur par username ou email
    $stmt = $db->prepare("SELECT id, username, email, password, role, is_active FROM User WHERE (username = ? OR email = ?) AND is_active = 1");
    $stmt->bind_param("ss", $login, $login);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        return ['success' => false, 'message' => 'Identifiants incorrects.'];
    }
    
    $user = $result->fetch_assoc();
    $stmt->close();
    
    // Vérification du mot de passe
    if (password_verify($password, $user['password'])) {
        // Suppression du hash du password du tableau retourné
        unset($user['password']);
        return ['success' => true, 'user' => $user];
    }
    
    return ['success' => false, 'message' => 'Identifiants incorrects.'];
}

/**
 * Récupère les informations d'un utilisateur par ID
 * 
 * @param int $user_id ID de l'utilisateur
 * @param bool $public_only Si true, ne retourne que les infos publiques
 * @return array|null Données de l'utilisateur ou null
 */
function getUserById($user_id, $public_only = false) {
    $db = getDBConnection();
    
    if ($public_only) {
        $stmt = $db->prepare("SELECT id, username, profile_picture, bio, skill_level, score, created_at FROM User WHERE id = ?");
    } else {
        $stmt = $db->prepare("SELECT id, username, email, balance, role, profile_picture, bio, skill_level, score, is_active, created_at FROM User WHERE id = ?");
    }
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        return null;
    }
    
    $user = $result->fetch_assoc();
    $stmt->close();
    
    return $user;
}

/**
 * Met à jour les informations d'un utilisateur
 * 
 * @param int $user_id ID de l'utilisateur
 * @param array $data Données à mettre à jour
 * @return bool Succès de l'opération
 */
function updateUser($user_id, $data) {
    $db = getDBConnection();
    
    $allowed_fields = ['email', 'bio', 'skill_level', 'profile_picture'];
    $updates = [];
    $types = '';
    $values = [];
    
    foreach ($data as $key => $value) {
        if (in_array($key, $allowed_fields)) {
            $updates[] = "$key = ?";
            $types .= 's';
            $values[] = $value;
        }
    }
    
    if (empty($updates)) {
        return false;
    }
    
    // Ajout de l'ID à la fin pour le WHERE
    $types .= 'i';
    $values[] = $user_id;
    
    $sql = "UPDATE User SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$values);
    
    $success = $stmt->execute();
    $stmt->close();
    
    return $success;
}

/**
 * Change le mot de passe d'un utilisateur
 * 
 * @param int $user_id ID de l'utilisateur
 * @param string $old_password Ancien mot de passe
 * @param string $new_password Nouveau mot de passe
 * @return array Résultat de l'opération
 */
function changePassword($user_id, $old_password, $new_password) {
    $db = getDBConnection();
    
    // Validation du nouveau mot de passe
    if (strlen($new_password) < 8) {
        return ['success' => false, 'message' => 'Le nouveau mot de passe doit contenir au moins 8 caractères.'];
    }
    
    // Récupération du hash actuel
    $stmt = $db->prepare("SELECT password FROM User WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        return ['success' => false, 'message' => 'Utilisateur introuvable.'];
    }
    
    $user = $result->fetch_assoc();
    $stmt->close();
    
    // Vérification de l'ancien mot de passe
    if (!password_verify($old_password, $user['password'])) {
        return ['success' => false, 'message' => 'L\'ancien mot de passe est incorrect.'];
    }
    
    // Mise à jour avec le nouveau hash
    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $db->prepare("UPDATE User SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $new_hash, $user_id);
    
    $success = $stmt->execute();
    $stmt->close();
    
    if ($success) {
        return ['success' => true, 'message' => 'Mot de passe modifié avec succès.'];
    }
    
    return ['success' => false, 'message' => 'Erreur lors de la modification du mot de passe.'];
}

/**
 * Ajoute du crédit au solde d'un utilisateur
 * 
 * @param int $user_id ID de l'utilisateur
 * @param float $amount Montant à ajouter
 * @return bool Succès de l'opération
 */
function addBalance($user_id, $amount) {
    $db = getDBConnection();
    
    if ($amount <= 0) {
        return false;
    }
    
    $stmt = $db->prepare("UPDATE User SET balance = balance + ? WHERE id = ?");
    $stmt->bind_param("di", $amount, $user_id);
    
    $success = $stmt->execute();
    $stmt->close();
    
    return $success;
}

/**
 * Déduit du crédit du solde d'un utilisateur
 * 
 * @param int $user_id ID de l'utilisateur
 * @param float $amount Montant à déduire
 * @return bool Succès de l'opération
 */
function deductBalance($user_id, $amount) {
    $db = getDBConnection();
    
    if ($amount <= 0) {
        return false;
    }
    
    // Vérification du solde suffisant
    $user = getUserById($user_id);
    if (!$user || $user['balance'] < $amount) {
        return false;
    }
    
    $stmt = $db->prepare("UPDATE User SET balance = balance - ? WHERE id = ?");
    $stmt->bind_param("di", $amount, $user_id);
    
    $success = $stmt->execute();
    $stmt->close();
    
    return $success;
}

/**
 * Récupère les challenges créés par un utilisateur
 * 
 * @param int $user_id ID de l'utilisateur
 * @return array Liste des challenges
 */
function getUserCreatedChallenges($user_id) {
    $db = getDBConnection();
    
    $stmt = $db->prepare("
        SELECT c.*, 
               COUNT(DISTINCT pc.id) as purchase_count
        FROM Challenge c
        LEFT JOIN PurchasedChallenge pc ON c.id = pc.challenge_id
        WHERE c.author_id = ?
        GROUP BY c.id
        ORDER BY c.created_at DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $challenges = [];
    while ($row = $result->fetch_assoc()) {
        $challenges[] = $row;
    }
    
    $stmt->close();
    return $challenges;
}

/**
 * Récupère les challenges achetés par un utilisateur
 * 
 * @param int $user_id ID de l'utilisateur
 * @return array Liste des challenges
 */
function getUserPurchasedChallenges($user_id) {
    $db = getDBConnection();
    
    $stmt = $db->prepare("
        SELECT c.*, 
               pc.purchased_at,
               pc.is_solved,
               pc.solved_at
        FROM PurchasedChallenge pc
        JOIN Challenge c ON pc.challenge_id = c.id
        WHERE pc.user_id = ?
        ORDER BY pc.purchased_at DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $challenges = [];
    while ($row = $result->fetch_assoc()) {
        $challenges[] = $row;
    }
    
    $stmt->close();
    return $challenges;
}

/**
 * Récupère les factures d'un utilisateur
 * 
 * @param int $user_id ID de l'utilisateur
 * @return array Liste des factures
 */
function getUserInvoices($user_id) {
    $db = getDBConnection();
    
    $stmt = $db->prepare("
        SELECT i.*,
               COUNT(ii.id) as item_count
        FROM Invoice i
        LEFT JOIN InvoiceItem ii ON i.id = ii.invoice_id
        WHERE i.user_id = ?
        GROUP BY i.id
        ORDER BY i.date DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $invoices = [];
    while ($row = $result->fetch_assoc()) {
        $invoices[] = $row;
    }
    
    $stmt->close();
    return $invoices;
}

/**
 * Récupère les statistiques d'un utilisateur
 * 
 * @param int $user_id ID de l'utilisateur
 * @return array Statistiques
 */
function getUserStats($user_id) {
    $db = getDBConnection();
    
    $stats = [
        'challenges_created' => 0,
        'challenges_purchased' => 0,
        'challenges_solved' => 0,
        'total_spent' => 0,
        'total_submissions' => 0
    ];
    
    // Challenges créés
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM Challenge WHERE author_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['challenges_created'] = $result->fetch_assoc()['count'];
    $stmt->close();
    
    // Challenges achetés
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM PurchasedChallenge WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['challenges_purchased'] = $result->fetch_assoc()['count'];
    $stmt->close();
    
    // Challenges résolus
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM PurchasedChallenge WHERE user_id = ? AND is_solved = 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['challenges_solved'] = $result->fetch_assoc()['count'];
    $stmt->close();
    
    // Total dépensé
    $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM Invoice WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['total_spent'] = $result->fetch_assoc()['total'];
    $stmt->close();
    
    // Soumissions totales
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM Submission WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['total_submissions'] = $result->fetch_assoc()['count'];
    $stmt->close();
    
    return $stats;
}

/**
 * Récupère tous les utilisateurs (pour admin)
 * 
 * @return array Liste des utilisateurs
 */
function getAllUsers() {
    $db = getDBConnection();
    
    $stmt = $db->prepare("
        SELECT u.*,
               COUNT(DISTINCT c.id) as challenges_created,
               COUNT(DISTINCT pc.id) as challenges_purchased
        FROM User u
        LEFT JOIN Challenge c ON u.id = c.author_id
        LEFT JOIN PurchasedChallenge pc ON u.id = pc.user_id
        GROUP BY u.id
        ORDER BY u.created_at DESC
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    
    $stmt->close();
    return $users;
}

/**
 * Change le rôle d'un utilisateur (admin uniquement)
 * 
 * @param int $user_id ID de l'utilisateur
 * @param string $role Nouveau rôle
 * @return bool Succès de l'opération
 */
function changeUserRole($user_id, $role) {
    $db = getDBConnection();
    
    $valid_roles = [ROLE_USER, ROLE_CREATOR, ROLE_ADMIN];
    if (!in_array($role, $valid_roles)) {
        return false;
    }
    
    $stmt = $db->prepare("UPDATE User SET role = ? WHERE id = ?");
    $stmt->bind_param("si", $role, $user_id);
    
    $success = $stmt->execute();
    $stmt->close();
    
    return $success;
}

/**
 * Active ou désactive un utilisateur
 * 
 * @param int $user_id ID de l'utilisateur
 * @param bool $is_active Statut actif
 * @return bool Succès de l'opération
 */
function toggleUserStatus($user_id, $is_active) {
    $db = getDBConnection();
    
    $status = $is_active ? 1 : 0;
    $stmt = $db->prepare("UPDATE User SET is_active = ? WHERE id = ?");
    $stmt->bind_param("ii", $status, $user_id);
    
    $success = $stmt->execute();
    $stmt->close();
    
    return $success;
}