<?php
// Kontaktų formos apdorojimas: patikrina duomenis ir išsiunčia laišką jums.

// ========== KEISTI ==========
$TO_EMAIL   = 'darius@novarodigital.lt';  // kur gausite užklausas (kol nėra info@ adreso)
$FROM_EMAIL = 'noreply@novarodigital.lt'; // turi būti jūsų domeno adresas, kitaip laiškai pateks į šlamštą
$SITE_NAME  = 'Novaro Digital';
$MIN_SECONDS = 3;                          // greičiau užpildyta forma laikoma botu

// SMTP nustatymai (slaptažodis) laikomi ATSKIRAME faile UŽ public_html ribų, kad nepatektų nei į Git, nei į internetą.
// Failas: <aplankas virš public_html>/novaro-mail.php, turinys:
//   <?php return ['host' => 'smtp.hostinger.com', 'port' => 465, 'user' => 'darius@novarodigital.lt', 'pass' => 'PAŠTO SLAPTAŽODIS'];
// Jei failo nėra, laiškas siunčiamas per PHP mail() – jis veikia, bet dažnai patenka į šlamštą.
$SMTP_CONFIG_FILE = dirname(__DIR__) . '/novaro-mail.php';
// ============================

// Siunčia laišką per Hostinger SMTP su prisijungimu – toks laiškas pasirašomas DKIM ir praeina SPF/DMARC patikrą.
function smtp_send(array $cfg, string $to, string $subject, string $body, array $headers): bool {
    $fp = @stream_socket_client('ssl://' . $cfg['host'] . ':' . (int)$cfg['port'], $errno, $errstr, 15);
    if (!$fp) {
        error_log("contact.php SMTP: nepavyko prisijungti ($errno $errstr)");
        return false;
    }
    stream_set_timeout($fp, 15);

    $read = function () use ($fp): string {
        $data = '';
        while (($line = fgets($fp, 515)) !== false) {
            $data .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') break; // paskutinė atsakymo eilutė
        }
        return $data;
    };
    $cmd = function (?string $line, array $okCodes) use ($fp, $read): bool {
        if ($line !== null) fwrite($fp, $line . "\r\n");
        $reply = $read();
        $ok = in_array((int)substr($reply, 0, 3), $okCodes, true);
        if (!$ok) error_log('contact.php SMTP: netikėtas atsakymas: ' . trim($reply));
        return $ok;
    };

    // Eilutės CRLF, o taškas eilutės pradžioje dubliuojamas (SMTP reikalavimas)
    $text = preg_replace('/^\./m', '..', str_replace(["\r\n", "\r"], "\n", $body));
    $message = implode("\r\n", array_merge(["To: <$to>", "Subject: $subject"], $headers))
             . "\r\n\r\n" . str_replace("\n", "\r\n", $text);

    $ok = $cmd(null, [220])
       && $cmd('EHLO novarodigital.lt', [250])
       && $cmd('AUTH LOGIN', [334])
       && $cmd(base64_encode($cfg['user']), [334])
       && $cmd(base64_encode($cfg['pass']), [235])
       && $cmd('MAIL FROM:<' . $cfg['user'] . '>', [250])
       && $cmd("RCPT TO:<$to>", [250, 251])
       && $cmd('DATA', [354])
       && $cmd($message . "\r\n.", [250]);

    $cmd('QUIT', [221]);
    fclose($fp);
    return $ok;
}

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

