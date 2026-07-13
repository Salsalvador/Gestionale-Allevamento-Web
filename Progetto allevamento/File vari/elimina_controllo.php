<?php
header('Content-Type: application/json');

require_once 'connessione.php';

$id = intval($_POST['id']);

// Recupera i dati necessari per identificare la spesa
$getInfo = $db->prepare("SELECT DATA, TAG_ANIMALE, ID_VETERINARIO FROM CONTROLLO_VETERINARIO WHERE ID_CONTROLLO = ?");
$getInfo->execute([$id]);
$info = $getInfo->fetch(PDO::FETCH_ASSOC);

if(!$info){
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Controllo non trovato']);
    exit;
}

// Elimina la spesa collegata (usando data, tag animale e id veterinario nella descrizione)
$delSpese = $db->prepare("DELETE FROM SPESE WHERE DATA = ? AND CATEGORIA = 'VETERINARIA' AND DESCRIZIONE LIKE ? AND DESCRIZIONE LIKE ?");
$delSpese->execute([
    $info['DATA'],
    "%{$info['TAG_ANIMALE']}%",
    "%{$info['ID_VETERINARIO']}%"
]);

// Elimina il controllo
$stmt = $db->prepare("DELETE FROM CONTROLLO_VETERINARIO WHERE ID_CONTROLLO = ?");
if(!$stmt->execute([$id])){
    throw new PDOException("Errore durante l'eliminazione");
}

echo json_encode(['success' => true, 'message' => 'Controllo eliminato con successo']);
?> 