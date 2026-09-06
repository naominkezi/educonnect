<?php
// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "educonnect_db";

$db = new mysqli($host, $user, $pass, $dbname);
if ($db->connect_error) {
    die("DB Error: " . $db->connect_error);
}

// WhatsApp Config - Get FREE from developers.facebook.com
$WHATSAPP_TOKEN = "PUT_YOUR_TOKEN_HERE"; // EAAxxxxxxxxx
$PHONE_ID = "PUT_YOUR_PHONE_ID_HERE"; // 123456789

// Function to send WhatsApp reply
function sendWhatsApp($to, $message)
{
    global $WHATSAPP_TOKEN, $PHONE_ID;
    $url = "https://graph.facebook.com/v19.0/$PHONE_ID/messages";

    $data = [
        "messaging_product" => "whatsapp",
        "to" => $to,
        "type" => "text",
        "text" => ["body" => $message]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $WHATSAPP_TOKEN",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

// Function to calculate fees balance
function getFeeBalance($matricule)
{
    global $db;
    $student = $db->query("SELECT institution_id, class_id FROM students WHERE matricule='$matricule'")->fetch_assoc();
    if (!$student) return null;

    $total = $db->query("SELECT SUM(amount) as total FROM fee_structure WHERE class_id={$student['class_id']}")->fetch_assoc()['total'] ?? 0;
    $paid = $db->query("SELECT SUM(amount_paid) as paid FROM fee_payments WHERE matricule='$matricule'")->fetch_assoc()['paid'] ?? 0;

    return ["total" => $total, "paid" => $paid, "balance" => $total - $paid];
}
