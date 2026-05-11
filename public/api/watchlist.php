<?php

if(session_status() === PHP_SESSION_NONE){
    session_start(['cookie_httponly' => true]);
}

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once '../../config/database.php';

function jsonOut(array $data): void {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if(!isset($_SESSION['user'])){
    http_response_code(401);
    jsonOut(['success' => false, 'message' => 'Não autenticado']);
}

$database = new Database();
$db = $database->connect();

if(!$db){
    http_response_code(500);
    jsonOut(['success' => false, 'message' => 'Erro de banco de dados']);
}

$userId = (int)$_SESSION['user']['id'];
$action = $_POST['action'] ?? '';

/*
========================================
ADICIONAR
========================================
*/

if($action === 'add'){

    $symbol = strtoupper(trim(preg_replace('/[^a-zA-Z0-9]/', '', $_POST['symbol'] ?? '')));
    $name   = htmlspecialchars(trim($_POST['name'] ?? ''), ENT_QUOTES);

    if(empty($symbol) || empty($name)){
        jsonOut(['success' => false, 'message' => 'Dados inválidos']);
    }

    // Verificar duplicata
    $check = $db->prepare("SELECT id FROM watchlist WHERE user_id = ? AND coin_symbol = ?");
    $check->execute([$userId, $symbol]);

    if($check->rowCount() > 0){
        jsonOut(['success' => false, 'message' => 'Moeda já está na watchlist']);
    }

    $stmt = $db->prepare("INSERT INTO watchlist (user_id, coin_symbol, coin_name) VALUES (?,?,?)");

    if($stmt->execute([$userId, $symbol, $name])){
        jsonOut(['success' => true, 'action' => 'added', 'id' => (int)$db->lastInsertId()]);
    }

    jsonOut(['success' => false, 'message' => 'Erro ao adicionar']);
}

/*
========================================
REMOVER
========================================
*/

if($action === 'remove'){

    $id = (int)($_POST['id'] ?? 0);

    if($id <= 0){
        jsonOut(['success' => false, 'message' => 'ID inválido']);
    }

    $stmt = $db->prepare("DELETE FROM watchlist WHERE id = ? AND user_id = ?");

    if($stmt->execute([$id, $userId])){
        jsonOut(['success' => true, 'action' => 'removed']);
    }

    jsonOut(['success' => false, 'message' => 'Erro ao remover']);
}

jsonOut(['success' => false, 'message' => 'Ação inválida']);