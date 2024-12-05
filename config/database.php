<?php
// Configurações do banco de dados
$db_config = [
    'local' => [
        'host' => 'localhost',
        'dbname' => 'shopping_list',
        'username' => 'root',
        'password' => ''
    ],
    'production' => [
        'host' => 'localhost', // Altere para o host do seu cPanel
        'dbname' => '', // Nome do banco no cPanel
        'username' => '', // Usuário do banco no cPanel
        'password' => '' // Senha do banco no cPanel
    ]
];

// Define o ambiente (local ou production)
$environment = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false) ? 'local' : 'production';

// Seleciona a configuração baseada no ambiente
$config = $db_config[$environment];

try {
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8",
        $config['username'],
        $config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
        ]
    );
} catch(PDOException $e) {
    // Se o banco de dados não existir, tenta criá-lo
    if($e->getCode() == 1049) {
        try {
            $pdo = new PDO("mysql:host={$config['host']};charset=utf8", $config['username'], $config['password']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Criar o banco de dados
            $pdo->exec("CREATE DATABASE IF NOT EXISTS {$config['dbname']}");
            $pdo->exec("USE {$config['dbname']}");
            
            // Primeiro, dropar as tabelas na ordem correta
            $pdo->exec("DROP TABLE IF EXISTS shopping_history_items");
            $pdo->exec("DROP TABLE IF EXISTS shopping_history");
            $pdo->exec("DROP TABLE IF EXISTS shopping_items");
            $pdo->exec("DROP TABLE IF EXISTS users");
            
            // Criar tabela users primeiro
            $pdo->exec("CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL UNIQUE,
                email VARCHAR(100) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
            
            // Criar tabela shopping_items
            $pdo->exec("CREATE TABLE shopping_items (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                item_name VARCHAR(100) NOT NULL,
                quantity INT DEFAULT 1,
                price DECIMAL(10,2) DEFAULT 0.00,
                is_purchased BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )");

            // Criar tabela shopping_history para armazenar as compras finalizadas
            $pdo->exec("CREATE TABLE shopping_history (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                total_amount DECIMAL(10,2) NOT NULL,
                purchase_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )");

            // Criar tabela shopping_history_items para armazenar os itens de cada compra
            $pdo->exec("CREATE TABLE shopping_history_items (
                id INT PRIMARY KEY AUTO_INCREMENT,
                history_id INT NOT NULL,
                item_name VARCHAR(100) NOT NULL,
                quantity INT NOT NULL,
                price DECIMAL(10,2) NOT NULL,
                FOREIGN KEY (history_id) REFERENCES shopping_history(id) ON DELETE CASCADE
            )");
            
        } catch(PDOException $e2) {
            die("Erro na criação do banco de dados: " . $e2->getMessage());
        }
    } else {
        die("Erro na conexão: " . $e->getMessage());
    }
}

// Verificar e criar tabelas se não existirem
try {
    $pdo->query("SELECT 1 FROM shopping_history LIMIT 1");
} catch(PDOException $e) {
    // Criar tabela shopping_history
    $pdo->exec("CREATE TABLE shopping_history (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        total_amount DECIMAL(10,2) NOT NULL,
        purchase_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
}

try {
    $pdo->query("SELECT 1 FROM shopping_history_items LIMIT 1");
} catch(PDOException $e) {
    // Criar tabela shopping_history_items
    $pdo->exec("CREATE TABLE shopping_history_items (
        id INT PRIMARY KEY AUTO_INCREMENT,
        history_id INT NOT NULL,
        item_name VARCHAR(100) NOT NULL,
        quantity INT NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (history_id) REFERENCES shopping_history(id) ON DELETE CASCADE
    )");
}

// Verificar se as tabelas existem, se não, criá-las
try {
    $pdo->query("SELECT 1 FROM users LIMIT 1");
} catch(PDOException $e) {
    // Tabela users não existe, vamos criar
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
}

try {
    $pdo->query("SELECT 1 FROM shopping_items LIMIT 1");
} catch(PDOException $e) {
    // Tabela shopping_items não existe, vamos criar
    $pdo->exec("CREATE TABLE shopping_items (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        item_name VARCHAR(100) NOT NULL,
        quantity INT DEFAULT 1,
        price DECIMAL(10,2) DEFAULT 0.00,
        is_purchased BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
}

// Verificar se a coluna is_purchased existe, se não, adicioná-la
try {
    $pdo->query("SELECT is_purchased FROM shopping_items LIMIT 1");
} catch(PDOException $e) {
    // Coluna não existe, vamos criar
    $pdo->exec("ALTER TABLE shopping_items ADD COLUMN is_purchased BOOLEAN DEFAULT FALSE");
}

// Verificar se a coluna price existe, se não, adicioná-la
try {
    $pdo->query("SELECT price FROM shopping_items LIMIT 1");
} catch(PDOException $e) {
    // Coluna não existe, vamos criar
    $pdo->exec("ALTER TABLE shopping_items ADD COLUMN price DECIMAL(10,2) DEFAULT 0.00");
}

// Verificar se a coluna email existe, se não, adicioná-la
try {
    $pdo->query("SELECT email FROM users LIMIT 1");
} catch(PDOException $e) {
    // Coluna não existe, vamos criar
    // Primeiro adiciona a coluna permitindo NULL
    $pdo->exec("ALTER TABLE users ADD COLUMN email VARCHAR(100) NULL");
    
    // Atualiza registros existentes com um email temporário baseado no username
    $pdo->exec("UPDATE users SET email = CONCAT(username, '@temp.com') WHERE email IS NULL");
    
    // Agora podemos adicionar as constraints
    $pdo->exec("ALTER TABLE users MODIFY email VARCHAR(100) NOT NULL");
    $pdo->exec("ALTER TABLE users ADD UNIQUE (email)");
}
?>
