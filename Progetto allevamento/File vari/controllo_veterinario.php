<?php

require_once 'connessione.php';

// recupera i vari dati dei controlli veterinari tramite la tabella spese
$query = "SELECT cv.*, a.NOME AS nome_animale, a.SPECIE, v.NOME AS nome_veterinario,
    (SELECT s.IMPORTO FROM SPESE s
        WHERE s.DATA = cv.DATA
        AND s.CATEGORIA = 'VETERINARIA'
        AND s.DESCRIZIONE LIKE CONCAT('%', cv.TAG_ANIMALE, '%')         -- i '%' si usano per la ricerca delle sottostringhe
        AND s.DESCRIZIONE LIKE CONCAT('%', cv.ID_VETERINARIO, '%')
        LIMIT 1) AS costo
    FROM CONTROLLO_VETERINARIO cv
    LEFT JOIN ANIMALE a ON cv.TAG_ANIMALE = a.TAG
    LEFT JOIN VETERINARIO v ON cv.ID_VETERINARIO = v.ID_VETERINARIO
    ORDER BY cv.DATA DESC";
// eseguo la query e salvo i risultati in un array
$stmt = $db->query($query);
$controlli = $stmt->fetchAll(PDO::FETCH_ASSOC);

// query per ottenere gli animali vivi
$animali = $db->query("SELECT TAG, NOME, SPECIE FROM ANIMALE ORDER BY NOME")->fetchAll(PDO::FETCH_ASSOC);

