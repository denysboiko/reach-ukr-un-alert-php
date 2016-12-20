<?php if(!isset($this_is_page) || $this_is_page != true) { exit(0); } ?>
<div class="checkbox <?= !$field->is_valid ? 'has-error' : '' ?>">
	<div class="col-sm-10 col-sm-offset-2">
		<?= $field ?>
		<?php if(!$field->is_valid) : ?>
			<span class="help-block"><?= $field->error ?></span>
		<?php endif ?>
	</div>
	<div class="clearfix"></div>
</div>