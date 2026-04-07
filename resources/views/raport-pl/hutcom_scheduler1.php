<?php

// HUTCOM SCHEDULER
// v 1.1.0
// v 1.0.0 uruchamianie

$file = '/var/www/html/alive.log';

$time = date('Y-m-d H:i:s');
$data = $time . "\n";

$hour = intval(date('H'));
$min = intval(date('i'));

file_put_contents($file, $data, FILE_APPEND | LOCK_EX); // FILE_APPEND zapobiega nadpisaniu pliku, LOCK_EX zapobiega równoczesnym zapisom

// ------------------------------------------------------------
// STOCK AUTOREPORT
// ------------------------------------------------------------

if ($hour > 21 && $min < 10) {
    file_put_contents($file, "uruchamiam stock_autoreport.php\n", FILE_APPEND | LOCK_EX); // FILE_APPEND zapobiega nadpisaniu pliku, LOCK_EX zapobiega równoczesnym zapisom

    require 'stock_autoreport.php';

    file_put_contents($file, "zakończony stock_autoreport.php\n", FILE_APPEND | LOCK_EX); // FILE_APPEND zapobiega nadpisaniu pliku, LOCK_EX zapobiega równoczesnym zapisom


 // ----------------
    // JOSERA STOCK
    // ----------------

   


} else {

    file_put_contents($file, "no stock autoreport\n", FILE_APPEND | LOCK_EX); // FILE_APPEND zapobiega nadpisaniu pliku, LOCK_EX zapobiega równoczesnym zapisom

    // ------------------------------------------------------------
    // STOCK ADJUSTMENT B6+ AUTOMAT
    // ------------------------------------------------------------

    $file = '/var/www/html/alive.log';

    file_put_contents($file, "uruchamiam stadj_automat.php\n", FILE_APPEND | LOCK_EX); // FILE_APPEND zapobiega nadpisaniu pliku, LOCK_EX zapobiega równoczesnym zapisom

    include('stadj_automat.php');

    file_put_contents($file, "zakończony stadj_automat.php\n", FILE_APPEND | LOCK_EX); // FILE_APPEND zapobiega nadpisaniu pliku, LOCK_EX zapobiega równoczesnym zapisom

    // ------------------------------------------------------------
    // RECEIPT AUTOMAT
    // ------------------------------------------------------------

    file_put_contents($file, "start receipt_automat.php\n", FILE_APPEND | LOCK_EX); // FILE_APPEND zapobiega nadpisaniu pliku, LOCK_EX zapobiega równoczesnym zapisom
    include('receipt_automat.php');
    file_put_contents($file, "zakończony receipt_automat.php\n", FILE_APPEND | LOCK_EX); // FILE_APPEND zapobiega nadpisaniu pliku, LOCK_EX zapobiega równoczesnym zapisom

 file_put_contents($file, "start joserastck.php\n", FILE_APPEND | LOCK_EX); 
    include('/var/www/html/ejosera/joserastock.php');
    file_put_contents($file, "zakończony joserastock.php\n", FILE_APPEND | LOCK_EX); 

    file_put_contents($file, "start joseraFTPcopy.php\n", FILE_APPEND | LOCK_EX); 
    include('/var/www/html/ejosera/joseraFTPcopy.php');
    file_put_contents($file, "zakończony joseraFTPcopy.php\n", FILE_APPEND | LOCK_EX); 


}
