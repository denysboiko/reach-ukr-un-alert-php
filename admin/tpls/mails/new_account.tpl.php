<?php if(!isset($this_is_page) || $this_is_page != true) { exit(0); } ?>
<?php $t->extend('tpls/mails/layout.tpl.php'); ?>
<?php $t->start('content') ?>
Hi <?= $name ?> from <?= $organization ?>!

New account for email <?= $email ?> has been registered.
Your password is: <?= $password ?>

<?php $t->end() ?>