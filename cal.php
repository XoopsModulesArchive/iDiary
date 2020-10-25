<?php

class Holiday
{
    public $holidays;     // yyyymmdd=>name 形式
    public $myholidays;   //       同　上

    public function __construct()
    { //インスタンス
        //  $year = date("Y");

        $_qs = $_SERVER['QUERY_STRING'];

        if (isset($_GET['date'])) {
            $today = getdate(strtotime($_GET['date']));
        } elseif (isset($_GET['archives'])) {
            $today = getdate(strtotime($_GET['archives'] . '01'));
        } elseif (preg_match('^[0-9]{8}', $_qs)) {
            $today = getdate(strtotime($_qs));
        } else {
            $today = getdate();
        }

        $year = $today['year'];

        $this->add((string)$year);

        $this->setHoliday($year);
    }

    public function holidayList($y)
    { // デフォルトの祝祭日のリストを作成（ハッピーマンデー対応）
        $this->holidays = [
            $y . '0101'                                                      => '元日',
$y . $this->getMonday($y, 1, 2)                                              => '成人の日',
$y . '0211'                                                                  => '建国記念の日',
$y . '03' . (int)(20.8431 + 0.242194 * ($y - 1980) - (int)(($y - 1980) / 4)) => '春分の日',
$y . '0429'                                                                  => 'みどりの日',
$y . '0503'                                                                  => '憲法記念日',
$y . '0504'                                                                  => '国民の休日',
$y . '0505'                                                                  => 'こどもの日',
$y . $this->getMonday($y, 7, 3)                                              => '海の日',
$y . $this->getMonday($y, 9, 2)                                              => '敬老の日',
$y . '09' . (int)(23.2488 + 0.242194 * ($y - 1980) - (int)(($y - 1980) / 4)) => '秋分の日',
$y . $this->getMonday($y, 10, 2)                                             => '体育の日',
$y . '1103'                                                                  => '文化の日',
$y . '1123'                                                                  => '勤労感謝の日',
$y . '1223'                                                                  => '天皇誕生日',
        ];

        return $this->holidays;
    }

    public function setHoliday($y)
    { // 振替休日や追加祝日も考慮した祝日リストを作成
        $this->holidayList($y);

        foreach ($this->holidays as $key => $v) {
            $y = mb_substr($key, 0, 4);

            $m = mb_substr($key, 4, 2);

            $d = mb_substr($key, 6, 2);

            if ('0' == date('w', mktime(0, 0, 0, $m, $d, $y))) {
                $this->holidays[date('Ymd', mktime(0, 0, 0, $m, $d + 1, $y))] = '振替休日';
            }
        }

        if (isset($this->myholidays)) {
            foreach ($this->myholidays as $key => $v) {
                if (array_key_exists($key, $this->holidays)) {
                    $v .= ' ' . $this->holidays[$key];
                }

                $this->holidays[$key] = $v;
            }
        }

        ksort($this->holidays, SORT_NUMERIC);

        return $this->holidays;
    }

    public function add($y)
    { // 記念日の追加
        global $xoopsDB;

        $this->myholidays = [];

        $query = 'SELECT *  FROM ' . $xoopsDB->prefix('idiary_myholiday') . ' ORDER BY myholiday';

        $result = $xoopsDB->query($query);

        while (false !== ($addholiday = $xoopsDB->fetchArray($result))) {
            $this->myholidays[$y . $addholiday['myholiday']] = $addholiday['content'];
        }

        return $this->myholidays;
    }

    public function getMonday($y, $m, $wk)
    { // 第wk週目の月曜日の日付を返す
        $d = (7 - date('w', mktime(0, 0, 0, $m, 1, $y))) % 7 + 7 * $wk - 5;

        return sprintf('%02d', $m) . sprintf('%02d', $d);
    }
}

$hd = new Holiday();

function checkHoliday($date)
{
    global $hd;

    if (isset($hd->holidays[$date])) {
        return 1;
    }

    return 0;
}

$F_month = [// 連想配列に月名をいれとく
            1 => '1',
            '2',
            '3',
            '4',
            '5',
            '6',
            '7',
            '8',
            '9',
            '10',
            '11',
            '12',
];

$_qs = $_SERVER['QUERY_STRING'];

if (isset($_GET['date'])) {
    $today = getdate(strtotime($_GET['date']));
} elseif (isset($_GET['archives'])) {
    $today = getdate(strtotime($_GET['archives'] . '01'));
} elseif (preg_match('^[0-9]{8}', $_qs)) {
    $today = getdate(strtotime($_qs));
} else {
    $today = getdate();
}

$prev = date('Ymd', mktime(0, 0, 0, $today['mon'], 0, $today['year']));
$next = date('Ymd', mktime(0, 0, 0, $today['mon'] + 1, 1, $today['year']));

function selectMonth($_month, $url = 'index.php')
{  // 月のセレクトメニュー
    global $F_month, $today;

    $s = " <select name=\"sel_month\" onchange=\"window.location=(this.options[this.selectedIndex].value)\">\n";

    foreach ($F_month as $i => $value) {
        $s .= "  <option value=\"$url?date=" . $today['year'] . sprintf('%02d', $i) . '01"';

        $s .= ($_month == $i) ? ' selected' : '';

        $s .= ">$value\n";
    }

    $s .= " </select>\n月<br>\n";

    return $s;
}

