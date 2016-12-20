<?php if(!isset($this_is_page) || $this_is_page != true) { exit(0); } ?>
<div class="form-group <?= !$field->is_valid ? 'has-error' : '' ?>">
	<?php $field->label->attrs['class'] = 'control-label col-sm-4' ?>
	<?= $field->label ?>
	<div class="col-sm-8">
		<?php $field->attrs['class'] = 'form-control' ?>
		<?= $field ?>
		<?php if(!$field->is_valid) : ?>
			<span class="help-block"><?= $field->error ?></span>
		<?php endif ?>
	</div>
	<div class="clearfix"></div>
</div>