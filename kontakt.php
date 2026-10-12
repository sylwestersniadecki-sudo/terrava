<?php
// Terrava — obsługa formularza kontaktowego.
// Wymaga PHP 7.4+ i działającej funkcji mail() na serwerze (standard na większości hostingów).
// USTAW: adres, na który mają przychodzić wiadomości, oraz adres nadawcy w Twojej domenie.

$DO     = 'kontakt@terrava.pl';   // odbiorca wiadomości
$OD     = 'formularz@terrava.pl'; // nadawca (musi istnieć w domenie terrava.pl)

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false]);
    exit;
}

// Pułapka na boty: ukryte pole musi być puste
if (!empty($_POST['www'])) {
    echo json_encode(['ok' => true]);
    exit;
}

function pole($k, $max = 2000) {
    $v = isset($_POST[$k]) ? trim((string)$_POST[$k]) : '';
    $v = str_replace(["\r", "\0"], '', $v);
    return mb_substr($v, 0, $max);
}

$rola    = pole('rola', 20) === 'projekt' ? 'Właściciel projektu' : 'Inwestor';
$imie    = pole('imie', 120);
$firma   = pole('firma', 160);
$email   = pole('email', 160);
$telefon = pole('telefon', 40);
$tresc   = pole('wiadomosc', 4000);
$zgoda   = pole('zgoda', 5) === 'tak';

if ($imie === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !$zgoda) {
    http_response_code(422);
    echo json_encode(['ok' => false]);
    exit;
}

$temat = '=?UTF-8?B?' . base64_encode("Terrava: nowa wiadomość ($rola)") . '?=';
$body  = "Rola: $rola\nImię i nazwisko: $imie\nFirma: $firma\nE-mail: $email\nTelefon: $telefon\n\nWiadomość:\n$tresc\n\n"
       . "Zgoda na przetwarzanie danych: tak\nData: " . date('Y-m-d H:i') . "\nIP: " . ($_SERVER['REMOTE_ADDR'] ?? '-') . "\n";

$naglowki = "From: Terrava <$OD>\r\n"
          . "Reply-To: $email\r\n"
          . "MIME-Version: 1.0\r\n"
          . "Content-Type: text/plain; charset=UTF-8\r\n"
          . "Content-Transfer-Encoding: 8bit\r\n";

$ok = @mail($DO, $temat, $body, $naglowki, "-f$OD");

echo json_encode(['ok' => (bool)$ok]);
