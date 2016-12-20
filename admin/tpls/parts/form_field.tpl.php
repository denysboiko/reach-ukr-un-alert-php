<?php if(!isset($this_is_page) || $this_is_page != true) { exit(0); } ?>
<div class="form-group <?= !$field->is_valid ? 'has-error' : '' ?>">
	<?php $field->label->attrs['class'] = 'control-label col-sm-2' ?>
	<?= $field->label ?>
	<div class="col-sm-10">
		<?php $field->attrs['class'] = 'form-control' ?>
		<?= $field ?>
		<?php if($field->help_text) : ?>
			<div class="help-block"><?= $field->help_text ?></div>
		<?php endif ?>
		<?php if(!$field->is_valid) : ?>
			<span class="help-block"><?= $field->error ?></span>
		<?php endif ?>
	</div>
	<div class="clearfix"></div>
</div>