<?php
/*
*  SCRIPT NAME : i-Diary v2.0
* LAST UPDATED : Sun 26 Oct, 2005
*    COPYRIGHT : Masayuki AOKI http://martin.bz
* 上の表示をそのまま残す限り，自由に改変・再配布OKです。
*/

// iDiary for XOOPS2 Modularized by HAL
// http://www.adslnet.org/

include '../../mainfile.php';
include '../../header.php';

global $xoopsDB, $xoopsUser;
$table_name = $xoopsDB->prefix('idiary_config');
$query      = 'SELECT *  FROM ' . $table_name;
$result     = $xoopsDB->query($query);
if (!list($entry_list, $entry_box, $html_enabled, $archives, $eventcol, $max_fsize, $max_isize, $extensions) = $xoopsDB->fetchRow($result)) {
    //Initialize
    $query = 'INSERT INTO ' . $table_name . '(entry_list, entry_box, html_enabled, archives, eventcol, max_fsize, max_isize, extensions) VALUES(15, 5, 1, 1, "#E6E6F0", 200, 250, "jpeg|gif|png|jpg")';
    $xoopsDB->queryF($query);
    $query  = 'SELECT *  FROM ' . $table_name;
    $result = $xoopsDB->query($query);
    [$entry_list, $entry_box, $html_enabled, $archives, $eventcol, $max_fsize, $max_isize, $extensions] = $xoopsDB->fetchRow($result);
}

$query  = 'SELECT version FROM ' . $xoopsDB->prefix('modules') . ' WHERE dirname = "iDiary"';
$result = $xoopsDB->query($query);
[$version] = $xoopsDB->fetchRow($result);
$version /= 100;
/* ユーザー設定エリア だけど設定の必要なし？ */
// 画像ファイルを収納するディレクトリをhttp://で始まるURLで指定。
define('IMG_PATH', XOOPS_URL . '/modules/iDiary/img');
// 日記のログファイルを入れるフォルダ（スラッシュまで入れる）
// 重複チェック後定義
if (!defined('LOG')) {
    define('LOG', XOOPS_ROOT_PATH . '/modules/iDiary/log/');
}
/* ユーザー設定エリア ここまで */

define('ENTRY_LIST', $entry_list);
define('ENTRY_BOX', $entry_box);
define('HTML_ENABLED', $html_enabled);
define('ARCHIVES', $archives);
define('EVENTCOL', $eventcol);
define('MAX_FSIZE', $max_fsize);
define('MAX_ISIZE', $max_isize);
define('EXTENSIONS', $extensions);

define('IMG_DIR', array_pop(explode('/', IMG_PATH))); // UPするディレクトリ名を抽出。
if (!is_dir(IMG_DIR)) {
    error('画像を入れるディレクトリがないようです。設定を確認して下さい。');
}
define('PATH', dirname($_SERVER['SCRIPT_FILENAME']) . '/' . IMG_DIR . '/'); // ルートパスを取得

$maxsize     = 1024 * MAX_FSIZE;        // 上限サイズをKBからバイトに直しておく
$_self       = 'index.php';           // このファイルの名前
$_mb_enabled = function_exists('mb_ereg_replace') ? 1 : 0; // マルチバイト対応か否か

$mode = isset($_REQUEST['mode']) ? strtolower($_REQUEST['mode']) : '';

// AdminCheck
$adminmode = '0';
if (is_object($xoopsUser)) {
    if ($xoopsUser->isAdmin()) {
        $adminmode = '1';
    }
}
if (($mode == 'write' || $mode == 'edit' || $mode == 'del' || $mode == 'update' || $mode == 'delete') && $adminmode == '0') {
    redirect_header('index.php', 3, _NOPERM);
}

$entry  = ($mode == 'list') ? ENTRY_LIST : ENTRY_BOX;
$date   = $_GET['date'] ?? ($_POST['date'] ?? 0);
$offset = $_GET['offset'] ?? 0;

$LOGS = [];
$d    = dir(LOG);  // ログディレクトリの走査(*.logをカウント)
while (false !== ($file = $d->read())) {
    preg_match('/\.[^.]*$/i', $file, $matches);
    if ($matches[0] == '.log') {
        $LOGS[] = LOG . $file;
    }
}
$d->close();
rsort($LOGS); // 日付の新しい順に
reset($LOGS);

$LINES = @file($LOGS[0]);
$i     = 1;
while (count($LINES) < $offset + max(ENTRY_LIST, ENTRY_BOX)) {
    if ($i < count($LOGS)) {
        $_LINES = file($LOGS[$i]);
        $LINES  = array_merge($LINES, $_LINES);
        $i++;
    } else {
        break;
    }
}

if ($mode == 'search' && isset($_GET['query'])) {
    $LINES = [];
    foreach ($LOGS as $logs) {
        $_LINES = file($logs);
        foreach ($_LINES as $_lines) {
            $LINES = array_merge($LINES, $_lines);
        }
    }
} elseif (isset($_GET['archives'])) {
    $LINES = file(LOG . $_GET['archives'] . '.log');
} elseif (isset($_POST['date'])) {
    $_date = substr($_POST['date'], 0, 6);
    if (!file_exists(LOG . $_date . '.log')) {
        mk_fl(LOG . $_date . '.log');
    }
    $LINES = file(LOG . $_date . '.log');
} elseif (is_array($LINES)) {
    $LINES = array_slice($LINES, $offset, $entry);
} // $entry分の配列をセット

