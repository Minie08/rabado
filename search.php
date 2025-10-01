<?php
/**
 * @var db $db
 */

require "settings/init.php";
header('Content-Type: application/json');
error_reporting(0);

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
if ($q === '') {
    echo json_encode([]);
    exit;
}

$nu = date('Y-m-d');

$rabatkoder = $db->sql("
    SELECT r.id, r.rabaTitel AS titel, r.rabaKode AS kode, r.rabaBeskrivelse AS beskrivelse, v.virkNavn, v.virkLogo, v.virkLink, 'rabat' AS type
    FROM rabatkoder r
    JOIN virksomheder v ON r.virkId = v.id
    WHERE (r.rabaTitel LIKE :q OR v.virkNavn LIKE :q)
    AND r.rabaStart <= :nu AND r.rabaUdloeb >= :nu LIMIT 10", [":q" => "%$q%", ":nu" => $nu]);

$tilbud = $db->sql("
    SELECT t.id, t.tilbTitel AS titel, NULL AS kode, t.tilbBeskrivelse AS beskrivelse, v.virkNavn, v.virkLogo, v.virkLink, 'tilbud' AS type
    FROM tilbud t
    JOIN virksomheder v ON t.virkId = v.id
    WHERE (t.tilbTitel LIKE :q OR v.virkNavn LIKE :q)
    AND t.tilbStart <= :nu AND t.tilbUdloeb >= :nu LIMIT 10", [":q" => "%$q%", ":nu" => $nu]);

echo json_encode(array_merge($rabatkoder, $tilbud));
