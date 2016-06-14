<?php

return [

	'users:invite' => 'Invite',
	'users:invite:invite' => 'Invite users',
	'users:invite:emails:select' => 'Emails to invite',
	'users:invite:emails:select:help' => 'Enter one email per line',
	'users:invite:message' => 'Message to include in the invitation',
	'users:invite:resend' => 'Resend invitations to previously invited emails',
	'users:invite:notify:subject' => 'You are invited to join %s',
	'users:invite:notify:body' => '%1$s has invited you to join %2$s.
		%3$s
		Please visit the following link to create an account:
		%4$s
		',
	'users:invite:notify:message' => '

		They have included the following message for you:
		%s

		',

	'users:invite:settings:invite_only_network' => 'Invite Only Registration',
	'users:invite:settings:invite_only_network:help' => 'If enabled, only users with a valid invitation code will be allowed to register',

	'users:invite:result:invited' => '%s of %s invitations were successfully sent',
	'users:invite:result:skipped' => '%s of %s invitations were skipped, because users have already been invited or have an account',
	'users:invite:result:error' => '%s of %s invitations could not be sent due to errors',

	'users:invite:required_invitecode' => 'Invitation Code',
	'users:invite:required_invitecode:mismatch' => 'The invitation code you have provided is not valid',

];
