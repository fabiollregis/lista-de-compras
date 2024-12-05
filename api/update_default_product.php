<?php
session_start();
require_once '../config/database.php';

// Verificar se usuário está logado
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit();
}

// Receber e decodificar dados JSON
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id']) || !isset($data['product_name']) || !isset($data['category']) || !isset($data['price'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
    exit();
}

$id = intval($data['id']);
$product_name = trim($data['product_name']);
$category = trim($data['category']);
$price = floatval($data['price']);

// Validar dados
if ($id <= 0 || empty($product_name) || empty($category) || $price < 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        UPDATE default_products 
        SET product_name = ?, category = ?, price = ? 
        WHERE id = ?
    ");
    
    if ($stmt->execute([$product_name, $category, $price, $id])) {
        echo json_encode(['success' => true, 'message' => 'Produto atualizado com sucesso']);
    } else {
        throw new Exception('Erro ao atualizar produto');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
