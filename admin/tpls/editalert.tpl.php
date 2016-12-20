<?php if(!isset($this_is_page) || $this_is_page != true) { exit(0); } ?>
<?php $t->extend('tpls/layouts/base.tpl.php') ?>

<?php $t->start('title') ?>
	<?php if($form->id->value) : ?>
		Edit Alert
	<?php else : ?>
		New Alert
	<?php endif ?>
<?php $t->end() ?>

<?php
	$breadcrumbs = [
		['link' => './admin.php', 'title' => 'Alerst list']
		, ['title' => $form->id->value ? 'Edit Alert' : 'New Alert' ]
	];
?>


<?php
	$template_field = new Template('tpls/parts/form_field.tpl.php');
	$template_field_sub = new Template('tpls/parts/form_field_two_in_row.tpl.php');
?>


<?php $t->start('content') ?>
	
	<form method="post" class="form-horizontal" id="mainForm">

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

		<div class="form-group <?= !$form->ADMIN1->is_valid ? 'has-error' : '' ?>">
			<label class="control-label col-sm-2">
				Oblast*:
			</label>
			<div class="col-sm-10">
				<div id="oblastPicker"></div>
				<?php if(!$form->ADMIN1->is_valid) : ?>
					<span class="help-block"><?= $form->ADMIN1->error ?></span>
				<?php endif ?>
			</div>
		</div>
		<div class="form-group <?= !$form->ADMIN2->is_valid ? 'has-error' : '' ?>">
			<label for="raionPicker" class="control-label col-sm-2">
				Raion*:
			</label>
			<div class="col-sm-10">
				<select id="raionPicker" name="<?= $form->ADMIN2->attrs['name'] ?>" class="form-control"></select>

				<spam class="help-block" id="raionPickerOblastNotif">Choose oblast to be able to choose raion</spam>
				
				<?php if(!$form->ADMIN2->is_valid) : ?>
					<span class="help-block"><?= $form->ADMIN2->error ?></span>
				<?php endif ?>
			</div>
		</div>
		<div class="form-group <?= !$form->ADMIN4->is_valid ? 'has-error' : '' ?>">
			<label for="settlementPicker" class="control-label col-sm-2">
				Settlement*:
			</label>
			<div class="col-sm-10">
				<select id="settlementPicker" name="<?= $form->ADMIN4->attrs['name'] ?>" class="form-control"></select>
				
				<spam class="help-block" id="settlementPickerRaionNotif">Choose raion to be able to choose settlement</spam>
				
				<?php if(!$form->ADMIN4->is_valid) : ?>
					<span class="help-block"><?= $form->ADMIN4->error ?></span>
				<?php endif ?>
			</div>
		</div>



		<?php $form->GCA_NGCA->template = $template_field ?>
		<?= $form->GCA_NGCA->render() ?>

		<?php $form->CONFLICT_RELATED->template = $template_field ?>
		<?= $form->CONFLICT_RELATED->render() ?>

		<?php $form->ALERT_TYPE->template = $template_field ?>
		<?= $form->ALERT_TYPE->render() ?>

		<?php $form->CLUSTER->template = $template_field ?>
		<?= $form->CLUSTER->render() ?>

		<?php $form->NEED_TYPE->template = $template_field ?>
		<?= $form->NEED_TYPE->render() ?>

		<?php $form->AFFECTED->help_text = 'Hold down the Ctrl key while selecting multiple items' ?>
		<?php $form->AFFECTED->template = $template_field ?>
		<?= $form->AFFECTED->render() ?>

		<?php $form->DATE_REFERAL->template = $template_field ?>
		<?= $form->DATE_REFERAL->render() ?>

		<?php $form->DATE_UPDATE->template = $template_field ?>
		<?= $form->DATE_UPDATE->render() ?>

		<?php $form->SOURCE_INFO->template = $template_field ?>
		<?= $form->SOURCE_INFO->render() ?>

		<?php $form->CONFIRMATION->template = $template_field ?>
		<?= $form->CONFIRMATION->render() ?>

		<?php $form->INFORMANT->template = $template_field ?>
		<?= $form->INFORMANT->render() ?>

		<?php $form->NO_AFFECTED->template = $template_field ?>
		<?= $form->NO_AFFECTED->render() ?>

		<?php $form->NO_BENEFICIARIES->template = $template_field ?>
		<?= $form->NO_BENEFICIARIES->render() ?>

		<?php $form->GAP_BENEFICIARIES->template = $template_field ?>
		<?= $form->GAP_BENEFICIARIES->render() ?>

		<?php $form->RESPONSE_PARTNER->template = $template_field ?>
		<?= $form->RESPONSE_PARTNER->render() ?>

		<?php $form->REFERRAL_AGENCY->template = $template_field ?>
		<?= $form->REFERRAL_AGENCY->render() ?>

		<?php $form->DESCRIPTION->template = $template_field ?>
		<?= $form->DESCRIPTION->render() ?>

		<?php $form->CONTEXT->template = $template_field ?>
		<?= $form->CONTEXT->render() ?>

		<?php $form->ACTION->template = $template_field ?>
		<?= $form->ACTION->render() ?>

		<?php $form->STATUS->template = $template_field ?>
		<?= $form->STATUS->render() ?>

		<?php $form->UNCOVERED_NEEDS->template = $template_field ?>
		<?= $form->UNCOVERED_NEEDS->render() ?>

		<?php $form->COMMENTS->template = $template_field ?>
		<?= $form->COMMENTS->render() ?>
		
		<?php $form->ADDITIONAL_INFO_LINK->template = $template_field ?>
		<?= $form->ADDITIONAL_INFO_LINK->render() ?>
		

		<?php if($form->id->value) : ?>
			<?= $form->id ?>
		<?php endif ?>

		<div class="form-group text-center">
			<input type="submit" value="Save" class="btn btn-primary btn-lg">
		</div>
	</form>
	
	<script>
		var locationPicked = ['<?= $form->ADMIN1->value ?>', '<?= $form->ADMIN2->value ?>', '<?= $form->ADMIN4->value ?>'];
		
		(function() {
			var $form = $('#mainForm')
				, $oblastPicker = $('#oblastPicker')
				, $raionPicker = $('#raionPicker')
				, $raionPickerOblastNotif = $('#raionPickerOblastNotif')
				, $settlementPicker = $('#settlementPicker')
				, $settlementPickerRaionNotif = $('#settlementPickerRaionNotif')

			var prevValue = [
				$oblastPicker.find('input[type=radio]:checked').val()
				, $raionPicker.val()
				, $settlementPicker.val()
			]

			$.getJSON('../data/Adm4_Luhansk_Donestk.json', function(adm4) {
				$.each(adm4, function(oblastCode, oblastData) {
					var $oblastRadio = $('<input type="radio" name="<?= $form->ADMIN1->attrs['name'] ?>" />')
						.val(oblastCode)
					var $label = $('<label class="radio-inline">')
						.text(oblastData[0])
					$label.prepend($oblastRadio)

					$oblastPicker.append($label)
				})

				$form.on('change', function() {
					var oblastCode = $oblastPicker.find('input[type=radio]:checked').val()
						, oblastData = adm4[oblastCode]
					
					if(prevValue[0] != oblastCode) {
						// oblast changed
						prevValue[0] = oblastCode

						$raionPicker.empty()
						$settlementPicker.empty()

						$.each(oblastData[1], function(raionCode, raionData) {
							$raionPicker.append('<option value="' + raionCode + '">' + raionData[0] + '</option>')
						})
					}

					var raionCode = $raionPicker.val()
						, raionData = oblastData ? oblastData[1][raionCode] : undefined
					if (prevValue[1] != raionCode) {
						// raion Changed
						prevValue[1] = raionCode

						$settlementPicker.empty()

						$.each(raionData[1], function(settlementCode, settlementData) {
							$settlementPicker.append('<option value="' + settlementCode + '">' + settlementData[0] + '</option>')
						})
					}

					var settlementCode = $settlementPicker.val()
						, settlementData = raionData ? raionData[1][settlementCode] : undefined
					if (prevValue[2] != settlementCode) {
						// settlement changed
						prevValue[2] == settlementCode
					}

					$settlementPickerRaionNotif.toggle( !raionCode )
					$raionPickerOblastNotif.toggle( !oblastCode )

				})

				if(locationPicked[0]) {
					$oblastPicker
						.find('input[type=radio][value=' + locationPicked[0] + ']').prop('checked', 'checked')
						.trigger('change')
				}
				if(locationPicked[1]) {
					$raionPicker
						.val(locationPicked[1])
						.trigger('change')
				}
				if(locationPicked[2]) {
					$settlementPicker
						.val(locationPicked[2])
						.trigger('change')
				}
			})
		})()
	</script>
<?php $t->end() ?>

<?php $t->extend('tpls/layouts/base.tpl.php') ?>