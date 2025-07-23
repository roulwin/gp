<?php
if (isset($update['message']['text']) && $update['message']['text'] == 'Account Info') {
    send_account_info($update['message']['chat']['id'], $update['message']['from']['id']);
}
