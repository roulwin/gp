<?php
function send_language_selection($chat_id) {
    $keyboard = [
        'keyboard' => [['English']],
        'resize_keyboard' => true
    ];
    send_message($chat_id, "Please select your language:", $keyboard);
}

function send_main_menu($chat_id) {
    $keyboard = [
        'keyboard' => [
            ['Account Info', 'Pricing'],
            ['Sell Group', 'Withdraw'],
            ['Support']
        ],
        'resize_keyboard' => true
    ];
    send_message($chat_id, "Main Menu:", $keyboard);
}

function send_account_info($chat_id, $user_id) {
    $user = get_user($user_id);
    $msg = \"💳 Balance: {$user['balance']}\\n📈 Groups Sold: {$user['groups_sold']}\\n✅ Settled: {$user['settled_amount']}\";
    send_message($chat_id, $msg);
}

function send_pricing_info($chat_id) {
    $stmt = get_db()->query(\"SELECT year, price FROM prices ORDER BY year ASC\");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $msg = \"📅 Group Prices by Year:\\n\";
    foreach ($rows as $row) {
        $msg .= \"{$row['year']} → {$row['price']} USDT\\n\";
    }
    send_message($chat_id, $msg);
}

function send_sell_instructions($chat_id) {
    $msg = \"To sell a group, please add the bot to your Telegram group, promote it to admin, then click 'Verify Ownership'.\";
    send_message($chat_id, $msg);
}

function send_withdraw_options($chat_id) {
    $keyboard = [
        'keyboard' => [['Card', 'TRX', 'USDT']],
        'resize_keyboard' => true
    ];
    send_message($chat_id, \"Please select your withdrawal method:\", $keyboard);
}

function prompt_user_support_message($chat_id) {
    send_message($chat_id, \"Please type your message and our admins will get back to you soon:\");
    mark_user_waiting_for_support($chat_id);
}

function is_waiting_for_support($user_id) {
    $stmt = get_db()->prepare(\"SELECT status FROM messages WHERE user_id = ? ORDER BY id DESC LIMIT 1\");
    $stmt->execute([$user_id]);
    $msg = $stmt->fetch(PDO::FETCH_ASSOC);
    return $msg && $msg['status'] === 'waiting';
}

function save_user_message($user_id, $text) {
    $stmt = get_db()->prepare(\"INSERT INTO messages (user_id, message, status) VALUES (?, ?, 'waiting')\");
    $stmt->execute([$user_id, $text]);
}

function mark_user_waiting_for_support($user_id) {
    $stmt = get_db()->prepare(\"INSERT INTO messages (user_id, status) VALUES (?, 'waiting')\");
    $stmt->execute([$user_id]);
}

function estimate_group_year($group_id) {
    $timestamp = ($group_id >> 32) + 0x100000000;
    return date("Y", $timestamp);
}

function get_price_by_year($year) {
    $stmt = get_db()->prepare("SELECT price FROM prices WHERE year = ?");
    $stmt->execute([$year]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['price'] : null;
}

function notify_admin_group_sale($admin_id, $group_id, $year, $price) {
    send_message($admin_id, \"📣 New group listed for sale:\\nGroup ID: $group_id\\nYear: $year\\nPrice: $price USDT\");
}

function leave_group($group_id) {
    $url = API_URL . 'leaveChat';
    file_get_contents($url . '?' . http_build_query(['chat_id' => $group_id]));
}
