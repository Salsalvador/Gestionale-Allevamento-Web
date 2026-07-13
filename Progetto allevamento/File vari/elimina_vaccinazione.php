<?php
header('Content-Type: application/json');

require_once 'connessione.php';

$id = intval($_POST['id']);

// Recupera i dati necessari per identificare la spesa
$getInfo = $db->prepare("SELECT DATA, TAG_ANIMALE, ID_VETERINARIO FROM VACCINAZIONE WHERE ID_VACCINAZIONE = ?");
$getInfo->execute([$id]);
$info = $getInfo->fetch(PDO::FETCH_ASSOC);

if(!$info){
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Vaccinazione non trovata']);
    exit;
}

// Elimina la spesa collegata (usando data, tag animale e id veterinario nella descrizione)
$delSpese = $db->prepare("DELETE FROM SPESE WHERE DATA = ? AND CATEGORIA = 'VETERINARIA' AND DESCRIZIONE LIKE ? AND DESCRIZIONE LIKE ?");
$delSpese->execute([
    $info['DATA'],
    "%{$info['TAG_ANIMALE']}%",
    "%{$info['ID_VETERINARIO']}%"
]);

// Elimina la vaccinazione
$stmt = $db->prepare("DELETE FROM VACCINAZIONE WHERE ID_VACCINAZIONE = ?");
if(!$stmt->execute([$id])){
    throw new PDOException("Errore durante l'eliminazione");
}

echo json_encode(['success' => true, 'message' => 'Vaccinazione eliminata con successo']);
?> 