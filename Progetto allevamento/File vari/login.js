async function login(e){
    e.preventDefault();  // evito di refreshare la pagina anzichè andare avanti

    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;        // metto a costanti mail e pw presi dall'html

    // manda una richiesta post di confronto di mail e pw in formato json col database tramite php
    const response = await fetch('login.php',{method: 'POST',headers: {'Content-Type': 'application/json'},body: JSON.stringify({email, password})});

    const result = await response.json();       // aspetta una risposta da parte del server

    if(result.success){
        if(result.ruolo === 'ADMIN')
        {
            window.location.href = 'dashboard_admin.php'; // reindirizzo a dashboard_admin.php se l'utente è un admin
        }
        else if(result.ruolo === 'ALLEVATORE')
        {
            window.location.href = 'dashboard_allevatore.php'; // reindirizzo a dashboard_allevatore.php se l'utente è un allevatore
        }
    }
    else
    {
        document.getElementById('errore').textContent = result.error;       // altrimenti c'è l'errore
    }
}

document.getElementById('loginForm').addEventListener('submit', login);    // effettua il login