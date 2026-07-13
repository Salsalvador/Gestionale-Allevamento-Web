<?php
include 'controlla_autorizzazione.php';
require_once 'connessione.php';

// recupera tutti gli animali con informazioni aggiuntive
$query = $db->query("
    SELECT a.*,
    (SELECT COUNT(*) FROM PRODOTTO WHERE TAG_ANIMALE = a.TAG) AS totale_produzioni,
    (SELECT MAX(DATA) FROM CONTROLLO_VETERINARIO WHERE TAG_ANIMALE = a.TAG) AS ultimo_controllo,
    (SELECT MAX(DATA_SCADENZA) FROM VACCINAZIONE WHERE TAG_ANIMALE = a.TAG) AS prossima_scadenza_vaccino
    FROM ANIMALE a ORDER BY a.DATA_NASCITA DESC");
$animali = $query->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="animali-container">
    <h2>Gestione Animali</h2>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <button id="aggiungiAnimaleBtn" class="btn-aggiungi">+ Aggiungi Animale</button>
    </div>
    <div class="table-responsive">
        <table class="animali-table">
            <thead>
                <tr>
                    <th>Tag</th>
                    <th>Nome</th>
                    <th>Specie</th>
                    <th>Razza</th>
                    <th>Nascita</th>
                    <th>Sesso</th>
                    <th>Numero Produzioni</th>
                    <th>Ultimo Controllo</th>
                    <th>Padre</th>
                    <th>Madre</th>
                    <th>Prossimo Vaccino</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($animali as $animale): ?>
                <tr>
                    <td><?= htmlspecialchars($animale['TAG']) ?></td>
                    <td><?= htmlspecialchars($animale['NOME']) ?></td>
                    <td><?= htmlspecialchars($animale['SPECIE']) ?></td>
                    <td><?= htmlspecialchars($animale['RAZZA']) ?></td>
                    <td><?= date('d/m/Y', strtotime($animale['DATA_NASCITA'])) ?></td>
                    <td><?= $animale['SESSO'] ?></td>
                    <td><?= $animale['totale_produzioni'] ?></td>
                    <td><?= $animale['ultimo_controllo'] ? date('d/m/Y', strtotime($animale['ultimo_controllo'])) : 'Nessuno' ?></td>
                    <td><?= $animale['PADRE_TAG'] ? htmlspecialchars($animale['PADRE_TAG']) : 'Nessuno' ?></td>
                    <td><?= $animale['MADRE_TAG'] ? htmlspecialchars($animale['MADRE_TAG']) : 'Nessuno' ?></td>
                    <td>
                        <?php if ($animale['prossima_scadenza_vaccino']): ?>
                            <?= date('d/m/Y', strtotime($animale['prossima_scadenza_vaccino'])) ?>
                            <?php if(strtotime($animale['prossima_scadenza_vaccino']) < time()): ?>
                                <span style="color: red;">(Scaduto!)</span>
                            <?php endif; ?>
                        <?php else: ?>
                            Nessuno
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="display: flex; gap: 6px;">
                            <button class="btn-modifica modificaAnimaleBtn" data-tag="<?= htmlspecialchars($animale['TAG']) ?>">Modifica</button>
                            <button class="btn-elimina eliminaAnimaleBtn" data-tag="<?= htmlspecialchars($animale['TAG']) ?>">Elimina</button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal per aggiungere un nuovo animale -->
<div id="animaleModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2 id="modalTitoloAnimale">Aggiungi Nuovo Animale</h2>
        <form id="animaleForm">
            <div class="form-group">
                <label for="nome">Nome:</label>
                <input type="text" id="nome" name="nome" required placeholder="Inserisci il nome">
            </div>
            <div class="form-group">
                <label for="specie">Specie:</label>
                <select id="specie" name="specie" required>
                    <option value="">Seleziona...</option>
                    <option value="Bovino">Bovino</option>
                    <option value="Suino">Suino</option>
                    <option value="Ovino">Ovino</option>
                    <option value="Caprino">Caprino</option>
                    <option value="Equino">Equino</option>
                </select>
            </div>
            <div class="form-group">
                <label for="razza">Razza:</label>
                <input type="text" id="razza" name="razza" required placeholder="Inserisci la razza">
            </div>
            <div class="form-group">
                <label for="data_nascita">Data di Nascita:</label>
                <input type="date" id="data_nascita" name="data_nascita" required>
            </div>
            <div class="form-group">
                <label for="sesso">Sesso:</label>
                <select id="sesso" name="sesso" required>
                    <option value="">Seleziona...</option>
                    <option value="M">Maschio</option>
                    <option value="F">Femmina</option>
                </select>
            </div>
            <div class="form-group">
                <label for="padre_tag">Tag Padre (opzionale):</label>
                <input type="text" id="padre_tag" name="padre_tag" placeholder="Inserisci TAG padre">
            </div>
            <div class="form-group">
                <label for="madre_tag">Tag Madre (opzionale):</label>
                <input type="text" id="madre_tag" name="madre_tag" placeholder="Inserisci TAG madre">
            </div>
            <div class="form-actions">
                <button type="button" class="btn-annulla">Annulla</button>
                <button type="submit" class="btn-salva">Salva</button>
            </div>
        </form>
    </div>
</div>

<!-- modal per modificare un animale (vuoto, si riempie via js) -->
<div id="modificaAnimaleModal" class="modal">
    <div class="modal-content" id="modificaAnimaleContent">
        <span class="close" id="closeModificaAnimale">&times;</span>
        <!-- qui verrà caricato il form di modifica via ajax -->
    </div>
</div>

<!-- stili css -->
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
const animaleModal = document.getElementById('animaleModal');
const btnAggiungiAnimale = document.getElementById('aggiungiAnimaleBtn');
const spanCloseAnimale = animaleModal.querySelector('.close');
const btnAnnullaAnimale = animaleModal.querySelector('.btn-annulla');

btnAggiungiAnimale.onclick = function(){
    document.getElementById('modalTitoloAnimale').textContent = 'Aggiungi Nuovo Animale';
    document.getElementById('animaleForm').reset();
    animaleModal.style.display = 'block';
}

spanCloseAnimale.onclick = function(){animaleModal.style.display = 'none';}

btnAnnullaAnimale.onclick = function(){animaleModal.style.display = 'none';}

window.onclick = function(e){
    if(e.target == animaleModal){
        animaleModal.style.display = 'none';
    }
}

document.getElementById('animaleForm').addEventListener('submit', async function(e){
    e.preventDefault();
    const formData = new FormData(this);
    try{
        const response = await fetch('aggiungi_animale.php',{
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        if(result.success){
            animaleModal.style.display = 'none';
            window.location.reload();
        }
        else{
            alert('Errore: ' + (result.message || 'Errore sconosciuto'));
        }
    }
    catch(error){
        alert('Errore di connessione: ' + error.message);
    }
});
</script>

<script>
// gestione eliminazione animale
document.addEventListener('click', function(e){
    if (e.target && e.target.classList.contains('eliminaAnimaleBtn')){
        const tag = e.target.getAttribute('data-tag');
        fetch('elimina_animale.php',{method: 'POST',headers:{'Content-Type': 'application/x-www-form-urlencoded',},body: 'tag=' + encodeURIComponent(tag)})
        .then(response => response.json())
        .then(result =>{
            if(result.success){
                window.location.reload();
            }
            else{
                alert('Errore: ' + (result.message || 'Errore durante l\'eliminazione'));
            }
        })
        .catch(error =>{
            alert('Errore di connessione: ' + error.message);
        });
    }
});
</script>

<script>
// gestione apertura modale modifica animale
const modificaAnimaleModal = document.getElementById('modificaAnimaleModal');
const modificaAnimaleContent = document.getElementById('modificaAnimaleContent');
const closeModificaAnimale = document.getElementById('closeModificaAnimale');

document.addEventListener('click', async function(e){
    if(e.target && e.target.classList.contains('modificaAnimaleBtn')){
        const tag = e.target.getAttribute('data-tag');
        // carica il form di modifica via AJAX
        try{
            const response = await fetch('modifica_animale.php?tag=' + encodeURIComponent(tag) + '&ajax=1');
            const html = await response.text();
            // inserisci il form nel modale
            modificaAnimaleContent.innerHTML = '<span class="close" id="closeModificaAnimale">&times;</span>' + html;
            modificaAnimaleModal.style.display = 'block';
            // ricollega il close dopo il replace
            document.getElementById('closeModificaAnimale').onclick = function(){
                modificaAnimaleModal.style.display = 'none';
            };
        }
        catch(err){
            alert('Errore nel caricamento del form di modifica');
        }
    }
});

// Chiudi il modale se clicchi fuori
window.addEventListener('click', function(e){
    if(e.target == modificaAnimaleModal){
        modificaAnimaleModal.style.display = 'none';
    }
});

document.addEventListener('submit', async function(e){
    if(e.target && e.target.closest('#modificaAnimaleModal form')){
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        
        try{
            const response = await fetch('modifica_animale.php',{
                method: 'POST',
                body: formData
            });
            
            const result = await response.text();
            
            if(result.includes('Animale modificato con successo!')){
                // Chiudi il modal e ricarica la pagina
                modificaAnimaleModal.style.display = 'none';
                window.location.reload();
            }
            else{
                // Aggiorna il contenuto del modal con eventuali messaggi di errore
                modificaAnimaleContent.innerHTML = result;
            }
        }
        catch(error){
            alert('Errore di connessione: ' + error.message);
        }
    }
});
</script>