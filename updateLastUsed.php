<?php
/**
 * @var db $db
 */
require "settings/init.php";

if (!empty($_POST['id'])) {
    $id = intval($_POST['id']);

    $db->sql("UPDATE rabatkoder SET rabaAnvendt = NOW() WHERE id = :id", [":id" => $id]);

    $row = $db->sql("SELECT rabaAnvendt FROM rabatkoder WHERE id = :id", [":id" => $id])[0];
    $timestamp = strtotime($row->rabaAnvendt);

    echo json_encode([
        "success" => true,
        "timestamp" => $timestamp
    ]);
    exit;
}

echo json_encode(["success" => false]);
