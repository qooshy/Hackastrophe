<?php
/**
 * Fonctions de gestion des challenges
 * 
 * Contient toutes les fonctions relatives aux opérations sur les challenges
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Récupère tous les challenges actifs
 * 
 * @param array $filters Filtres de recherche (category, difficulty, etc.)
 * @param int $limit Nombre de résultats
 * @param int $offset Décalage pour pagination
 * @return array Liste des challenges
 */
function getAllChallenges($filters = [], $limit = null, $offset = 0) {
    $db = getDBConnection();
    
    $where_clauses = ["c.is_active = 1"];
    $params = [];
    $types = '';
    
    // Filtres
    if (!empty($filters['category'])) {
        $where_clauses[] = "c.category = ?";
        $types .= 's';
        $params[] = $filters['category'];
    }
    
    if (!empty($filters['difficulty'])) {
        $where_clauses[] = "c.difficulty = ?";
        $types .= 's';
        $params[] = $filters['difficulty'];
    }
    
    if (!empty($filters['search'])) {
        $where_clauses[] = "(c.title LIKE ? OR c.description LIKE ?)";
        $types .= 'ss';
        $search_term = '%' . $filters['search'] . '%';
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    if (!empty($filters['min_price'])) {
        $where_clauses[] = "c.price >= ?";
        $types .= 'd';
        $params[] = $filters['min_price'];
    }
    
    if (!empty($filters['max_price'])) {
        $where_clauses[] = "c.price <= ?";
        $types .= 'd';
        $params[] = $filters['max_price'];
    }
    
    $sql = "
        SELECT c.*, 
               u.username as author_name,
               COUNT(DISTINCT pc.id) as purchase_count
        FROM Challenge c
        JOIN User u ON c.author_id = u.id
        LEFT JOIN PurchasedChallenge pc ON c.id = pc.challenge_id
        WHERE " . implode(' AND ', $where_clauses) . "
        GROUP BY c.id
        ORDER BY c.created_at DESC
    ";
    
    if ($limit !== null) {
        $sql .= " LIMIT ? OFFSET ?";
        $types .= 'ii';
        $params[] = $limit;
        $params[] = $offset;
    }
    
    $stmt = $db->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
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
 * Récupère un challenge par son ID
 * 
 * @param int $challenge_id ID du challenge
 * @return array|null Données du challenge ou null
 */
function getChallengeById($challenge_id) {
    $db = getDBConnection();
    
    $stmt = $db->prepare("
        SELECT c.*, 
               u.username as author_name,
               u.id as author_id,
               COUNT(DISTINCT pc.id) as purchase_count,
               COUNT(DISTINCT s.id) as total_submissions,
               COUNT(DISTINCT CASE WHEN s.is_valid = 1 THEN s.id END) as valid_submissions
        FROM Challenge c
        JOIN User u ON c.author_id = u.id
        LEFT JOIN PurchasedChallenge pc ON c.id = pc.challenge_id
        LEFT JOIN Submission s ON c.id = s.challenge_id
        WHERE c.id = ?
        GROUP BY c.id
    ");
    $stmt->bind_param("i", $challenge_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        return null;
    }
    
    $challenge = $result->fetch_assoc();
    $stmt->close();
    
    return $challenge;
}

/**
 * Crée un nouveau challenge
 * 
 * @param array $data Données du challenge
 * @return array Résultat avec 'success' et 'message' ou 'challenge_id'
 */
function createChallenge($data) {
    $db = getDBConnection();
    
    // Validation des données
    if (empty($data['title']) || strlen($data['title']) < 5) {
        return ['success' => false, 'message' => 'Le titre doit contenir au moins 5 caractères.'];
    }
    
    if (empty($data['description']) || strlen($data['description']) < 20) {
        return ['success' => false, 'message' => 'La description doit contenir au moins 20 caractères.'];
    }
    
    if (!isset($data['category']) || !array_key_exists($data['category'], CHALLENGE_CATEGORIES)) {
        return ['success' => false, 'message' => 'Catégorie invalide.'];
    }
    
    if (!isset($data['difficulty']) || !array_key_exists($data['difficulty'], DIFFICULTY_LEVELS)) {
        return ['success' => false, 'message' => 'Difficulté invalide.'];
    }
    
    if (!isset($data['price']) || $data['price'] < 0) {
        return ['success' => false, 'message' => 'Le prix doit être positif.'];
    }
    
    if (empty($data['flag'])) {
        return ['success' => false, 'message' => 'Le flag est obligatoire.'];
    }
    
    // Hash du flag pour le stockage
    $flag_hash = password_hash($data['flag'], PASSWORD_DEFAULT);
    
    // Calcul des points selon la difficulté
    $points = DIFFICULTY_POINTS[$data['difficulty']];
    
    // Insertion du challenge
    $stmt = $db->prepare("
        INSERT INTO Challenge 
        (title, description, category, difficulty, price, author_id, image_url, access_url, flag_hash, points) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $image_url = $data['image_url'] ?? null;
    $access_url = $data['access_url'] ?? null;
    
    $stmt->bind_param(
        "ssssdiissi",
        $data['title'],
        $data['description'],
        $data['category'],
        $data['difficulty'],
        $data['price'],
        $data['author_id'],
        $image_url,
        $access_url,
        $flag_hash,
        $points
    );
    
    if ($stmt->execute()) {
        $challenge_id = $db->insert_id;
        $stmt->close();
        
        // Création de l'instance (stock illimité par défaut)
        $stmt = $db->prepare("INSERT INTO ChallengeInstance (challenge_id, available_instances) VALUES (?, -1)");
        $stmt->bind_param("i", $challenge_id);
        $stmt->execute();
        $stmt->close();
        
        return ['success' => true, 'challenge_id' => $challenge_id];
    }
    
    $stmt->close();
    return ['success' => false, 'message' => 'Erreur lors de la création du challenge.'];
}

/**
 * Met à jour un challenge existant
 * 
 * @param int $challenge_id ID du challenge
 * @param array $data Données à mettre à jour
 * @param int $user_id ID de l'utilisateur effectuant la modification
 * @return array Résultat de l'opération
 */
function updateChallenge($challenge_id, $data, $user_id) {
    $db = getDBConnection();
    
    // Vérification des permissions
    $challenge = getChallengeById($challenge_id);
    if (!$challenge) {
        return ['success' => false, 'message' => 'Challenge introuvable.'];
    }
    
    // Seul l'auteur ou un admin peut modifier
    $user = getUserById($user_id);
    if ($challenge['author_id'] != $user_id && $user['role'] !== ROLE_ADMIN) {
        return ['success' => false, 'message' => 'Vous n\'avez pas la permission de modifier ce challenge.'];
    }
    
    $allowed_fields = ['title', 'description', 'category', 'difficulty', 'price', 'image_url', 'access_url', 'is_active'];
    $updates = [];
    $types = '';
    $values = [];
    
    foreach ($data as $key => $value) {
        if (in_array($key, $allowed_fields)) {
            $updates[] = "$key = ?";
            
            if ($key === 'price') {
                $types .= 'd';
            } elseif ($key === 'is_active') {
                $types .= 'i';
            } else {
                $types .= 's';
            }
            
            $values[] = $value;
        }
    }
    
    // Mise à jour des points si la difficulté change
    if (isset($data['difficulty'])) {
        $updates[] = "points = ?";
        $types .= 'i';
        $values[] = DIFFICULTY_POINTS[$data['difficulty']];
    }
    
    if (empty($updates)) {
        return ['success' => false, 'message' => 'Aucune donnée à mettre à jour.'];
    }
    
    // Ajout de l'ID à la fin pour le WHERE
    $types .= 'i';
    $values[] = $challenge_id;
    
    $sql = "UPDATE Challenge SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$values);
    
    $success = $stmt->execute();
    $stmt->close();
    
    if ($success) {
        return ['success' => true, 'message' => 'Challenge mis à jour avec succès.'];
    }
    
    return ['success' => false, 'message' => 'Erreur lors de la mise à jour.'];
}

/**
 * Supprime un challenge
 * 
 * @param int $challenge_id ID du challenge
 * @param int $user_id ID de l'utilisateur effectuant la suppression
 * @return array Résultat de l'opération
 */
function deleteChallenge($challenge_id, $user_id) {
    $db = getDBConnection();
    
    // Vérification des permissions
    $challenge = getChallengeById($challenge_id);
    if (!$challenge) {
        return ['success' => false, 'message' => 'Challenge introuvable.'];
    }
    
    $user = getUserById($user_id);
    if ($challenge['author_id'] != $user_id && $user['role'] !== ROLE_ADMIN) {
        return ['success' => false, 'message' => 'Vous n\'avez pas la permission de supprimer ce challenge.'];
    }
    
    // Suppression (CASCADE supprimera les entrées liées)
    $stmt = $db->prepare("DELETE FROM Challenge WHERE id = ?");
    $stmt->bind_param("i", $challenge_id);
    
    $success = $stmt->execute();
    $stmt->close();
    
    if ($success) {
        return ['success' => true, 'message' => 'Challenge supprimé avec succès.'];
    }
    
    return ['success' => false, 'message' => 'Erreur lors de la suppression.'];
}

/**
 * Vérifie si un utilisateur a acheté un challenge
 * 
 * @param int $user_id ID de l'utilisateur
 * @param int $challenge_id ID du challenge
 * @return bool
 */
function hasUserPurchasedChallenge($user_id, $challenge_id) {
    $db = getDBConnection();
    
    $stmt = $db->prepare("SELECT id FROM PurchasedChallenge WHERE user_id = ? AND challenge_id = ?");
    $stmt->bind_param("ii", $user_id, $challenge_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $has_purchased = $result->num_rows > 0;
    $stmt->close();
    
    return $has_purchased;
}

/**
 * Vérifie si un utilisateur a résolu un challenge
 * 
 * @param int $user_id ID de l'utilisateur
 * @param int $challenge_id ID du challenge
 * @return bool
 */
function hasUserSolvedChallenge($user_id, $challenge_id) {
    $db = getDBConnection();
    
    $stmt = $db->prepare("SELECT id FROM PurchasedChallenge WHERE user_id = ? AND challenge_id = ? AND is_solved = 1");
    $stmt->bind_param("ii", $user_id, $challenge_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $has_solved = $result->num_rows > 0;
    $stmt->close();
    
    return $has_solved;
}

/**
 * Soumet un flag pour validation
 * 
 * @param int $user_id ID de l'utilisateur
 * @param int $challenge_id ID du challenge
 * @param string $flag Flag soumis
 * @return array Résultat de la soumission
 */
function submitFlag($user_id, $challenge_id, $flag) {
    $db = getDBConnection();
    
    // Vérification que l'utilisateur a acheté le challenge
    if (!hasUserPurchasedChallenge($user_id, $challenge_id)) {
        return ['success' => false, 'message' => 'Vous devez acheter ce challenge pour soumettre un flag.'];
    }
    
    // Vérification que le challenge n'est pas déjà résolu
    if (hasUserSolvedChallenge($user_id, $challenge_id)) {
        return ['success' => false, 'message' => 'Vous avez déjà résolu ce challenge.'];
    }
    
    // Récupération du hash du flag
    $challenge = getChallengeById($challenge_id);
    if (!$challenge) {
        return ['success' => false, 'message' => 'Challenge introuvable.'];
    }
    
    // Vérification du flag
    $is_valid = password_verify($flag, $challenge['flag_hash']);
    
    // Enregistrement de la soumission
    $stmt = $db->prepare("INSERT INTO Submission (user_id, challenge_id, flag_submitted, is_valid) VALUES (?, ?, ?, ?)");
    $is_valid_int = $is_valid ? 1 : 0;
    $stmt->bind_param("iisi", $user_id, $challenge_id, $flag, $is_valid_int);
    $stmt->execute();
    $stmt->close();
    
    if ($is_valid) {
        // Mise à jour du challenge comme résolu
        $stmt = $db->prepare("UPDATE PurchasedChallenge SET is_solved = 1, solved_at = NOW() WHERE user_id = ? AND challenge_id = ?");
        $stmt->bind_param("ii", $user_id, $challenge_id);
        $stmt->execute();
        $stmt->close();
        
        // Mise à jour du compteur de résolutions
        $stmt = $db->prepare("UPDATE Challenge SET solved_count = solved_count + 1 WHERE id = ?");
        $stmt->bind_param("i", $challenge_id);
        $stmt->execute();
        $stmt->close();
        
        // Ajout des points au score de l'utilisateur
        $stmt = $db->prepare("UPDATE User SET score = score + ? WHERE id = ?");
        $stmt->bind_param("ii", $challenge['points'], $user_id);
        $stmt->execute();
        $stmt->close();
        
        return [
            'success' => true,
            'valid' => true,
            'message' => 'Félicitations ! Flag correct. Vous avez gagné ' . $challenge['points'] . ' points.',
            'points_earned' => $challenge['points']
        ];
    }
    
    return [
        'success' => true,
        'valid' => false,
        'message' => 'Flag incorrect. Réessayez.'
    ];
}

/**
 * Récupère les soumissions d'un utilisateur pour un challenge
 * 
 * @param int $user_id ID de l'utilisateur
 * @param int $challenge_id ID du challenge
 * @return array Liste des soumissions
 */
function getChallengeSubmissions($user_id, $challenge_id) {
    $db = getDBConnection();
    
    $stmt = $db->prepare("
        SELECT * FROM Submission 
        WHERE user_id = ? AND challenge_id = ? 
        ORDER BY submitted_at DESC
    ");
    $stmt->bind_param("ii", $user_id, $challenge_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $submissions = [];
    while ($row = $result->fetch_assoc()) {
        $submissions[] = $row;
    }
    
    $stmt->close();
    return $submissions;
}

/**
 * Compte le nombre total de challenges
 * 
 * @param array $filters Filtres de recherche
 * @return int Nombre de challenges
 */
function countChallenges($filters = []) {
    $db = getDBConnection();
    
    $where_clauses = ["is_active = 1"];
    $params = [];
    $types = '';
    
    if (!empty($filters['category'])) {
        $where_clauses[] = "category = ?";
        $types .= 's';
        $params[] = $filters['category'];
    }
    
    if (!empty($filters['difficulty'])) {
        $where_clauses[] = "difficulty = ?";
        $types .= 's';
        $params[] = $filters['difficulty'];
    }
    
    if (!empty($filters['search'])) {
        $where_clauses[] = "(title LIKE ? OR description LIKE ?)";
        $types .= 'ss';
        $search_term = '%' . $filters['search'] . '%';
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    $sql = "SELECT COUNT(*) as count FROM Challenge WHERE " . implode(' AND ', $where_clauses);
    $stmt = $db->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    $stmt->close();
    
    return $count;
}