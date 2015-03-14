# Controller Example #

for the request `http://127.0.0.1/aabot/foo/bar` the expected app resources are:


```
app
 \
  controller
   \
    Foo.php

view
 \
  foo
  |\
  | bar.php
  |
  \
   layout
    \
     default.php
```

Where Foo.php would look like below with public method bar()

```
<?php
class Controller_Foo extends Base_Controller {
	
	public function bar() {
		$this->$payload->->message = "Message From ".__METHOD__;
	}
}
?>
```

and bar.php would look something like this

```
<?php $payload->title = 'boo'; ?>
<h1>Hello, <?php echo ($payload->->message); ?></h1>
```
_Note you can assign values in a template to `$payload` to be rendered in the surrounding layout (i.e. `$payload->title = 'boo';`)_

and default.php would look something like this:

```
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title><?php echo ($payload->title); ?></title>
</head>
<body>
<?php echo $this->template_contents; ?>
</body>
</html>
```

## Not using a Layout ##
```
/**
  * we can optionally set the layout to null.  This signals to the
  * Controller that we are not using a contoller
  */
  $this->layout_path = null;
```

So if you normally get:

```
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>boo</title>
</head>
<body>
<h1>Hello, Message From Controller_Foo::bar</h1>
</body>
</html>
```

with layout set to null you would get:

```
<h1>Hello, Message From Controller_Foo::bar</h1>
```