function respond(int $code, bool $ok, string $message): void {
    $wantsJson = stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;
    if (!$wantsJson) {
        // Jei JavaScript išjungtas – grąžiname lankytoją atgal į puslapį
        header('Location: /?sent=' . ($ok ? '1' : '0') . '#kontaktai', true, 303);
        exit;
    }
    http_response_code($code);
    echo json_encode(['ok' => $ok, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    respond(405, false, 'Netinkama užklausa.');
}

// Botų patikrinimai: paslėptas laukas turi būti tuščias, forma neturi būti užpildyta per greitai
$started = (int)($_POST['form_started'] ?? 0);
if (!empty($_POST['website']) || ($started > 0 && time() - $started < $MIN_SECONDS)) {
    respond(200, true, 'Ačiū! Netrukus susisieksime.'); // botui apsimetame, kad pavyko
}

// Paprastas apribojimas: ne dažniau nei kartą per 30 s iš to paties IP
$ipKey = sys_get_temp_dir() . '/contact_' . md5($_SERVER['REMOTE_ADDR'] ?? '');
if (is_file($ipKey) && time() - filemtime($ipKey) < 30) {
    respond(429, false, 'Palaukite kelias sekundes ir bandykite dar kartą.');
}

$cleanText = fn($value, int $max) => mb_substr(trim(str_replace(["\r", "\0"], '', strip_tags(is_string($value) ? $value : ''))), 0, $max);
$clean     = fn(string $key, int $max) => $cleanText($_POST[$key] ?? '', $max);
$oneLine   = fn(string $value) => str_replace("\n", ' ', $value);

// Vardas, telefonas ir kt. patenka į laiško antraštes ar eilutes, todėl naujos eilutės draudžiamos
$name    = $oneLine($clean('name', 100));
$email   = filter_var(trim(is_string($_POST['email'] ?? null) ? $_POST['email'] : ''), FILTER_VALIDATE_EMAIL);
$phone   = $oneLine($clean('phone', 40));
$company = $oneLine($clean('company', 150));
$budget  = $oneLine($clean('budget', 50));
$message = $clean('message', 5000);

$services = [];
foreach (array_slice((array)($_POST['services'] ?? []), 0, 10) as $service) {
    $service = $oneLine($cleanText($service, 50));
    if ($service !== '') $services[] = $service;
}

if ($name === '' || !$email || $message === '') {
    respond(422, false, 'Užpildykite vardą, teisingą el. paštą ir žinutę.');
}

$subject = '=?UTF-8?B?' . base64_encode("Nauja užklausa iš svetainės: $name") . '?=';
$body = "Vardas: $name\n"
      . "El. paštas: $email\n"
      . ($phone !== '' ? "Telefonas: $phone\n" : '')
      . ($company !== '' ? "Įmonė / svetainė: $company\n" : '')
      . ($services ? 'Domina: ' . implode(', ', $services) . "\n" : '')
      . ($budget !== '' ? "Reklamos biudžetas: $budget\n" : '')
      . "\nŽinutė:\n$message\n\n"
      . "---\nIšsiųsta iš $SITE_NAME svetainės " . date('Y-m-d H:i');

$smtp = is_file($SMTP_CONFIG_FILE) ? include $SMTP_CONFIG_FILE : null;

if (is_array($smtp) && !empty($smtp['user']) && !empty($smtp['pass'])) {
    // Siuntėjas turi būti tas pats pašto dėžutės adresas, kuriuo prisijungiama prie SMTP
    $sent = smtp_send($smtp, $TO_EMAIL, $subject, $body, [
        'Date: ' . date('r'),
        'Message-ID: <' . bin2hex(random_bytes(12)) . '@novarodigital.lt>',
        "From: $SITE_NAME <{$smtp['user']}>",
        "Reply-To: $email",
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
    ]);
} else {
    $headers = implode("\r\n", [
        "From: $SITE_NAME <$FROM_EMAIL>",
        "Reply-To: $email",
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
    ]);
    $sent = mail($TO_EMAIL, $subject, $body, $headers, "-f$FROM_EMAIL");
}

if (!$sent) {
    respond(500, false, 'Nepavyko išsiųsti. Parašykite mums tiesiogiai el. paštu.');
}

@touch($ipKey);
respond(200, true, 'Ačiū! Gavome jūsų žinutę ir netrukus susisieksime.');
