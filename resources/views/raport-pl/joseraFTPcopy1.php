<?php
$ftp_server = "s129.cyber-folks.pl"; // Adres serwera FTP
$ftp_username = "dev@cyberrum.site"; // Nazwa użytkownika FTP
$ftp_password = "3__E!-X7_-y6Imb7"; // Hasło FTP

$date = date("Y-m-d", strtotime("-1 day"));
$local_file = "/var/www/html/JoseraStockReport$date.csv";
$remote_file = "producee/JoseraStockReport$date.csv"; // Nazwa pliku na serwerze


// Nawiązanie połączenia FTP
$conn_id = ftp_connect($ftp_server);
if (!$conn_id) {
    die("Nie udało się połączyć z serwerem FTP");
}

// Logowanie
$login_result = ftp_login($conn_id, $ftp_username, $ftp_password);
if (!$login_result) {
    ftp_close($conn_id);
    die("Nie udało się zalogować na serwer FTP");
}

// Włączenie trybu pasywnego
ftp_pasv($conn_id, true);

// Wysłanie pliku
if (ftp_put($conn_id, $remote_file, $local_file, FTP_BINARY)) {
    echo "Plik został przesłany na serwer FTP.";
} else {
    echo "Błąd podczas przesyłania pliku.";
}

// Zamknięcie połączenia
ftp_close($conn_id);
?>
