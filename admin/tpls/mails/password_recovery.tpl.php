<?php if(!isset($this_is_page) || $this_is_page != true) { exit(0); } ?>
<?php $t->extend('tpls/mails/layout.tpl.php'); ?>
<?php $t->start('content') ?>
Hi!

To recover your password go to http://.../passwdrecovery.php?code=<?= $recovery_code ?>

<?php $t->end() ?>