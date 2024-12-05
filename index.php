<?php
session_start();
require_once 'config/database.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Add new item
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_item'])) {
    $item_name = trim($_POST['item_name']);
    $quantity = (int)$_POST['quantity'];
    $price = str_replace(',', '.', $_POST['price']);

    try {
        // Primeiro, verifica se o item já existe
        $stmt = $pdo->prepare("SELECT id, quantity, is_purchased FROM shopping_items WHERE user_id = ? AND item_name = ?");
        $stmt->execute([$_SESSION['user_id'], $item_name]);
        $existingItem = $stmt->fetch();

        if ($existingItem) {
            // Se o item existe, pergunta ao usuário se quer atualizar
            $_SESSION['item_exists'] = [
                'name' => $item_name,
                'new_quantity' => $quantity,
                'new_price' => $price,
                'current_quantity' => $existingItem['quantity']
            ];
            header("Location: index.php?exists=1");
            exit();
        } else {
            // Se o item não existe, insere como novo com status não comprado
            $stmt = $pdo->prepare("
                INSERT INTO shopping_items (user_id, item_name, quantity, price, is_purchased) 
                VALUES (?, ?, ?, ?, 0)
            ");
            $stmt->execute([$_SESSION['user_id'], $item_name, $quantity, $price]);
            header("Location: index.php?success=1");
            exit();
        }
    } catch (PDOException $e) {
        $error = "Erro ao adicionar item: " . $e->getMessage();
    }
}

// Processar a atualização se o usuário confirmar
if (isset($_POST['update_existing'])) {
    try {
        if (isset($_SESSION['item_exists'])) {
            $item = $_SESSION['item_exists'];
            
            // Primeiro, buscar o status atual do item
            $stmt = $pdo->prepare("SELECT is_purchased FROM shopping_items WHERE user_id = ? AND item_name = ?");
            $stmt->execute([$_SESSION['user_id'], $item['name']]);
            $currentItem = $stmt->fetch();
            
            // Atualizar mantendo o status atual
            $stmt = $pdo->prepare("
                UPDATE shopping_items 
                SET quantity = quantity + ?,
                    price = ?,
                    is_purchased = ?
                WHERE user_id = ? AND item_name = ?
            ");
            $stmt->execute([
                $item['new_quantity'], 
                $item['new_price'], 
                $currentItem['is_purchased'] ?? 0,
                $_SESSION['user_id'], 
                $item['name']
            ]);
            unset($_SESSION['item_exists']);
            header("Location: index.php?updated=1");
            exit();
        }
    } catch (PDOException $e) {
        $error = "Erro ao atualizar item: " . $e->getMessage();
    }
}

// Limpar a sessão se o usuário cancelar
if (isset($_GET['cancel'])) {
    unset($_SESSION['item_exists']);
    header("Location: index.php");
    exit();
}

// Toggle item purchase status
if (isset($_GET['toggle_status'])) {
    try {
        // Primeiro, verificar o status atual
        $stmt = $pdo->prepare("SELECT is_purchased FROM shopping_items WHERE id = ? AND user_id = ?");
        $stmt->execute([$_GET['toggle_status'], $_SESSION['user_id']]);
        $currentStatus = $stmt->fetch();

        if ($currentStatus !== false) {
            // Atualizar para o status oposto
            $newStatus = !$currentStatus['is_purchased'];
            $stmt = $pdo->prepare("
                UPDATE shopping_items 
                SET is_purchased = ? 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$newStatus, $_GET['toggle_status'], $_SESSION['user_id']]);
        }
    } catch (PDOException $e) {
        $error = "Erro ao atualizar status do item: " . $e->getMessage();
    }
}

