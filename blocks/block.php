<?php

function b_idiary_block($options)
{
    // オプション情報を取得する

    $show_num = $options[0];

    // 重複チェック後定義

    if (!defined('LOG')) {
        define('LOG', XOOPS_ROOT_PATH . '/modules/iDiary/log/');
    }

    $LOGS = [];

    $d = dir(LOG);  // ログディレクトリの走査(*.logをカウント)

    while (false !== ($file = $d->read())) {
        preg_match('/\.[^.]*$/i', $file, $matches);

        if ('.log' == $matches[0]) {
            $LOGS[] = LOG . $file;
        }
    }

    $d->close();

    rsort($LOGS); // 日付の新しい順に

    reset($LOGS);

    $LINES = @file($LOGS[0]);

    $i = 1;

    while (count($LINES) < $show_num) {
        if ($i < count($LOGS)) {
            $_LINES = file($LOGS[$i]);

            $LINES = array_merge($LINES, $_LINES);

            $i++;
        } else {
            break;
        }
    }

    array_slice($LINES, 0, $show_num);

    $block = [];

    for ($i = 0; $i < $show_num; $i++) {
        [$date, $title, $com, $img, $w, $h, $fs] = explode('|', $LINES[$i]);

        $block['date'][] = format_date($date);

        $block['title'][] = $title;

        $block['url'][] = XOOPS_URL . '/modules/iDiary/index.php?mode=show&date=' . $date;
    }

    return $block;
}

function b_idiary_block_edit($options)
{
    // 表示件数

    $form = "何件表示しますか？ <input type='text' name='options[0]' value='" . $options[0] . "'>";

    return $form;
}

function format_date($date)
{
    $day = $date % 100;

    $mon = (($date - $day) / 100) % 100;

    $year = ($date - $mon * 100 - $day) / 10000;

    return sprintf('%04d年%02d月%02d日', $year, $mon, $day);
}
