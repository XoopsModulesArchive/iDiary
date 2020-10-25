<?php

require dirname(__DIR__, 3) . '/include/cp_header.php';

xoops_cp_header();

$cmd = empty($_POST['cmd']) ? false : $_POST['cmd'];
if ('update' == $cmd) {
    $xoopsDB->queryF(
        'update '
        . $xoopsDB->prefix('idiary_config')
        . ' set entry_list = '
        . $_POST['entry_list']
        . ', entry_box = '
        . $_POST['entry_box']
        . ', html_enabled = '
        . $_POST['html_enabled']
        . ', archives = '
        . $_POST['archives']
        . ', eventcol = "'
        . $_POST['eventcol']
        . '", max_fsize = '
        . $_POST['max_fsize']
        . ', max_isize = '
        . $_POST['max_isize']
        . ', extensions = "'
        . $_POST['extensions']
        . '"'
    );
}

?>
<?php
global $xoopsDB, $xoopsUser;
$table_name = $xoopsDB->prefix('idiary_config');
$query = 'SELECT *  FROM ' . $table_name;
$result = $xoopsDB->query($query);
if (!list($entry_list, $entry_box, $html_enabled, $archives, $eventcol, $max_fsize, $max_isize, $extensions) = $xoopsDB->fetchRow($result)) {
    //Initialize

    $query = 'INSERT INTO ' . $table_name . '(entry_list, entry_box, html_enabled, archives, eventcol, max_fsize, max_isize, extensions) VALUES(15, 5, 1, 1, "#E6E6F0", 200, 250, "jpeg|gif|png|jpg")';

    $xoopsDB->queryF($query);

    $query = 'SELECT *  FROM ' . $table_name;

    $result = $xoopsDB->query($query);

    [$entry_list, $entry_box, $html_enabled, $archives, $eventcol, $max_fsize, $max_isize, $extensions] = $xoopsDB->fetchRow($result);
}
?>
    <center><h4>iDiary一般設定</h4></center>
    <form action="index.php" method="post">
        <table width='100%' border='1' cellspacing='1'>
            <tr>
                <td>リスト表示で１ページに表示する件数</td>
                <td><input type="text" name="entry_list" value="<?php echo $entry_list ?>"></td>
            </tr>
            <tr>
                <td>ボックス表示で１ページに表示する件数</td>
                <td><input type="text" name="entry_box" value="<?php echo $entry_box ?>"></td>
            </tr>
            <tr>
                <td>HTMLタグを有効にするかどうか</td>
                <td><input type="radio" name="html_enabled" value="0" <?php if (0 == $html_enabled) {
    echo ' checked>';
} else {
    echo '>';
} ?>無効<br><input type="radio" name="html_enabled" value="1" <?php if (1 == $html_enabled) {
    echo ' checked>';
} else {
    echo '>';
} ?>有効</td>
            </tr>
            <tr>
                <td>アーカイブスへのリンクを表示するか</td>
                <td><input type="radio" name="archives" value="0" <?php if (0 == $archives) {
    echo ' checked>';
} else {
    echo '>';
} ?>表示しない<br><input type="radio" name="archives" value="1" <?php if (1 == $archives) {
    echo ' checked>';
} else {
    echo '>';
} ?>表示する</td>
            </tr>
            <tr>
                <td>書かれた日のカレンダー背景色</td>
                <td><input type="text" name="eventcol" value="<?php echo $eventcol ?>"></td>
            </tr>
            <tr>
                <td>アップする画像のファイルサイズ上限。KBで指定</td>
                <td><input type="text" name="max_fsize" value="<?php echo $max_fsize ?>"></td>
            </tr>
            <tr>
                <td>アップする画像の大きさ上限。縦か横の大きい方。ピクセル値。</td>
                <td><input type="text" name="max_isize" value="<?php echo $max_isize ?>"></td>
            </tr>
            <tr>
                <td>許可する画像ファイルの拡張子。（|で区切って下さい）</td>
                <td><input type="text" name="extensions" value="<?php echo $extensions ?>"></td>
            </tr>
        </table>
        <input type="hidden" name="cmd" value="update"><input type="submit" name="submit" value="更新"></form>
<?php
xoops_cp_footer();
?>
