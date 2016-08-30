User Invitations for Elgg
=========================
![Elgg 2.0](https://img.shields.io/badge/Elgg-2.0.x-orange.svg?style=flat-square)

## Features

 * Allows users to invite new users by email
 * An option to create an invite-only network
 * Keeps track of all invitations to the same email address
 * Creates friend requests when invitations are accepted

## Notes

 * Registration must be enabled on the site for this plugin to work
 * In an invite-only network, uservalidationbyemail will be bypassed, 
   as it is assumed that users would have received their invitation code by email

## Developer Notes

### Creating Invites

Other plugins may centralize off-site invitations and attach custom behaviour to the invites.
For example, to invite non-registered users to a group by their email:

```php

$invite = users_invite_create_user_invite($email);
add_entity_relationship($invite->guid, 'group_invite', $group->guid);
add_entity_relationship($invite->guid, 'invited_by', $inviter->guid);

// generate a registration link to include in the notification
$registration_link = elgg_trigger_plugin_hook('registration_link', 'site', [
	'email' => $email,
	'friend_guid' => $inviter->guid,
		], elgg_normalize_url('register'));


// implement a custom handler
elgg_register_plugin_hook_handler('accept', 'invite', function($hook, $type, $return, $params) {

	$invite = $params['invite'];
	$user = $params['user'];

	$groups = elgg_get_entities_from_relationship([
		'relationship' => 'group_invite',
		'relationship_guid' => $invite->guid,
		'limit' => 0,
	]);

	if (!$groups) {
		return;
	}

	foreach ($groups as $group) {
		// Let users confirm individual group invitations
		add_entity_relationship($group->guid, 'invited', $user->guid);
	}
});
```
