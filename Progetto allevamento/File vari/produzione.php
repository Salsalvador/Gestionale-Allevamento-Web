<?php
require_once 'connessione.php';

// Recupera i dati di produzione dal database con informazioni sui ricavi
$query = "SELECT p.*, a.NOME AS nome_animale, a.SPECIE, a.RAZZA,
    (SELECT vp.IMPORTO FROM VENDITA_PRODOTTI vp
        WHERE vp.DESCRIZIONE LIKE CONCAT('%', p.LOTTO, '%') AND vp.DATA = p.DATA LIMIT 1) AS ricavo
    FROM PRODOTTO p
    LEFT JOIN ANIMALE a ON p.TAG_ANIMALE = a.TAG
    ORDER BY p.DATA DESC";
$stmt = $db->query($query);
$prodotti = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Query per ottenere i tipi di prodotto disponibili
$tipi_prodotto = $db->query("SELECT TIPO FROM TIPO_PRODOTTO ORDER BY TIPO")->fetchAll(PDO::FETCH_COLUMN);

// Query per ottenere gli animali vivi (senza data decesso)
$animali = $db->query("SELECT TAG, NOME, SPECIE FROM ANIMALE ORDER BY NOME")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="produzione-container">
    <h2>Gestione Produzione</h2>
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <button id="aggiungiProdottoBtn" class="btn-aggiungi">+ Aggiungi Prodotto</button>
        <div style="background: lightgray; padding: 10px; border-radius: 4px;">
            <strong>Totale ricavi produzione:</strong>
            <?php
                $totale_ricavi = array_sum(array_column($prodotti, 'ricavo'));
                echo number_format($totale_ricavi, 2) . ' €';
            ?>
        </div>
    </div>
    
    <!-- Tabella dei prodotti esistenti -->
    <div class="table-responsive">
        <table class="produzione-table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Animale</th>
                    <th>Specie</th>
                    <th>Tipo</th>
                    <th>Quantità</th>
                    <th>Lotto</th>
                    <th>Ricavo</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($prodotti)): ?>
                    <tr>
                        <td colspan="8">Nessun dato di produzione registrato</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($prodotti as $prodotto): ?>
                        <tr>
                            <td><?= htmlspecialchars($prodotto['DATA']) ?></td>
                            <td>
                                <?= htmlspecialchars($prodotto['nome_animale'] ?? 'N/A') ?>
                                <?= $prodotto['TAG_ANIMALE'] ? '(' . htmlspecialchars($prodotto['TAG_ANIMALE']) . ')' : '' ?>
                            </td>
                            <td><?= htmlspecialchars($prodotto['SPECIE'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($prodotto['TIPO']) ?></td>
                            <td><?= htmlspecialchars($prodotto['QUANTITA']) ?> kg</td>
                            <td><?= htmlspecialchars($prodotto['LOTTO']) ?></td>
                            <td>
                                <?= $prodotto['ricavo'] !== null ? number_format($prodotto['ricavo'], 2) . ' €' : 'N/D' ?>
                            </td>
                            <td>
                                <button class="btn-elimina" style="width: 100%;" onclick="eliminaProdotto(<?= $prodotto['ID_PRODUZIONE'] ?>)">Elimina</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modale per aggiungere/modificare produzione -->
<div id="produzioneModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2 id="modalTitolo">Aggiungi Nuovo Prodotto</h2>
        <form id="produzioneForm">
            <input type="hidden" id="produzioneId" name="id_produzione" value="">
            
            <div class="form-group">
                <label for="data">Data:</label>
                <input type="date" id="data" name="data" required>
            </div>
            
            <div class="form-group">
                <label for="tipo">Tipo:</label>
                <select id="tipo" name="tipo" required>
                    <option value="">Seleziona tipo...</option>
                    <?php foreach ($tipi_prodotto as $tipo): ?>
                        <option value="<?= htmlspecialchars($tipo) ?>"><?= htmlspecialchars($tipo) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="animale">Animale (opzionale):</label>
                <select id="animale" name="tag_animale">
                    <option value="">Nessun animale specifico</option>
                    <?php foreach ($animali as $animale): ?>
                        <option value="<?= htmlspecialchars($animale['TAG']) ?>">
                            <?= htmlspecialchars($animale['NOME']) ?>
                            (<?= htmlspecialchars($animale['TAG']) ?> - <?= htmlspecialchars($animale['SPECIE']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="quantita">Quantità (kg):</label>
                <input type="number" id="quantita" name="quantita" step="0.01" min="0" required>
            </div>

            <div class="form-group">
                <label for="importo">Ricavo (€):</label>
                <input type="number" id="importo" name="importo" step="0.01" min="0" required>
            </div>
            
            <div class="form-group">
                <label for="lotto">Lotto/Identificativo:</label>
                <input type="text" id="lotto" name="lotto">
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn-annulla">Annulla</button>
                <button type="submit" class="btn-salva">Salva</button>
            </div>
        </form>
    </div>
</div>

<!-- Stili CSS -->
<style>
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

.grafico-container{
    margin-top: 40px;
    padding: 20px;
    border: 1px solid lightgrey;
    border-radius: 5px;
    background-color: white;
}

/* Stili per il modale */
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

<!-- Script JavaScript -->
<script>
// Gestione del modale
const modal = document.getElementById('produzioneModal');
const btnAggiungi = document.getElementById('aggiungiProdottoBtn');
const spanClose = document.getElementsByClassName('close')[0];
const btnAnnulla = document.querySelector('.btn-annulla');

btnAggiungi.onclick = function(){
    document.getElementById('modalTitolo').textContent = 'Aggiungi Nuovo Prodotto';
    document.getElementById('produzioneForm').reset();
    document.getElementById('produzioneId').value = '';
    modal.style.display = 'block';
}

spanClose.onclick = function(){modal.style.display = 'none';}

btnAnnulla.onclick = function(){modal.style.display = 'none';}

window.onclick = function(event){
    if(event.target == modal){
        modal.style.display = 'none';
    }
}

document.getElementById('produzioneForm').addEventListener('submit', function(e){
    e.preventDefault();

    const formData = new FormData(this);
    const id = document.getElementById('produzioneId').value;
    const url = 'aggiungi_prodotto.php';

    fetch(url,{method: 'POST', body: new URLSearchParams(formData)})
    .then(response => response.json())
    .then(data =>{
        if(data.success){
            modal.style.display = 'none';
            location.reload();
        }
    });
});

// Funzione per eliminare un prodotto
function eliminaProdotto(id){
    console.log('Elimina prodotto cliccato, id:', id);
    fetch('elimina_prodotto.php',{method: 'POST',headers:{'Content-Type': 'application/x-www-form-urlencoded',},body: 'id=' + encodeURIComponent(id)})
    .then(response => response.json())
    .then(data =>{
        if(data.success){
            location.reload();
        }
    });
}
</script>