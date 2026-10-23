<?php
// Kontaktų formos apdorojimas: patikrina duomenis ir išsiunčia laišką jums.

// ========== KEISTI ==========
$TO_EMAIL   = 'info@novarodigital.lt';    // kur gausite užklausas
$FROM_EMAIL = 'noreply@novarodigital.lt'; // turi būti jūsų domeno adresas, kitaip laiškai pateks į šlamštą
$SITE_NAME  = 'Novaro Digital';
$MIN_SECONDS = 3;                          // greičiau užpildyta forma laikoma botu
// ============================

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

$headers = implode("\r\n", [
    "From: $SITE_NAME <$FROM_EMAIL>",
    "Reply-To: $email",
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
    'Content-Transfer-Encoding: 8bit',
]);

if (!mail($TO_EMAIL, $subject, $body, $headers, "-f$FROM_EMAIL")) {
    respond(500, false, 'Nepavyko išsiųsti. Parašykite mums tiesiogiai el. paštu.');
}

@touch($ipKey);
respond(200, true, 'Ačiū! Gavome jūsų žinutę ir netrukus susisieksime.');
