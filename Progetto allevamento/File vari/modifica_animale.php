<?php
require_once 'connessione.php';
session_start();

// Recupera il TAG dell'animale da modificare
$tag = $_POST['tag'] ?? ($_GET['tag'] ?? null);
if(!$tag){
    die('Tag animale non specificato');
}

// Recupera i dati dell'animale
$stmt = $db->prepare("SELECT * FROM ANIMALE WHERE TAG = ?");
$stmt->execute([$tag]);
$animale = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$animale){
    die('Animale non trovato');
}

// Recupera i possibili genitori (escludendo l'animale stesso)
$stmt = $db->prepare("SELECT TAG, NOME, SPECIE, RAZZA, SESSO FROM ANIMALE WHERE TAG != ? ORDER BY SPECIE, NOME");
$stmt->execute([$tag]);
$possibili_genitori = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Elabora i dati del form se inviato
$messaggio = '';
$errore = '';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $tag = $_POST['tag'] ?? $tag;
    $nome = trim($_POST['nome']);
    $data_nascita = $_POST['data_nascita'];
    $sesso = $_POST['sesso'];
    $specie = $_POST['specie'];
    $razza = trim($_POST['razza']);
    $padre_tag = $_POST['padre_tag'] ?: null;
    $madre_tag = $_POST['madre_tag'] ?: null;

    // Validazioni
    if(empty($nome) || empty($data_nascita) || empty($sesso) || empty($specie) || empty($razza)){
        $errore = 'Tutti i campi obbligatori devono essere compilati';
    }
    elseif ($padre_tag && $padre_tag === $madre_tag){
        $errore = 'Padre e madre non possono essere lo stesso animale';
    }
    else{
        try{
            $db->beginTransaction();
            
            // Aggiorna i dati dell'animale
            $stmt = $db->prepare("
                UPDATE ANIMALE SET NOME = ?, DATA_NASCITA = ?, SESSO = ?, SPECIE = ?, RAZZA = ?, PADRE_TAG = ?, MADRE_TAG = ? WHERE TAG = ?");
            $stmt->execute([$nome, $data_nascita, $sesso, $specie, $razza, $padre_tag, $madre_tag, $tag]);
            
            $db->commit();
            $messaggio = 'Animale modificato con successo!';
            
            // Ricarica i dati dell'animale per mostrare le modifiche
            $stmt = $db->prepare("SELECT * FROM ANIMALE WHERE TAG = ?");
            $stmt->execute([$tag]);
            $animale = $stmt->fetch(PDO::FETCH_ASSOC);
            
        }
        catch (PDOException $e){
            $db->rollBack();
            $errore = 'Errore durante la modifica: ' . $e->getMessage();
        }
    }
}

$ruolo = $_SESSION['ruolo'] ?? null;
$dashboard_url = 'dashboard_allevatore.php';
if($ruolo === 'AMMINISTRATORE'){
    $dashboard_url = 'dashboard_amministratore.php';
}

?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifica Animale - Gestione Allevamento</title>
    <style>
body{font-family: Arial, sans-serif; margin: 0;background: white;}

.animali-container{padding: 20px; max-width: 1200px; margin: 0 auto;}

.btn-aggiungi{
    background-color: green;
    color: white;
    border: none;
    padding: 10px 15px;
    margin-bottom: 20px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 16px;
}

.btn-aggiungi:hover{background-color: darkgreen;}

.animali-table{width: 100%; border-collapse: collapse; margin-bottom: 30px;}

.animali-table th, .animali-table td{border: 1px solid lightgrey; padding: 12px; text-align: left;}

.animali-table th{background-color: white;}

.animali-table tr:nth-child(even){background-color: white;}

.animali-table tr:hover{background-color: lightgrey;}

.btn-elimina{
    background-color: red;
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 4px;
    cursor: pointer;
}

.btn-elimina:hover{background-color: darkred;}

.btn-modifica{
    background-color: blue;
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 4px;
    cursor: pointer;
    text-align: center;
    text-decoration: none;
    font-size: 15px;
}

.btn-modifica:hover{background-color: darkblue;}

.modal{
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.4);
}

.modal-content{
    background-color: white;
    margin: 5% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 80%;
    max-width: 600px;
    border-radius: 5px;
    box-shadow: 0 4px 8px 0 rgba(0,0,0,0.2);
}

