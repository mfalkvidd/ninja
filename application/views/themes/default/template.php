<?php defined('SYSPATH') OR die('No direct access allowed.'); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>

	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title><?php if (isset($title)) echo html::specialchars($title) ?></title>

	<style type="text/css">
	html { background: #fff; }
	body { font-size: 76%; font-family: Arial, sans-serif; color: #273907; line-height: 1.5; text-align: left; }
	h1 { font-size: 3em; font-weight: normal; text-transform: uppercase; color: #000; }
	a { color: inherit; }
	code { font-size: 1.3em; }
	table tr {background:#fff}
	ul { list-style: none; padding: 2em 0; }
	ul li { display: inline; padding-right: 1em; text-transform: uppercase; }
	ul li a { padding: 0.5em 1em; background: #CA7D17; border: 1px solid #7A4B0D; color: #fff; text-decoration: none; }
	ul li a:hover { background: #c0c0c0; }
	.box { padding: 2em; background: #CA7D17; border: 1px solid #7A4B0D; }
	img{border:0}
	.copyright { font-size: 0.9em; text-transform: uppercase; color: #7A4B0D; }
	</style>
	<?php
		if (!empty($css_header)) {
			echo $css_header;
		}
	?>

	<?php echo html::script('application/media/js/jquery.min.js') ?>
	<?php echo html::script('application/media/js/jquery.form.js') ?>
	<script type="text/javascript">
		//<!--
		var _site_domain = '<?php echo Kohana::config('config.site_domain') ?>';
		var _index_page = '<?php echo Kohana::config('config.index_page') ?>';
		//-->
	</script>
	<?php echo html::script('application/media/js/ajax_test.js') ?>
	<?php
		if (!empty($js_header)) {
			echo $js_header;
		}
	?>

</head>
<body>

	<h1><?php if (isset($title)) echo html::specialchars($title) ?></h1>
	<?php if (isset($content)) echo $content ?>

	<p class="copyright">
		Rendered in {execution_time} seconds, using {memory_usage} of memory
	</p>
</body>
</html>