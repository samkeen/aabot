<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title><?php echo ($payload->title); ?></title>
	<link rel="stylesheet" href="/static/base.css" type="text/css" />
</head>
<body>
<div id="page-header">
<h1>Aabot</h1>
<div id="feedback-output"><?php echo $feedback; ?></div>
</div>
<div id="body-content">
<?php echo $this->rendered_template; ?>
</div>
<div id="page-footer">

</div>

</body>
</html>