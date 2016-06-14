<?php
$entity = elgg_extract('entity', $vars);
?>

<div>
	<label><?= elgg_echo('users:invite:settings:invite_only_network') ?></label>
	<div class="elgg-text-help"><?= elgg_echo('users:invite:settings:invite_only_network:help') ?></div>
	<?php
	echo elgg_view('input/select', array(
		'name' => 'params[invite_only_network]',
		'value' => $entity->invite_only_network,
		'options_values' => array(
			0 => elgg_echo('option:no'),
			1 => elgg_echo('option:yes'),
		)
	));
	?>
</div>
