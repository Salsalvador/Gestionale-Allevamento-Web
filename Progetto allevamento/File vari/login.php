<?php
require_once 'connessione.php';                 // connessione.php fa da link diretto al database
session_start();                                // avvia la sessione php

header('Content-Type: application/json');       // così sa che i dati in ingresso sono json

$json_input = file_get_contents('php://input');     // legge lo stream in input
$dati = json_decode($json_input, true);             // usa json_decode per leggere il json in input da js

$email = $dati['email'] ?? null;
$password = $dati['password'] ?? null;          // prende i valori da dati, se non esistono li imposta a null

$message = '';

if(!$email || !$password){  // se non sono riempiti tutti i campi restituisce un errore
    echo json_encode(['success' => false, 'error' => 'Compila tutti i campi.']);
    exit;
}

// se entrambi i campi sono riempiti, prova a connettersi al database
$pw_criptata = hash('sha256', $password);       // pw criptata con sha256 per sicurezza
$query = $db->prepare("SELECT * FROM UTENTE WHERE EMAIL = ? AND PASSWORD = ?"); // prepara la query per trovare l'utente
$query->execute([$email, $pw_criptata]);        // esegue la query con i parametri passati

/* per evitare le sql injection effettuo prima la preparazione e poi l'esecuzione con i parametri
altrimenti avrei fatto direttamente:
$query = $db->query("SELECT * FROM UTENTE WHERE EMAIL = '$email' AND PASSWORD = '$pw_criptata'");
*/

if($user = $query->fetch(PDO::FETCH_ASSOC)){
    $_SESSION['user_id'] = $user['ID'];
    $_SESSION['ruolo'] = $user['RUOLO'];
    /* avendo startato la sessione a inizio script, salvo in maniera persistente id e ruolo dell'utente tramite
    la var superglobale _SESSION così che vengano memorizzati anche su altri script php fintanto che la sessione è attiva
    */
    echo json_encode(['success' => true, 'ruolo' => $user['RUOLO']]);       // invia la risposta col ruolo e conferma di successo al client in formato json
}
else{
    echo json_encode(['success' => false, 'error' => 'Credenziali non valide.']);   // altrimenti ha sbagliato le credenziali
}
?>