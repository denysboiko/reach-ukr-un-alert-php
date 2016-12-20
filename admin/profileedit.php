<?php
	$this_is_page = true;

	include 'back/init.php';

	$session = new Session();

	if(!$session->user->is_authorized()) {
		Framework::redirect('./index.php');
	}

	$method = Framework::getRequestMethod();
	$request = Framework::getRequestData();

	$conn = Framework::getDBConn();


	class ProfileEditForm extends Form {
		function init() {
			$this->email = new EmailField(['label' => new Label(' Email*: '), 'required' => true]);

			$this->new_password = new PasswordField(['label' => new Label(' Password: '), 'required' => false ]);
			$this->new_password_confirm = new PasswordField(['label' => new Label(' Confirmation: '), 'required' => false]);
			$this->notify_new_password = new CheckboxField(['label' => new Label(' send me new password on email '), 'required' => false]);
			
			$this->notify_on_new_user = new BooleanField([ 'label' => new Label(' notify me on new users registration '), 'required' => false]);

			// this field must be last. because validation of other fields can make it required = true
			$this->password = new PasswordField(['label' => new Label(' Current password: '), 'required' => false ]);;
		}
	}

	$form = new ProfileEditForm();

	$form->email->validator = function($value) {
		global $session;
		if($value == $session->user->email) {
			// password not changed
			return true;
		}

		global $form;

		// current password is required when changing email
		$form->password->required = true;
		
		// apply default email validator;
		$test = new EmailField(['required' => true]);
		$test->value = $value;
		$test->validate();
		$form->email->error = $test->error;
		$form->email->is_valid = $test->is_valid;

		return $form->email->is_valid;
	};


	$form->new_password->validator = function($value) {
		global $form;
		if(!empty($value)) {

			// current password is required when changing password
			$form->password->required = true;

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

			// current password is required when changing password
			$form->password->required = true;

			if($value != $form->new_password->value) {
				$form->new_password_confirm->error = 'Password and Password confirmation mismatch!';
				return false;
			}
		}
		return true;
	};

	$form->password->validator = function($value) {
		global $session;
		global $form;

		if($form->password->required || !empty($value)) {
			$test = User::getAuthorize($session->user->email, $value);

			if(!($test instanceof User)) {
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


	if($method == 'GET') {
		$form->email->value = $session->user->email;
		$form->notify_on_new_user->value = $session->user->notify_on_new_user;
	} else if ($method == 'POST') {
		$form->validate($request);

		$user = $session->user;


		if($form->is_valid) {
			$user->email = $form->email->value;
			if(!empty($form->new_password->value)) {
				$user->setPassword($form->password->value);
			}

			if($user->checkRole(['admin'])) {
				$user->notify_on_new_user = $form->notify_on_new_user->value;
			}
			
			try {
				$user->save();
			} catch (Exception $error) {
				$form->addError($error->getMessage());
			}
		}

		if($form->is_valid) {
			$session->message("Your account has been changed!");
			
			if($form->notify_new_password->value) {
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

			Framework::redirect('./admin.php');
		}

	}




	$template = new Template('tpls/profileedit.tpl.php');

	echo $template->render([
		'user' => $session->user
		, 'form' => $form
		, 'messages' => $session->fetch_messages()
		, 'nav' => 'profile_edit'
	]);