<?php
require_once 'connessione.php';

// Recupera i dati delle vaccinazioni dal database, includendo nome animale, specie e nome veterinario
$query = "SELECT v.*, a.NOME AS nome_animale, a.SPECIE, vet.NOME AS nome_veterinario,
    (
        SELECT s.IMPORTO FROM SPESE s
        WHERE s.DATA = v.DATA
          AND s.CATEGORIA = 'VETERINARIA'
          AND s.DESCRIZIONE LIKE CONCAT('%', v.TAG_ANIMALE, '%')
          AND s.DESCRIZIONE LIKE CONCAT('%', v.ID_VETERINARIO, '%')
        LIMIT 1
    ) AS costo
    FROM VACCINAZIONE v
    LEFT JOIN ANIMALE a ON v.TAG_ANIMALE = a.TAG
    LEFT JOIN VETERINARIO vet ON v.ID_VETERINARIO = vet.ID_VETERINARIO
    ORDER BY v.DATA DESC";
$stmt = $db->query($query);
$vaccinazioni = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Query per ottenere gli animali vivi (senza data decesso)
$animali = $db->query("SELECT TAG, NOME, SPECIE FROM ANIMALE ORDER BY NOME")->fetchAll(PDO::FETCH_ASSOC);

// Query per ottenere i veterinari
$veterinari = $db->query("SELECT ID_VETERINARIO, NOME FROM VETERINARIO ORDER BY NOME")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="produzione-container">
    <h2>Gestione Vaccinazioni</h2>
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <button id="aggiungiVaccinazioneBtn" class="btn-aggiungi">+ Aggiungi Vaccinazione</button>
        <div style="background: lightgray; padding: 10px; border-radius: 4px;">
            <strong>Totale costo vaccinazioni:</strong>
            <?php
                $totale_costi = array_sum(array_column($vaccinazioni, 'costo'));
                echo number_format($totale_costi, 2) . ' €';
            ?>
        </div>
    </div>
    
    <!-- Tabella delle vaccinazioni esistenti -->
    <div class="table-responsive">
        <table class="produzione-table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Animale</th>
                    <th>Specie</th>
                    <th>Descrizione</th>
                    <th>Nome Vaccino</th>
                    <th>Dosaggio</th>
                    <th>Veterinario</th>
                    <th>Costo (€)</th>
                    <th>Prossimo Vaccino</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($vaccinazioni)): ?>
                    <tr>
                        <td colspan="10">Nessuna vaccinazione registrata</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($vaccinazioni as $vaccinazione): ?>
                        <tr>
                            <td><?= htmlspecialchars($vaccinazione['DATA']) ?></td>
                            <td>
                                <?= htmlspecialchars($vaccinazione['nome_animale'] ?? 'N/A') ?>
                                <?= $vaccinazione['TAG_ANIMALE'] ? '(' . htmlspecialchars($vaccinazione['TAG_ANIMALE']) . ')' : '' ?>
                            </td>
                            <td><?= htmlspecialchars($vaccinazione['SPECIE'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($vaccinazione['NOTE']) ?></td>
                            <td><?= htmlspecialchars($vaccinazione['NOME_VACCINO']) ?></td>
                            <td><?= htmlspecialchars($vaccinazione['DOSAGGIO']) !== '' ? htmlspecialchars($vaccinazione['DOSAGGIO']) . ' ml' : '' ?></td>
                            <td><?= htmlspecialchars($vaccinazione['nome_veterinario'] ?? 'N/A') ?></td>
                            <td><?= $vaccinazione['costo'] !== null ? number_format($vaccinazione['costo'], 2) . ' €' : 'N/D' ?></td>
                            <td>
                                <?php if (!empty($vaccinazione['DATA_SCADENZA'])): ?>
                                    <?= date('d/m/Y', strtotime($vaccinazione['DATA_SCADENZA'])) ?>
                                    <?php if(strtotime($vaccinazione['DATA_SCADENZA']) < time()): ?>
                                        <span style="color: red;">(Scaduto!)</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    Nessuna
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn-elimina" style="width: 100%;" onclick="eliminaVaccinazione(<?= $vaccinazione['ID_VACCINAZIONE'] ?>)">Elimina</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modale per aggiungere vaccinazione -->
<div id="vaccinazioneModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2 id="modalTitolo">Aggiungi Nuova Vaccinazione</h2>
        <form id="vaccinazioneForm">
            <input type="hidden" id="vaccinazioneId" name="id_vaccinazione" value="">
            <div class="form-group">
                <label for="data">Data:</label>
                <input type="date" id="data" name="data" required>
            </div>
            <div class="form-group">
                <label for="data_scadenza">Data Scadenza Vaccino:</label>
                <input type="date" id="data_scadenza" name="data_scadenza">
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
                <input type="text" id="descrizione" name="note" maxlength="255">
            </div>
            <div class="form-group">
                <label for="nome_vaccino">Nome Vaccino:</label>
                <input type="text" id="nome_vaccino" name="nome_vaccino" maxlength="30" required>
            </div>
            <div class="form-group">
                <label for="dosaggio">Dosaggio:</label>
                <input type="number" id="dosaggio" name="dosaggio" min="0" step="0.01" required>
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
            <div class="form-actions">
                <button type="button" class="btn-annulla">Annulla</button>
                <button type="submit" class="btn-salva">Salva</button>
            </div>
        </form>
    </div>
</div>

<style>
<?php // Riutilizza lo stile di controllo_veterinario.php ?>
.produzione-container{padding: 20px; max-width: 1200px; margin: 0 auto;}

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

.produzione-table{width: 100%; border-collapse: collapse; margin-bottom: 30px;}

.produzione-table th, .produzione-table td{border: 1px solid lightgrey; padding: 12px; text-align: left;}

.produzione-table th{background-color: white;}

.produzione-table tr:nth-child(even){background-color: white;}

.produzione-table tr:hover{background-color: lightgrey;}

.btn-elimina{
    background-color: red;
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 4px;
    cursor: pointer;
}

.btn-elimina:hover{background-color: darkred;}

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
const modal = document.getElementById('vaccinazioneModal');
const btnAggiungi = document.getElementById('aggiungiVaccinazioneBtn');
const spanClose = document.getElementsByClassName('close')[0];
const btnAnnulla = document.querySelector('.btn-annulla');

btnAggiungi.onclick = function(){
    document.getElementById('modalTitolo').textContent = 'Aggiungi Nuova Vaccinazione';
    document.getElementById('vaccinazioneForm').reset();
    document.getElementById('vaccinazioneId').value = '';
    modal.style.display = 'block';
}

spanClose.onclick = function(){modal.style.display = 'none';}

btnAnnulla.onclick = function(){modal.style.display = 'none';}

window.onclick = function(event){
    if(event.target == modal){
        modal.style.display = 'none';
    }
}

document.getElementById('vaccinazioneForm').addEventListener('submit', function(e){
    e.preventDefault();
    const formData = new FormData(this);
    const id = document.getElementById('vaccinazioneId').value;
    const url = 'aggiungi_vaccinazione.php';
    fetch(url,{method: 'POST', body: new URLSearchParams(formData)})
    .then(response => response.json())
    .then(data =>{
        if(data.success){
            modal.style.display = 'none';
            location.reload();
        }
    });
});

function eliminaVaccinazione(id){
    fetch('elimina_vaccinazione.php',{method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded',},body: 'id=' + encodeURIComponent(id)})
    .then(response => response.json())
    .then(data =>{
        if(data.success){
            location.reload();
        }
    });
}
</script> 