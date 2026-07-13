<?php
require_once 'connessione.php';

// recupera i dati di alimentazione dal database, includendo il costo dalla tabella Spese
$query = "SELECT al.*, a.NOME AS nome_animale, a.SPECIE, ta.NOME AS nome_alimento, ta.TIPO AS tipo_alimento, s.IMPORTO AS costo
    FROM ALIMENTO al
    LEFT JOIN ANIMALE a ON al.TAG_ANIMALE = a.TAG
    LEFT JOIN TIPO_ALIMENTO ta ON al.ID_ALIMENTO = ta.ID_ALIMENTO
    LEFT JOIN SPESE s ON s.ID_CONSUMO = al.ID_CONSUMO AND s.CATEGORIA = 'ALIMENTAZIONE'
    ORDER BY al.DATA DESC";
$stmt = $db->query($query);
$alimenti = $stmt->fetchAll(PDO::FETCH_ASSOC);

// query per ottenere i tipi di alimento disponibili
$tipi_alimento = $db->query("SELECT ID_ALIMENTO, NOME, TIPO FROM TIPO_ALIMENTO ORDER BY NOME")->fetchAll(PDO::FETCH_ASSOC);

// query per ottenere gli animali
$animali = $db->query("SELECT TAG, NOME, SPECIE FROM ANIMALE ORDER BY NOME")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="alimentazione-container">
    <h2>Gestione Alimentazione</h2>
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <button id="aggiungiAlimentoBtn" class="btn-aggiungi">+ Aggiungi Alimento</button>
        <div style="background: lightgray; padding: 10px; border-radius: 4px;">
            <strong>Totale costo alimentazione:</strong>
            <?php
                $totale_costi = array_sum(array_column($alimenti, 'costo'));
                echo number_format($totale_costi, 2) . ' €';
            ?>
        </div>
    </div>
    
    <!-- tabella degli alimenti esistenti -->
    <div class="table-responsive">
        <table class="alimentazione-table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Animale</th>
                    <th>Specie</th>
                    <th>Alimento</th>
                    <th>Tipo</th>
                    <th>Quantità (kg)</th>
                    <th>Costo (€)</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($alimenti)): ?>      <!-- se la tabella è vuota -->
                    <tr>
                        <td colspan="8">Nessun dato di alimentazione registrato</td>
                    </tr>
                <?php else: ?>       <!-- altrimenti cicla tutti gli alimenti -->        
                    <?php foreach($alimenti as $alimento): ?>
                        <tr>
                            <td><?= htmlspecialchars($alimento['DATA']) ?></td>
                            <td>
                                <?= htmlspecialchars($alimento['nome_animale'] ?? 'N/A') ?>
                                <?= $alimento['TAG_ANIMALE'] ? '(' . htmlspecialchars($alimento['TAG_ANIMALE']) . ')' : '' ?>
                            </td>
                            <td><?= htmlspecialchars($alimento['SPECIE'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($alimento['nome_alimento']) ?></td>
                            <td><?= htmlspecialchars($alimento['tipo_alimento']) ?></td>
                            <td><?= htmlspecialchars($alimento['QUANTITA_KG']) ?> kg</td>
                            <td><?= $alimento['costo'] !== null ? number_format($alimento['costo'], 2) . ' €' : 'N/D' ?></td>
                            <td>
                                <button class="btn-elimina" style="width: 100%;" onclick="eliminaAlimento(<?= $alimento['ID_CONSUMO'] ?>)">Elimina</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- modale per aggiungere alimenti -->
<div id="alimentazioneModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2 id="modalTitolo">Aggiungi Nuovo Alimento</h2>
        <form id="alimentazioneForm">
            <input type="hidden" id="consumoId" name="id_consumo" value="">
            
            <div class="form-group">
                <label for="data">Data:</label>
                <input type="date" id="data" name="data" required>
            </div>
            <div class="form-group">
                <label for="animale">Animale:</label>
                <select id="animale" name="tag_animale" required>
                    <option value="">Seleziona animale...</option>
                    <?php foreach($animali as $animale): ?>
                        <option value="<?= htmlspecialchars($animale['TAG']) ?>">
                            <?= htmlspecialchars($animale['NOME']) ?> (<?= htmlspecialchars($animale['TAG']) ?> - <?= htmlspecialchars($animale['SPECIE']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="alimento">Alimento:</label>
                <select id="alimento" name="id_alimento" required>
                    <option value="">Seleziona alimento...</option>
                    <?php foreach($tipi_alimento as $alimento): ?>
                        <option value="<?= htmlspecialchars($alimento['ID_ALIMENTO']) ?>">
                            <?= htmlspecialchars($alimento['NOME']) ?> (<?= htmlspecialchars($alimento['TIPO']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="quantita">Quantità (kg):</label>
                <input type="number" id="quantita" name="quantita_kg" step="0.01" min="0" required>
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

<!-- stili CSS e JS -->
<style>
.alimentazione-container{padding: 20px; max-width: 1200px; margin: 0 auto;}

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

.alimentazione-table{width: 100%; border-collapse: collapse; margin-bottom: 30px;}

.alimentazione-table th, .alimentazione-table td{border: 1px solid lightgrey; padding: 12px; text-align: left;}

.alimentazione-table th{background-color: white;}

.alimentazione-table tr:nth-child(even){background-color: white;}

.alimentazione-table tr:hover{background-color: lightgrey;}

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
    background-color: #fefefe;
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
.form-group select{
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
const modal = document.getElementById('alimentazioneModal');
const btnAggiungi = document.getElementById('aggiungiAlimentoBtn');
const spanClose = document.getElementsByClassName('close')[0];
const btnAnnulla = document.querySelector('.btn-annulla');

btnAggiungi.onclick = function(){      // mostra modale quando si clicca su + aggiungi alimento
    document.getElementById('modalTitolo').textContent = 'Aggiungi Nuovo Alimento';
    document.getElementById('alimentazioneForm').reset();
    document.getElementById('consumoId').value = '';
    modal.style.display = 'block';
}

spanClose.onclick = function(){modal.style.display = 'none';}      // nascondi modale quando si clicca su x

btnAnnulla.onclick = function(){modal.style.display = 'none';}      // nascondi modale quando si clicca su annulla

window.onclick = function(e){                              // nascondi modale quando si clicca fuori
    if(e.target == modal){
        modal.style.display = 'none';
    }
}

document.getElementById('alimentazioneForm').addEventListener('submit', function(e){
    e.preventDefault();
    const formData = new FormData(this);                // crea un oggetto FormData con i dati del form
    const id = document.getElementById('consumoId').value;          // recupera l'id del consumo
    const url = 'aggiungi_alimento.php';      // url del file php che gestisce l'inserimento

    fetch(url,{method: 'POST', body: new URLSearchParams(formData)})      // invia i dati al file php
    .then(response => response.json())      // converte la risposta in json
    .then(data =>{      // se la risposta è successo
        if(data.success){
            modal.style.display = 'none';      // nascondi modale
            location.reload();      // ricarica la pagina
        }
    });
});

function eliminaAlimento(id){      // funzione per eliminare un alimento
    fetch('elimina_alimento.php',{method: 'POST',headers:{'Content-Type': 'application/x-www-form-urlencoded',},body: 'id=' + encodeURIComponent(id)})
    .then(response => response.json())
    .then(data =>{
        if(data.success){
            location.reload();
        }
    });
}
</script>