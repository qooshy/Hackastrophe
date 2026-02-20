<?php
/**
 * Fonctions de gestion du panier et des factures
 * 
 * Contient toutes les fonctions relatives au panier et aux transactions
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/challenge_functions.php';
require_once __DIR__ . '/user_functions.php';

/**
 * Ajoute un challenge au panier
 * 
 * @param int $user_id ID de l'utilisateur
 * @param int $challenge_id ID du challenge
 * @param int $quantity Quantité (généralement 1)
 * @return array Résultat de l'opération
 */
function addToCart($user_id, $challenge_id, $quantity = 1) {
    $db = getDBConnection();
    
    // Vérification que le challenge existe et est actif
    $challenge = getChallengeById($challenge_id);
    if (!$challenge || !$challenge['is_active']) {
        return ['success' => false, 'message' => 'Challenge introuvable ou inactif.'];
    }
    
    // Vérification que l'utilisateur n'a pas déjà acheté ce challenge
    if (hasUserPurchasedChallenge($user_id, $challenge_id)) {
        return ['success' => false, 'message' => 'Vous possédez déjà ce challenge.'];
    }
    
    // Vérification si déjà dans le panier
    $stmt = $db->prepare("SELECT id, quantity FROM Cart WHERE user_id = ? AND challenge_id = ?");
    $stmt->bind_param("ii", $user_id, $challenge_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $stmt->close();
        return ['success' => false, 'message' => 'Ce challenge est déjà dans votre panier.'];
    }
    $stmt->close();
    
    // Ajout au panier
    $stmt = $db->prepare("INSERT INTO Cart (user_id, challenge_id, quantity) VALUES (?, ?, ?)");
    $stmt->bind_param("iii", $user_id, $challenge_id, $quantity);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => 'Challenge ajouté au panier.'];
    }
    
    $stmt->close();
    return ['success' => false, 'message' => 'Erreur lors de l\'ajout au panier.'];
}

/**
 * Supprime un challenge du panier
 * 
 * @param int $user_id ID de l'utilisateur
 * @param int $challenge_id ID du challenge
 * @return bool Succès de l'opération
 */
function removeFromCart($user_id, $challenge_id) {
    $db = getDBConnection();
    
    $stmt = $db->prepare("DELETE FROM Cart WHERE user_id = ? AND challenge_id = ?");
    $stmt->bind_param("ii", $user_id, $challenge_id);
    
    $success = $stmt->execute();
    $stmt->close();
    
    return $success;
}

/**
 * Met à jour la quantité d'un article dans le panier
 * 
 * @param int $user_id ID de l'utilisateur
 * @param int $challenge_id ID du challenge
 * @param int $quantity Nouvelle quantité
 * @return bool Succès de l'opération
 */
function updateCartQuantity($user_id, $challenge_id, $quantity) {
    $db = getDBConnection();
    
    if ($quantity <= 0) {
        return removeFromCart($user_id, $challenge_id);
    }
    
    $stmt = $db->prepare("UPDATE Cart SET quantity = ? WHERE user_id = ? AND challenge_id = ?");
    $stmt->bind_param("iii", $quantity, $user_id, $challenge_id);
    
    $success = $stmt->execute();
    $stmt->close();
    
    return $success;
}

/**
 * Récupère le contenu du panier d'un utilisateur
 * 
 * @param int $user_id ID de l'utilisateur
 * @return array Contenu du panier
 */
