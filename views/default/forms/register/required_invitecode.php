<?php

if (!elgg_get_plugin_setting('invite_only_network', 'users_invite')) {
	return;
}

echo elgg_view_input('text', [
	'name' => 'required_invitecode',
	'value' => elgg_extract('invitecode', $vars, get_input('invite_code')),
	'label' => elgg_echo('users:invite:required_invitecode'),
	'required' => true,
]);