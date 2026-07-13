Quest'applicazione web è stata sviluppata facendo uso di PHP, Javascript, HTML, CSS e MySQL. E' pensata per supportare gli amministratori e gli allevatori nella gestione del proprio allevamento. Permette di monitorare ed organizzare animali, modificarne informazioni, oltre che visionare alimentazione, produzione, spese, ricavi ed attività sanitarie. Inoltre, presenta una sezione dedicata all'analisi del ritorno di investimento, divisa in tabella generale e tabella per ciascun animale.

Come installare il progetto:

I prerequisiti sono:
- Versione di PHP >= 7.0
- MySQL oppure MariaDB
- Server Web (preferibilmente XAMPP)

Per importare il database, aprire il terminale e digitare:
[PERCORSO_A_MYSQL.EXE] -u [USERNAME] -p [NOME_DATABASE] < [PERCORSO_AL_FILE_SQL]
Placeholder di riferimento:
"C:\xampp\mysql\bin\mysql.exe" -u root -p gestione_allevamento < "C:\Users\TUO_NOME\Desktop\dump.sql"
Altrimenti è possibile utilizzare phpMyAdmin per importare 'dump.sql'.
Host, database, username e password sono specificati nel file connessione.php. 

Dopodichè, copiare la cartella del progetto nella directory del server (htdocs per XAMPP) e aprire il link:
http://localhost/Allevamento/login.php

L'intero progetto è stato sviluppato senza l'utilizzo di framework esterni, nè per front-end nè per back-end, nè lato server nè lato client.


Istruzioni per l'esecuzione dei test

1. Installare Composer da https://getcomposer.org/

2. Installare PHPUnit (Versione 10.4):
Tramite terminale, spostarsi nella cartella del progetto col comando "cd PERCORSO" ed installare PHPUnit col comando "composer require --dev phpunit/phpunit ^10.4"

3. Scrivere i test tramite file PHP, strutturando le funzioni di testing all'interno di un'unica classe di tipo final

4. Dopodichè eseguire i test via terminale:
Spostarsi nella cartella del progetto col comando "cd PERCORSO" e lanciare i test col comando ".\vendor\bin\phpunit FileTest.php", dove per FileTest.php si intende il file di test di cui si dispone effettivamente


Progetto per l'esame di Sistemi web e basi di dati di Salvatore Giannone, MAT. A13002913