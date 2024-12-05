<?php
session_start();
require_once 'config/database.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Processar adição de novo produto padrão
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_default_product'])) {
    $product_name = trim($_POST['product_name']);
    $category = trim($_POST['category']);
    $price = str_replace(',', '.', $_POST['price']);

    try {
        $stmt = $pdo->prepare("
            INSERT INTO default_products (product_name, category, price) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$product_name, $category, $price]);
        header("Location: default_products.php?success=1");
        exit();
    } catch (PDOException $e) {
        $error = "Erro ao adicionar produto: " . $e->getMessage();
    }
}

// Processar edição de produto padrão
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_default_product'])) {
    $product_id = $_POST['product_id'];
    $product_name = trim($_POST['edit_product_name']);
    $category = trim($_POST['edit_category']);
    $price = str_replace(',', '.', $_POST['edit_price']);

    try {
        $stmt = $pdo->prepare("
            UPDATE default_products 
            SET product_name = ?, category = ?, price = ? 
            WHERE id = ?
        ");
        $stmt->execute([$product_name, $category, $price, $product_id]);
        header("Location: default_products.php?updated=1");
        exit();
    } catch (PDOException $e) {
        $error = "Erro ao atualizar produto: " . $e->getMessage();
    }
}

// Processar exclusão de produto padrão
if (isset($_GET['delete'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM default_products WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        header("Location: default_products.php?deleted=1");
        exit();
    } catch (PDOException $e) {
        $error = "Erro ao excluir produto: " . $e->getMessage();
    }
}

// Buscar categorias existentes
try {
    $stmt = $pdo->query("SELECT DISTINCT category FROM default_products ORDER BY category");
    $existingCategories = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $error = "Erro ao buscar categorias: " . $e->getMessage();
}

// Buscar produtos padrão agrupados por categoria
$categories = [];
try {
    $stmt = $pdo->query("
        SELECT id, product_name, category, price 
        FROM default_products 
        ORDER BY category, product_name ASC
    ");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($products as $product) {
        if (!isset($categories[$product['category']])) {
            $categories[$product['category']] = [];
        }
        $categories[$product['category']][] = $product;
    }
} catch (PDOException $e) {
    $error = "Erro ao buscar produtos: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Lista Padrão de Produtos</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        .product-card {
            transition: transform 0.2s;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
        }
        .product-card:hover {
            transform: translateY(-5px);
            cursor: pointer;
            background-color: #e9ecef;
        }
        .category-section {
            margin-bottom: 2rem;
        }
        .success-animation {
            animation: successPulse 1s;
        }
        @keyframes successPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        .action-buttons {
            opacity: 0;
            transition: opacity 0.2s;
        }
        .product-card:hover .action-buttons {
            opacity: 1;
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
                        <a class="nav-link" href="index.php">Minha Lista</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="default_products.php">Lista Padrão</a>
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
            <h2>Lista Padrão de Produtos</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                <i class="fas fa-plus"></i> Adicionar Produto
            </button>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">Produto adicionado com sucesso!</div>
        <?php endif; ?>

        <?php if (isset($_GET['updated'])): ?>
            <div class="alert alert-success">Produto atualizado com sucesso!</div>
        <?php endif; ?>

        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success">Produto excluído com sucesso!</div>
        <?php endif; ?>

        <?php if (empty($categories)): ?>
            <div class="alert alert-warning">
                Nenhum produto encontrado. Adicione alguns produtos à lista padrão.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($categories as $category => $products): ?>
                    <div class="col-md-4 category-section">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><?php echo htmlspecialchars($category); ?></h5>
                            </div>
                            <div class="card-body">
                                <?php foreach ($products as $product): ?>
                                    <div class="product-card p-2 mb-2 rounded">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div onclick="addToList('<?php echo htmlspecialchars($product['product_name']); ?>', <?php echo $product['price']; ?>)">
                                                <i class="fas fa-plus-circle text-primary me-2"></i>
                                                <?php echo htmlspecialchars($product['product_name']); ?>
                                                <span class="badge bg-secondary ms-2">
                                                    R$ <?php echo number_format($product['price'], 2, ',', '.'); ?>
                                                </span>
                                            </div>
                                            <div class="action-buttons">
                                                <button class="btn btn-sm btn-warning" 
                                                        onclick="editProduct(<?php echo htmlspecialchars(json_encode($product)); ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <a href="?delete=<?php echo $product['id']; ?>" 
                                                   class="btn btn-sm btn-danger"
                                                   onclick="return confirm('Tem certeza que deseja excluir este produto?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal Adicionar Produto -->
    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Adicionar Produto Padrão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nome do Produto</label>
                            <input type="text" name="product_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Categoria</label>
                            <input type="text" name="category" class="form-control" 
                                   list="categories" required>
                            <datalist id="categories">
                                <?php foreach ($existingCategories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat); ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Preço Médio (R$)</label>
                            <input type="text" name="price" class="form-control" 
                                   pattern="[0-9]*[.,]?[0-9]+" 
                                   title="Digite um valor válido" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="add_default_product" class="btn btn-primary">Adicionar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Produto -->
    <div class="modal fade" id="editProductModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Produto Padrão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="product_id" id="edit_product_id">
                        <div class="mb-3">
                            <label class="form-label">Nome do Produto</label>
                            <input type="text" name="edit_product_name" id="edit_product_name" 
                                   class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Categoria</label>
                            <input type="text" name="edit_category" id="edit_category" 
                                   class="form-control" list="categories" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Preço Médio (R$)</label>
                            <input type="text" name="edit_price" id="edit_price" 
                                   class="form-control" pattern="[0-9]*[.,]?[0-9]+" 
                                   title="Digite um valor válido" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="edit_default_product" class="btn btn-primary">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Toast para feedback -->
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
        <div id="successToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-success text-white">
                <strong class="me-auto">Sucesso!</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">
                Produto adicionado à sua lista!
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function addToList(productName, price) {
            fetch('add_to_main_list.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    item_name: productName,
                    price: price
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const toast = new bootstrap.Toast(document.getElementById('successToast'));
                    toast.show();
                    
                    const productCards = document.querySelectorAll('.product-card');
                    productCards.forEach(card => {
                        if (card.textContent.includes(productName)) {
                            card.classList.add('success-animation');
                            setTimeout(() => card.classList.remove('success-animation'), 1000);
                        }
                    });
                } else {
                    alert('Erro ao adicionar produto: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Erro ao adicionar produto');
            });
        }

        function editProduct(product) {
            document.getElementById('edit_product_id').value = product.id;
            document.getElementById('edit_product_name').value = product.product_name;
            document.getElementById('edit_category').value = product.category;
            document.getElementById('edit_price').value = product.price.toString().replace('.', ',');
            
            new bootstrap.Modal(document.getElementById('editProductModal')).show();
        }
    </script>
</body>
</html>