// Delete item
if (isset($_GET['delete'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM shopping_items WHERE id = ? AND user_id = ?");
        $stmt->execute([$_GET['delete'], $_SESSION['user_id']]);
    } catch (PDOException $e) {
        $error = "Erro ao deletar item: " . $e->getMessage();
    }
}

// Update item
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_item'])) {
    $item_id = $_POST['item_id'];
    $item_name = trim($_POST['edit_item_name']);
    $quantity = (int)$_POST['edit_quantity'];
    $price = str_replace(',', '.', $_POST['edit_price']);

    try {
        // Primeiro, buscar o status atual do item
        $stmt = $pdo->prepare("SELECT is_purchased FROM shopping_items WHERE id = ? AND user_id = ?");
        $stmt->execute([$item_id, $_SESSION['user_id']]);
        $currentItem = $stmt->fetch();

        // Atualizar mantendo o status atual
        $stmt = $pdo->prepare("
            UPDATE shopping_items 
            SET item_name = ?, 
                quantity = ?, 
                price = ?,
                is_purchased = ?
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([
            $item_name, 
            $quantity, 
            $price,
            $currentItem['is_purchased'] ?? 0,
            $item_id, 
            $_SESSION['user_id']
        ]);
        
        header("Location: index.php?updated=1");
        exit();
    } catch (PDOException $e) {
        $error = "Erro ao atualizar item: " . $e->getMessage();
    }
}

// Get all items and calculate totals
try {
    $stmt = $pdo->prepare("
        SELECT id, item_name, quantity, price, is_purchased, (quantity * price) as total_price 
        FROM shopping_items 
        WHERE user_id = ? 
        ORDER BY item_name ASC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $total_value = 0;
    $total_purchased = 0;
    $total_pending = 0;
    
    foreach ($items as $item) {
        $item_total = $item['quantity'] * $item['price'];
        if ($item['is_purchased']) {
            $total_purchased += $item_total;
        } else {
            $total_pending += $item_total;
        }
        $total_value += $item_total;
    }
} catch (PDOException $e) {
    $error = "Erro ao carregar itens: " . $e->getMessage();
    $items = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Compras</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 20px;
        }
        .shopping-list {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-top: 20px;
        }
        .item-row {
            display: flex;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .item-row:last-child {
            border-bottom: none;
        }
        .action-buttons {
            margin-left: auto;
            display: flex;
            gap: 5px;
        }
        .purchased {
            background-color: #e8f5e9;
            text-decoration: line-through;
            color: #666;
        }
        .btn-toggle-status {
            min-width: 38px;
        }
        .totals-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 15px;
            margin-bottom: 20px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .total-row:last-child {
            margin-bottom: 0;
            padding-top: 10px;
            border-top: 2px solid #dee2e6;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand" href="index.php">Lista de Compras</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Minha Lista</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="default_products.php">Lista Padrão</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">Relatórios</a>
                    </li>
                </ul>
                <?php if (isset($_SESSION['username'])): ?>
                    <span class="navbar-text text-white">
                        Olá, <?php echo htmlspecialchars($_SESSION['username']); ?>!
                        <a href="logout.php" class="btn btn-outline-light btn-sm ms-2">Sair</a>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Olá, <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Usuário'; ?>!</h2>
            <div class="d-flex gap-2">
                <a href="profile.php" class="btn btn-outline-primary">
                    <i class="fas fa-user"></i> Perfil
                </a>
                <a href="reports.php" class="btn btn-info text-white me-2">
                    <i class="fas fa-chart-bar"></i> Relatórios
                </a>
                <a href="logout.php" class="btn btn-danger">Sair</a>
            </div>
        </div>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?php 
                    echo $_SESSION['success_message'];
                    unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger">
                <?php 
                    echo $_SESSION['error_message'];
                    unset($_SESSION['error_message']);
                ?>
            </div>
        <?php endif; ?>

        <!-- Alertas -->
        <div class="container mt-4">
            <?php if (isset($_GET['exists']) && isset($_SESSION['item_exists'])): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <strong>Atenção!</strong> O item "<?php echo htmlspecialchars($_SESSION['item_exists']['name']); ?>" já existe na sua lista.
                    <br>Quantidade atual: <?php echo $_SESSION['item_exists']['current_quantity']; ?>
                    <br>Quantidade a adicionar: <?php echo $_SESSION['item_exists']['new_quantity']; ?>
                    <form method="POST" class="mt-2">
                        <button type="submit" name="update_existing" class="btn btn-success btn-sm">
                            <i class="fas fa-plus"></i> Adicionar à quantidade existente
                        </button>
                        <a href="index.php?cancel=1" class="btn btn-secondary btn-sm">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                    </form>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    Item adicionado com sucesso!
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['updated'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    Quantidade do item atualizada com sucesso!
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
        </div>

        <!-- Totals Card -->
        <div class="totals-card">
            <div class="total-row">
                <span>Total Pendente:</span>
                <span class="text-primary">R$ <?php echo number_format($total_pending, 2, ',', '.'); ?></span>
            </div>
            <div class="total-row">
                <span>Total Comprado:</span>
                <span class="text-success">R$ <?php echo number_format($total_purchased, 2, ',', '.'); ?></span>
            </div>
            <div class="total-row">
                <span>Total Geral:</span>
                <span>R$ <?php echo number_format($total_value, 2, ',', '.'); ?></span>
            </div>
            <?php if ($total_purchased > 0): ?>
                <div class="text-center mt-3">
                    <form action="finish_purchase.php" method="POST" style="display: inline;">
                        <button type="submit" class="btn btn-success" 
                                onclick="return confirm('Deseja finalizar a compra? Os itens comprados serão movidos para o histórico.')">
                            <i class="fas fa-check-circle"></i> Finalizar Compra no Caixa
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="shopping-list">
            <form method="POST" class="mb-4">
                <div class="row">
                    <div class="col-md-4">
                        <input type="text" name="item_name" class="form-control" placeholder="Nome do item" required>
                    </div>
                    <div class="col-md-3">
                        <input type="number" name="quantity" class="form-control" value="1" min="1" required>
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="price" class="form-control" placeholder="Preço" required>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" name="add_item" class="btn btn-primary w-100">
                            <i class="fas fa-plus"></i> Adicionar
                        </button>
                    </div>
                </div>
            </form>

            <!-- Lista de Itens Não Comprados -->
            <div class="card mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-shopping-cart"></i> Itens a Comprar</h5>
                </div>
                <div class="card-body">
                    <?php 
                    $total_pending = 0;
                    foreach ($items as $item): 
                        if (!$item['is_purchased']):
                            $total_pending += $item['total_price'];
                    ?>
                        <div class="item-row">
                            <div class="flex-grow-1">
                                <span class="h5"><?php echo htmlspecialchars($item['item_name']); ?></span>
                                <span class="badge bg-secondary ms-2">Qtd: <?php echo $item['quantity']; ?></span>
                                <span class="badge bg-info ms-2">R$ <?php echo number_format($item['price'], 2, ',', '.'); ?></span>
                                <span class="badge bg-primary ms-2">Total: R$ <?php echo number_format($item['total_price'], 2, ',', '.'); ?></span>
                            </div>
                            <div class="action-buttons">
                                <a href="?toggle_status=<?php echo $item['id']; ?>" 
                                   class="btn btn-sm btn-outline-success btn-toggle-status"
                                   title="Marcar como comprado">
                                    <i class="fas fa-shopping-cart"></i>
                                </a>
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" 
                                        data-bs-target="#editModal<?php echo $item['id']; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="?delete=<?php echo $item['id']; ?>" class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Tem certeza que deseja excluir este item?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </div>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                    <div class="mt-3 text-end">
                        <h5>Total a Comprar: R$ <?php echo number_format($total_pending, 2, ',', '.'); ?></h5>
                    </div>
                </div>
            </div>

            <!-- Lista de Itens Comprados -->
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-check"></i> Itens Comprados</h5>
                </div>
                <div class="card-body">
                    <?php 
                    $total_purchased = 0;
                    foreach ($items as $item): 
                        if ($item['is_purchased']):
                            $total_purchased += $item['total_price'];
                    ?>
                        <div class="item-row purchased">
                            <div class="flex-grow-1">
                                <span class="h5"><?php echo htmlspecialchars($item['item_name']); ?></span>
                                <span class="badge bg-secondary ms-2">Qtd: <?php echo $item['quantity']; ?></span>
                                <span class="badge bg-info ms-2">R$ <?php echo number_format($item['price'], 2, ',', '.'); ?></span>
                                <span class="badge bg-primary ms-2">Total: R$ <?php echo number_format($item['total_price'], 2, ',', '.'); ?></span>
                            </div>
                            <div class="action-buttons">
                                <a href="?toggle_status=<?php echo $item['id']; ?>" 
                                   class="btn btn-sm btn-success btn-toggle-status"
                                   title="Desmarcar como comprado">
                                    <i class="fas fa-check"></i>
                                </a>
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" 
                                        data-bs-target="#editModal<?php echo $item['id']; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="?delete=<?php echo $item['id']; ?>" class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Tem certeza que deseja excluir este item?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </div>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                    <div class="mt-3 text-end">
                        <h5>Total Comprado: R$ <?php echo number_format($total_purchased, 2, ',', '.'); ?></h5>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php foreach ($items as $item): ?>
        <div class="modal fade" id="editModal<?php echo $item['id']; ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Item</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                            <div class="mb-3">
                                <label class="form-label">Nome do Item</label>
                                <input type="text" name="edit_item_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($item['item_name']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Quantidade</label>
                                <input type="number" name="edit_quantity" class="form-control" 
                                       value="<?php echo $item['quantity']; ?>" min="1" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Preço</label>
                                <input type="text" name="edit_price" class="form-control" 
                                       value="<?php echo number_format($item['price'], 2, ',', ''); ?>" 
                                       pattern="[0-9]*[.,]?[0-9]+" title="Digite um valor válido" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" name="update_item" class="btn btn-primary">Salvar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Função para adicionar item à lista principal
        function addToMainList(id, itemName, quantity, price) {
            fetch('add_to_main_list.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    item_id: id,
                    item_name: itemName,
                    quantity: quantity,
                    price: price
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Item adicionado à lista principal com sucesso!');
                    location.reload();
                } else {
                    alert('Erro ao adicionar item: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Erro ao adicionar item à lista principal');
            });
        }
    </script>
</body>
</html>
