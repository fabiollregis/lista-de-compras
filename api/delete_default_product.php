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

if (!isset($data['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID do produto não fornecido']);
    exit();
}

$id = intval($data['id']);

// Validar ID
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit();
}

try {
    $stmt = $pdo->prepare("DELETE FROM default_products WHERE id = ?");
    
    if ($stmt->execute([$id])) {
        echo json_encode(['success' => true, 'message' => 'Produto excluído com sucesso']);
    } else {
        throw new Exception('Erro ao excluir produto');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
