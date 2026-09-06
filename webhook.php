<?php
include "config.php";

// 1. Verification for Meta (only first time)
if (isset($_GET['hub_challenge'])) {
    echo $_GET['hub_challenge'];
    exit;
}

// 2. Get incoming message
$input = json_decode(file_get_contents("php://input"), true);
if (!isset($input['entry'][0]['changes'][0]['value']['messages'][0])) {
    exit; // not a message
}

$msg_data = $input['entry'][0]['changes'][0]['value']['messages'][0];
$from = $msg_data['from']; // e.g 237699111111
$text = trim($msg_data['text']['body'] ?? '');
$upper = strtoupper($text);

// 3. Log it
$db->query("INSERT INTO whatsapp_chats(phone_number, incoming_message) VALUES('$from', '$text')");

// 4. Parse command
$parts = explode(" ", $upper);
$command = $parts[0] ?? '';
$arg1 = $parts[1] ?? ''; // Matricule or Class

$reply = "";

if ($command == "FEES" && $arg1) {
    $bal = getFeeBalance($arg1);
    if ($bal) {
        $stu = $db->query("SELECT full_name, class_id FROM students WHERE matricule='$arg1'")->fetch_assoc();
        $class = $db->query("SELECT class_name FROM classes WHERE id={$stu['class_id']}")->fetch_assoc()['class_name'];
        $reply = "📚 EDUCONNECT\nName: {$stu['full_name']}\nClass: $class\nMatricule: $arg1\n\n💰 Total: {$bal['total']} FCFA\n✅ Paid: {$bal['paid']} FCFA\n❌ Balance: {$bal['balance']} FCFA\n\nPay to MoMo: 6 99 XX XX XX";
    } else {
        $reply = "❌ Matricule $arg1 not found. Check and try again. Ex: FEES SEC-001";
    }
} elseif ($command == "RESULT" && $arg1) {
    $res = $db->query("SELECT s.full_name, r.term, sub.subject_name, r.score
                       FROM results r
                       JOIN students s ON r.matricule=s.matricule
                       JOIN subjects sub ON r.subject_id=sub.id
                       WHERE r.matricule='$arg1' ORDER BY r.term DESC LIMIT 5");
    if ($res->num_rows > 0) {
        $reply = "📊 RESULT FOR $arg1\n";
        while ($row = $res->fetch_assoc()) {
            $reply .= "{$row['subject_name']} ({$row['term']}): {$row['score']}/20\n";
        }
        $avg = $db->query("SELECT AVG(score) as avg FROM results WHERE matricule='$arg1'")->fetch_assoc()['avg'];
        $reply .= "\nAverage: " . round($avg, 2) . "/20";
    } else {
        $reply = "❌ No results found for $arg1";
    }
} elseif ($command == "ABSENCE" && $arg1) {
    $att = $db->query("SELECT attendance_date, period_label, status FROM attendance WHERE matricule='$arg1' AND status!='present' ORDER BY attendance_date DESC LIMIT 3");
    if ($att->num_rows > 0) {
        $reply = "⚠️ ABSENCE FOR $arg1\n";
        while ($a = $att->fetch_assoc()) {
            $reply .= "{$a['attendance_date']} {$a['period_label']}: {$a['status']}\n";
        }
    } else {
        $reply = "✅ No absence recorded for $arg1. Good conduct.";
    }
} elseif ($command == "TIMETABLE" && $arg1) {
    // arg1 is class name like 3E or L2
    $class = $db->query("SELECT id FROM classes WHERE class_name LIKE '%$arg1%' LIMIT 1")->fetch_assoc();
    if ($class) {
        $tt = $db->query("SELECT t.day_of_week, t.start_time, sub.subject_name FROM timetable t JOIN subjects sub ON t.subject_id=sub.id WHERE t.class_id={$class['id']} ORDER BY FIELD(t.day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'), t.start_time");
        $reply = "🗓 TIMETABLE $arg1\n";
        while ($row = $tt->fetch_assoc()) {
            $reply .= "{$row['day_of_week']} {$row['start_time']}: {$row['subject_name']}\n";
        }
    } else {
        $reply = "❌ Class $arg1 not found. Try: TIMETABLE 3e or TIMETABLE L2";
    }
} elseif ($command == "HELP") {
    $reply = "🤖 EDUCONNECT COMMANDS:\n\nFEES [MATRICULE] - Check balance\nEx: FEES SEC-001\n\nRESULT [MATRICULE] - Check results\nEx: RESULT UNIV-001\n\nABSENCE [MATRICULE] - Check absences\nEx: ABSENCE SEC-001\n\nTIMETABLE [CLASS] - Get timetable\nEx: TIMETABLE 3e\n\nHELP - Show this";
} else {
    $reply = "❓ Unknown command. Send HELP to see all commands.";
}

// 5. Send reply
sendWhatsApp($from, $reply);
$db->query("UPDATE whatsapp_chats SET bot_reply='$reply' WHERE phone_number='$from' ORDER BY id DESC LIMIT 1");

echo "ok";
