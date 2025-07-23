<?php
if (isset($update['message']['text']) && $update['message']['text'] == 'English') {
    set_user_language($update['message']['from']['id'], 'en');
    send_main_menu($update['message']['chat']['id']);
}
