<?php
session_start();
if(!isset($_SESSION['user_id']))
{
    header('Location: login.html');
    exit();
}

// se la sessione non è autenticata rimanda alla pagina di login
// per sessione non autenticata si intende che l'utente ha cercato di accedere ad una delle sezioni della dashboard senza essersi loggato prima

?>