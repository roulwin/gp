<?php
require_once __DIR__ . '/../config.php';

function get_db() {
    static $pdo;
    if (!$pdo) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
    }
    return $pdo;
}

function get_user($telegram_id) {
    $stmt = get_db()->prepare("SELECT * FROM users WHERE telegram_id = ?");
    $stmt->execute([$telegram_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function add_user($telegram_id, $username) {
    $stmt = get_db()->prepare("INSERT INTO users (telegram_id, username) VALUES (?, ?)");
    $stmt->execute([$telegram_id, $username]);
}

function set_user_language($telegram_id, $lang) {
    $stmt = get_db()->prepare("UPDATE users SET language = ? WHERE telegram_id = ?");
    $stmt->execute([$lang, $telegram_id]);
}

function save_group_pending($group_id, $owner_id, $year, $price) {
    $stmt = get_db()->prepare(\"INSERT INTO groups (group_id, owner_id, year, price, status) VALUES (?, ?, ?, ?, 'pending')\");
    $stmt->execute([$group_id, $owner_id, $year, $price]);
}

function verify_group_transfer($user_id) {
    // برای ساده‌سازی، فرض بر این است که اگر در جدول گروه ادمین تجاری ذخیره شده باشد یعنی انتقال انجام شده
    // در واقعیت باید با getChatAdministrators بررسی شود
    $stmt = get_db()->prepare(\"SELECT * FROM groups WHERE owner_id = ? AND status = 'pending'\");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ? true : false;
}

function credit_user_for_group($user_id) {
    $stmt = get_db()->prepare(\"SELECT * FROM groups WHERE owner_id = ? AND status = 'pending'\");
    $stmt->execute([$user_id]);
    $group = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$group) return false;

    // به‌روزرسانی وضعیت گروه
    $stmt = get_db()->prepare(\"UPDATE groups SET status = 'sold', ownership_transferred = 1 WHERE id = ?\");
    $stmt->execute([$group['id']]);

    // افزایش موجودی کاربر
    $stmt = get_db()->prepare(\"UPDATE users SET balance = balance + ?, groups_sold = groups_sold + 1 WHERE telegram_id = ?\");
    $stmt->execute([$group['price'], $user_id]);

    return true;
}

function is_waiting_for_wallet($user_id) {
    $stmt = get_db()->prepare(\"SELECT * FROM withdraw_requests WHERE user_id = ? AND status = 'awaiting_wallet' ORDER BY id DESC LIMIT 1\");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ? true : false;
}

function set_withdraw_method($user_id, $method) {
    $stmt = get_db()->prepare(\"INSERT INTO withdraw_requests (user_id, method, status) VALUES (?, ?, 'awaiting_wallet')\");
    $stmt->execute([$user_id, $method]);
}

function save_withdraw_request($user_id, $wallet) {
    $stmt = get_db()->prepare(\"UPDATE withdraw_requests SET wallet_or_card = ?, status = 'pending' WHERE user_id = ? AND status = 'awaiting_wallet'\");
    $stmt->execute([$wallet, $user_id]);
}
