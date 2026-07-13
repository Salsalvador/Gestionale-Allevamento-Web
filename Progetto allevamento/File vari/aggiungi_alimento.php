<?php
require_once 'connessione.php';

header('Content-Type: application/json');           // il client si aspetta un json

// recupera i dati dal form
$data = $_POST['data'];
$tag_animale = !empty($_POST['tag_animale']) ? $_POST['tag_animale'] : null;
$id_alimento = $_POST['id_alimento'];
$quantita_kg = $_POST['quantita_kg'];
$costo = isset($_POST['costo']) && $_POST['costo'] !== '' ? $_POST['costo'] : null;

// inizia una transazione
$db->beginTransaction();

try{
    // inserisci il consumo di alimento
    $stmtAlimento = $db->prepare("INSERT INTO ALIMENTO (DATA, TAG_ANIMALE, ID_ALIMENTO, QUANTITA_KG) VALUES (?, ?, ?, ?)");
    $stmtAlimento->execute([$data, $tag_animale, $id_alimento, $quantita_kg]);
    $id_consumo = $db->lastInsertId();

    // un altro modo per inserire nel database anzichè usare ? è usare :nomeVar e poi usare bindParam per assegnare a ogni placeholder il valore corretto

    // se è stato inserito un costo, aggiungi una spesa
    if($costo !== null){
        $descrizione = "Alimentazione animale: ".$tag_animale." - Alimento ID: ".$id_alimento;
        $stmtSpesa = $db->prepare("INSERT INTO SPESE (DATA, DESCRIZIONE, IMPORTO, CATEGORIA, ID_CONSUMO) VALUES (?, ?, ?, 'ALIMENTAZIONE', ?)");
        $stmtSpesa->execute([$data, $descrizione, $costo, $id_consumo]);
    }

    $db->commit();                  // conferma modifiche nel db
    echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
}
catch(Exception $e){
    $db->rollBack();            // annulla tutte le modifiche fatte se ci sono errori
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?> 