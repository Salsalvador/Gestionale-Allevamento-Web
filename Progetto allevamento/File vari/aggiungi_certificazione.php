<?php
require_once 'connessione.php';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    header('Content-Type: application/json');
    $data_rilascio = $_POST['data_rilascio'] ?? null;
    $data_scadenza = $_POST['data_scadenza'] ?? null;
    $ente = $_POST['ente_certificatore'] ?? null;
    $descrizione = $_POST['descrizione'] ?? null;
    $tag_animale = $_POST['tag_animale'] ?? null;
    if(!$data_rilascio || !$ente || !$tag_animale){
        echo json_encode(['success' => false, 'message' => 'Compila tutti i campi obbligatori e seleziona un animale.']);
        exit;
    }
    try{
        $db->beginTransaction();
        $stmt = $db->prepare("INSERT INTO CERTIFICAZIONE (DATA_RILASCIO, DATA_SCADENZA, ENTE_CERTIFICATORE, DESCRIZIONE, TAG_ANIMALE) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$data_rilascio, $data_scadenza, $ente, $descrizione, $tag_animale]);
        $db->commit();
        echo json_encode(['success' => true]);
    }
    catch(Exception $e){
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}
// Se non POST, non mostrare nulla
http_response_code(405);
exit; 