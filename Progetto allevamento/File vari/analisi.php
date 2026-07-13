<?php
require_once 'connessione.php';

$ricavi_totali = 0;
$spese_totali = 0;

// riepilogo per animale
$animali = $db->query("SELECT TAG, NOME, SPECIE FROM ANIMALE ORDER BY NOME")->fetchAll(PDO::FETCH_ASSOC);
$riepilogo_animali = [];
foreach($animali as $animale){
    $tag = $animale['TAG'];
    // ricavi per animale
    $ricavi = $db->prepare("SELECT SUM(vp.IMPORTO) FROM PRODOTTO p JOIN VENDITA_PRODOTTI vp ON vp.DESCRIZIONE LIKE CONCAT('%', p.LOTTO, '%') AND vp.DATA = p.DATA WHERE p.TAG_ANIMALE = ?");
    $ricavi->execute([$tag]);
    $ricavi_animale = $ricavi->fetchColumn() ?: 0;
    $ricavi_totali += $ricavi_animale;

    // spese alimentazione
    $spese_alim = $db->prepare("SELECT SUM(s.IMPORTO) FROM ALIMENTO al JOIN SPESE s ON s.ID_CONSUMO = al.ID_CONSUMO AND s.CATEGORIA = 'ALIMENTAZIONE' WHERE al.TAG_ANIMALE = ?");
    $spese_alim->execute([$tag]);
    $spese_alimentazione = $spese_alim->fetchColumn() ?: 0;

    // spese veterinarie (controlli + vaccinazioni)
    $spese_vet = $db->prepare("SELECT SUM(s.IMPORTO) FROM SPESE s WHERE s.CATEGORIA = 'VETERINARIA' AND s.DESCRIZIONE LIKE CONCAT('%', ?, '%')");
    $spese_vet->execute([$tag]);
    $spese_veterinarie = $spese_vet->fetchColumn() ?: 0;

    $spese_tot = $spese_alimentazione + $spese_veterinarie;
    $spese_totali += $spese_tot;

    $profitto = $ricavi_animale - $spese_tot;
    $roi_animale = $spese_tot > 0 ? ($profitto / $spese_tot) * 100 : 0;         // calcolo roi per singolo animale: 0 se non ci sono spese
    $riepilogo_animali[] =[
        'TAG' => $tag,
        'NOME' => $animale['NOME'],
        'SPECIE' => $animale['SPECIE'],
        'RICAVI' => $ricavi_animale,
        'SPESE' => $spese_tot,
        'PROFITTO' => $profitto,
        'ROI' => $roi_animale
    ];
}

// profitto netto
$profitto_netto = $ricavi_totali - $spese_totali;

// roi
$roi = $spese_totali > 0 ? (($profitto_netto) / $spese_totali) * 100 : 0;           // calcolo roi totale

