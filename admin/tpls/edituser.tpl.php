<?php if(!isset($this_is_page) || $this_is_page != true) { exit(0); } ?>
<?php $t->extend('tpls/layouts/base.tpl.php') ?>

<?php $t->start('title') ?>
	<?php if($form->id->value) : ?>
		Edit User
	<?php else : ?>
		New User
	<?php endif ?>
<?php $t->end() ?>

<?php
	$breadcrumbs = [
		['link' => './admin.php', 'title' => 'Admin']
		, ['link' => './listuser.php', 'title' => 'User management']
		, ['title' => $form->id->value ? 'Edit user' : 'New user' ]
	];
?>

<?php
	$template_field = new Template('tpls/parts/form_field.tpl.php');
	$template_field_checkbox = new Template('tpls/parts/form_field_checkbox.tpl.php');
?>

<?php $t->start('content') ?>

	<div class="row">
		<div class="col-md-6 col-md-offset-3">

			<form method="post" class="form-horizontal">
				<?php if(!$form->is_valid) : ?>
					<div class="form-group has-error">
						<?php if(empty($form->errors)) : ?>
							<span class="help-block col-md-10 col-md-offset-2">Form invalid</span>
						<?php endif ?>

						<?php foreach($form->errors as $error) : ?>
							<span class="help-block col-md-10 col-md-offset-2"><?= $error ?></span>
						<?php endforeach; ?>
					</div>
				<?php endif ?>



				<?php $form->email->template = $template_field ?>
				<?= $form->email->render() ?>

				<?php $form->role->template = $template_field ?>
				<?= $form->role->render() ?>

				<hr>

				<p class="help-text col-md-10 col-md-offset-2">
					Password reset.
					<br />
					Leave it blank to keep old password.
				</p>
				
				<?php $form->new_password->template = $template_field ?>
				<?= $form->new_password->render() ?>

				<?php $form->new_password_confirm->template = $template_field ?>
				<?= $form->new_password_confirm->render() ?>

				<?php if($form->id->value) : ?>
					<?php $form->notify_new_password->template = $template_field_checkbox ?>
					<?= $form->notify_new_password->render() ?>
				<?php endif ?>
				
				<hr>

				<?php if(!$form->id->value) : ?>
					<?php $form->notify_new_account->template = $template_field_checkbox ?>
					<?= $form->notify_new_account->render() ?>
					<br>
				<?php endif ?>

				<?= $form->id ?>

				<div class="form-group text-center">
					<input type="submit" value="Save" class="btn btn-primary btn-lg">
				</div>
			</form>
		</div>
	</div>

<?php $t->end() ?>