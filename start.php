<?php

/**
 * Invite users
 *
 * @author Ismayil Khayredinov <info@hypejunction.com>
 * @copyright Copyright (c) 2016, Ismayil Khayredinov
 */
require_once __DIR__ . '/autoloader.php';

elgg_register_event_handler('init', 'system', 'users_invite_init');

/**
 * Initialize the plugin
 * @return void
 */
function users_invite_init() {

	// Pages
	elgg_extend_view('filters/friends', 'filters/users/invite', 100);
	elgg_register_plugin_hook_handler('route', 'friends', 'users_invite_route_friends');

	// Menus
	elgg_register_plugin_hook_handler('register', 'menu:page', 'users_invite_setup_page_menu');

	// Actions
	elgg_register_action('users/invite', __DIR__ . '/actions/users/invite.php');

	// Events
	elgg_register_event_handler('create', 'user', 'users_invite_user_created_event');

	// Invite-only network
	elgg_extend_view('register/extend', 'forms/register/required_invitecode', 100);
	elgg_register_plugin_hook_handler('action', 'register', 'users_invite_validate_required_invitecode', 1);
	elgg_register_plugin_hook_handler('registration_link', 'site', 'users_invite_generate_registration_link');
}

/**
 * Route friends page
 * 
 * @param string $hook   "route"
 * @param string $type   "friends"
 * @param mixed  $return Route details
 * @param array  $params Hook params
 * @return mixed
 */
function users_invite_route_friends($hook, $type, $return, $params) {

	if (!is_array($return)) {
		return;
	}

	$identifier = elgg_extract('identifier', $return);
	$segments = (array) elgg_extract('segments', $return, []);

	if ($identifier != 'friends') {
		return;
	}

	$username = array_shift($segments);
	$page = array_shift($segments);

	switch ($page) {
		case 'invite' :
			echo elgg_view_resource('friends/invite', [
				'username' => $username,
			]);
			return false;
	}
}

/**
 * Setup page menu
 * 
 * @param string         $hook   "register"
 * @param string         $type   "menu:page"
 * @param ElggMenuItem[] $return Menu
 * @param array          $params Hook params
 * @return array
 */
function users_invite_setup_page_menu($hook, $type, $return, $params) {

	if (!elgg_in_context('friends')) {
		return;
	}

	$page_owner = elgg_get_page_owner_entity();

	$return[] = ElggMenuItem::factory([
		'name' => 'friends:invite',
		'href' => "friends/$page_owner->username/invite",
		'text' => elgg_echo('users:invite:invite'),
	]);

	return $return;
}

/**
 * Returns an invite object
 * 
 * @param string $email Email address
 * @return ElggObject|false
 */
function users_invite_get_user_invite($email) {

	$invites = elgg_get_entities_from_metadata(array(
		'types' => 'object',
		'subtypes' => 'user_invite',
		'metadata_name_value_pairs' => array(
			'name' => 'email',
			'value' => $email,
		),
		'limit' => 1,
	));

	return $invites ? $invites[0] : false;
}

/**
 * Creates a new user invite
 *
 * @param string $email Email address
 * @return ElggObject
 */
function users_invite_create_user_invite($email) {
	$user_invite = users_invite_get_user_invite($email);
	if ($user_invite) {
		return $user_invite;
	}

	$ia = elgg_set_ignore_access(true);

	$site = elgg_get_site_entity();

	$user_invite = new ElggObject();
	$user_invite->subtype = 'user_invite';
	$user_invite->owner_guid = $site->guid;
	$user_invite->container_guid = $site->guid;
	$user_invite->access_id = ACCESS_PUBLIC;
	$user_invite->email = $email;
	$user_invite->save();

	elgg_set_ignore_access($ia);

	return $user_invite;
}

/**
 * Convert group invites to group invitations and friend requests
 *
 * @param string   $event "create"
 * @param string   $type  "user"
 * @param ElggUser $user  User entity
 * @return void
 */
function users_invite_user_created_event($event, $type, $user) {

	$email = $user->email;
	$user_invite = users_invite_get_user_invite($email);
	if (!$user_invite) {
		return;
	}

	$ia = elgg_set_ignore_access(true);

	if (elgg_is_active_plugin('friend_request')) {
		// We don't want to make people friends automatically
		// Least we can do is create a friend request, so that the new user can confirm it
		$inviters = new ElggBatch('elgg_get_entities_from_relationship', array(
			'types' => 'user',
			'relationship' => 'invited_by',
			'relationship_guid' => $user_invite->guid,
			'inverse_relationship' => false,
			'limit' => 0,
		));

		foreach ($inviters as $inviter) {
			/* @var $inviter ElggUser */
			if (!$inviter->isFriendsWith($user->guid)) {
				add_entity_relationship($inviter->guid, 'friendrequest', $user->guid);
			}
		}
	}

	if (USERS_INVITE_VALIDATED === true) {
		elgg_set_user_validation_status($user->guid, true, 'invitation_code');
	}

	// Allow other plugins to attach custom logic
	$params = [
		'invite' => $user_invite,
		'user' => $user,
	];
	if (elgg_trigger_plugin_hook('accept', 'invite', $params, true)) {
		$user_invite->delete();
	}

	elgg_set_ignore_access($ia);
}

/**
 * Validate required invitation code
 * 
 * @param string $hook   "action"
 * @param string $type   "register"
 * @param bool   $return Proceed with action?
 * @param array  $params Hook params
 * @return void
 */
function users_invite_validate_required_invitecode($hook, $type, $return, $params) {

	if (!elgg_get_plugin_setting('invite_only_network', 'users_invite')) {
		return;
	}

	$email = get_input('email');
	$code = get_input('required_invitecode');

	$invites = elgg_get_entities_from_metadata(array(
		'types' => 'object',
		'subtypes' => 'user_invite',
		'metadata_name_value_pairs' => array(
			array(
				'name' => 'email',
				'value' => $email,
			),
			array(
				'name' => 'invite_codes',
				'value' => $code,
			)
		),
		'count' => true,
	));

	if (!$invites) {
		elgg_make_sticky_form('register');
		register_error(elgg_echo('users:invite:required_invitecode:mismatch'));
		forward(REFERRER);
	}

	define('USERS_INVITE_VALIDATED', true);
}

/**
 * Generate registration link
 * 
 * @param string $hook   "registration_link"
 * @param string $type   "site"
 * @param string $return Link
 * @param string $params Hook params
 * @uses $params['email']
 * @return string
 */
function users_invite_generate_registration_link($hook, $type, $return, $params) {

	if (!$return) {
		$return = elgg_normalize_url('register');
	}

	$email = elgg_extract('email', $params);
	if (!$email) {
		return false;
	}

	$user_invite = users_invite_get_user_invite($email);
	if (!$user_invite) {
		$user_invite = users_invite_create_user_invite($email);
	}

	$friend_guid = elgg_extract('friend_guid', $params);
	if ($friend_guid) {
		add_entity_relationship($user_invite->guid, 'invited_by', $friend_guid);
	}

	$time = time();

	// We won't be validating HMAC, but it may come in handy
	// The token will be stored on the invite object along with the email,
	// which can be used to validate if the code is valid
	$token = elgg_build_hmac([
		'e' => $email,
		'ts' => $time,
	])->getToken();

	$link = elgg_http_add_url_query_elements($return, [
		'e' => $email,
		'ts' => $time,
		'friend_guid' => $friend_guid,
		'invitecode' => $token,
	]);

	$codes = (array) $user_invite->invite_codes;
	$codes[] = $token;
	$user_invite->invite_codes = array_unique($codes);

	return $link;
}
