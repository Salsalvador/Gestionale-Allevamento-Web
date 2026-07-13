<?php
require_once 'connessione.php';
session_start();

$nome = $_POST['nome'] ?? '';
$specie = $_POST['specie'] ?? '';
$razza = $_POST['razza'] ?? '';
$data_nascita = $_POST['data_nascita'] ?? '';
$sesso = $_POST['sesso'] ?? '';
$padre_tag = $_POST['padre_tag'] ?? null;
$madre_tag = $_POST['madre_tag'] ?? null;

// verifica di aver riempito tutti i campi obbligatori
if(!$nome || !$specie || !$razza || !$data_nascita || !$sesso){
    echo json_encode(['success' => false, 'message' => 'Tutti i campi sono obbligatori']);
    exit();
}

// mappa specie -> tag
$specieLettera =['Bovino' => 'B', 'Ovino' => 'O', 'Suino' => 'S', 'Caprino' => 'C', 'Equino' => 'E'];
$letteraTag = $specieLettera[$specie];

// Verifica esistenza genitori e compatibilità specie
$db->beginTransaction();

try {
    // verifica padre se specificato
    if($padre_tag){
        $query = $db->prepare("SELECT TAG, SPECIE, SESSO FROM ANIMALE WHERE TAG = ?");
        $query->execute([$padre_tag]);
        $padre = $query->fetch(PDO::FETCH_ASSOC);

        // controlla che il tag associato esista effettivamente
        if(!$padre){
            echo json_encode(['success' => false, 'message' => "Padre con TAG $padre_tag non trovato"]);
            exit();
        }

        // controlla che il padre sia della stessa specie del figlio
        if($padre['SPECIE'] !== $specie){
            echo json_encode(['success' => false, 'message' => "Il padre deve essere della stessa specie ($specie)"]);
            exit();
        }

        // controlla che il padre sia maschio
        if ($padre['SESSO'] !== 'M'){
            echo json_encode(['success' => false, 'message' => "Il padre specificato non è di sesso maschile"]);
            exit();
        }
    }

    // verifica madre se specificata
    if($madre_tag){
        $query = $db->prepare("SELECT TAG, SPECIE, SESSO FROM ANIMALE WHERE TAG = ?");
        $query->execute([$madre_tag]);
        $madre = $query->fetch(PDO::FETCH_ASSOC);

        // analogamente al padre, verifica che i parametri inseriti siano realistici
        if(!$madre){
            echo json_encode(['success' => false, 'message' => "Madre con TAG $madre_tag non trovata"]);
            exit();
        }

        if($madre['SPECIE'] !== $specie){
            echo json_encode(['success' => false, 'message' => "La madre deve essere della stessa specie ($specie)"]);
            exit();
        }

        if($madre['SESSO'] !== 'F'){
            echo json_encode(['success' => false, 'message' => "La madre specificata non è di sesso femminile"]);
            exit();
        }
    }

    // genera l'animale dandogli un nuovo tag

    // cerca tutti gli animali con la lettera del tag di interesse, ordine decrescente, prende la parte del tag dalla 2 posiz. in poi, converte da stringa ad intero e prende solo il 1 risultato
    $query = $db->prepare("SELECT TAG FROM ANIMALE WHERE TAG LIKE ? ORDER BY CAST(SUBSTRING(TAG, 2) AS UNSIGNED) DESC LIMIT 1");
    $likeTag = $letteraTag . '%';
    $query->execute([$likeTag]);
    $result = $query->fetch(PDO::FETCH_ASSOC);          // quindi di fatto prende l'animale della specie specificata e col tag più alto

    if($result){
        $ultimoNumero = (int)substr($result['TAG'], 1);
        $nuovoNumero = $ultimoNumero + 1;               // quindi il nuovo tag è uguale al tag più alto + 1
    }
    else
    {
        $nuovoNumero = 0;                               // altrimenti la query fallisce (non ci sono ancora animali), quindi il nuovo tag è il primo => 0
    }

    $tag = $letteraTag.$nuovoNumero;

    // Inserisci animale con genitori
    $queryInsert = $db->prepare("INSERT INTO ANIMALE (TAG, NOME, SPECIE, RAZZA, DATA_NASCITA, SESSO, PADRE_TAG, MADRE_TAG) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $queryInsert->execute([$tag, $nome, $specie, $razza, $data_nascita, $sesso, $padre_tag ?: null, $madre_tag ?: null]);

    $db->commit();
    echo json_encode(['success' => true, 'tag' => $tag]);
} catch(Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>