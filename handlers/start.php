<?php
if (isset($update['message']['text']) && $update['message']['text'] == '/start') {
    $user_id = $update['message']['from']['id'];
    $chat_id = $update['message']['chat']['id'];
    $username = $update['message']['from']['username'] ?? '';

    if (!get_user($user_id)) {
        add_user($user_id, $username);
    }

    send_language_selection($chat_id);
}
