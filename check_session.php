<?php
// Ativar exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

echo "Status da Sessão:\n";
echo "session_id: " . session_id() . "\n";
echo "SESSION: " . print_r($_SESSION, true) . "\n";
echo "Usuário logado? " . (isset($_SESSION['user_id']) ? "Sim (ID: {$_SESSION['user_id']})" : "Não") . "\n";
?>
