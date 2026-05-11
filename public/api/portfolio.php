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

    $symbol   = strtoupper(trim(preg_replace('/[^a-zA-Z0-9]/', '', $_POST['symbol'] ?? '')));
    $name     = htmlspecialchars(trim($_POST['name'] ?? ''), ENT_QUOTES);
    $quantity = (float)($_POST['quantity'] ?? 0);
    $buyPrice = (float)($_POST['buy_price'] ?? 0);

    if(empty($symbol) || empty($name)){
        jsonOut(['success' => false, 'message' => 'Símbolo ou nome inválido']);
    }
    if($quantity <= 0){
        jsonOut(['success' => false, 'message' => 'Quantidade deve ser maior que zero']);
    }
    if($buyPrice <= 0){
        jsonOut(['success' => false, 'message' => 'Preço de compra deve ser maior que zero']);
    }

    $stmt = $db->prepare("
        INSERT INTO portfolio (user_id, coin_symbol, coin_name, quantity, buy_price)
        VALUES (?,?,?,?,?)
    ");

    if($stmt->execute([$userId, $symbol, $name, $quantity, $buyPrice])){
        jsonOut(['success' => true, 'id' => (int)$db->lastInsertId()]);
    }

    jsonOut(['success' => false, 'message' => 'Erro ao inserir']);
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

    $stmt = $db->prepare("DELETE FROM portfolio WHERE id = ? AND user_id = ?");

    if($stmt->execute([$id, $userId])){
        jsonOut(['success' => true]);
    }

    jsonOut(['success' => false, 'message' => 'Erro ao remover']);
}

jsonOut(['success' => false, 'message' => 'Ação inválida']);