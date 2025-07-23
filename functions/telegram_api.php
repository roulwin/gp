<?php
function send_message($chat_id, $text, $keyboard = null) {
    $url = API_URL . 'sendMessage';
    $payload = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => 'HTML'
    ];
    if ($keyboard) {
        $payload['reply_markup'] = json_encode($keyboard);
    }
    file_get_contents($url . '?' . http_build_query($payload));
}
