<?php
	$this_is_page = true;

	include 'back/init.php';

	$conn = Framework::getDBConn();

	$session = new Session();
	if($session->user->is_authorized()) {
		Framework::redirect('./admin.php');
	}

	class LoginForm extends Form {
		function init() {
			$this->email = new EmailField(['label' => new Label(' Email: ')]);
			$this->password = new PasswordField(['label' => new Label(' Password: ')]);
		}
	}

	if(Framework::getRequestMethod() == 'POST') {
		$form = new LoginForm();
		$form->validate(Framework::getPost());
		if($form->is_valid) {

			$user = User::getAuthorize($form->email->value, $form->password->value);

			if($user instanceof Exception) {
				$form->is_valid = false;
				array_push($form->errors, $user->getMessage());
			} else if($user instanceof User) {
				$session->setUser($user);
				Framework::redirect('./admin.php');
			}
		}
	} else {
		$form = new LoginForm();
	}

	$template = new Template('tpls/index.tpl.php');



	echo $template->render([
		'form' => $form
		, 'nav' => 'login'
		, 'messages' => $session->fetch_messages()
	]);