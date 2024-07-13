<?php

namespace MediaWiki\Extension\Notifications;

class EmailFrequency {
	/**
	 * Never send email notifications
	 */
	public const NEVER = -1;

	/**
	 * Send email notifications immediately as they come in
	 */
	public const IMMEDIATELY = 0;

	/**
	 * Send daily email digests
	 */
	public const DAILY_DIGEST = 1;

	/**
	 * Send weekly email digests
	 */
	public const WEEKLY_DIGEST = 7;
}