// recupero tutte le certificazioni e gli animali certificati
$certificazioni = $db->query("SELECT c.*, a.NOME AS nome_animale, a.SPECIE AS specie_animale FROM CERTIFICAZIONE c JOIN ANIMALE a ON c.TAG_ANIMALE = a.TAG ORDER BY c.DATA_RILASCIO DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="analisi-container">
    <h2>Analisi Ritorno di investimento</h2>
    <div style="margin-bottom: 30px;">
        <h3>Riepilogo Totale</h3>
        <div class="table-responsive">
            <table class="analisi-table">
                <thead>
                    <tr>
                        <th>Ricavi Totali</th>
                        <th>Spese Totali</th>
                        <th>Profitto Netto</th>
                        <th>ROI</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?= number_format($ricavi_totali, 2) ?> €</td>
                        <td><?= number_format($spese_totali, 2) ?> €</td>
                        <td><?= number_format($profitto_netto, 2) ?> €</td>
                        <td><?= number_format($roi, 2) ?> %</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div>
        <h3>Riepilogo per Animale</h3>
        <div class="table-responsive">
            <table class="analisi-table">
                <thead>
                    <tr>
                        <th>Tag</th>
                        <th>Nome</th>
                        <th>Specie</th>
                        <th>Ricavi</th>
                        <th>Spese</th>
                        <th>Profitto Netto</th>
                        <th>ROI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($riepilogo_animali as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['TAG']) ?></td>
                            <td><?= htmlspecialchars($row['NOME']) ?></td>
                            <td><?= htmlspecialchars($row['SPECIE']) ?></td>
                            <td><?= number_format($row['RICAVI'], 2) ?> €</td>
                            <td><?= number_format($row['SPESE'], 2) ?> €</td>
                            <td><?= number_format($row['PROFITTO'], 2) ?> €</td>
                            <td><?= number_format($row['ROI'], 2) ?> %</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div style="margin-top: 40px;">
    <h3>Gestione Certificazioni Biologiche</h3>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <button id="aggiungiCertBtn" class="btn-aggiungi">+ Aggiungi Certificazione</button>
        <div></div>
    </div>
    <div class="table-responsive">
        <table class="analisi-table">
            <thead>
                <tr>
                    <th>Data Rilascio</th>
                    <th>Data Scadenza</th>
                    <th>Ente Certificatore</th>
                    <th>Descrizione</th>
                    <th>Animale Certificato</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($certificazioni)): ?>
                    <tr>
                        <td colspan="6">Nessuna certificazione registrata</td>
                    </tr>
                <?php else: ?>
                    <?php foreach($certificazioni as $cert): ?>
                        <tr>
                            <td><?= htmlspecialchars($cert['DATA_RILASCIO']) ?></td>
                            <td><?= htmlspecialchars($cert['DATA_SCADENZA']) ?></td>
                            <td><?= htmlspecialchars($cert['ENTE_CERTIFICATORE']) ?></td>
                            <td><?= htmlspecialchars($cert['DESCRIZIONE']) ?></td>
                            <td>
                                <?= htmlspecialchars($cert['nome_animale']) ?> (<?= htmlspecialchars($cert['TAG_ANIMALE']) ?> - <?= htmlspecialchars($cert['specie_animale']) ?>)
                            </td>
                            <td>
                                <button class="btn-elimina" onclick="eliminaCertificazione(<?= (int)$cert['ID_CERTIFICAZIONE'] ?>)">Elimina</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="certModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2 id="modalTitoloCert">Aggiungi Certificazione Biologica</h2>
        <form id="certForm">
            <div class="form-group">
                <label for="data_rilascio">Data Rilascio:</label>
                <input type="date" id="data_rilascio" name="data_rilascio" required>
            </div>
            <div class="form-group">
                <label for="data_scadenza">Data Scadenza:</label>
                <input type="date" id="data_scadenza" name="data_scadenza">
            </div>
            <div class="form-group">
                <label for="ente_certificatore">Ente Certificatore:</label>
                <input type="text" id="ente_certificatore" name="ente_certificatore" required>
            </div>
            <div class="form-group">
                <label for="descrizione">Descrizione:</label>
                <input type="text" id="descrizione" name="descrizione">
            </div>
            <div class="form-group">
                <label for="tag_animale">Animale Certificato:</label>
                <select id="tag_animale" name="tag_animale" required>
                    <option value="">Seleziona animale...</option>
                    <?php foreach ($animali as $an): ?>
                        <option value="<?= htmlspecialchars($an['TAG']) ?>">
                            <?= htmlspecialchars($an['NOME']) ?> (<?= htmlspecialchars($an['TAG']) ?> - <?= htmlspecialchars($an['SPECIE']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-actions">
                <button type="button" class="btn-annulla">Annulla</button>
                <button type="submit" class="btn-salva">Salva</button>
            </div>
            <div id="certMsg" style="color:red; margin-top:10px;"></div>
        </form>
    </div>
</div>

<script>
const certModal = document.getElementById('certModal');
const btnAggiungiCert = document.getElementById('aggiungiCertBtn');
const spanCloseCert = certModal.querySelector('.close');
const btnAnnullaCert = certModal.querySelector('.btn-annulla');

btnAggiungiCert.onclick = function(){
    document.getElementById('modalTitoloCert').textContent = 'Aggiungi Certificazione Biologica';
    document.getElementById('certForm').reset();
    document.getElementById('certMsg').textContent = '';
    certModal.style.display = 'block';
}
spanCloseCert.onclick = btnAnnullaCert.onclick = function(){
    certModal.style.display = 'none';
}
window.onclick = function(e){
    if(e.target == certModal){
    certModal.style.display = 'none';
    }
}
document.getElementById('certForm').addEventListener('submit', function(e){
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    fetch('aggiungi_certificazione.php',{method: 'POST', body: formData})
    .then(r => r.json())
    .then(data =>{
        if(data.success){
            certModal.style.display = 'none';
            location.reload();
        }
        else{
            document.getElementById('certMsg').textContent = data.message || 'Errore.';
        }
    });
});

function eliminaCertificazione(id){
    fetch('elimina_certificazione.php',{method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: 'id=' + encodeURIComponent(id)})
    .then(r => r.json())
    .then(data =>{
        if(data.success){
            location.reload();
        }
        else{
            alert(data.message || 'Errore durante l\'eliminazione.');
        }
    });
}
</script>

<style>
.analisi-container{padding: 20px; max-width: 1200px; margin: 0 auto;}
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
.analisi-table{width: 100%; border-collapse: collapse; margin-bottom: 30px;}

.analisi-table th, .analisi-table td{border: 1px solid lightgrey; padding: 12px; text-align: left;}

.analisi-table th{background-color: white;}

.analisi-table tr:nth-child(even){background-color: white;}

.analisi-table tr:hover{background-color: lightgrey;}

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