function selectYear($yearSelected = 2005, $yearStart = 2005, $yearEnd = 2010, $url = 'index.php')
{  // 西暦のセレクトメニュー
    global $today;

    $yearStart = ($yearSelected < $yearStart) ? $yearSelected : $yearStart;

    $s = " <select name=\"sel_year\" onchange=\"window.location=(this.options[this.selectedIndex].value)\">\n";

    for ($i = $yearStart; $i <= $yearEnd; $i++) {
        $s .= "  <option value=\"$url?date=$i" . sprintf('%02d', $today['mon']) . '01"';

        if ($i == $yearSelected) {
            $s .= ' selected';
        }

        $s .= "> $i\n";
    }

    $s .= " </select>\n年";

    return $s;
}

function selectForm($month, $year, $yearStart, $yearEnd)
{
    $h = '<div style="margin-bottom:-15px;">
  <form name="goto" action="index.php" method="post" >
 ';

    $h .= selectYear($year, $yearStart, $yearEnd);

    $h .= selectMonth($month);

    $h .= '
  </form>
 </div>';

    return $h;
}

function Calender($month, $year)
{                // このスクリプトのキモ
    global $F_month, $prev, $next, $today, $hd, $_self, $adminmode;

    if (file_exists(LOG . $year . sprintf('%02d', $month) . '.log')) {
        $LINES = file(LOG . $year . sprintf('%02d', $month) . '.log');
    } else {
        $LINES = [];
    }

    $h = "\n<table class=\"calendar\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">\n";

    if ($month < 1) {
        $month = 12 - $month;

        $year--;
    } elseif ($month > 12) {
        $month -= 12;

        $year++;
    }

    $day = 1;

    $dayFirst = getdate(mktime(0, 0, 0, $month, $day, $year));

    $wdayFirst = $dayFirst['wday'];

    $h .= '
   <tr><th colspan="7">' . selectForm($today['mon'], $today['year'], $today['year'] - 5, $today['year'] + 5) . '</th></tr>
   <tr>
    <td><a href="?' . $prev . "\"><img src=\"images/prev.png\" border=\"0\" title=\"先月を表示\" width=\"15\" height=\"16\"></a></td>
    <td colSpan=\"5\">
    <b class=month>  $year 年  $F_month[$month] 月</b>
    </td>
    <td><a href=\"?" . $next . '"><img src="images/next.png" border="0" title="来月を表示" width="15" height="16"></a></td>
   </tr>
   <tr class="header">
    <td><span class="Sun">日</span></td>
    <td>月</td>
    <td>火</td>
    <td>水</td>
    <td>木</td>
    <td>金</td>
    <td><span class="Sat">土</span></td>
   </tr>
   <tr>
 ';

    for ($i = 0; $i < $wdayFirst; $i++) {  // first week
        $h .= '<td>&nbsp;</td>';
    }

    //Initialize

    $mark = '';

    // modified by HAL

    while (checkdate($month, $day, $year)) {
        $link = sprintf('%4d%02d%02d', $year, $month, $day);

        $h .= '<td';

        foreach ($LINES as $i => $value) {
            [$date, $title] = explode('|', $value);

            if ($date == $link) {
                $mark = "mode=show&amp;date=$date";

                $h .= " style='background:" . EVENTCOL . ";' title='" . $title . "'";

                break;
            }

            $mark = '';
        }

        $h .= '>';

        if ('' != $mark) {
            $_day = ($day == date('j', time()) && $month == date('n', time()) && $year == date('Y', time())) ? "<span class=\"Today\">$day</span>" : $day;

            $h .= "<a href=\"$_self?$mark\" class=\"event\">$_day</a></td>\n";
        } else {
            if ($day == date('j', time()) && $month == date('n', time()) && $year == date('Y', time())) {
                if ('1' == $adminmode) {
                    $h .= "<a href=\"$_self?mode=write&amp;date=$link\"><span class=\"Today\">$day</span></a></td>\n";
                } else {
                    $h .= "<span class=\"Today\">$day</span></td>\n";
                }
            } elseif (0 == $wdayFirst || checkHoliday($link)) {                     // Sunday or Holiday
                if (checkHoliday($link)) {
                    if ('1' == $adminmode) {
                        $h .= "<a href=\"$_self?mode=write&amp;date=$link\" title=\"" . $hd->holidays[$link] . "\"><span class=\"Sun\">$day</span></a></td>\n";
                    } else {
                        $h .= '<span title="' . $hd->holidays[$link] . "\" class=\"Sun\">$day</span></td>\n";
                    }
                } else {
                    if ('1' == $adminmode) {
                        $h .= "<a href=\"$_self?mode=write&amp;date=$link\"><span class=\"Sun\">$day</span></a></td>\n";
                    } else {
                        $h .= "<span class=\"Sun\">$day</span></td>\n";
                    }
                }
            } elseif (6 == $wdayFirst) {                                                        // Saturday
                if ('1' == $adminmode) {
                    $h .= "<a href=\"$_self?mode=write&amp;date=$link\"><span class=\"Sat\">$day</span></a></td>\n";
                } else {
                    $h .= "<span class=\"Sat\">$day</span></td>\n";
                }
            } else {                                                                          // weekday
                if ('1' == $adminmode) {
                    $h .= "<a href=\"$_self?mode=write&amp;date=$link\">$day</a></td>\n";
                } else {
                    $h .= "$day</td>\n";
                }
            }
        }

        $day++;

        $wdayFirst++;

        if (0 == ($wdayFirst % 7)) {
            $h .= "</tr>\n";

            $h .= ($day / 7 < 4) ? "<tr>\n" : '';

            $wdayFirst = 0;
        }
    }

    while ($wdayFirst < 7 && 0 != $wdayFirst) { // Blank
        $h .= '<td>&nbsp;</td>';

        $wdayFirst++;
    }

    $h .= ($day / 7 < 4) ? "</tr>\n" : '';

    $h .= "</table>\n";

    return $h;
}
