<?php if(!isset($this_is_page) || $this_is_page != true) { exit(0); } ?>
<?php $t->extend('tpls/layouts/base.tpl.php') ?>

<?php $t->start('title') ?>
	User management
<?php $t->end() ?>

<?php
	$breadcrumbs = [
		['link' => './admin.php', 'title' => 'Admin']
		, ['title' => 'User management' ]
	];
?>

<?php $t->start('content') ?>

	<p class="text-right">
		<a href="./edituser.php" class="btn btn-primary"> <span class="glyphicon glyphicon-plus"></span> New user</a>
	</p>

	<table class="table">
		<?php foreach ($users as $value) : ?>
			<tr>
				<td style="vertical-align:middle;">
					<b><?= $value['email'] ?></b>
				</td>

				<td style="width:5em;text-align:center;vertical-align:middle;">
					<?php switch($value['role']) :
						case 'admin' : ?>
							<span class="label label-danger"><?= $value['role'] ?></span>
						<?php break; case 'editor' : ?>
							<span class="label label-warning"><?= $value['role'] ?></span>
						<?php break; case 'disabled' : ?>
							<span class="label label-default"><?= $value['role'] ?></span>
						<?php break; default : ?>
							<span class="label label-default"><?= $value['role'] ?></span>
					<?php endswitch ?>
				</td>

				<td style="width:5em;text-align:center;vertical-align:middle;">
					<?php if($value['id'] != $user->id) : ?>
						<a href="<?= "./edituser.php?id=$value[id]" ?>" class="btn btn-primary btn-sm"> <span class="glyphicon glyphicon-pencil"></span> Edit</a>
					<?php else : ?>
						<span class="badge" title="You cant edit your own account here!"> &emsp; ! &emsp; </span>
					<?php endif ?>
				</td>
			</tr>
		<?php endforeach ?>
	</table>

<?php $t->end() ?>