// query per ottenere i veterinari
$veterinari = $db->query("SELECT ID_VETERINARIO, NOME FROM VETERINARIO ORDER BY NOME")->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- container principale per la gestione dei controlli veterinari -->
<div class="controllo-container">
    <h2>Gestione Controlli Veterinari</h2>
    
    <!-- barra superiore con bottone aggiungi e totale costi -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <button id="aggiungiControlloBtn" class="btn-aggiungi">+ Aggiungi Controllo</button>
        <div style="background: lightgray; padding: 10px; border-radius: 4px;">
            <strong>Totale costo controlli veterinari:</strong>
            <?php
                // calcolo il totale dei costi dei controlli veterinari
                $totale_costi = array_sum(array_column($controlli, 'costo'));
                echo number_format($totale_costi, 2) . ' €';
            ?>
        </div>
    </div>
    
    <!-- tabella dei controlli veterinari esistenti -->
    <div class="table-responsive">
        <table class="controllo-table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Animale</th>
                    <th>Specie</th>
                    <th>Descrizione</th>
                    <th>Esito</th>
                    <th>Veterinario</th>
                    <th>Costo (€)</th>
                    <th>Note</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($controlli)): ?>
                    <tr>
                        <td colspan="9">Nessun controllo veterinario registrato</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($controlli as $controllo): ?>
                        <tr>
                            <td><?= htmlspecialchars($controllo['DATA']) ?></td>
                            <td>
                                <?= htmlspecialchars($controllo['nome_animale'] ?? 'N/A') ?>
                                <?= $controllo['TAG_ANIMALE'] ? '(' . htmlspecialchars($controllo['TAG_ANIMALE']) . ')' : '' ?>
                            </td>
                            <td><?= htmlspecialchars($controllo['SPECIE'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($controllo['DESCRIZIONE']) ?></td>
                            <td><?= htmlspecialchars($controllo['ESITO']) ?></td>
                            <td><?= htmlspecialchars($controllo['nome_veterinario'] ?? 'N/A') ?></td>
                            <td><?= $controllo['costo'] !== null ? number_format($controllo['costo'], 2) . ' €' : 'N/D' ?></td>
                            <td><?= htmlspecialchars($controllo['NOTE']) ?></td>
                            <td>
                                <!-- bottone per eliminare il controllo -->
                                <button class="btn-elimina" style="width: 100%;" onclick="eliminaControllo(<?= $controllo['ID_CONTROLLO'] ?>)">Elimina</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- modale per aggiungere controllo veterinario -->
<div id="controlloModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2 id="modalTitolo">Aggiungi Nuovo Controllo</h2>
        <!-- form per inserire o modificare un controllo veterinario -->
        <form id="controlloForm">
            <input type="hidden" id="controlloId" name="id_controllo" value="">
            
            <div class="form-group">
                <label for="data">Data:</label>
                <input type="date" id="data" name="data" required>
            </div>
            <div class="form-group">
                <label for="animale">Animale:</label>
                <select id="animale" name="tag_animale" required>
                    <option value="">Seleziona animale...</option>
                    <?php foreach ($animali as $animale): ?>
                        <option value="<?= htmlspecialchars($animale['TAG']) ?>">
                            <?= htmlspecialchars($animale['NOME']) ?> (<?= htmlspecialchars($animale['TAG']) ?> - <?= htmlspecialchars($animale['SPECIE']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="descrizione">Descrizione:</label>
                <input type="text" id="descrizione" name="descrizione" maxlength="255" required>
            </div>
            <div class="form-group">
                <label for="esito">Esito:</label>
                <select id="esito" name="esito" required>
                    <option value="">Seleziona esito...</option>
                    <option value="Ottimo">Ottimo</option>
                    <option value="Buono">Buono</option>
                    <option value="Pericoloso">Pericoloso</option>
                </select>
            </div>
            <div class="form-group">
                <label for="veterinario">Veterinario:</label>
                <select id="veterinario" name="id_veterinario" required>
                    <option value="">Seleziona veterinario...</option>
                    <?php foreach ($veterinari as $veterinario): ?>
                        <option value="<?= htmlspecialchars($veterinario['ID_VETERINARIO']) ?>">
                            <?= htmlspecialchars($veterinario['NOME']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="costo">Costo (€):</label>
                <input type="number" id="costo" name="costo" step="0.01" min="0">
            </div>
            <div class="form-group">
                <label for="note">Note:</label>
                <textarea id="note" name="note" rows="2"></textarea>
            </div>
            <div class="form-actions">
                <button type="button" class="btn-annulla">Annulla</button>
                <button type="submit" class="btn-salva">Salva</button>
            </div>
        </form>
    </div>
</div>

<style>
/* stile per il container principale */
.controllo-container{padding: 20px; max-width: 1300px; margin: 0 auto;}

/* bottone aggiungi */
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

/* tabella dei controlli */
.controllo-table{width: 100%; border-collapse: collapse; margin-bottom: 30px;}

.controllo-table th, .controllo-table td{border: 1px solid lightgrey; padding: 12px; text-align: left;}

.controllo-table th{background-color: white;}

.controllo-table tr:nth-child(even){background-color: white;}

.controllo-table tr:hover{background-color: lightgrey;}

/* bottone elimina */
.btn-elimina{
    background-color: red;
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 4px;
    cursor: pointer;
}

.btn-elimina:hover{background-color: darkred;}

/* modale per inserimento */
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
.form-group select,
.form-group textarea{
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

<script>
// seleziono elementi del DOM per la gestione della modale
const modal = document.getElementById('controlloModal');
const btnAggiungi = document.getElementById('aggiungiControlloBtn');
const spanClose = document.getElementsByClassName('close')[0];
const btnAnnulla = document.querySelector('.btn-annulla');

// apertura modale per aggiunta nuovo controllo
btnAggiungi.onclick = function(){
    document.getElementById('modalTitolo').textContent = 'Aggiungi Nuovo Controllo';
    document.getElementById('controlloForm').reset();
    document.getElementById('controlloId').value = '';
    modal.style.display = 'block';
}

// chiusura modale cliccando sulla X
spanClose.onclick = function(){modal.style.display = 'none';}

// chiusura modale cliccando su Annulla
btnAnnulla.onclick = function(){modal.style.display = 'none';}

// chiusura modale cliccando fuori dalla modale
window.onclick = function(event){
    if(event.target == modal){
        modal.style.display = 'none';
    }
}

// gestione invio del form per aggiungere/modificare controllo
// invia i dati via fetch a aggiungi_controllo.php e aggiorna la pagina se successo
document.getElementById('controlloForm').addEventListener('submit', function(e){
    e.preventDefault();
    const formData = new FormData(this);
    const id = document.getElementById('controlloId').value;
    const url = 'aggiungi_controllo.php';
    fetch(url,{method: 'POST', body: new URLSearchParams(formData)})
    .then(response => response.json())
    .then(data =>{
        if(data.success){
            modal.style.display = 'none';
            location.reload();
        }
    });
});

// funzione per eliminare un controllo veterinario
function eliminaControllo(id){
    fetch('elimina_controllo.php',{method: 'POST',headers: {'Content-Type': 'application/x-www-form-urlencoded',},body: 'id=' + encodeURIComponent(id)})
    .then(response => response.json())
    .then(data =>{
        if(data.success){
            location.reload();
        }
    });
}
</script> 