<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $pdo->beginTransaction();

        // Pegar todos os itens comprados
        $stmt = $pdo->prepare("SELECT * FROM shopping_items WHERE user_id = ? AND is_purchased = 1");
        $stmt->execute([$_SESSION['user_id']]);
        $items = $stmt->fetchAll();

        if (count($items) > 0) {
            // Calcular total
            $total_amount = 0;
            foreach ($items as $item) {
                $total_amount += $item['quantity'] * $item['price'];
            }

            // Criar registro na tabela shopping_history
            $stmt = $pdo->prepare("INSERT INTO shopping_history (user_id, total_amount) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user_id'], $total_amount]);
            $history_id = $pdo->lastInsertId();

            // Salvar itens no histórico
            $stmt = $pdo->prepare("INSERT INTO shopping_history_items (history_id, item_name, quantity, price) VALUES (?, ?, ?, ?)");
            foreach ($items as $item) {
                $stmt->execute([$history_id, $item['item_name'], $item['quantity'], $item['price']]);
            }

            // Remover itens comprados da lista atual
            $stmt = $pdo->prepare("DELETE FROM shopping_items WHERE user_id = ? AND is_purchased = 1");
            $stmt->execute([$_SESSION['user_id']]);

            $pdo->commit();
            $_SESSION['success_message'] = "Compra finalizada com sucesso!";
        } else {
            $_SESSION['error_message'] = "Não há itens marcados como comprados para finalizar.";
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = "Erro ao finalizar compra: " . $e->getMessage();
    }
    
    header("Location: index.php");
    exit();
}
?>
