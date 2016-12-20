<?php if(!isset($this_is_page) || $this_is_page != true) { exit(0); } ?>
<?php $t->extend('tpls/mails/layout.tpl.php'); ?>
<?php $t->start('content') ?>
Hi!

Email for account <?= $email ?> has been reset.
New password is: <?= $password ?>

<?php $t->end() ?>