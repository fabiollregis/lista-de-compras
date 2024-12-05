<?php
require_once 'config/database.php';

try {
    // Adicionar coluna price se não existir
    $pdo->exec("ALTER TABLE default_products ADD COLUMN IF NOT EXISTS price DECIMAL(10,2) DEFAULT 0.00");
    echo "Coluna price adicionada com sucesso!\n";

    // Limpar a tabela para reinserir os dados
    $pdo->exec("TRUNCATE TABLE default_products");
    echo "Tabela limpa com sucesso!\n";

    // Lista de produtos padrão por categoria com preços médios
    $defaultProducts = [
        'Hortifruti' => [
            ['Alface', 3.50], ['Tomate', 6.00], ['Cebola', 4.50], ['Batata', 5.00],
            ['Cenoura', 4.00], ['Banana', 5.50], ['Maçã', 8.00], ['Laranja', 4.50],
            ['Limão', 3.50], ['Alho', 3.00], ['Pimentão', 4.50], ['Repolho', 5.00]
        ],
        'Carnes' => [
            ['Frango', 15.00], ['Carne Moída', 30.00], ['Filé de Frango', 20.00],
            ['Costela', 35.00], ['Linguiça', 18.00], ['Peito de Frango', 22.00],
            ['Carne de Porco', 25.00], ['Peixe', 30.00]
        ],
        'Laticínios' => [
            ['Leite', 5.50], ['Queijo', 25.00], ['Iogurte', 7.00], ['Manteiga', 12.00],
            ['Requeijão', 8.00], ['Cream Cheese', 10.00], ['Leite Condensado', 7.50],
            ['Creme de Leite', 4.50]
        ],
        'Mercearia' => [
            ['Arroz', 20.00], ['Feijão', 8.00], ['Macarrão', 5.00], ['Óleo', 8.50],
            ['Sal', 3.00], ['Açúcar', 4.50], ['Café', 15.00], ['Farinha de Trigo', 5.00],
            ['Molho de Tomate', 4.00]
        ],
        'Bebidas' => [
            ['Água', 3.00], ['Refrigerante', 8.00], ['Suco', 6.00], ['Cerveja', 4.50],
            ['Vinho', 35.00], ['Água de Coco', 5.00]
        ],
        'Padaria' => [
            ['Pão Francês', 15.00], ['Pão de Forma', 8.00], ['Bolo', 20.00],
            ['Biscoito', 5.00], ['Torrada', 6.00], ['Pão de Queijo', 25.00]
        ],
        'Limpeza' => [
            ['Detergente', 3.50], ['Sabão em Pó', 15.00], ['Desinfetante', 8.00],
            ['Papel Higiênico', 18.00], ['Água Sanitária', 6.00], ['Amaciante', 12.00],
            ['Esponja', 2.50]
        ],
        'Higiene' => [
            ['Sabonete', 3.00], ['Shampoo', 15.00], ['Condicionador', 15.00],
            ['Pasta de Dente', 8.00], ['Escova de Dente', 5.00], ['Desodorante', 12.00],
            ['Papel Toalha', 6.00]
        ]
    ];

    // Inserir produtos com preços
    $stmt = $pdo->prepare("
        INSERT INTO default_products (product_name, category, price) 
        VALUES (?, ?, ?)
    ");

    foreach ($defaultProducts as $category => $products) {
        foreach ($products as $product) {
            $stmt->execute([$product[0], $category, $product[1]]);
            echo "Produto adicionado: {$product[0]} - {$category} - R$ {$product[1]}\n";
        }
    }

    echo "\nTodos os produtos foram inseridos com sucesso!";

} catch (PDOException $e) {
    die("Erro: " . $e->getMessage());
}
?>
