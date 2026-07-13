<?php
require_once 'connessione.php';

header('Content-Type: application/json');

// Recupera i dati dal form
$data = $_POST['data'];
$data_scadenza = isset($_POST['data_scadenza']) && $_POST['data_scadenza'] !== '' ? $_POST['data_scadenza'] : null;
$tag_animale = !empty($_POST['tag_animale']) ? $_POST['tag_animale'] : null;
$nome_vaccino = $_POST['nome_vaccino'];
$dosaggio = isset($_POST['dosaggio']) && $_POST['dosaggio'] !== '' ? floatval($_POST['dosaggio']) : null;
$id_veterinario = $_POST['id_veterinario'];
$costo = isset($_POST['costo']) && $_POST['costo'] !== '' ? $_POST['costo'] : null;
$note = isset($_POST['note']) ? $_POST['note'] : null;

// Inizia una transazione
$db->beginTransaction();

try{
    // Inserisci la vaccinazione
    $stmtVaccinazione = $db->prepare("INSERT INTO VACCINAZIONE (DATA, DATA_SCADENZA, TAG_ANIMALE, NOME_VACCINO, DOSAGGIO, ID_VETERINARIO, NOTE) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmtVaccinazione->execute([$data, $data_scadenza, $tag_animale, $nome_vaccino, $dosaggio, $id_veterinario, $note]);

    // Se è stato inserito un costo, aggiungi una spesa
    if($costo !== null){
        $descrizioneSpesa = "Vaccinazione: ".$tag_animale." - Vaccino: ".$nome_vaccino." - Veterinario ID: ".$id_veterinario;
        $stmtSpesa = $db->prepare("INSERT INTO SPESE (DATA, DESCRIZIONE, IMPORTO, CATEGORIA) VALUES (?, ?, ?, 'VETERINARIA')");
        $stmtSpesa->execute([$data, $descrizioneSpesa, $costo]);
    }

    $db->commit();
    echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
}
catch (Exception $e){
    $db->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?> 