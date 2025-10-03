<?php
/**
 * @var db $db
 */
require "settings/init.php";

header("Content-Type: application/json");

if (!empty($_POST)) {
    $rabaTitel       = trim($_POST['rabaTitel'] ?? '');
    $virkNavn        = trim($_POST['virkNavn'] ?? '');
    $virkLink        = trim($_POST['virkLink'] ?? '');
    $virkLogo        = trim($_POST['virkLogo'] ?? '');
    $rabaBeskrivelse = trim($_POST['rabaBeskrivelse'] ?? '');
    $rabaKode        = trim($_POST['rabaKode'] ?? '');
    $rabaSats        = trim($_POST['rabaSats'] ?? null);
    $rabaStart       = $_POST['rabaStart'] ?: null;
    $rabaUdloeb      = $_POST['rabaUdloeb'] ?: null;
    $kateId          = intval($_POST['kateId'] ?? 0);

    if (!$rabaTitel || !$virkNavn || !$rabaBeskrivelse || !$rabaKode || !$kateId) {
        echo json_encode([
            "success" => false,
            "error" => "Manglende obligatoriske felter."
        ]);
        exit;
    }

    // Find eller opret ny virksomhed //
    try {
        $virk = $db->sql("SELECT id FROM virksomheder WHERE virkNavn = :navn", [":navn" => $virkNavn]);
        if (!empty($virk)) {
            $virkId = $virk[0]->id;
        } else {
            $db->sql("INSERT INTO virksomheder (virkNavn, virkLink, virkLogo) VALUES (:navn, :link, :logo)", [
                ":navn" => $virkNavn,
                ":link" => $virkLink,
                ":logo" => $virkLogo
            ]);
            $virkId = $db->lastInsertId();
        }

        $db->sql("
            INSERT INTO rabatkoder 
            (rabaBillede, rabaTitel, virkId, rabaBeskrivelse, rabaKode, rabaSats, rabaStart, rabaUdloeb, kateId) 
            VALUES 
            (:rabaBillede, :rabaTitel, :virkId, :rabaBeskrivelse, :rabaKode, :rabaSats, :rabaStart, :rabaUdloeb, :kateId)
        ", [
            ":rabaBillede"     => "-",
            ":rabaTitel"       => $rabaTitel,
            ":virkId"          => $virkId,
            ":rabaBeskrivelse" => $rabaBeskrivelse,
            ":rabaKode"        => $rabaKode,
            ":rabaSats"        => $rabaSats,
            ":rabaStart"       => $rabaStart,
            ":rabaUdloeb"      => $rabaUdloeb,
            ":kateId"          => $kateId
        ]);

        echo json_encode([
            "success" => true,
            "rabatkode" => [
                "rabaTitel"       => $rabaTitel,
                "rabaBeskrivelse" => $rabaBeskrivelse,
                "rabaKode"        => $rabaKode,
                "rabaSats"        => $rabaSats,
                "rabaStart"       => $rabaStart,
                "rabaUdloeb"      => $rabaUdloeb,
                "virkId"          => $virkId,
                "kateId"          => $kateId,
                "virkNavn"        => $virkNavn,
                "virkLink"        => $virkLink,
                "virkLogo"        => $virkLogo
            ]
        ]);

    } catch (Exception $e) {
        echo json_encode([
            "success" => false,
            "error" => $e->getMessage()
        ]);
    }
    exit;
}

echo json_encode([
    "success" => false,
    "error" => "Ingen data modtaget."
]);