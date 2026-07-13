<?php
require_once 'connessione.php';

header('Content-Type: application/json');

// recupera i dati dal form
$data = $_POST['data'];
$tipo = $_POST['tipo'];
$tag_animale = !empty($_POST['tag_animale']) ? $_POST['tag_animale'] : null;
$quantita = $_POST['quantita'];
$lotto = !empty($_POST['lotto']) ? $_POST['lotto'] : null;
$importo = $_POST['importo'];

$db->beginTransaction();

try
{
    // inserisci il prodotto
    $stmtProdotto = $db->prepare("INSERT INTO PRODOTTO (DATA, TIPO, TAG_ANIMALE, QUANTITA, LOTTO) VALUES (?, ?, ?, ?, ?)");
    $stmtProdotto->execute([$data, $tipo, $tag_animale, $quantita, $lotto]);

    // recupera il nome del prodotto per la descrizione della vendita
    $queryNomeProdotto = "SELECT TIPO FROM TIPO_PRODOTTO WHERE TIPO = ?";
    $stmtNome = $db->prepare($queryNomeProdotto);
    $stmtNome->execute([$tipo]);
    $nomeProdotto = $stmtNome->fetchColumn();

    // inserisci la vendita correlata
    $descrizione = "Vendita " . $nomeProdotto . ($lotto ? " - Lotto " . $lotto : "");
    $stmtRicavo = $db->prepare("INSERT INTO VENDITA_PRODOTTI (DATA, DESCRIZIONE, IMPORTO, QUANTITA) VALUES (?, ?, ?, ?)");
    $stmtRicavo->execute([$data, $descrizione, $importo, $quantita]);

    $db->commit();

    echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
}
catch(Exception $e){
    // annulla la transazione in caso di errore
    $db->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>