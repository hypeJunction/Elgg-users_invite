<?php

$guid = get_input('guid');
$inviter = get_entity($guid);

if (!$inviter || !$inviter->canEdit()) {
	register_error(elgg_echo('actionunauthorized'));
	forward(REFERRER);
}

$emails = (string) get_input('emails', '');
$resend = get_input('resend', false);
$message = get_input('message', '');

$skipped = 0;
$invited = 0;
$error = 0;

$emails = explode(PHP_EOL, $emails);

foreach ($emails as $email) {
	if (empty($email)) {
		continue;
	}
	$email = trim($email);
	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$error++;
		continue;
	}
	$users = get_user_by_email($email);
	if ($users) {
		if ($users[0] == $inviter) {
			$skipped++;
			continue;
		}
		if (elgg_is_active_plugin('friend_request') && !$inviter->isFriendsWith($users[0]->guid)) {
			add_entity_relationship($inviter->guid, 'friendrequest', $users[0]->guid);
		}
		$skipped++;
		continue;
	}

	$new = false;
	$user_invite = users_invite_get_user_invite($email);
	if (!$user_invite) {
		$new = true;
		$user_invite = users_invite_create_user_invite($email);
	}

	if (!$new && !$resend) {
		$skipped++;
		continue;
	}

	$link = elgg_trigger_plugin_hook('registration_link', 'site', [
		'email' => $email,
		'friend_guid' => $inviter->guid,
			], elgg_normalize_url('register'));

	add_entity_relationship($user_invite->guid, 'invited_by', $inviter->guid);

	$site = elgg_get_site_entity();
	$notification_params = array(
		'inviter' => elgg_view('output/url', array(
			'text' => $inviter->getDisplayName(),
			'href' => $inviter->getURL(),
		)),
		'site' => elgg_view('output/url', array(
			'text' => $site->getDisplayName(),
			'href' => $link,
		)),
		'message' => ($message) ? elgg_echo('users:invite:notify:message', array($message)) : '',
		'link' => $link,
	);
	$subject = elgg_echo('users:invite:notify:subject', array($site->getDisplayName()));
	$body = elgg_echo('users:invite:notify:body', $notification_params);

	$sent = elgg_send_email($site->email, $email, $subject, $body);
	if ($sent) {
		$invited++;
	} else {
		$error++;
	}
}

$total = $error + $invited + $skipped;
if ($invited) {
	system_message(elgg_echo('users:invite:result:invited', array($invited, $total)));
}
if ($skipped) {
	system_message(elgg_echo('users:invite:result:skipped', array($skipped, $total)));
}
if ($error) {
	register_error(elgg_echo('users:invite:result:error', array($error, $total)));
}
