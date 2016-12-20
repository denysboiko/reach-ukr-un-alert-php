<?php
	$this_is_page = true;

	include 'back/init.php';

	$session = new Session();

	$session->destroy();

	Framework::redirect('./index.php');