<?php
if (isset($update['message']['text']) && $update['message']['text'] == 'Sell Group') {
    send_sell_instructions($update['message']['chat']['id']);
}