function getCartContents($user_id) {
    $db = getDBConnection();
    
    $stmt = $db->prepare("
        SELECT cart.id as cart_id,
               cart.quantity,
               c.*,
               u.username as author_name
        FROM Cart cart
        JOIN Challenge c ON cart.challenge_id = c.id
        JOIN User u ON c.author_id = u.id
        WHERE cart.user_id = ? AND c.is_active = 1
        ORDER BY cart.added_at DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    
    $stmt->close();
    return $items;
}

/**
 * Calcule le total du panier
 * 
 * @param int $user_id ID de l'utilisateur
 * @return float Total
 */
function getCartTotal($user_id) {
    $db = getDBConnection();
    
    $stmt = $db->prepare("
        SELECT COALESCE(SUM(c.price * cart.quantity), 0) as total
        FROM Cart cart
        JOIN Challenge c ON cart.challenge_id = c.id
        WHERE cart.user_id = ? AND c.is_active = 1
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $total = $result->fetch_assoc()['total'];
    $stmt->close();
    
    return $total;
}

/**
 * Compte le nombre d'articles dans le panier
 * 
 * @param int $user_id ID de l'utilisateur
 * @return int Nombre d'articles
 */
function getCartItemCount($user_id) {
    $db = getDBConnection();
    
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM Cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $count = $result->fetch_assoc()['count'];
    $stmt->close();
    
    return $count;
}

/**
 * Vide le panier d'un utilisateur
 * 
 * @param int $user_id ID de l'utilisateur
 * @return bool Succès de l'opération
 */
function clearCart($user_id) {
    $db = getDBConnection();
    
    $stmt = $db->prepare("DELETE FROM Cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    
    $success = $stmt->execute();
    $stmt->close();
    
    return $success;
}

/**
 * Valide le panier et crée la facture
 * 
 * @param int $user_id ID de l'utilisateur
 * @param array $billing_info Informations de facturation
 * @return array Résultat de la validation
 */
function validateCart($user_id, $billing_info) {
    $db = getDBConnection();
    
    // Validation des informations de facturation
    if (empty($billing_info['address']) || empty($billing_info['city']) || empty($billing_info['zip'])) {
        return ['success' => false, 'message' => 'Informations de facturation incomplètes.'];
    }
    
    // Récupération du contenu du panier
    $cart_items = getCartContents($user_id);
    
    if (empty($cart_items)) {
        return ['success' => false, 'message' => 'Votre panier est vide.'];
    }
    
    // Calcul du total
    $total = getCartTotal($user_id);
    
    // Vérification du solde
    $user = getUserById($user_id);
    if ($user['balance'] < $total) {
        return [
            'success' => false,
            'message' => 'Solde insuffisant. Total: ' . formatPrice($total) . ', Solde: ' . formatPrice($user['balance'])
        ];
    }
    
    // Début de la transaction
    $db->begin_transaction();
    
    try {
        // Création de la facture
        $stmt = $db->prepare("
            INSERT INTO Invoice (user_id, amount, billing_address, billing_city, billing_zip) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("idsss", $user_id, $total, $billing_info['address'], $billing_info['city'], $billing_info['zip']);
        $stmt->execute();
        $invoice_id = $db->insert_id;
        $stmt->close();
        
        // Traitement de chaque article du panier
        foreach ($cart_items as $item) {
            // Ajout de l'article à la facture
            $stmt = $db->prepare("
                INSERT INTO InvoiceItem (invoice_id, challenge_id, challenge_title, price, quantity) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("iisdi", $invoice_id, $item['id'], $item['title'], $item['price'], $item['quantity']);
            $stmt->execute();
            $stmt->close();
            
            // Ajout du challenge aux challenges achetés
            $stmt = $db->prepare("
                INSERT INTO PurchasedChallenge (user_id, challenge_id) 
                VALUES (?, ?)
            ");
            $stmt->bind_param("ii", $user_id, $item['id']);
            $stmt->execute();
            $stmt->close();
        }
        
        // Déduction du solde
        if (!deductBalance($user_id, $total)) {
            throw new Exception("Erreur lors de la déduction du solde");
        }
        
        // Vidage du panier
        clearCart($user_id);
        
        // Validation de la transaction
        $db->commit();
        
        return [
            'success' => true,
            'message' => 'Achat validé avec succès.',
            'invoice_id' => $invoice_id,
            'total' => $total
        ];
        
    } catch (Exception $e) {
        // Annulation de la transaction en cas d'erreur
        $db->rollback();
        error_log("Erreur lors de la validation du panier: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erreur lors du traitement de la commande.'];
    }
}

/**
 * Récupère une facture par son ID
 * 
 * @param int $invoice_id ID de la facture
 * @param int $user_id ID de l'utilisateur (pour vérification)
 * @return array|null Données de la facture ou null
 */
function getInvoiceById($invoice_id, $user_id = null) {
    $db = getDBConnection();
    
    $sql = "
        SELECT i.*,
               u.username,
               u.email
        FROM Invoice i
        JOIN User u ON i.user_id = u.id
        WHERE i.id = ?
    ";
    
    // Si user_id fourni, vérifier que la facture lui appartient
    if ($user_id !== null) {
        $sql .= " AND i.user_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("ii", $invoice_id, $user_id);
    } else {
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $invoice_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        return null;
    }
    
    $invoice = $result->fetch_assoc();
    $stmt->close();
    
    // Récupération des articles de la facture
    $stmt = $db->prepare("
        SELECT * FROM InvoiceItem 
        WHERE invoice_id = ?
    ");
    $stmt->bind_param("i", $invoice_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $invoice['items'] = [];
    while ($row = $result->fetch_assoc()) {
        $invoice['items'][] = $row;
    }
    $stmt->close();
    
    return $invoice;
}

/**
 * Récupère toutes les factures (pour admin)
 * 
 * @return array Liste des factures
 */
function getAllInvoices() {
    $db = getDBConnection();
    
    $stmt = $db->prepare("
        SELECT i.*,
               u.username,
               COUNT(ii.id) as item_count
        FROM Invoice i
        JOIN User u ON i.user_id = u.id
        LEFT JOIN InvoiceItem ii ON i.id = ii.invoice_id
        GROUP BY i.id
        ORDER BY i.date DESC
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $invoices = [];
    while ($row = $result->fetch_assoc()) {
        $invoices[] = $row;
    }
    
    $stmt->close();
    return $invoices;
}