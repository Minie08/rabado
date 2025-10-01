<?php
/**
 * @var db $db
 */
require "settings/init.php";
header('Content-Type: application/json');

$kategoriId = isset($_GET['kategori']) ? intval($_GET['kategori']) : null;
$type = isset($_GET['type']) ? $_GET['type'] : null;

$nu = date('Y-m-d');

$rabatkoder = [];
$tilbud = [];

// Hent rabatkoder hvis kategori eller type matcher
if (!$type || $type === "rabatkoder") {
    $sql = "SELECT r.*, v.virkNavn, v.virkLogo, v.virkLink, k.kateNavn 
            FROM rabatkoder r
            JOIN virksomheder v ON r.virkId = v.id
            JOIN kategorier k ON r.kateId = k.id
            WHERE r.rabaStart <= :nu AND r.rabaUdloeb >= :nu";
    $params = [":nu" => $nu];

    if ($kategoriId) {
        $sql .= " AND r.kateId = :kateId";
        $params[":kateId"] = $kategoriId;
    }

    $rabatkoder = $db->sql($sql, $params);
}

// Hent tilbud hvis kategori eller type matcher
if (!$type || $type === "tilbud") {
    $sql = "SELECT t.*, v.virkNavn, v.virkLogo, v.virkLink, k.kateNavn 
            FROM tilbud t
            JOIN virksomheder v ON t.virkId = v.id
            JOIN kategorier k ON t.kateId = k.id
            WHERE t.tilbStart <= :nu AND t.tilbUdloeb >= :nu";
    $params = [":nu" => $nu];

    if ($kategoriId) {
        $sql .= " AND t.kateId = :kateId";
        $params[":kateId"] = $kategoriId;
    }

    $tilbud = $db->sql($sql, $params);
}

// Marker type for JS
$rabatkoder = array_map(function($r) {
    $r->type = "rabat";
    return $r;
}, $rabatkoder);

$tilbud = array_map(function($t) {
    $t->type = "tilbud";
    return $t;
}, $tilbud);

// Returner samlet array
echo json_encode(array_merge($rabatkoder, $tilbud));
