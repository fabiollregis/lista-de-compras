<?php
require_once 'config/database.php';

try {
    // Verificar se a tabela existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'default_products'");
    $tableExists = $stmt->rowCount() > 0;
    
    echo "Tabela default_products existe? " . ($tableExists ? "Sim" : "Não") . "\n";
    
    if ($tableExists) {
        // Mostrar estrutura da tabela
        $stmt = $pdo->query("DESCRIBE default_products");
        echo "\nEstrutura da tabela:\n";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo json_encode($row) . "\n";
        }
        
        // Contar registros
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM default_products");
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "\nTotal de registros: " . $count['total'] . "\n";
        
        // Mostrar alguns registros
        $stmt = $pdo->query("SELECT * FROM default_products LIMIT 5");
        echo "\nPrimeiros 5 registros:\n";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo json_encode($row) . "\n";
        }
    }
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?>
