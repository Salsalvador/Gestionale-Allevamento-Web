<?php
use PHPUnit\Framework\TestCase;

// questo file testa prima il caricamento delle varie pagine, poi crea un utente, lo logga, crea un animale e lo elimina

class TestFunzioni extends TestCase
{
    private $baseUrl = 'http://localhost/Allevamento/';

    public function testLoginCaricaPagina()
    {
        $response = file_get_contents($this->baseUrl . 'login.html');
        $this->assertStringContainsString('Accesso', $response);
    }

    public function testAnimaliPageRedirectsToLoginIfNotAuthenticated()
    {
        $response = file_get_contents($this->baseUrl . 'animali.php');
        $this->assertStringContainsString('Accesso', $response);
    }

    public function testAnimaliCaricaPagina()
    {
        $response = file_get_contents($this->baseUrl . 'animali.php');
        $this->assertTrue(
            strpos($response, 'Accesso') !== false || strpos($response, 'Pagina di Login') !== false,
            'La pagina animali.php dovrebbe mostrare la pagina di login se non autenticato.'
        );
    }

    public function testProduzioneCaricaPagina()
    {
        $response = file_get_contents($this->baseUrl . 'produzione.php');
        $this->assertStringContainsString('Produzione', $response);
    }

    public function testAlimentazioneCaricaPagina()
    {
        $response = file_get_contents($this->baseUrl . 'alimentazione.php');
        $this->assertStringContainsString('Alimentazione', $response);
    }

    public function testVaccinazioneCaricaPagina()
    {
        $response = file_get_contents($this->baseUrl . 'vaccinazione.php');
        $this->assertStringContainsString('Vaccinazione', $response);
    }

    public function testControlloVeterinarioCaricaPagina()
    {
        $response = file_get_contents($this->baseUrl . 'controllo_veterinario.php');
        $this->assertStringContainsString('Controllo', $response);
    }

    public function testCertificazioneCaricaPagina()
    {
        $response = file_get_contents($this->baseUrl . 'analisi.php');
        $this->assertStringContainsString('Certificazione', $response);
    }

    // questa funzione testa la creazione di un utente, il login, l'aggiunta di un animale e la sua cancellazione
    public function testInserimentoEdEliminazioneAnimaleAutenticato()
    {
        // credenziali di test
        $email = 'testallevatore@gmail.com';
        $password = 'test_password';
        $username = 'test_Allevatore';
        $ruolo = 'ALLEVATORE';
        $cookieFile = tempnam(sys_get_temp_dir(), 'cookie');        // file temporaneo per gestire i cookie e mantenere la sessione tra le richieste

        // prova a registrare l'utente di test (se già esiste, ignora l'errore)
        $registerData = json_encode(['username' => $username, 'email' => $email, 'password' => $password, 'ruolo' => $ruolo]);
        $richiesta = curl_init($this->baseUrl . 'registrazione.php');      // curl per simulare richieste http
        curl_setopt($richiesta, CURLOPT_RETURNTRANSFER, true);             // restituisce la risposta come stringa
        curl_setopt($richiesta, CURLOPT_POST, true);                       // imposta la richiesta come post per inviare i dati al server
        curl_setopt($richiesta, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);    // imposta l'header a json perchè i dati sono in questo formato
        curl_setopt($richiesta, CURLOPT_POSTFIELDS, $registerData);        // specifica i dati da inviare nella post (codificati in json)
        $registerResponse = curl_exec($richiesta);                         // manda richiesta di registrazione
        curl_close($richiesta);
        $registerJson = json_decode($registerResponse, true);
        // se la registrazione fallisce per "Email già registrata", va bene, altrimenti deve essere success
        $this->assertTrue((isset($registerJson['success']) && $registerJson['success']) || (isset($registerJson['message']) && strpos($registerJson['message'], 'Email già registrata') !== false),
            'Registrazione utente di test fallita: '.($registerJson['message'] ?? '')
        );

        // effettua login e salva la sessione
        $loginData = json_encode(['email' => $email, 'password' => $password]);     // usa le credenziali appena create per il login
        $richiesta = curl_init($this->baseUrl . 'login.php');
        curl_setopt($richiesta, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($richiesta, CURLOPT_POST, true);
        curl_setopt($richiesta, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($richiesta, CURLOPT_POSTFIELDS, $loginData);
        curl_setopt($richiesta, CURLOPT_COOKIEJAR, $cookieFile);                   // notare che usa il cookie per mantenere la sessione
        $loginResponse = curl_exec($richiesta);                                    // manda richiesta di login
        curl_close($richiesta);
        $loginJson = json_decode($loginResponse, true);
        $this->assertTrue(isset($loginJson['success']) && $loginJson['success'], 'Login fallito: '.($loginJson['error'] ?? ''));

        // inserimento animale
        $nomeTest = 'test_Animale';
        $specieTest = 'Bovino';
        $razzaTest = 'test_Razza';
        $dataNascita = '2020-01-01';
        $sesso = 'M';
        $postData = http_build_query(['nome' => $nomeTest, 'specie' => $specieTest, 'razza' => $razzaTest, 'data_nascita' => $dataNascita,'sesso' => $sesso]);
        $richiesta = curl_init($this->baseUrl . 'aggiungi_animale.php');
        curl_setopt($richiesta, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($richiesta, CURLOPT_POST, true);
        curl_setopt($richiesta, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($richiesta, CURLOPT_COOKIEFILE, $cookieFile);
        $response = curl_exec($richiesta);
        curl_close($richiesta);
        $json = json_decode($response, true);
        $this->assertTrue(isset($json['success']) && $json['success'], 'Inserimento animale fallito: '.($json['message'] ?? ''));
        $tagTest = $json['tag'];

        // verifica che l'animale sia stato inserito (controllo presenza nella pagina animali.php)
        $richiesta = curl_init($this->baseUrl . 'animali.php');
        curl_setopt($richiesta, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($richiesta, CURLOPT_COOKIEFILE, $cookieFile);
        $paginaAnimali = curl_exec($richiesta);
        curl_close($richiesta);
        $this->assertStringContainsString($tagTest, $paginaAnimali, 'L\'animale di test non è stato trovato nella lista.');     // verifica che il tag dell'animale appena aggiunto sia presente nella lista

        // eliminazione animale
        $postCancella = http_build_query(['tag' => $tagTest]);                  // l'animale da eliminare è specificato dal tag
        $richiesta = curl_init($this->baseUrl . 'elimina_animale.php');         // richiesta di accesso al file di eliminazione
        curl_setopt($richiesta, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($richiesta, CURLOPT_POST, true);
        curl_setopt($richiesta, CURLOPT_POSTFIELDS, $postCancella);
        curl_setopt($richiesta, CURLOPT_COOKIEFILE, $cookieFile);
        $responseCancella = curl_exec($richiesta);
        curl_close($richiesta);
        $jsonCancella = json_decode($responseCancella, true);
        $this->assertTrue(isset($jsonCancella['success']) && $jsonCancella['success'], 'Eliminazione animale fallita.');

        // verifica che l'animale non sia più presente
        $richiesta = curl_init($this->baseUrl . 'animali.php');
        curl_setopt($richiesta, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($richiesta, CURLOPT_COOKIEFILE, $cookieFile);
        $paginaAnimaliDopo = curl_exec($richiesta);
        curl_close($richiesta);
        $this->assertStringNotContainsString($tagTest, $paginaAnimaliDopo, 'L\'animale di test è ancora presente dopo l\'eliminazione.');

        @unlink($cookieFile);               // elimina il file temporaneo dei cookie
    }
}