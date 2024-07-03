The LoginNotify extension notifies you when someone logs into your account. It can be configured to give warnings after a certain number of failed login attempts (The number is configurable, and can be different between unknown IPs/devices and known IP/devices). It can also give echo/email notices for successful logins from IPs you don't normally use. It can optionally integrate into the CheckUser extension in order to determine if the login is from an IP address you don't normally use. It can also set a cookie to try and determine if the login is from a device you normally use.

#### Installation
* This extension requires the Echo extension to be installed. This extension can optionally integrate with the CheckUser extension if it is installed, but does not require it.
* Download and place the file(s) in a directory called LoginNotify in your extensions/ folder.
* Add the following code at the bottom of your LocalSettings.php: `wfLoadExtension( 'LoginNotify' );`
* Navigate to Special:Version on your wiki to verify that the extension is successfully installed.

#### Configuration parameters

See extension.json.

To place the loginnotify_seen_net table in a shared database, use

```php
$wgVirtualDomainsMapping['virtual-LoginNotify'] = [
	'db' => '<shared database name>'
];
$wgLoginNotifyUseCentralId = true;
```
