<?php
// Configurazione database di default
$host = 'localhost';
$dbname = 'gestione_allevamento';
$username = 'root';
$password = '';

try
{
    $db = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);     // creazione di oggetto PDO (classe PHP che accede al database) con i parametri di connessione
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);               // eccezioni in caso di errore
}
catch(PDOException $e)
{
    die('Connessione fallita: ' . $e->getMessage());        // messaggio di errore se la connessione fallisce
}
?>