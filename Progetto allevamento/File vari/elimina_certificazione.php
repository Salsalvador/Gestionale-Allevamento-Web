<?php
require_once 'connessione.php';
header('Content-Type: application/json');

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

$stmt = $db->prepare('DELETE FROM CERTIFICAZIONE WHERE ID_CERTIFICAZIONE = ?');
$stmt->execute([$id]);
echo json_encode(['success' => true]);