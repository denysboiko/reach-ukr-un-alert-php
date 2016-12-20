<?php if(!isset($this_is_page) || $this_is_page != true) { exit(0); } ?>
<?php $t->extend('tpls/layouts/base.tpl.php') ?>

<?php $t->start('title') ?>
	Alerts
<?php $t->end() ?>

<?php
	$breadcrumbs = [
		['title' => 'Alerst list']
	];
?>


<?php if(empty($data)) : ?>
	<?php $t->start('content') ?>
		<div class="row">
			<div class="col-md-6 col-md-offset-3">
				<div class="well well-lg text-center">
					<p class="lead">
						You have no added alerts yet!
					</p>
					<p class="lead">
						<a href="./editalert.php" class="btn btn-primary"> <span class="glyphicon glyphicon-plus"></span> New alert</a>
					</p>
				</div>
			</div>
		</div>
	<?php $t->end() ?>
<?php else : ?>
	<?php $t->start('content-fluid') ?>
		
		<nav class="text-center">
			<ul class="pagination">
				<?php for($i = $page_num_max; $i >= 0; --$i) : ?>
					<li class="<?= $i == $page_num ? 'active' : '' ?>"><a href="<?= "./admin.php?page=$i" ?>"><?= $i + 1 ?></a></li>
				<?php endfor ?>
			</ul>
		</nav>

		<p class="text-right">
			<a href="./editalert.php" class="btn btn-primary"> <span class="glyphicon glyphicon-plus"></span> New alert</a>
		</p>


		<div class="data-table">
			<table id="dataTable">
				<thead>
					<tr>
						<td style="width:120px;">Oblast / <wbr>Raion / <wbr>Settlement</td>
						<td style="width:80px;">Covered / Affected</td>
						<td style="width:100px;">Type alerts</td>
						<td style="width:140px;">Type needs</td>
						<td style="width:20%;">Context</td>
						<td style="width:20%;">Description</td>
						<td style="width:100px;">Additional information</td>
						<td style="width:80px;">Conflict related</td>
						<td style="width:100px;">Date referral</td>
						<td style="width:80px;">Gaps-not covered need</td>
						<?php if ($user->checkRole(['admin'])) : ?>
							<td>Added by</td>
						<?php endif ?>
						<td style="width:80px;">Edit</td>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($data as $value) : ?>
						<tr>
							<td>
								<div class="data-table-spoiled"><?= $value['OBLAST'] ?> / <?= $value['RAION'] ?> / <?= $value['SETTLEMENTS'] ?></div>
							</td>
							<td style="text-align:center;">
								<div class="data-table-spoiled"><?= $value['GAP_BENEFICIARIES'] ?> / <?= $value['NO_AFFECTED'] ?></div>
							</td>
							<td style="text-align:center;">
								<div class="data-table-spoiled"><?= $value['ALERT_TYPE'] ?></div>
							</td>
							<td>
								<div class="data-table-spoiled"><?= $value['NEED_TYPE'] ?></div>
							</td>
							<td>
								<div class="data-table-spoiled"><?= $value['CONTEXT'] ?></div>
							</td>
							<td>
								<div class="data-table-spoiled"><?= $value['DESCRIPTION'] ?></div>
							</td>
							<td style="text-align:center;">
								<div class="data-table-spoiled"><?php if(!empty(trim($value['ADDITIONAL_INFO_LINK']))) : ?><a href="<?= $value['ADDITIONAL_INFO_LINK'] ?>" target="_blank">Additional Info</a><?php endif ?></div>
							</td>
							<td style="text-align:center;">
								<div class="data-table-spoiled"><?= $value['CONFLICT_RELATED'] ?></div>
							</td>
							<td style="text-align:center;">
								<div class="data-table-spoiled"><?= $value['DATE_REFERAL'] ?></div>
							</td>
							<td style="text-align:center;">
								<div class="data-table-spoiled"><?= $value['UNCOVERED_NEEDS'] ?></div>
							</td>
							<?php if ($user->checkRole(['admin'])) : ?>
								<td>
									<div class="data-table-spoiled"><?= $value['email'] ?></div>
								</td>
							<?php endif ?>
							<td>
								<div class="data-table-spoiled"><a href="<?= "./editalert.php?id=$value[id]" ?>" class="btn btn-primary btn-sm"> <span class="glyphicon glyphicon-pencil"></span> Edit</a></div>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>


		<script>
			(function() {
				$('.data-table-spoiled').each(function() {
					var div = this
						, $div = $(div)

					// it would be fine to reset this property on window resize, but i dont care
					$div.attr('data-jajaja', div.offsetHeight + '-' + div.scrollHeight)
					$div.toggleClass('data-table-spoiled-ready', div.offsetHeight < div.scrollHeight)

					$div.on('mouseenter', function() {
						if(div.offsetHeight >= div.scrollHeight) { return }
						
						var $spoiler = $('<div>')
						$div.append($spoiler)
						$spoiler
							.toggleClass('data-table-spoiler', true)
							.css({ 'height': div.scrollHeight + 'px' })
							.text($div.text())
					})
					$div.on('mouseleave', function() {
						$div.find('.data-table-spoiler').remove()
					})
				})
			})()
		</script>
	<?php $t->end() ?>

<?php endif ?>



