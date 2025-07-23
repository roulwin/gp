<?php
$text = $update['message']['text'] ?? '';
$chat_id = $update['message']['chat']['id'];
$user_id = $update['message']['from']['id'];

if ($text === 'Withdraw') {
    send_withdraw_options($chat_id);
} elseif (in_array($text, ['Card', 'TRX', 'USDT'])) {
    set_withdraw_method($user_id, $text);
    send_message($chat_id, "Please send your card number or wallet address:");
} elseif (is_waiting_for_wallet($user_id)) {
    save_withdraw_request($user_id, $text);
    send_message($chat_id, "Your withdrawal request has been submitted and will be reviewed by an admin.");
}
