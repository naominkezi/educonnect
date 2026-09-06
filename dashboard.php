<?php
include "../config.php";
$chats = $db->query("SELECT * FROM whatsapp_chats ORDER BY id DESC LIMIT 50");
?>
<h2>WhatsApp Requests</h2>
<table border="1" cellpadding="5">
    <tr>
        <th>Phone</th>
        <th>Message</th>
        <th>Reply</th>
        <th>Date</th>
    </tr>
    <?php while ($c = $chats->fetch_assoc()) { ?>
        <tr>
            <td><?= $c['phone_number'] ?></td>
            <td><?= $c['incoming_message'] ?></td>
            <td><?= $c['bot_reply'] ?></td>
            <td><?= $c['created_at'] ?></td>
        </tr>
    <?php } ?>
</table>