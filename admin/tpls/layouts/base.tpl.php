<?php if(!isset($this_is_page) || $this_is_page != true) { exit(0); } ?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title><?= $t->part('title') ?> | Admin | Ukrainian alerts</title>

	<link href='https://fonts.googleapis.com/css?family=Roboto:400,500' rel='stylesheet' type='text/css'>

	<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>

	<script src="static/lib/jquery-ui/jquery-ui.min.js"></script>
	<link rel="stylesheet" href="static/lib/jquery-ui/jquery-ui.min.css">

	<link rel="stylesheet" href="static/bootstrap/css/bootstrap.min.css">
	<script src="static/bootstrap/js/bootstrap.min.js"></script>

	<link rel="stylesheet" href="static/css/style.css">
</head>
<body>
	<nav class="navbar navbar-inverse navbar-static-top">
		<div class="container">
			<a href="./admin.php" class="navbar-brand <?= $nav == 'alert_management' ? 'active' : '' ?>">Admin | Ukrainian alerts</a>
			<ul class="nav navbar-nav navbar-right">
				<?php if(isset($user) && $user->is_authorized()) : ?>
					<?php if($user->role == 'admin') : ?>
						<li class="<?= $nav == 'user_management' ? 'active' : '' ?>"><a href="./listuser.php">User management</a></li>
					<?php endif; ?>
					<li><a href="./logout.php">Logout</a></li>
				<?php else : ?>
					<li class="<?= $nav == 'login' ? 'active' : '' ?>"><a href="./index.php">Login</a></li>
				<?php endif; ?>
			</ul>
		</div>
	</nav>

	<div class="container">
		<?php if(isset($user) && $user->is_authorized()) : ?>
			<div class="userinfo text-right">
				Logged in as: <?= $user->email ?>
				|
				<?php if($nav == 'profile_edit') : ?>
					<u>Edit profile</u>
				<?php else : ?>
					<a href="./profileedit.php">Edit profile</a>
				<?php endif ?>
			</div>
		<?php endif ?>

		<?php foreach($messages as $message) : ?>
			<p class="alert alert-warning"><?= $message ?></p>
		<?php endforeach ?>


		<h2 class="text-center"><?= $t->part('title') ?></h2>
		<?php if(isset($breadcrumbs)) : ?>
			<br>
			<ol class="breadcrumb">
				<?php foreach ($breadcrumbs as $breadcrumb) : ?>
					<?php if(isset($breadcrumb['link']) && !empty($breadcrumb['link'])) : ?>
						<li><a href="<?= $breadcrumb['link'] ?>"><?= $breadcrumb['title'] ?></a></li>
					<?php else : ?>
						<li class="active"><?= $breadcrumb['title'] ?></li>
					<?php endif ?>
				<?php endforeach ?>
			</ol>
		<?php endif ?>
		<br>
	</div>

	<div class="container-fluid">
		<?= $t->part('content-fluid') ?>
	</div>
	
	<div class="container">
		<?= $t->part('content') ?>
	</div>


	<script src="static/js/script.js"></script>
</body>
</html>