<?php
	$this_is_page = true;

	include 'back/init.php';

	$session = new Session();

	if($session->user->is_authorized()) {
		Framework::redirect('./admin.php');
	}

	$method = Framework::getRequestMethod();
	$request = Framework::getRequestData();

	class PasswordRecoveryForm extends Form {
		function init() {
			$this->email = new EmailField(['label' => new Label(' Email*: '), 'required' => true]);
		}
	}

	$form = new PasswordRecoveryForm();


	if($method == 'GET') {
		if(isset($request['code'])) {

			$user = User::passwordRecovery($request['code']);

			if($user instanceof Exception) {
				$session->message($user->getMessage());
			} else {

				$mail = new Email();
				$mail_tempalte = new Template('tpls/mails/password_recovery_new_password.tpl.php');

				$mail->title = 'Password recovery. New password';
				$mail->body = $mail_tempalte->render([
					'new_password' => $user->new_password
				]);

				$mail->send($user->email);

				$session->message('New password has been sent to your email!');
			}

		}
	} else if($method == 'POST') {
		$form->validate($request);

		if($form->is_valid) {
			$recovery_code = User::getRecoveryCode($form->email->value);
			if($recovery_code instanceof Exception) {
				$form->addError($recovery_code->getMessage());
			} else {

				$mail = new Email();
				$mail_tempalte = new Template('tpls/mails/password_recovery.tpl.php');

				$mail->title = 'Password recovery';
				$mail->body = $mail_tempalte->render([
					'recovery_code' => $recovery_code
				]);

				$mail->send($form->email->value);

				$session->message('Recovery link has been sent to your email.');
			}
		}
	}


	$template = new Template('tpls/passwdrecovery.tpl.php');

	echo $template->render([
			'user' => $session->user
			, 'form' => $form
			, 'messages' => $session->fetch_messages()
			, 'nav' => 'login'
		]);
