<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 */

namespace MediaWiki\Extension\ConfirmEdit\Hooks;

use MediaWiki\Page\PageIdentity;

/**
 * This is a hook handler interface, see docs/Hooks.md in core.
 * Use the hook name "ConfirmEditTriggersCaptcha" to register handlers implementing this interface.
 *
 * @author Zabe
 * @stable to implement
 * @ingroup Hooks
 */
interface ConfirmEditTriggersCaptchaHook {
	/**
	 * This hook is called when the extension checks whether the passed
	 * action should trigger a CAPTCHA.
	 *
	 * @since 1.39
	 *
	 * @param string $action Action user is performing, one of sendmail, createaccount,
	 *                       badlogin, edit, create, addurl.
	 * @param PageIdentity|null $page
	 * @param bool &$result
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onConfirmEditTriggersCaptcha(
		string $action,
		?PageIdentity $page,
		bool &$result
	);
}
