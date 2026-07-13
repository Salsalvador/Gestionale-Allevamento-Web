<?php
include 'controlla_autorizzazione.php';       // include i controlli e il session_start()
require_once 'connessione.php';

// la dashboard dell'admin è fondamentalmente uguale a quella dell'allevatore, ma presenta alcune sezioni aggiuntive, così da permettere agli amministratori maggior controllo

// Recupera l'username dal DB per mostrarlo (opzionale se già salvato in sessione)
$query = $db->prepare("SELECT USERNAME FROM UTENTE WHERE ID = ?");
$query->execute([$_SESSION['user_id']]);
$user = $query->fetch(PDO::FETCH_ASSOC);
$username = $user ? htmlspecialchars($user['USERNAME']) : 'Utente';

// Sezione attiva (default: animali)
$active_section = isset($_GET['section']) ? $_GET['section'] : 'animali';
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <title>Dashboard - Gestione Allevamento</title>
  <style>
    body{font-family: sans-serif; margin: 0; background: white;}
    header{background: green; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center;}
    header h1{margin: 0; font-size: 24px;}
    .user-info{display: flex; align-items: center; gap: 15px;}
    .user-info span{font-size: 16px;}
    .user-info button{
      background: red;
      border: none;
      padding: 10px 16px;
      font-size: 15px;
      border-radius: 5px;
      cursor: pointer;
      transition: background 0.3s;
    }
    .user-info button:hover{background: darkred;}
    main {padding: 20px;}
    .nav-buttons{
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-bottom: 20px;
    }
    .nav-buttons button{
      background: green;
      color: white;
      border: none;
      flex: 1 1 0;
      font-size: 16px;
      border-radius: 5px;
      cursor: pointer;
      transition: background 0.3s;
      padding: 10px 0;
      min-width: 0;
    }
    .nav-buttons button:hover{background: darkgreen;}
    .nav-buttons button.active {background: darkgreen; margin: -5px -5px; font-weight: bold;}
    .section-container{
      background: white;
      padding: 20px;
      border-radius: 5px;
      box-shadow: 0 0 5px rgba(0,0,0,0.2);
      min-height: 300px;
    }
    .section-placeholder{text-align: center; padding: 50px; color: #666;}
    .versione{
      position: fixed;
      right: 18px;
      bottom: 10px;
      color: gray;
      font-size: 15px;
      z-index: auto;
    }
  </style>
</head>
<body>

  <header>
    <h1>Area privata</h1>
    <div class="user-info">
      <span>Amministratore <strong><?php echo $username; ?></strong></span>
      <button onclick="window.location.href='logout.php'">Esci</button>
    </div>
  </header>

  <main>
    <div class="nav-buttons">
      <button onclick="caricaSezione('animali')" <?php echo $active_section === 'animali' ? 'class="active"' : ''; ?>>Animali</button>
      <button onclick="caricaSezione('produzione')" <?php echo $active_section === 'produzione' ? 'class="active"' : ''; ?>>Produzione</button>
      <button onclick="caricaSezione('alimentazione')" <?php echo $active_section === 'alimentazione' ? 'class="active"' : ''; ?>>Alimentazione</button>
      <button onclick="caricaSezione('controlli')" <?php echo $active_section === 'controlli' ? 'class="active"' : ''; ?>>Controlli Veterinari</button>
      <button onclick="caricaSezione('vaccini')" <?php echo $active_section === 'vaccini' ? 'class="active"' : ''; ?>>Vaccinazioni</button>
      <button onclick="caricaSezione('analisi')" <?php echo $active_section === 'analisi' ? 'class="active"' : ''; ?>>Analisi Economica</button>
    </div>

    <div class="section-container"> </div>
  </main>

  <div class="versione">v. 1.0</div>

  <script>
    // Carica la sezione iniziale
    window.onload = function(){
      caricaSezione('<?php echo $active_section; ?>', true);
    };

    async function caricaSezione(section, primoCaricamento = false){
      if(!primoCaricamento){
        // uso questo metodo per forzare il reload delle varie pagine, altrimenti i tasti non funzionano, non ho trovato un modo migliore
        window.location.href = `?section=${section}`;
        return;
      }

    const container = document.querySelector('.section-container');

    try{
        let endpoint;
        switch(section){
            case 'animali':
                endpoint = 'animali.php';
                break;
            case 'produzione':
                endpoint = 'produzione.php';
                break;
            case 'alimentazione':
                endpoint = 'alimentazione.php';
                break;
            case 'controlli':
                endpoint = 'controllo_veterinario.php';
                break;
            case 'vaccini':
                endpoint = 'vaccinazione.php';
                break;
            case 'analisi':
                endpoint = 'analisi.php';
                break;
        }

        const res = await fetch(endpoint);
        const text = await res.text();
        container.innerHTML = text;
        
        // Aggiungi questo blocco per eseguire gli script nella sezione caricata
        const scripts = container.querySelectorAll('script');
        scripts.forEach(script =>{
            const newScript = document.createElement('script');
            newScript.text = script.text;
            document.body.appendChild(newScript).parentNode.removeChild(newScript);
        });
    }
    catch (error){
        container.innerHTML = `<div class="section-placeholder">Errore nel caricamento della sezione ${section}</div>`;
        console.error('Errore:', error);
    }
}

    // Gestisce il pulsante indietro del browser
    window.addEventListener('popstate', function(){
      const urlParams = new URLSearchParams(window.location.search);
      const section = urlParams.get('section') || 'animali';
      caricaSezione(section);
    });
  </script>
</body>
</html>