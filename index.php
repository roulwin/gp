<?php
// index.php

require_once 'config.php';
require_once 'functions/db.php';
require_once 'functions/helpers.php';
require_once 'functions/telegram_api.php';

$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update) {
    exit;
}

$message = $update['message'] ?? $update['callback_query']['message'] ?? null;
$chat_id = $message['chat']['id'] ?? null;
$text = $update['message']['text'] ?? $update['callback_query']['data'] ?? '';
$user_id = $message['from']['id'] ?? null;
$username = $message['from']['username'] ?? '';
$is_group = isset($message['chat']['type']) && in_array($message['chat']['type'], ['group', 'supergroup']);

if ($user_id && !$is_group && !get_user($user_id)) {
    add_user($user_id, $username);
}

$main_admins = [123456789, 987654321];
$commercial_admins = [111222333];

if ($is_group) {
    $group_id = $chat_id;
    $group_year = estimate_group_year($group_id);
    $price = get_price_by_year($group_year);

    if ($price) {
        notify_admin_group_sale($commercial_admins[0], $group_id, $group_year, $price);
        send_message($group_id, "✅ Please transfer group ownership to the commercial admin now. Once done, click the verification button in the bot.");
        save_group_pending($group_id, $user_id, $group_year, $price);
    } else {
        send_message($group_id, "❌ No pricing defined for this group year ($group_year). Exiting group.");
        leave_group($group_id);
    }
    exit;
}

if (in_array($user_id, $main_admins)) {
    if ($text == '/admin') {
        send_admin_panel($chat_id);
        exit;
    }
    if (strpos($text, 'reply_') === 0) {
        [$prefix, $uid] = explode('_', $text);
        set_admin_reply_mode($user_id, $uid);
        send_message($chat_id, "✍️ Send your reply to user #$uid");
        exit;
    }
    if (is_admin_replying($user_id)) {
        $to_user = get_admin_reply_target($user_id);
        send_message($to_user, "📬 Support reply from admin: \n$text");
        clear_admin_reply_mode($user_id);
        send_message($chat_id, "✅ Message sent to user.");
        exit;
    }
    if ($text == 'Withdraw Requests') {
        $list = get_pending_withdrawals();
        foreach ($list as $req) {
            send_message($chat_id, "💳 User: {$req['user_id']} | Method: {$req['method']}\nWallet/Card: {$req['wallet_or_card']}\nAmount: [SET MANUALLY]\nClick to confirm:", [
                'inline_keyboard' => [[
                    ['text' => 'Confirm', 'callback_data' => 'confirm_withdraw_' . $req['id']]
                ]]
            ]);
        }
        exit;
    }
}

if (isset($update['callback_query']['data'])) {
    $cb = $update['callback_query']['data'];
    if (strpos($cb, 'confirm_withdraw_') === 0) {
        $wid = str_replace('confirm_withdraw_', '', $cb);
        confirm_withdraw_request($wid);
        send_message($chat_id, "✅ Withdrawal marked as completed.");
        exit;
    }
}

switch ($text) {
    case '/start':
        send_language_selection($chat_id);
        break;

    case 'English':
        set_user_language($user_id, 'en');
        send_main_menu($chat_id);
        break;

    case 'Account Info':
        send_account_info($chat_id, $user_id);
        break;

    case 'Pricing':
        send_pricing_info($chat_id);
        break;

    case 'Sell Group':
        send_sell_instructions($chat_id);
        break;

    case 'Withdraw':
        send_withdraw_options($chat_id);
        break;

    case 'Card':
    case 'TRX':
    case 'USDT':
        set_withdraw_method($user_id, $text);
        send_message($chat_id, "Please send your card number or wallet address:");
        break;

    case 'Support':
        prompt_user_support_message($chat_id);
        break;

    case 'Verify Ownership':
        if (verify_group_transfer($user_id)) {
            $added = credit_user_for_group($user_id);
            send_message($chat_id, $added ? "✅ Ownership verified. Amount added to your balance." : "❌ No valid group found for verification.");
        } else {
            send_message($chat_id, "❌ Ownership has not yet been transferred.");
        }
        break;

    default:
        if ($text && $text != '') {
            if (is_waiting_for_support($user_id)) {
                save_user_message($user_id, $text);
                foreach ($main_admins as $admin_id) {
                    send_message($admin_id, "📩 Support message from @$username: \n$text\nReply with: reply_$user_id");
                }
                send_message($chat_id, "Your message has been sent to support. They will reply soon.");
            } elseif (is_waiting_for_wallet($user_id)) {
                save_withdraw_request($user_id, $text);
                foreach ($main_admins as $admin_id) {
                    send_message($admin_id, "💸 New withdrawal request from @$username");
                }
                send_message($chat_id, "Your withdrawal request has been submitted and will be reviewed by an admin.");
            } else {
                send_message($chat_id, "Unknown command. Please use the menu.");
            }
        }
        break;
}
