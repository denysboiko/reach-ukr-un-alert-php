<?php
	$this_is_page = true;

	include 'back/init.php';

	$session = new Session();
	if($session->user->is_authorized()) {
		Framework::redirect('./admin.php');
	}


	$method = Framework::getRequestMethod();
	$request = Framework::getRequestData();

	$conn = Framework::getDBConn();


	class RegistrationForm extends Form {
		function init() {
            $this->name = new CharField(['label' => new Label(' Name*: ')]);
            $this->organization = new CharField(['label' => new Label(' Organization*: ')]);
			$this->phone = new CharField(['label' => new Label(' Phone: ')]);
            $this->email = new EmailField(['label' => new Label(' Email*: ')]);

			$this->new_password = new PasswordField(['label' => new Label(' Password*: ')]);
			$this->new_password_confirm = new PasswordField(['label' => new Label(' Confirmation*: ')]);
		}
	}


	$form = new RegistrationForm();

    $form->phone->required = false;

	$form->new_password->validator = function($value) {
		global $form;
		
		if($value != $form->new_password_confirm->value) {
			$form->new_password->error = 'Password and Password confirmation mismatch!';
			return false;
		}
		
		return true;
	};

	$form->new_password_confirm->validator = function($value) {
		global $form;
		
		if($value != $form->new_password->value) {
			$form->new_password_confirm->error = 'Password and Password confirmation mismatch!';
			return false;
		}
		
		return true;
	};





	if($method == 'POST') {
		$form->validate($request);
		if($form->is_valid) {
			$user = new User();
			$user->email = $form->email->value;
			$user->role = 'unconfirmed';
            $user->name = $form->name->value;
            $user->phone = $form->phone->value;
            $user->organization = $form->organization->value;
			$user->setPassword($form->new_password->value);

			try {
				$user->save();
			} catch (Exception $error) {
				$form->addError($error->getMessage());
			}


			if($form->is_valid) {
				$recovery_code = User::getRecoveryCode($user->email);

				$mail = new Email();
				$mail_tempalte = new Template('tpls/mails/registration_confirm.tpl.php');

				$mail->title = 'Registration confirmation';
				$mail->body = $mail_tempalte->render([
                    'name' => $form->name->value
                    , 'organization' => $form->organization->value
					, 'email' => $user->email
					, 'password' => $form->new_password->value
					, 'recovery_code' => $recovery_code
				]);

				$mail->send($user->email);

				$session->message('Confirmation mail has been sent to your email address.');

				Framework::redirect('./index.php');
			}
		}

	} else {
		if(isset($request['code'])) {

			$user = User::confirmEmail($request['code']);

			if($user instanceof Exception) {
				$session->message($user->getMessage());
			} else {

				$new_user_admins = User::getUserAdmins();
				print_r($new_user_admins);

				$mail_tempalte = new Template('tpls/mails/user_needs_to_be_confirmed.tpl.php');

				for($i = 0; $i < count($new_user_admins); ++$i) {
					$admin = $new_user_admins[$i];
					$mail = new Email();
					$mail->title = 'New user needs to be confirmed';
					$mail->body = $mail_tempalte->render([
						'email' => $user->email
						, 'id' => $user->id
					]);

					$mail->send($admin->email);
				}

				$session->message('Your email has been verified. Now, you should wait until our managers enable your account!');
			}

			Framework::redirect('./index.php');
		}


		// get method without data
		// nothing to do
	}

	$template = new Template('tpls/registration.tpl.php');

	echo $template->render([
		'form' => $form
		, 'nav' => 'login'
		, 'messages' => $session->fetch_messages()
	]);