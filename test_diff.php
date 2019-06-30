<?php
/*
 * test_diff.php
 *
 * @(#) $Id: test_diff.php,v 1.6 2014/01/30 04:07:41 mlemos Exp $
 *
 */
	require('diff.php');
 
 $before = IsSet($_POST['before']) ? $_POST['before'] : 'Some text before';
 $after = IsSet($_POST['after']) ? $_POST['after'] : 'This is the text after';
 $mode = (IsSet($_POST['mode']) ? $_POST['mode'] : 'w');
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="en">
<head>
<title>Test the Diff Object</title>
<meta http-equiv="content-type" content="text/html; charset=iso-8859-1">
<style type="text/css">
* { font-family: sans-serif,arial,helvetica }
.frameResults { border-style: solid; border-width: 1px; }
</style>
<body>
<form method="POST" action="?">
<div><label for="before">Before</label><br>
<textarea id="before" cols="80" rows="10" name="before"><?php echo HtmlSpecialChars($before); ?></textarea></div>
<div><label for="after">After</label><br>
<textarea id="after" cols="80" rows="10" name="after"><?php echo HtmlSpecialChars($after); ?></textarea></div>
<div><input type="submit" name="compare" value="Compare"> by <select name="mode">
<option value="c"<?php if($mode === 'c') echo ' selected'; ?>>Character</option>
<option value="w"<?php if($mode === 'w') echo ' selected'; ?>>Word</option>
<option value="l"<?php if($mode === 'l') echo ' selected'; ?>>Line</option>
</select></div>
<?php
	if(IsSet($_POST['compare']))
	{
		$diff = new diff_class;
		$difference = new stdClass;
		$difference->mode = $mode;
		$difference->patch = true;
		$after_patch = new stdClass;
		if($diff->FormatDiffAsHtml($before, $after, $difference)
		&& $diff->Patch($before, $difference->difference, $after_patch))
		{
			echo '<div>Difference</div><div class="frameResults">', $difference->html, '</div>';
			echo '<div>Patch</div><div class="frameResults">', ($after === $after_patch->after ? 'OK: The patched text matches the text after.' : 'There is a BUG: The patched text (<b>'.HtmlSpecialChars($after_patch->after).'</b>) does not match the text after (<b>'.HtmlSpecialChars($after).'</b>).'), '</div>';
		}
		else
			echo '<div>Error: ', HtmlSpecialChars($diff->error), '</div>';
	}
?>
</form>
</body>
</html>
