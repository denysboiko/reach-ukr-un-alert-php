<?php
	$this_is_page = true;

	include 'back/init.php';

	$session = new Session();

	$method = Framework::getRequestMethod();
	$request = Framework::getRequestData();

	if(!$session->user->is_authorized()) {
		Framework::redirect('./index.php');
	}

	if(!$session->user->checkRole(['admin'])) {
		Framework::httpError(403);
	}

	if(isset($request['id']) && $request['id'] == $session->user->id) {
		$session->message('You cant edit your own account!');
		Framework::redirect('./listuser.php');
	}


	$template = new Template('tpls/edituser.tpl.php');

	$conn = Framework::getDBConn();










	/*===================================
	=            Create from            =
	===================================*/
	
	class EditUserForm extends Form {
		function init() {
			$this->id = new HiddenField(['required' => false]);
			$this->email = new EmailField(['label' => new Label(' Email*: '), 'required' => true]);
			$this->role = new SelectField(['label' => new Label(' Role*: '), 'values' => ['admin' => 'Administrator', 'editor' => 'Editor', 'disabled' => 'Accuont disabled', 'unconfirmed' => 'Email not confirmed'], 'required' => true]);

			$this->new_password = new PasswordField(['label' => new Label(' Password: '), 'required' => false ]);
			$this->new_password_confirm = new PasswordField(['label' => new Label(' Confirmation: '), 'required' => false]);
			$this->notify_new_password = new CheckboxField(['label' => new Label(' notify user, that password has been reset '), 'required' => false]);
			$this->notify_new_account = new CheckboxField(['label' => new Label(' send email to user with account information '), 'required' => false]);
		}
	}

	$form = new EditUserForm();

	$form->new_password->validator = function($value) {
		global $form;
		if(!empty($value)) {
			if($value != $form->new_password_confirm->value) {
				$form->new_password->error = 'Password and Password confirmation mismatch!';
				return false;
			}
		}
		return true;
	};

	$form->new_password_confirm->validator = function($value) {
		global $form;
		if(!empty($value)) {
			if($value != $form->new_password->value) {
				$form->new_password_confirm->error = 'Password and Password confirmation mismatch!';
				return false;
			}
		}
		return true;
	};
	
	$form->notify_new_password->validator = function($value) {
		global $form;
		if($value) {
			if(empty($form->new_password->value) || empty($form->new_password_confirm->value)) {
				$form->notify_new_password->error = 'You cant notify user about new password, without new password!';
				return false;
			}
		}
		return true;
	};


	$form->validator = function() {
		global $form;
		
		if(!$form->id->value && !$form->new_password->value) {
			$form->addError('You cant create user without password!');
			return false;
		}

		return true;
	};

	/*=====  End of Create from  ======*/










	/*==========================================================================
	=            If its editing of existing user, we get it from db            =
	==========================================================================*/
	
	if(isset($request['id']) && !empty($request['id'])) {
		$user = $db->select('user', ['id', 'email', 'role'], [ 'id' => $request['id'] ]);
		$user = count($user) == 1 ? $user[0] : null;
		if(!$user) {
			Framework::httpError(404);
		}
	} else {
		$user = null;
	}
	
	/*=====  End of If its editing of existing user, we get it from db  ======*/









	/*=========================================================================
	=            Init or Validate form and if save user if success            =
	=========================================================================*/
	
	if($method == 'GET') {
		if($user) {
			$form->validate($user);
		}
	} else if ($method == 'POST') {
		$form->validate($request);

		if($form->is_valid) {
			$user = new User($user ? $user['id'] : null ); // existing or new
			$user->email = $request['email'];
			$user->role = $request['role'];

			if($form->new_password->value) {
				$user->setPassword($form->new_password->value);
			}
			
			try {
				$user->save();
			} catch (Exception $error) {
				$form->addError($error->getMessage());
			}
		}

		if($form->is_valid) {
			$session->message("User <b>{$user->email}</b> has been saved!");
			
			if($form->id->value && $form->notify_new_password->value) {
				$email = new Email();
				$email->title = 'Your password has been reset!';
				$mail_tpl = new Template('tpls/mails/password_reset.tpl.php');
				$email->body = $mail_tpl->render([
					'email' => $form->email->value
					, 'password' => $form->new_password->value
				]);
				$email->send($form->email->value);

				$session->message("Email with new password has been sent to <b>{$user->email}</b>!");
			}

			if(!$form->id->value && $form->notify_new_account->value) {
				$email = new Email();
				$email->title = 'New account details!';
				$mail_tpl = new Template('tpls/mails/new_account.tpl.php');
				$email->body = $mail_tpl->render([
					'email' => $form->email->value
					, 'password' => $form->new_password->value
				]);
				$email->send($form->email->value);

				$session->message("Email with new account details has been sent to <b>{$user->email}</b>!");
			}


			Framework::redirect('./listuser.php');
		}
	}
	
	/*=====  End of Init or Validate form and if save user if success  ======*/










	echo $template->render([
		'user' => $session->user
		, 'nav' => 'user_management'
		, 'eduser' => $user
		, 'form' => $form
		, 'messages' => $session->fetch_messages()
	]);