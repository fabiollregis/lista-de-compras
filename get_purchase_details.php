<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    exit('Acesso negado');
}

try {
    // Buscar detalhes da compra
    $stmt = $pdo->prepare("
        SELECT 
            h.purchase_date,
            h.total_amount,
            hi.item_name,
            hi.quantity,
            hi.price,
            (hi.quantity * hi.price) as item_total
        FROM shopping_history h
        JOIN shopping_history_items hi ON h.id = hi.history_id
        WHERE h.id = ? AND h.user_id = ?
        ORDER BY hi.item_name
    ");
    $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
    $items = $stmt->fetchAll();

    if (empty($items)) {
        echo '<p class="text-center text-muted">Compra não encontrada.</p>';
        exit;
    }

    // Exibir detalhes
    ?>
    <div class="mb-3">
        <strong>Data da Compra:</strong> 
        <?php echo date('d/m/Y H:i', strtotime($items[0]['purchase_date'])); ?>
    </div>

    <div class="table-responsive">
        <table class="table table-sm">
            <thead class="table-light">
                <tr>
                    <th>Item</th>
                    <th class="text-center">Qtd</th>
                    <th class="text-end">Preço Unit.</th>
                    <th class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $total = 0;
                foreach ($items as $item): 
                    $total += $item['item_total'];
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                        <td class="text-center"><?php echo $item['quantity']; ?></td>
                        <td class="text-end">R$ <?php echo number_format($item['price'], 2, ',', '.'); ?></td>
                        <td class="text-end">R$ <?php echo number_format($item['item_total'], 2, ',', '.'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="table-light">
                <tr>
                    <td colspan="3" class="text-end"><strong>Total da Compra:</strong></td>
                    <td class="text-end"><strong>R$ <?php echo number_format($total, 2, ',', '.'); ?></strong></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php
} catch (PDOException $e) {
    echo '<p class="text-center text-danger">Erro ao carregar detalhes: ' . $e->getMessage() . '</p>';
}
?>
