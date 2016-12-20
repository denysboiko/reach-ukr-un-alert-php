<?php if(!isset($this_is_page) || $this_is_page != true) { exit(0); } ?>
<?php $t->extend('tpls/mails/layout.tpl.php'); ?>
<?php $t->start('content') ?>
Hi!

New account for email <?= $email ?> has been registered.
Your password is: <?= $password ?>

Now, you need to confirm your email.
To do this, go to:
<?= "./admin/registration.php?code=$recovery_code" ?>

<?php $t->end() ?>