require_once 'cal.php';
?>
<!-- Start of iDiary -->
<link rel="stylesheet" type="text/css" href="base.css">
<script type="text/javascript" src="script.js"></script>
<div id="iDiary">
    <div id="calendar">
        <?php
        echo <<<__EOF
 <div class="view">
  <a href="$_self?mode=list" class="alink" title=" リスト表示 " >&nbsp;view List </a>
  &nbsp;&nbsp;&nbsp;
  <a href="$_self?mode=box" class="alink" title=" ボックス表示 " >&nbsp;view Box </a>
  &nbsp;&nbsp;&nbsp;
  <a href="$_self?mode=gallery" class="alink" title=" 画像一覧 " >&nbsp;Image Gallery </a>
 </div>
__EOF;
        ?>
        <table>
            <tr>
                <td>
                    <?php
                    echo Calender($today['mon'], $today['year']);
                    ?>
                </td>
                <td>
                    <?php
                    if ($mode != 'archives' && ARCHIVES == 1 && count($LOGS) >= 1 && count(file($LOGS[0])) >= 1) {
                        echo <<<__EOF
  <div id="archives">
   <b>Archives</b><br>
   <img width="100" height="8" src="images/line.png" alt="*"><br>
__EOF;
                        foreach ($LOGS as $i => $logs) {
                            if ($i == 3) {
                                echo "<div style=\"text-align:right;\">\n";
                                echo "<a href=\"$_self?mode=archives\" title=\"すべてのアーカイブスを表示\">all</a>&nbsp;&nbsp;\n</div>\n";
                                break;
                            }
                            $_logs  = substr($logs, strlen(LOG), strlen($logs) - strlen(LOG));
                            $__logs = substr($_logs, 0, 4) . '年' . substr($_logs, 4, 2) . '月';
                            if (isset($_GET['archives'])) {
                                if ($_GET['archives'] == substr($_logs, 0, 6)) {
                                    echo "<b style=\"font-size:13px;\">$__logs</b><br>";
                                } else {
                                    echo "<a href=\"$_self?mode=list&amp;archives=" . substr($_logs, 0, 6) . "\">$__logs</a><br>\n";
                                }
                            } else {
                                echo "<a href=\"$_self?mode=list&amp;archives=" . substr($_logs, 0, 6) . "\">$__logs</a><br>\n";
                            }
                            echo "<img width=\"100\" height=\"8\" src=\"images/line.png\" alt=\"\"><br>\n";
                        }
                        echo " </div>\n";
                        echo <<<__EOF
 <br><br>
  <a href="$_self?mode=search" title=" 日記の検索 " style="text-decoration:none;">
   <b style="font:600 12px arial">Search</b>
   <img src="images/search.png" class="search">
  </a>
  <!--COPYRIGHT-->
  <div class="footer">Turbinado pelo <a href="http://www.martin.bz" target="_blank"><i>i</i>-Diary</a><br>
  Modularized by <a href="http://www.adslnet.org/" target="_blank">ADSLNet</a> Ver $version</div>
__EOF;
                        echo;
                    }
                    ?>
                </td>
            </tr>
        </table>
    </div>
    <div>
        <?php
        if ($offset == 0) {
            if (isset($_GET['archives'])) {
                echo '<div class="header">
   ::: <b>' . substr($_GET['archives'], 0, 4) . '</b>年<b>' . substr($_GET['archives'], 4, 2) . "</b>月のエントリーです :::
</div>\n";
            } elseif ($mode == 'box' || $mode == 'list' || empty($mode)) {
                if (count($LINES) >= $entry) {
                    echo "<div class=\"header\">::: 最新の<b>$entry</b>件を日付順に表示しています :::</div>\n";
                } elseif (!empty($LINES)) {
                    echo '<div class="header">::: <b>' . count($LINES) . "</b>件を表示しています :::</div>\n";
                }
            }
        }

        switch ($mode) {
            case 'show' :
                show_box($date);
                break;
            case 'box' :
                show_box_all();
                break;
            case 'archives' :
                show_archives();
                break;
            case 'gallery' :
                img_gallery();
                break;
            case 'list' :
                show_list($LINES);
                break;
            case 'write' :
                write_form();
                break;
            case 'edit' :
                edit_form();
                break;
            case 'del' :
                del_form();
                break;
            case 'submit' :
            case 'update' :
            case 'delete' :
                transfer_form();
                break;
            case 'search' :
                isset($_GET['query']) ? do_search($_GET['words']) : search_form();
                break;
            default :
                show_box_all();
                break;
        }
        echo("\n</div>\n<!-- End of iDiary -->");

        /*******************************************
         * ユーティリティー関数群
         ******************************************
         * @param $logline
         * @param $date
         * @return int|string
         */

        function get_date($logline, $date)
        { // $dateのインデックスを検出
            foreach ($logline as $i => $val) {
                if (preg_match("$date\|", $val)) {
                    return $i;
                    break;
                }
            }
            return -1;
        }

        function balloon($img, $w, $h, $fsize)
        { // 画像にマウスが乗ったときのバルーンヘルプ
            [, $_img] = explode('_', $img);
            return '<a href="' . IMG_PATH . '/' . $img . '" target="_blank">' . "\n" . '<img align="right" src="' . IMG_PATH . '/' . $img . '" border="0" width="' . $w . '" height="' . $h . '"
        title="画像名：　' . $_img . "\n" . 'サ イ ズ：　' . $fsize . "\n" . '大 き さ：　' . $w . '(W)×' . $h . "(H)\n\n" . ' →クリックで別ウィンドウ表示"></a>';
        }

        function show_archives()
        {
            global $LOGS, $_self;
            $cnt = count($LOGS);
            echo <<<__EOF
 <div class="center" style="margin-top:10px;">
  <div class="watch">:: 現在，<b>$cnt</b>本のアーカイブスがあります ::</div>
  <div id="archives">
   <b>Archives</b><br>
   <img width="100" height="8" src="images/line.png" alt="*"><br>
__EOF;
            foreach ($LOGS as $i => $logs) {
                $_logs  = substr($logs, strlen(LOG), strlen($logs) - strlen(LOG));
                $__logs = substr($_logs, 0, 4) . '年' . substr($_logs, 4, 2) . '月';
                if (isset($_GET['archives'])) {
                    if ($_GET['archives'] == substr($_logs, 0, 6)) {
                        echo "<b style=\"font-size:13px;\">$__logs</b><br>";
                    } else {
                        echo "<a href=\"$_self?mode=list&amp;archives=" . substr($_logs, 0, 6) . "\">$__logs</a><br>\n";
                    }
                } else {
                    echo "<a href=\"$_self?mode=list&amp;archives=" . substr($_logs, 0, 6) . "\">$__logs</a><br>\n";
                }
                echo "<img width=\"100\" height=\"8\" src=\"images/line.png\" alt=\"*\"><br>\n";
            }
            echo "  </div>\n</div>\n";
        }

        function edit_form()
        { // 編集フォーム
            global $LINES, $mode, $back, $maxsize, $hd, $_pass, $_self;
            $back   = '<span onclick="history.go(-1)" title=" 前に戻る " class="button">Back</span>';
            $date   = $_POST['date'];
            $target = file(LOG . substr($date, 0, 6) . '.log');
            $ext    = explode('|', EXTENSIONS);
            $EXT    = '';
            for ($i = 0; $i < count($ext) - 1; $i++) {
                $EXT .= ($ext[$i] . ', ');
            }
            $EXT .= $ext[count($ext) - 1];// 許可する拡張子のリスト作成
            foreach ($target as $line) {
                if (preg_match("$date\|", $line)) {
                    [$date, $title, $com, $img, $w, $h, $fs] = explode('|', $line);
                    break;
                }
            }
            if (preg_match('[0-9]{8}_', $img)) {
                $ratio = 100 / max($w, $h); // デカイやつは縮小表示
                $_w    = ($ratio < 1) ? round($w * $ratio) : $w;
                $_h    = ($ratio < 1) ? round($h * $ratio) : $h;
                [, $_img] = explode('_', $img);
                $info = '
  <div style="margin:10px auto 5px 10px;background:#E6E6FF; font-size:12px;padding:10px;">
   <img src="' . IMG_PATH . '/' . trim($img) . '" align="right" width="' . $_w . '" height="' . $_h . '">
   [元の画像情報]<br>
   画像名 : ' . $_img . ' 　→ 大きいものは縮小表示しています.<br>
   大きさ :  ' . $w . ' (W) × ' . $h . '(H)<br>
   サイズ : ' . $fs . '
   <br clear="all">
   </div>
   &nbsp;<input type="checkbox" name="delimg" id="delimg" value="' . $img . '"><label for="delimg">元の画像を削除する</label>
  ';
            } else {
                $info = '';
            }
            $com = str_replace('<br>', "\n", $com);
            $com = rtrim($com);
            $_hd = checkHoliday($date) ? '&nbsp;<span style="color:#FF00FF;font-size:9px;font-weight:500;">' . $hd->holidays[$date] . '</span>' : '';
            if (isset($_POST['title'])) {
                $title = $_POST['title'];
            }
            if (isset($_POST['com'])) {
                $com = $_POST['com'];
            }
            $h = '
<div class="box">
<div class="header">::: 編集モード :::</div>
<form method="post" action="' . $_self . '" enctype="multipart/form-data">
 <input type="hidden" name="MAX_FILE_SIZE" value="' . $maxsize . '">
 <input type="hidden" name="date" value="' . $date . '">
 <input type="hidden" name="imgprop" value="' . trim($img) . '|' . $w . '|' . $h . '|' . rtrim($fs) . '">
 <table class="pretty_box" cellpadding="0" cellspacing="0">
  <tr>
   <td class="bar_top">Title:<input name="title" type="text" size="40" class="text" value="' . $title . '"></td>
  </tr>
  <tr>
   <td>
    <div class="right">' . mdate($date) . $_hd . '</div>
    <div class="content">
     <textarea name="com" cols="60" rows="5" OnKeyDown="return (event.keyCode!=27);">' . $com . '</textarea><br>
     <span class="tips">添付画像：</span><input type="file" name="src" size="30" class="text">
     <span class="tips"> 新しく画像を選択すると，元の画像は消去されます。</span>' . "\n" . $info . '<br>
    </div>
    <div class="right">
     <span onclick="insertLink(\'リンク先のアドレスをどうぞ\',\'http://\');return false;" title="ハイパーリンクを入力します" class="button">
         リンク作成</span>&nbsp;
     <span onclick="colorMe();return false;" title="部分的に指定色の文字を入力します" class="button"> カラー </span>&nbsp;&nbsp;&nbsp;
     <input title=" この内容で投稿 " name="mode" type="submit" value="Update" class="button">&nbsp;
     ' . $back . '&nbsp;&nbsp;&nbsp;
    </div>
   </td>
  </tr>
 </table>
</form>
</div>
';
            echo ltrim($h);
        }

        function del_form()
        { // 削除フォーム
            global $LINES, $mode, $_pass, $_self;
            $date   = $_POST['date'];
            $back   = '<span onclick="history.go(-1)" title=" 前に戻る " class="button">Back</span>';
            $target = file(LOG . substr($date, 0, 6) . '.log');
            [$date, $title, $com, $img, $w, $h, $fs] = explode('|', $LINES[get_date($LINES, $date)]);

            $_com    = autolink1($com);
            $_com    = autolink2($_com);
            $_com    = colorize($_com);
            $w       = $w ?? "";
            $h       = $h ?? "";
            $fs      = $fs ?? "";
            $imgprop = $img . '|' . $w . '|' . $h . '|' . $fs;
            if (!empty($img)) {
                $_com = "<div class=\"inbox\">\n" . balloon($img, $w, $h, $fs) . $_com . "\n</div>\n<br clear=\"all\"><br>\n";
            } else {
                $_com = "<div class=\"inbox\">\n{$_com}\n</div>\n<br clear=\"all\"><br>\n";
            }

            echo '
 <div class="box">
 <div class="header">::: 削除モード :::</div>
 <form method="post" action="' . $_self . '">
 <div class="watch">以下の内容を削除します。<br><br>
 <input type="submit" name="mode" value="Delete" title="OK！" class="button">
  &nbsp;&nbsp;' . $back . '</div><br>
 <input type="hidden" name="date" value="' . $date . '">
 <input type="hidden" name="title" value="' . $title . '">
 <input type="hidden" name="delimg" value="' . trim($img) . '">
 <input type="hidden" name="imgprop" value="' . trim($imgprop) . '">
 <input type="hidden" name="check" value="1">' . _box($date, $title, $_com, 0, 0, 0) . '
</form>
</div>
';
        }

        function transfer_form()
        {
            global $LINES, $mode, $_self, $_pass;
            $date    = $_POST['date'];
            $imgprop = $_POST['imgprop'] ?? '';
            $target  = LOG . substr($date, 0, 6) . '.log';// ターゲットログの絞込み
            $delimg  = $_POST['delimg'] ?? '';
            $back    = '<span onclick="history.go(-1)" title=" 前に戻る " class="button">Back</span>';
            if (isset($LINES[0])) {
                [$_date] = explode('|', $LINES[0]);
            } else {
                $_date = '';
            }
            if ($_date == $date && $mode == 'submit') { // 二重投稿チェック
                refresh_page(1000);
                error('-!-!- 二重投稿です -!-!-', 0);
                return;
            }
            if (isset($_FILES['src']['name']) && isset($_FILES['src']['error'])) {
                if ($_FILES['src']['name'] != '' && $_FILES['src']['error'] > 0) {
                    switch ($_FILES['src']['error']) { // エラーに応じたメッセージを出力。
                        case 1 :
                            error('サーバーの設定ファイルphp.iniの upload_max_filesize の値を超えています。', 2);
                            break;
                            return;
                        case 2 :
                            error('ファイルサイズが設定した上限の <b>' . MAX_FSIZE . ' KB</b>を超えています。', 2);
                            break;
                            return;
                        case 3 :
                        case 4 :
                            error('アップロードが正常に行われなかったようです。', 2);
                            break;
                            return;
                    }
                }
            }
            if (isset($_FILES['src']['tmp_name'])) {
                $img_name = str_replace('_', '', $_FILES['src']['name']);
                $uploaded = PATH . $date . '_' . $img_name;
                if (!empty($_FILES['src']['tmp_name'])) {
                    if (!eregi(EXTENSIONS, $_FILES['src']['name'])) {
                        error('拡張子が許可されていないファイルのようです。', 2);
                        return;
                    }
                    [$width, $height] = getimagesize($_FILES['src']['tmp_name']);
                    $ratio  = MAX_ISIZE / max($width, $height); // デカイやつは縮小表示
                    $width  = ($ratio < 1) ? round($width * $ratio) : $width;
                    $height = ($ratio < 1) ? round($height * $ratio) : $height;
                    if (move_uploaded_file($_FILES['src']['tmp_name'], $uploaded)) {
                        $fsize = $_FILES['src']['size'];
                        $fsize = ($fsize < 1024) ? $fsize . ' Bytes' : round($fsize / 1024, 2) . 'KB';
                        [$delimg] = explode('|', $imgprop);
                        if ($delimg == $date . '_' . $img_name) {
                            $delimg = '';
                        }
                    }
                }
            }
            $com   = (isset($_POST['com'])) ? mdata($_POST['com']) : '';     // 文の整形
            $title = trim($_POST['title']);

            if ($mode != 'delete') {
                if (empty($com) || empty($title)) {
                    error('タイトルや内容は空白ではいけません。', 2);
                    return;
                }
            }
            $_title = mdata($title); // タイトルの整形
            $_com   = autolink1($com);
            $_com   = autolink2($_com);
            $_com   = colorize($_com);
            $w      = $width ?? "";
            $h      = $height ?? "";
            $fs     = $fsize ?? "";

            if (isset($fsize)) { //新たに画像がセットされたら
                $_com    = "<div class=\"inbox\">\n" . balloon($date . '_' . $img_name, $w, $h, $fsize) . $_com . "\n</div>\n<br clear=\"all\"><br>";
                $imgprop = $date . '_' . $img_name . '|' . $w . '|' . $h . '|' . $fs;
            } else {
                if (!empty($delimg) && !isset($_POST['check'])) {
                    $imgprop = '|||';
                }
                if ($imgprop == '|||' || $imgprop == '') {
                    $_com = "<div class=\"inbox\">\n{$_com}\n</div>\n<br clear=\"all\"><br>\n";
                } else {
                    [$img, $w, $h, $fs] = explode('|', $imgprop); // 画像情報を抽出
                    $_com = "<div class=\"inbox\">\n" . balloon($img, $w, $h, $fs) . $_com . "\n</div>\n<br clear=\"all\"><br>\n";
                }
            }
            if (!isset($_POST['check']) && $mode != 'submit') {
                $html = '
<div class="box">
 <form method="post" action="' . $_self . '">
 <div class="watch">以下の内容で送信します。<br><br>
 <input type="submit" name="mode" value="' . $mode . '" title="OK！" class="button">
  &nbsp;&nbsp;' . $back . '</div><br>
 <input type="hidden" name="date" value="' . $date . '">
 <input type="hidden" name="title" value="' . $_title . '">
 <input type="hidden" name="delimg" value="' . trim($delimg) . '">
 <input type="hidden" name="imgprop" value="' . trim($imgprop) . '">
 <input type="hidden" name="check" value="1">
 <textarea name="com" cols="80" rows="10" style="display:none;">' . $_POST['com'] . '</textarea>' . "\n" . _box($date, $_title, $_com, 0, 0, 0) . '
</form>
</div>
';
                echo $html;
            } else {
                $imgprop   = empty($imgprop) ? '|||' : $imgprop;
                $logFormat = $date . '|' . $_title . '|' . $com . '|' . $imgprop . "\n"; // ログのフォーマット
                if (!empty($delimg)) {
                    unlink(PATH . $delimg);
                } // 画像を削除
                if ($mode == 'update') {
                    array_splice($LINES, get_date($LINES, $date), 1, $logFormat);// もとのログを新たに入れ替え
                    $fp = fopen($target, 'wb');
                    flock($fp, LOCK_EX);
                    foreach ($LINES as $value) {
                        fwrite($fp, rtrim($value) . "\n");
                    }
                    fclose($fp);
                    refresh_page(1500);
                    error(' 更 新 完 了！ <br><br>すぐにページを更新します。', 0);
                    return;
                } elseif ($mode == 'submit') {  // 新規に日記
                    $LINES = file($target);
                    array_unshift($LINES, $logFormat); // 先頭に追加
                    rsort($LINES);       // 日付の新しい順に
                    reset($LINES);
                    $fp = fopen($target, 'wb');
                    flock($fp, LOCK_EX);
                    for ($i = 0, $iMax = count($LINES); $i < $iMax; $i++) {
                        fwrite($fp, $LINES[$i]);
                    }
                    fclose($fp);
                    refresh_page(1000);
                    error(' 書き込み 完 了！ <br><br>すぐにページを更新します。', 0);
                    return;
                } elseif ($mode == 'delete') {
                    array_splice($LINES, get_date($LINES, $date), 1);// $dateの配列要素をカット
                    $fp = fopen($target, 'wb');        //ログをオープン
                    flock($fp, LOCK_EX);
                    for ($i = 0, $iMax = count($LINES); $i < $iMax; $i++) {
                        fwrite($fp, $LINES[$i]);
                    }
                    fclose($fp);
                    if (count(file($target)) == 0) {
                        unlink($target);
                    }
                    refresh_page(1000);
                    error(' 選択した項目を削除しました。<br><br>すぐにページを更新します。', 0);
                    return;
                }
            }
        }

        function img_gallery($offset = 0)
        { // 画像ギャラリー
            global $_self, $img;
            $entry  = 8;        // 1ページに表示する画像の数(偶数を指定)
            $offset = $_GET['offset'] ?? 0;
            $pre    = $offset - $entry;
            $d      = dir(IMG_DIR);  // 画像ディレクトリの走査
            while (false !== ($file = $d->read())) {
                if ($file != '.' && $file != '..' && $file != 'index.html') {
                    $IMGS[] = IMG_DIR . '/' . $file;
                }
            }
            $d->close();
            if (empty($IMGS)) {
                error('まだ画像はアップされていません。');
                return;
            }
            $IMGS = array_slice($IMGS, $offset, $entry);
            echo <<<__EOF
 <div class="header">::: 画像をクリックするとその日記が見れます :::</div>
 <div id="gallery">
 <table>
__EOF;
            foreach ($IMGS as $i => $imgs) {
                $size  = getimagesize($imgs);
                $ratio = 100 / max($size[0], $size[1]); // デカイやつは縮小表示
                $w     = ($ratio < 1) ? round($size[0] * $ratio) : $size[0];
                $h     = ($ratio < 1) ? round($size[1] * $ratio) : $size[1];
                [$_img] = explode('_', $img);
                $_size[3] = 'width="' . $w . '" height="' . $h . '"';
                [$date, $name] = explode('_', str_replace(IMG_DIR . '/', '', $imgs));
                $tr = ($i % ($entry / 2) == 0) ? (($i == 0) ? "\n  <tr>\n   <td width=\"180\" height=\"100\">" : "\n  </tr>\n  <tr>\n   <td width=\"180\" height=\"100\">") : "\n   <td width=\"180\" height=\"100\">";
                echo $tr . "\n   <a href=\"$_self?mode=show&amp;date=$date\"><img class=\"ig\" src=\"$IMGS[$i]\" alt=\"\" $_size[3]></a>\n";
                echo "   <br><small>$name</small>\n   </td>\n";
            }
            while ($i % 5 < 4) {
                echo "   <td>　</td>\n";
                $i++;
            }
            echo <<<__EOF
  </tr>
 </table>
 </div>
__EOF;
            echo "<div class=\"nav\">\n";
            if ($offset > 0) {
                echo " <a href=\"$_self?mode=gallery&amp;offset=$pre\"><< 前の頁</a>\n";
            }
            if (count($IMGS) == $entry) {
                if ($offset > 0) {
                    echo ' | ';
                }
                echo " <a href=\"$_self?mode=gallery&amp;offset=" . ($offset + $entry) . "\">次の頁 >></a>\n";
            }
            echo "</div>\n";
        }

        function show_list()
        { // リスト表示
            global $LINES, $offset, $entry, $_self;
            $size = count($LINES);
            $pre  = $offset - $entry;
            if (empty($LINES)) {
                error('初めての日記を待っています。', 0);
                return;
            }
            if ($size == 0) {
                echo <<<__EOF
  <br>
  <div class="box">
   これ以上の日記はありません。<br><br>
   <div class="nav"><a href="$_self?mode=list&amp;offset=$pre"><< 前の <b>$entry</b> 件</a></div>
  </div>
__EOF;
                return;
            }
            echo <<<__EOF
<div style="margin:auto;width:380px;padding-top:55px;">
 <table class="list">\n
__EOF;
            for ($i = 0; $i < $size; $i++) {
                [$date, $title] = explode('|', $LINES[$i]);
                echo "  <tr>\n   <td><span class=\"square\">&#9632;</span> <a href=\"$_self?mode=show&amp;date=$date\" class=\"listLink\">$title</a></td>\n" . '   <td class="rt">&nbsp;' . mdate($date) . "</td>\n  </tr>\n";
            }
            echo " </table>\n";
            echo "</div>\n";
            echo "<div class=\"nav\">\n";
            if ($offset > 0) {
                echo " <a href=\"$_self?mode=list&amp;offset=$pre\"><< 前の <b>$entry</b> 件</a>\n";
            }
            if (count($LINES) == $entry) {
                if ($offset > 0) {
                    echo ' | ';
                }
                echo " <a href=\"$_self?mode=list&amp;offset=" . ($offset + $entry) . "\">次の <b>$entry</b> 件 >></a>\n";
            }
            echo "</div>\n";
        }

        function show_box($date = '')
        { // ボックス表示（シングル）
            $target = file(LOG . substr($date, 0, 6) . '.log');
            foreach ($target as $line) {
                if (preg_match("$date\|", $line)) {
                    [$date, $title, $com, $img, $w, $h, $fs] = explode('|', $line);
                    $_img = ($img == '') ? '' : balloon($img, $w, $h, $fs);
                    $com  = autolink1($com);
                    $com  = autolink2($com);
                    $com  = colorize($com);
                    $com  = ($_img == '') ? $com : "<div class=\"inbox\">\n" . $_img . "\n" . $com . '</div><br clear="all"><br>';
                    echo _box($date, $title, $com, 1, 1, 1, 10);
                    break;
                }
            }
        }

        function show_box_all()
        { // ボックス表示（マルチ）
            global $LINES, $offset, $entry, $back, $_self;
            $size = count($LINES);
            $pre  = $offset - $entry;
            if (empty($LINES)) {
                error('初めての日記を待っています。', 0);
                return;
            }
            if ($size == 0) {
                echo <<<__EOF
  <br>
  <div class="box">
   これ以上の日記はありません。<br><br>
   <div class="nav"><a href="$_self?mode=box&amp;offset=$pre"><< 前の <b>$entry</b> 件</a></div>
  </div>
__EOF;
                return;
            }
            for ($i = 0; $i < $size; $i++) {
                [$date, $title, $com, $img, $w, $h, $fs] = explode('|', $LINES[$i]);
                $_img = ($img == '') ? '' : balloon($img, $w, $h, $fs);
                $com  = autolink1($com);
                $com  = autolink2($com);
                $com  = colorize($com);
                $com  = ($_img == '') ? $com : "<div class=\"inbox\">\n" . $_img . "\n" . $com . '</div><br clear="all"><br>';
                echo _box($date, $title, $com, 1, 1, $back);
            }
            echo "<div class=\"nav\">\n";
            if ($offset > 0) {
                echo " <a href=\"$_self?mode=box&amp;offset=$pre\"><< 前の <b>$entry</b> 件</a>\n";
            }
            if (count($LINES) == $entry) {
                if ($offset > 0) {
                    echo ' | ';
                }
                echo " <a href=\"$_self?mode=box&amp;offset=" . ($offset + $entry) . "\">次の <b>$entry</b> 件 >></a>\n";
            }
            echo "</div>\n";
        }

        function _box($date, $title, $com, $mod = 0, $form = 1, $_back = 1, $offsetY = 10)
        {
            global $hd, $_self, $_del, $adminmode;
            $back  = '<span onclick="history.go(-1)" title=" 前に戻る " class="button">Back</span>';
            $_back = ($_back == 1) ? $back : '';
            $_date = mdate($date);
            $_hd   = checkHoliday($date) ? '&nbsp;<span style="color:#f064df;font-size:9px;font-weight:500;">' . $hd->holidays[$date] . '</span>' : '';
            if ($adminmode == '1') {
                $_mod = $mod ? "<br><div class=\"edit\">\n" . '<input title=" この内容を修正 " name="mode" type="submit" value="Edit" class="button">&nbsp;' . "\n" . '<input title=" 削除します " name="mode" value="Del" type="submit" class="button">' . "\n" . $_back . "</div>\n" : "\n";
            } else {
                $_mod = $mod ? '<br><div class="edit">' . $_back . "</div>\n" : "\n";
            }

            $pre  = '
  <div class="box" style="padding-top:' . $offsetY . 'px">
   <form method="post" action="' . $_self . '">
    <input type="hidden" name="date" value="' . $date . '">' . "\n";
            $h    = <<<__EOF
    <table class="pretty_box" cellpadding="0" cellspacing="0">
     <tr>
      <td class="bar_top">$title</td>
     </tr>
     <tr>
      <td>
       <div class="right">$_date{$_hd}</div>
       <div class="content">$com</div>
       <div class="right">$_mod</div>
      </td>
     </tr>
    </table>
__EOF;
            $post = '
 </form>
 </div>
 ';
            return ($form) ? $pre . $_del . $h . $post : $h;
        }

        function refresh_page($timer = 1000)
        { // JavaScriptでのページ更新
            global $_self;
            $script = "<script language=\"JavaScript\">\n" . " setTimeout(\"self.location.href='$_self'\",$timer);\n" . "</script>\n";
            echo $script;
        }

        function mdata($str)
        { // データの整形
            $str = get_magic_quotes_gpc() ? stripslashes($str) : $str;
            $str = (HTML_ENABLED) ? $str : htmlspecialchars($str, ENT_QUOTES | ENT_HTML5);
            $str = nl2br($str);
            $str = preg_replace("/\r\n|\r|\n/", '', $str);
            $str = str_replace('|', '&#124;', $str); // 文中の|(パイプ)は変換(ログのセパレータに使用しているので)
            return $str;
        }

        function mdate($date)
        {
            $ey = substr($date, 0, 4);
            $em = substr($date, 4, 2);
            $ed = substr($date, 6, 2);
            return $ey . '-' . $em . '-' . $ed;
        }

        function write_form()
        {
            global $LINES, $hd, $_pass, $maxsize, $_self, $title, $com, $mode;
            $date = $_GET['date'] ?? $_POST['date'];
            $ext  = explode('|', EXTENSIONS);
            $EXT  = '';
            for ($i = 0; $i < count($ext) - 1; $i++) {
                $EXT .= ($ext[$i] . ', ');
            }
            $EXT .= $ext[count($ext) - 1];// 許可する拡張子のリスト作成
            $_hd = checkHoliday($date) ? '&nbsp;<span style="color:#FF0683;font-size:9px;font-weight:500;">' . $hd->holidays[$date] . '</span>' : '';
            if (isset($_POST['title'])) {
                $title = $_POST['title'];
            }
            if (isset($_POST['com'])) {
                $com = $_POST['com'];
            }
            $h = '

<div class="box">
<div class="header">タイトルと内容は必須。画像はオプションです。画像は後から追加できます。</div>
<form method="post" action="' . $_self . '" enctype="multipart/form-data">
 <input type="hidden" name="MAX_FILE_SIZE" value="' . $maxsize . '">
 <input type="hidden" name="date" value="' . $date . '">
 <table class="pretty_box" cellpadding="0" cellspacing="0">
  <tr>
   <td class="bar_top">Title:<input name="title" type="text" size="40" class="text" value="' . $title . '"></td>
  </tr>
  <tr>
   <td>
    <div class="right">' . mdate($date) . $_hd . '</div>
    <div class="content">
     <textarea name="com" cols="60" rows="5" OnKeyDown="return (event.keyCode!=27);">' . $com . '</textarea><br>
     <span class="tips">添付画像：</span><input type="file" name="src" size="30" class="text"><br>
     <span class="tips">＊許可された画像のサイズ上限は ' . MAX_FSIZE . ' KBで，拡張子は，' . $EXT . 'です。</span><br>
    </div>
    <div class="right">
     <span onclick="insertLink(\'リンク先のアドレスをどうぞ\',\'http://\');return false;" title="ハイパーリンクを入力します" class="button">
         リンク作成</span>&nbsp;
     <span onclick="colorMe();return false;" title="部分的に指定色の文字を入力します" class="button"> カラー </span>&nbsp;&nbsp;&nbsp;
     <input title=" この内容で投稿 " name="mode" type="submit" value="Submit" class="button">&nbsp;
     <span onclick="history.go(-1); return false;" title=" １つ前に戻ります " class="button">Back</span>&nbsp;&nbsp;&nbsp;
    </div>
   </td>
  </tr>
 </table>
</form>
</div>
';
            echo ltrim($h);
        }

        function autolink1($str)
        {  // リンク http(s)://www.hoge.com をそのままの表示でリンクする
            return eregi_replace(
                "(https?)(://[[:alnum:]\S\+\$\?\.%,!#~*/:@&=_-]+)",
                "<a href=\"\\1\\2\" target=\"_blank\" class=link>\\1\\2</a>",
                $str
            );
        }

        function autolink2($str)
        {  // 独自タグ [link:www.hogehoge.com]ほげほげ[/link] をリンクする
            return eregi_replace(
                "(\[link:)([[:alnum:]\S\+\$\?\.%,!#~*/:@&=_-]+)(\])([^/]+)(\[/link\])",
                "<a href=\"http://\\2\" target=\"_blank\" class=link>\\4</a>",
                $str
            );
        }

        function colorize($str)
        {  // 独自タグ [color:red]COLORIZE ME[/color] をリンクする
            return eregi_replace(
                "(\[color:)([#0-9A-Za-z]+(\(([0-9]{1,3},?){3}\)){0,1})(\])([^/]+)(\[/color\])",
                "<span style=\"color:\\2;\">\\6</span>",
                $str
            );
        }

        function search_form($out = '')
        { // 検索フォーム
            global $_self;
            echo <<<EOF
    &#9632; キーワードを入力して下さい．<br>
    &#9632; 複数のときはスペース(全角・半角OK)で区切ります．<br>
    &#9632; 検索語はタイトルか内容にヒットします．<br>
   <form action="$_self" method="get">
    <input type="hidden" name="query" value="1">
    <input type="hidden" name="mode" value="search">
    <input type="text" size="50" name="words" value="$out" class="text" style="width:215px;">
    <input type="radio" value="and" name="andor">AND
    <input type="radio" value="or" name="andor" checked>OR
    <input type="submit" value=" Search " title=" これで検索 ">
   </form>
EOF;
        }

        function do_search()
        { // キーワード検索
            global $LINES, $_self, $_mb_enabled;
            if (isset($_GET['words'])) {
                if (empty($_GET['words'])) {
                    error('検索したい文字列を入力して下さい。');
                    return;
                }
                search_form($_GET['words']);
                $w      = get_magic_quotes_gpc() ? stripslashes($_GET['words']) : $_GET['words'];  //\消去
                $HL     = '#B0E0E6;'; // ハイライトの色
                $AND    = ($_GET['andor'] == 'and') ? 1 : 0;
                $result = [];
                $words  = preg_preg_split("/\s+/", trim(str_replace('　', ' ', $w)));

                foreach ($LINES as $value) {
                    [$date, $title, $com, $img, $w, $h, $fs] = explode('|', $value);
                    $_value = $title . '|' . $com;
                    if (!$AND) {
                        $pre = $_value;
                    }
                    for ($i = 0, $iMax = count($words); $i < $iMax; $i++) {
                        if ($AND) {
                            $pre = $_value;
                        }
                        $_value = ($_mb_enabled) ? mb_preg_replace((string)$words[$i], "<span style='background:$HL'>{$words[$i]}</span>", $_value) : preg_replace((string)$words[$i], "<span style='background:$HL'>{$words[$i]}</span>", $_value);
                        $post   = $_value;
                        if ($AND) {
                            if ($pre == $post) {
                                break 1;
                            }
                        }
                    }
                    if ($pre != $post) {
                        $d        = explode('|', $post);
                        $result[] = $date . '|' . $d[0] . '|' . $d[1] . '|' . $img . '|' . $w . '|' . $h . '|' . $fs;
                    }
                }
                if (count($result) == 0) {
                    echo "<br><br><div class=\"header\">１件もヒットしませんでした。違うキーワードで試して下さい。</div>\n";
                    echo "<br><br>\n";
                } else {
                    echo ' 上の検索で <strong>' . count($result) . "</strong> 件ヒットしました。<br></div>\n<br>\n";
                    foreach ($result as $output) {
                        [$date, $title, $com, $img, $w, $h, $fs] = explode('|', $output);
                        $_img = ($img == '') ? '' : balloon($img, $w, $h, $fs);
                        $com  = autolink1($com);
                        $com  = autolink2($com);
                        $com  = colorize($com);
                        $com  = ($_img == '') ? $com : "<div class=\"inbox\">\n" . $_img . "\n" . $com . '</div><br clear="all"><br>';
                        echo _box($date, $title, $com, 1, 1, 1, 30);
                    }
                }
            }
        }

        function mk_fl($name)
        { // ファイル自動生成
            if (!file_exists($name)) {
                rewrite($name, '');
                chmod($name, 0666);
            } else {
                return;
            }
        }

        function rewrite($file, $data)
        { // fopen($file, "w")してデータ$dataを書き込む
            if (!($fp = fopen($file, 'wb'))) {
                error('パーミッションの設定はあっていますか？');
                return;
            }
            flock($fp, LOCK_EX);
            fwrite($fp, $data);
            fclose($fp);
        }

        function error($er, $_back = 1)
        {
            global $_self, $date, $mode;
            $back = '';
            if ($mode == 'submit') {
                $mode = 'write';
            }
            if ($mode == 'update') {
                $mode = 'edit';
            }
            if ($_back == '0') {
                $back = '<span onclick="history.go(-1)" title=" 前に戻る " class="button">Back</span>';
            }
            if ($_back == '2') {
                $back = '<br><form method="post" action="'
                        . $_self
                        . '"><input type="hidden" name="date" value="'
                        . $date
                        . '"><input type="hidden" name="title" value="'
                        . $_POST['title']
                        . '"><input type="hidden" name="com" value="'
                        . $_POST['com']
                        . '"><input type="hidden" name="mode" value="'
                        . $mode
                        . '"><button type="submit" name="submit" value="submit">back</button></form>';
            }
            echo("<div class=\"box\" style=\"text-align:center;margin-top:110px;\">\n$er<br><br>$back</div>\n</body>\n</html>");
        }

        function P($s)
        {//Debug
            echo '<pre>';
            print_r($s);
            echo '</pre>';
        }

        require XOOPS_ROOT_PATH . '/footer.php';
        ?>
