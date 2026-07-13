<?php
require_once 'connessione.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

$ruolo = $input['ruolo'] ?? 'ALLEVATORE'; // Default: ALLEVATORE
$username = $input['username'] ?? null;
$email = $input['email'] ?? null;
$password = $input['password'] ?? null;

if(!$ruolo || !$username || !$email || !$password){
    echo json_encode(['success' => false, 'message' => 'Compila tutti i campi.']);
    exit;
}

// Controlla se email o username sono già usati
$check = $db->prepare("SELECT * FROM UTENTE WHERE EMAIL = ?");
$check->execute([$email]);
if($check->fetch()){
    echo json_encode(['success' => false, 'message' => 'Email già registrata.']);
    exit;
}

// Inserisci nuovo utente
$pw = hash('sha256', $password);
$stmt = $db->prepare("INSERT INTO UTENTE (RUOLO, USERNAME, EMAIL, PASSWORD) VALUES (?, ?, ?, ?)");
$stmt->execute([$ruolo, $username, $email, $pw]);

echo json_encode(['success' => true, 'message' => 'Registrazione completata.']);
?>