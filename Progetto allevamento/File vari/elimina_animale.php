<?php
require_once 'connessione.php';
session_start();

$tag = $_POST['tag'] ?? '';
    
$db->beginTransaction();

// Verifica se l'animale ha figli
$stmt = $db->prepare("SELECT COUNT(*) FROM ANIMALE WHERE PADRE_TAG = ? OR MADRE_TAG = ?");
$stmt->execute([$tag, $tag]);
$hasChildren = $stmt->fetchColumn();

if($hasChildren > 0){
    // Se ha figli, aggiorna i riferimenti a NULL
    $stmt = $db->prepare("UPDATE ANIMALE SET PADRE_TAG = NULL WHERE PADRE_TAG = ?");
    $stmt->execute([$tag]);
    
    $stmt = $db->prepare("UPDATE ANIMALE SET MADRE_TAG = NULL WHERE MADRE_TAG = ?");
    $stmt->execute([$tag]);
}

// Elimina l'animale
$stmt = $db->prepare("DELETE FROM ANIMALE WHERE TAG = ?");
$stmt->execute([$tag]);

$db->commit();

echo json_encode(['success' => true]);
?>