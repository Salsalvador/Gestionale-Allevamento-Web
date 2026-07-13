<?php
require_once 'connessione.php';

header('Content-Type: application/json');

// recupera i dati dal form
$data = $_POST['data'];
$tag_animale = !empty($_POST['tag_animale']) ? $_POST['tag_animale'] : null;
$descrizione = $_POST['descrizione'];
$esito = $_POST['esito'];
$id_veterinario = $_POST['id_veterinario'];
$costo = isset($_POST['costo']) && $_POST['costo'] !== '' ? $_POST['costo'] : null;
$note = isset($_POST['note']) ? $_POST['note'] : null;

// inizia una transazione
$db->beginTransaction();

try{
    // inserisci il controllo veterinario
    $stmtControllo = $db->prepare("INSERT INTO CONTROLLO_VETERINARIO (DATA, TAG_ANIMALE, DESCRIZIONE, ESITO, ID_VETERINARIO, NOTE) VALUES (?, ?, ?, ?, ?, ?)");
    $stmtControllo->execute([$data, $tag_animale, $descrizione, $esito, $id_veterinario, $note]);

    // se è stato inserito un costo, aggiungi una spesa
    if($costo !== null){
        $descrizioneSpesa = "Controllo veterinario: ".$tag_animale." - Veterinario ID: ".$id_veterinario;
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