<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title><?php echo ($payload->title); ?></title>
    <link rel="stylesheet" href="/static/css/blueprint/screen.css" type="text/css" media="screen, projection">
    <link rel="stylesheet" href="/static/css/blueprint/print.css" type="text/css" media="print">
    <!--[if IE]><link rel="stylesheet" href="/static/css/blueprint/ie.css" type="text/css" media="screen, projection"><![endif]-->
	<link rel="stylesheet" href="/static/base.css" type="text/css" />
</head>
<body>
<div class="container">
    <div id="page-header" class="span-24">
        <h1>Aabot</h1>
        <?php if($FW->auth->logged_in()) { ?>
        <a href="/auth/logout">logout</a>
        <?php } else { ?>
        <a href="/auth/login">login</a>
        <?php } ?>
        <?php $feedback->html(); ?>
    </div>
    <div id="body-content" class="span-20">
        <?php echo $this->rendered_template; ?>
    </div>
    <div class="span-4 last">
        Right sidebar
    </div>
    <div id="page-footer"  class="span-24">

    </div>
</div>
</body>
</html>