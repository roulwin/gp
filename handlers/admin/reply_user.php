<?php
$text = $update['message']['text'] ?? '';
$admin_id = $update['message']['from']['id'];
$chat_id = $update['message']['chat']['id'];

if (strpos($text, 'reply_') === 0) {
    [$prefix, $uid] = explode('_', $text);
    set_admin_reply_mode($admin_id, $uid);
    send_message($chat_id, "✍️ Send your reply to user ID: $uid");
} elseif (is_admin_replying($admin_id)) {
    $target = get_admin_reply_target($admin_id);
    send_message($target, "📬 Support reply: \n" . $text);
    clear_admin_reply_mode($admin_id);
    send_message($chat_id, "✅ Sent to user.");
}