.close{
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close:hover{color: black;}

.form-group{margin-bottom: 15px;}

.form-group label{display: block; margin-bottom: 5px; font-weight: bold;}

.form-group input[type="date"],
.form-group input[type="number"],
.form-group input[type="text"],
.form-group select {
    width: 100%;
    padding: 8px;
    border: 1px solid lightgrey;
    border-radius: 4px;
    box-sizing: border-box;
}

.form-actions{margin-top: 20px; text-align: right;}

.btn-annulla{
    background-color: red;
    color: white;
    border: none;
    padding: 10px 15px;
    border-radius: 4px;
    cursor: pointer;
    margin-right: 10px;
    text-decoration: none;
}

.btn-annulla:hover{background-color: darkred;}

.btn-salva{
    background-color: green;
    color: white;
    border: none;
    padding: 10px 15px;
    border-radius: 4px;
    cursor: pointer;
}

.btn-salva:hover{background-color: darkgreen;}
    </style>
</head>
<body>
    <div class="container">
        <h2>Modifica Animale: <?= htmlspecialchars($animale['TAG']) ?></h2>
        <?php if ($errore): ?>
            <div class="errore"><?= $errore ?></div>
        <?php endif; ?>
        <form method="post">
            <div class="form-group">
                <input type="hidden" name="tag" value="<?= htmlspecialchars($animale['TAG']) ?>">
            </div>
            <div class="form-group">
                <label for="nome">Nome *</label>
                <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($animale['NOME']) ?>" required>
            </div>
            <div class="form-group">
                <label for="specie">Specie *</label>
                <select id="specie" name="specie" required>
                    <option value="Bovino" <?= $animale['SPECIE'] === 'Bovino' ? 'selected' : '' ?>>Bovino</option>
                    <option value="Suino" <?= $animale['SPECIE'] === 'Suino' ? 'selected' : '' ?>>Suino</option>
                    <option value="Ovino" <?= $animale['SPECIE'] === 'Ovino' ? 'selected' : '' ?>>Ovino</option>
                    <option value="Caprino" <?= $animale['SPECIE'] === 'Caprino' ? 'selected' : '' ?>>Caprino</option>
                    <option value="Equino" <?= $animale['SPECIE'] === 'Equino' ? 'selected' : '' ?>>Equino</option>
                </select>
            </div>
            <div class="form-group">
                <label for="razza">Razza *</label>
                <input type="text" id="razza" name="razza" value="<?= htmlspecialchars($animale['RAZZA']) ?>" required>
            </div>
            <div class="form-group">
                <label for="data_nascita">Data di Nascita *</label>
                <input type="date" id="data_nascita" name="data_nascita" value="<?= htmlspecialchars($animale['DATA_NASCITA']) ?>" required>
            </div>
            <div class="form-group">
                <label for="sesso">Sesso *</label>
                <select id="sesso" name="sesso" required>
                    <option value="M" <?= $animale['SESSO'] === 'M' ? 'selected' : '' ?>>Maschio</option>
                    <option value="F" <?= $animale['SESSO'] === 'F' ? 'selected' : '' ?>>Femmina</option>
                </select>
            </div>
            <div class="form-group">
                <label for="padre_tag">Padre (Tag)</label>
                <select id="padre_tag" name="padre_tag">
                    <option value="">Nessuno</option>
                    <?php foreach ($possibili_genitori as $genitore): ?>
                        <?php if ($genitore['SESSO'] === 'M' || $genitore['SESSO'] === null): ?>
                            <option value="<?= htmlspecialchars($genitore['TAG']) ?>" 
                                <?= $animale['PADRE_TAG'] === $genitore['TAG'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($genitore['TAG']) ?> - <?= htmlspecialchars($genitore['NOME']) ?> 
                                (<?= htmlspecialchars($genitore['SPECIE']) ?>, <?= htmlspecialchars($genitore['RAZZA']) ?>)
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="madre_tag">Madre (Tag)</label>
                <select id="madre_tag" name="madre_tag">
                    <option value="">Nessuno</option>
                    <?php foreach ($possibili_genitori as $genitore): ?>
                        <?php if ($genitore['SESSO'] === 'F' || $genitore['SESSO'] === null): ?>
                            <option value="<?= htmlspecialchars($genitore['TAG']) ?>" 
                                <?= $animale['MADRE_TAG'] === $genitore['TAG'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($genitore['TAG']) ?> - <?= htmlspecialchars($genitore['NOME']) ?> 
                                (<?= htmlspecialchars($genitore['SPECIE']) ?>, <?= htmlspecialchars($genitore['RAZZA']) ?>)
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-salva">Salva</button>
                <a href="<?= $dashboard_url ?>" class="btn-annulla">Torna alla lista animali</a>
            </div>
        </form>
        <?php if($messaggio): ?><div class="messaggio" style="margin-top: 18px;"><?= $messaggio ?></div><?php endif; ?>
    </div>
</body>
</html>