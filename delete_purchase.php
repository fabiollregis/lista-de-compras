<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['purchase_id'])) {
    $_SESSION['error_message'] = "Acesso negado";
    header("Location: reports.php");
    exit();
}

try {
    $pdo->beginTransaction();

    // Deletar os itens da compra
    $stmt = $pdo->prepare("DELETE FROM shopping_history_items WHERE history_id = ?");
    $stmt->execute([$_POST['purchase_id']]);

    // Deletar a compra
    $stmt = $pdo->prepare("DELETE FROM shopping_history WHERE id = ? AND user_id = ?");
    $stmt->execute([$_POST['purchase_id'], $_SESSION['user_id']]);

    $pdo->commit();
    $_SESSION['success_message'] = "Compra excluída com sucesso!";
} catch (PDOException $e) {
    $pdo->rollBack();
    $_SESSION['error_message'] = "Erro ao excluir a compra: " . $e->getMessage();
}

header("Location: reports.php");
exit();
?>
