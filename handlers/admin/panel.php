<?php
$admin_ids = [123456789, 987654321];
if (in_array($update['message']['from']['id'], $admin_ids) && $update['message']['text'] == '/admin') {
    $keyboard = [
        'keyboard' => [['Withdraw Requests']],
        'resize_keyboard' => true
    ];
    send_message($update['message']['chat']['id'], "Admin Panel:", $keyboard);
}
