<?php
include 'controlla_autorizzazione.php';     // include i controlli e il session_start()
require_once 'connessione.php';

// recupera l'username dal DB per mostrarlo (opzionale se già salvato in sessione)
$query = $db->prepare("SELECT USERNAME FROM UTENTE WHERE ID = ?");
$query->execute([$_SESSION['user_id']]);              // recupero l'username dell'utente loggato tramite id salvato in sessione (prepare + execute per evitare sql injection)
$user = $query->fetch(PDO::FETCH_ASSOC);              // recupera i dati
$username = $user ? htmlspecialchars($user['USERNAME']) : 'Utente';       // se l'utente esiste, lo salva in una variabile, altrimenti lo imposta a 'Utente' di default

$active_section = isset($_GET['section']) ? $_GET['section'] : 'animali';     // sezione attiva al momento, di default è impostata a quella degli animali
?>

<!DOCTYPE html>         <!-- inizio della parte html + css -->
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
    main{padding: 20px;}
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
    .nav-buttons button.active{background: darkgreen; margin: -5px -5px; font-weight: bold;}
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
    <div class="user-info">         <!-- nome della classe dell'username a cui fanno riferimento gli stili css -->
      <span>Allevatore <strong><?php echo $username; ?></strong></span>     <!-- mostra in grassetto l'username dell'utente -->
      <button onclick="window.location.href='logout.php'">Esci</button>   <!-- tasto per uscire che reindirizza a logout.php -->
    </div>
  </header>

  <main>
    <div class="nav-buttons">     <!-- classe per i pulsanti di navigazione -->
      <button onclick="caricaSezione('animali')" <?php echo $active_section === 'animali' ? 'class="active"' : ''; ?>>Animali</button>
      <button onclick="caricaSezione('produzione')" <?php echo $active_section === 'produzione' ? 'class="active"' : ''; ?>>Produzione</button>
      <button onclick="caricaSezione('alimentazione')" <?php echo $active_section === 'alimentazione' ? 'class="active"' : ''; ?>>Alimentazione</button>      <!-- usa la funzione caricaSezione per caricare la sezione specificata tramite AJAX -->
    </div>

    <div class="section-container"></div>
  </main>

  <div class="versione">v. 1.0</div>

  <script>
    // Funzione principale per caricare le sezioni via AJAX
  async function caricaSezione(section, primoCaricamento = false){
    if(!primoCaricamento){
       // uso questo metodo per forzare il reload delle varie pagine, altrimenti i tasti non fuzionano, non ho trovato un modo migliore
      window.location.href = `?section=${section}`;
      return;
    }
    const container = document.querySelector('.section-container');

    try{
        let endpoint;
        // reindirizzamento alle varie pagine tramite endpoint con switch case
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
            default:
                endpoint = 'animali.php';
        }

        const richiesta = await fetch(endpoint);    // fetch asincrona dell'url
        const text = await richiesta.text();
        container.innerHTML = text;                 // inserisce il testo della pagina selezionata nel container
        
        const scripts = container.querySelectorAll('script');     // crea array scripts con gli script
        scripts.forEach(script =>{
            const newScript = document.createElement('script');   // crea nuovo elemento script vuoto
            newScript.text = script.text;                         // copia al suo interno la roba dello script associato ciclato
            document.body.appendChild(newScript).parentNode.removeChild(newScript);   //aggiunge al dom lo script per l'esecuzione e lo rimuove subito dopo
        });
    }
    catch(error){
        container.innerHTML = `<div class="section-placeholder">Errore nel caricamento della sezione ${section}</div>`;
        console.error('Errore:', error);
    }
  }

  // carica la sezione iniziale al caricamento della pagina
  window.addEventListener('DOMContentLoaded', function(){
    caricaSezione('<?php echo $active_section; ?>', true);      // una volta caricata la pagina (window.onload), chiama la funzione caricaSezione per caricare la sezione specificata in parametro
  });

  // gestisce il pulsante indietro del browser
  window.addEventListener('popstate', function(){             // evento popstate si attiva quando si va avanti o indietro nel browser
    const urlParams = new URLSearchParams(window.location.search);    // legge i parametri della query nell'url (la parte dopo il ?)
    const section = urlParams.get('section') || 'animali';    // prende il valore di section, se non esiste prende di default animali
    caricaSezione(section);                                   // carica la sezione
  });
  </script>

</body>
</html>