<?php if(!isset($this_is_page) || $this_is_page != true) { exit(0); } ?>
<?php $t->extend('tpls/mails/layout.tpl.php'); ?>
<?php $t->start('content') ?>

Hi!

New user was registered with the email address <?= $email ?>.
Go to <?= "./admin/edituser.php?id=$id" ?> to verify these account.

<?php $t->end() ?>