<?php
header('Content-Type: application/json');

require_once 'connessione.php';

$id = intval($_POST['id']);

$check = $db->prepare("SELECT 1 FROM ALIMENTO WHERE ID_CONSUMO = ?");
if (!$check->execute([$id])) {
    throw new PDOException("Errore nella verifica del consumo");
}

if(!$check->fetch()){
    echo json_encode(['success' => false, 'message' => 'Consumo non trovato']);
    exit;
}

// elimina prima le spese collegate
$delSpese = $db->prepare("DELETE FROM SPESE WHERE ID_CONSUMO = ?");
if(!$delSpese->execute([$id])){
    throw new PDOException("Errore durante l'eliminazione delle spese");
}

// elimina il consumo
$stmt = $db->prepare("DELETE FROM ALIMENTO WHERE ID_CONSUMO = ?");
if(!$stmt->execute([$id])){
    throw new PDOException("Errore durante l'eliminazione");
}

echo json_encode(['success' => true, 'message' => 'Consumo eliminato con successo']);
?> 