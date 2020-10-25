<?php

require dirname(__DIR__, 3) . '/include/cp_header.php';

xoops_cp_header();

$cmd = empty($_POST['cmd']) ? false : $_POST['cmd'];
if ('update' == $cmd) {
    $xoopsDB->queryF('update ' . $xoopsDB->prefix('idiary_myholiday') . ' set myholiday = "' . $_POST['myholiday'] . '", content = "' . $_POST['content'] . '"  WHERE myholiday = "' . $_POST['original'] . '"');
}

if ('create' == $cmd) {
    $xoopsDB->queryF('INSERT INTO ' . $xoopsDB->prefix('idiary_myholiday') . ' set myholiday = "' . $_POST['myholiday'] . '", content = "' . $_POST['content'] . '"');
}

if ('delete' == $cmd) {
    $xoopsDB->query('delete from ' . $xoopsDB->prefix('idiary_myholiday') . ' WHERE myholiday = "' . $_POST['myholiday'] . '"');
}

?>
    <center><h4>iDiary記念日設定</h4></center>
    <table width='100%' border='1' cellspacing='1'>
        <tr>
            <td>日付（0101形式で４桁数字）</td>
            <td>記念日名称</td>
            <td></td>
        </tr>
<?php
global $xoopsDB;
$query = 'SELECT *  FROM ' . $xoopsDB->prefix('idiary_myholiday') . ' ORDER BY myholiday';
$result = $xoopsDB->query($query);
while (false !== ($display = $xoopsDB->fetchArray($result))) {
    echo('<form action="editmyholiday.php" method="post">');

    echo('<tr><td><input type="text" name="myholiday" value="' . $display['myholiday'] . '"></td>');

    echo('<td><input type="text" name="content" value="' . $display['content'] . '"></td>');

    echo('<td><input type="hidden" name="original" value="' . $display['myholiday'] . '"><input type="hidden" name="cmd" value="update"><input type="submit" name="submit" value="更新"></td></form></tr>');
}

echo('<form action="editmyholiday.php" method="post">');
echo('<tr><td><input type="text" name="myholiday"></td>');
echo('<td><input type="text" name="content"></td>');
echo('<td><input type="hidden" name="cmd" value="create"><input type="submit" name="submit" value="作成"></td></form></tr>');
echo('</table>');

echo('<form action="editmyholiday.php" method="post">削除：日付<input type="text" name="myholiday"><input type="hidden" name="cmd" value="delete"><input type="submit" name="submit" value="削除"></form>');

xoops_cp_footer();
?>
