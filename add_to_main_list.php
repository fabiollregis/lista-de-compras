<?php
session_start();
require_once 'config/database.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Usuário não está logado']);
    exit();
}

// Receber e decodificar os dados JSON
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['item_name'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit();
}

try {
    // Primeiro, tentar obter o preço da tabela default_products
    $stmt = $pdo->prepare("
        SELECT price 
        FROM default_products 
        WHERE product_name = ?
    ");
    $stmt->execute([$data['item_name']]);
    $defaultProduct = $stmt->fetch();
    
    if ($defaultProduct && $defaultProduct['price'] > 0) {
        $price = $defaultProduct['price'];
    } else {
        // Se não encontrar na tabela default_products, buscar o preço médio do histórico
        $stmt = $pdo->prepare("
            SELECT AVG(price) as avg_price 
            FROM shopping_history_items hi 
            JOIN shopping_history h ON hi.history_id = h.id 
            WHERE h.user_id = ? AND hi.item_name = ?
            AND h.purchase_date >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
        ");
        $stmt->execute([$_SESSION['user_id'], $data['item_name']]);
        $priceData = $stmt->fetch();
        $price = $priceData['avg_price'] ?? 0;
    }

    // Verificar se o item já existe
    $stmt = $pdo->prepare("
        SELECT id, quantity, is_purchased 
        FROM shopping_items 
        WHERE user_id = ? AND item_name = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $data['item_name']]);
    $existingItem = $stmt->fetch();

    if ($existingItem) {
        // Se o item existe, atualiza a quantidade mantendo o status
        $newQuantity = $existingItem['quantity'] + 1;
        $stmt = $pdo->prepare("
            UPDATE shopping_items 
            SET quantity = ?, is_purchased = ?
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$newQuantity, $existingItem['is_purchased'], $existingItem['id'], $_SESSION['user_id']]);
    } else {
        // Se o item não existe, insere como novo
        $stmt = $pdo->prepare("
            INSERT INTO shopping_items 
            (user_id, item_name, quantity, price, is_purchased) 
            VALUES (?, ?, 1, ?, 0)
        ");
        $stmt->execute([$_SESSION['user_id'], $data['item_name'], $price]);
    }

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao adicionar item: ' . $e->getMessage()]);
}
