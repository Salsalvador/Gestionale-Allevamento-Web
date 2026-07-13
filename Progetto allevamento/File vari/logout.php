<?php
session_start();
session_unset();      // Cancella tutte le variabili di sessione
session_destroy();    // Distrugge la sessione
header("Location: login.html");  // Torna al login
exit;
?>