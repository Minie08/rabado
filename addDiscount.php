<?php
/**
 * @var db $db
 */
require "settings/init.php";

header("Content-Type: application/json");

if (!empty($_POST)) {
    $data = [
        ":rabaBillede"    => "-", // evt. filupload senere
        ":rabaTitel"      => $_POST["rabaTitel"],
        ":virkId"         => $_POST["virkId"],
        ":rabaBeskrivelse"=> $_POST["rabaBeskrivelse"],
        ":rabaKode"       => $_POST["rabaKode"],
        ":rabaSats"       => $_POST["rabaSats"],
        ":rabaStart"      => $_POST["rabaStart"],
        ":rabaUdloeb"     => $_POST["rabaUdloeb"],
        ":kateId"         => $_POST["kateId"]
    ];

    try {
        $db->sql("
            INSERT INTO rabatkoder 
            (rabaBillede, rabaTitel, virkId, rabaBeskrivelse, rabaKode, rabaSats, rabaStart, rabaUdloeb, kateId)
            VALUES (:rabaBillede, :rabaTitel, :virkId, :rabaBeskrivelse, :rabaKode, :rabaSats, :rabaStart, :rabaUdloeb, :kateId)
        ", $data);

        // returner succes og den indsatte kode til JS
        echo json_encode([
            "success" => true,
            "rabatkode" => [
                "rabaTitel"       => $_POST["rabaTitel"],
                "rabaBeskrivelse" => $_POST["rabaBeskrivelse"],
                "rabaKode"        => $_POST["rabaKode"],
                "rabaSats"        => $_POST["rabaSats"],
                "rabaStart"       => $_POST["rabaStart"],
                "rabaUdloeb"      => $_POST["rabaUdloeb"],
                "virkId"          => $_POST["virkId"],
                "kateId"          => $_POST["kateId"]
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(["success" => false, "error" => $e->getMessage()]);
    }
    exit;
}