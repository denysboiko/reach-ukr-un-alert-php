<?php
	$this_is_page = true;

	include 'back/init.php';

	$session = new Session();

	if(!$session->user->is_authorized()) {
		Framework::redirect('./index.php');
	}

	if(!$session->user->checkRole(['admin'])) {
		Framework::httpError(403);
	}

	$template = new Template('tpls/listuser.tpl.php');

	$conn = Framework::getDBConn();

	$users = $conn->select('user', ['id', 'email', 'role']);

	echo $template->render([
		'user' => $session->user
		, 'nav' => 'user_management'
		, 'users' => $users
		, 'messages' => $session->fetch_messages()
	]);