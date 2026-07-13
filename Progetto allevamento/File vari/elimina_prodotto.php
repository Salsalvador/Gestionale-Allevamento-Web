<?php

header('Content-Type: application/json');

require_once 'connessione.php';

$id = intval($_POST['id']);

// Verifica che il prodotto esista e recupera DATA e LOTTO
$stmtProdotto = $db->prepare("SELECT DATA, LOTTO FROM PRODOTTO WHERE ID_PRODUZIONE = ?");
if(!$stmtProdotto->execute([$id])){
    throw new PDOException("Errore nella verifica del prodotto");
}
$prodotto = $stmtProdotto->fetch(PDO::FETCH_ASSOC);
$data = $prodotto['DATA'];
$lotto = $prodotto['LOTTO'];

// Elimina prima le vendite collegate (usando DATA e DESCRIZIONE LIKE %lotto%)
if($lotto !== null && $lotto !== ''){
    $delVendite = $db->prepare("DELETE FROM VENDITA_PRODOTTI WHERE DATA = ? AND DESCRIZIONE LIKE ?");
    if(!$delVendite->execute([$data, "%$lotto%"])){
        throw new PDOException("Errore durante l'eliminazione delle vendite");
    }
}

// Elimina il prodotto
$stmt = $db->prepare("DELETE FROM PRODOTTO WHERE ID_PRODUZIONE = ?");
if(!$stmt->execute([$id])){
    throw new PDOException("Errore durante l'eliminazione");
}

echo json_encode(['success' => true, 'message' => 'Prodotto eliminato con successo']);
?>