<?php

function idiary_search($queryarray, $andor, $limit, $offset, $userid)
{
    $_mb_enabled = function_exists('mb_ereg_replace') ? 1 : 0; // マルチバイト対応か否か

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

    if ('1' == $userid) {
        $LINES = @file($LOGS[0]);

        $i = 1;

        while (count($LINES) < $offset + $limit) {
            if ($i < count($LOGS)) {
                $_LINES = file($LOGS[$i]);

                $LINES = array_merge($LINES, $_LINES);

                $i++;
            } else {
                break;
            }
        }

        $LINES = array_slice($LINES, $offset, $limit);

        $j = 0;

        $ret = [];

        foreach ($LINES as $value) {
            [$date, $title, $com, $img, $w, $h, $fs] = explode('|', $value);

            $ret[$j]['image'] = 'images/search.png';

            $ret[$j]['link'] = 'index.php?mode=show&date=' . $date;

            $ret[$j]['title'] = $title;

            $ret[$j]['time'] = convert_time($date);

            $ret[$j]['uid'] = '1';

            $j++;
        }

        return $ret;
    }

    if ('' == $userid) {
        $LINES = [];

        foreach ($LOGS as $logs) {
            $_LINES = file($logs);

            foreach ($_LINES as $_lines) {
                $LINES = array_merge($LINES, $_lines);
            }
        }

        $j = 0;

        $ret = [];

        foreach ($LINES as $value) {
            [$date, $title, $com, $img, $w, $h, $fs] = explode('|', $value);

            $_value = $title . '|' . $com;

            if (!$andor) {
                $pre = $_value;
            }

            for ($i = 0, $iMax = count($queryarray); $i < $iMax; $i++) {
                if ($andor) {
                    $pre = $_value;
                }

                $_value = ($_mb_enabled) ? mb_preg_replace((string)$queryarray[$i], "<b>{$queryarray[$i]}</b>", $_value) : preg_replace((string)$queryarray[$i], "<b>{$queryarray[$i]}</b>", $_value);

                $post = $_value;

                if ($andor) {
                    if ($pre == $post) {
                        break 1;
                    }
                }
            }

            if ($pre != $post) {
                $d = explode('|', $post);

                $ret[$j]['image'] = 'images/search.png';

                $ret[$j]['link'] = 'index.php?mode=show&date=' . $date;

                $ret[$j]['title'] = $title;

                $ret[$j]['time'] = convert_time($date);

                $ret[$j]['uid'] = '1';

                $j++;
            }
        }

        $ret = array_slice($ret, $offset, $limit);

        return $ret;
    }
}

function convert_time($time)
{
    $day = $time % 100;

    $mon = (($time - $day) / 100) % 100;

    $year = ($time - $mon * 100 - $day) / 10000;

    return mktime(0, 0, 0, $mon, $day, $year);
}
