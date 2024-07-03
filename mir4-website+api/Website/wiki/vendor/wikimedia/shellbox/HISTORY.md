# Release History

## 4.0.2 (2024-03-05)
* blubber: create videoscaler variant
* dev: Replace blubberoid with blubber buildkit
* Allow the use of wikimedia/wikipeg 4.0.0

## 4.0.1 (2023-02-20)
* In the Firejail wrapper, fix handling of empty environment variables.
* Fix compatibility with Firejail 0.9.72 and fix a possible sandbox escape by
  passing the whole command string to a shell that runs under Firejail.

## 4.0.0 (2022-11-10)
* Require PHP >=7.4.3, up from 7.2.9.
* Fix OOM in MultipartAction when handling a large request body.
* Compatibility with php8.1: Use ENT_COMPAT on htmlspecialchars.
* Allow the use of wikimedia/wikipeg 3.0.0.

## 3.0.0 (2021-11-04)
* Add RpcClient interface for remote code execution and a LocalRpcClient
  implementation to be used as fallback when Shellbox server is not
  available.
* PSR-18 ClientInterface is now used instead of Shellbox own HttpClientInterface

## 2.1.1 (2022-07-27)
* Loosen guzzlehttp/guzzle requirement.

## 2.1.0 (2021-09-24)
* Roll our own *nix shell escaping function, improving PHP 8 support
  while still protecting against attacks using GBK locales by filtering
  them out of the environment.

## 2.0.0 (2021-08-20)

* Require PHP >=7.2.9, up from 7.2.0.
* Add a "spec" action, which exposes a swagger spec for service-checker.
* Remove non-functional allowlist code (T277981).
* Documentation has been added to <https://www.mediawiki.org/wiki/Shellbox>.

## 1.0.4 (2021-02-26)

* Raise priority of limit.sh if it uses a cgroup (T274942).

## 1.0.3 (2021-02-10)

* Only allow path to limit.sh when command has other allowed paths (T274474).
* build: Exclude config folder from Composer package.

## 1.0.2 (2021-02-07)

* Don't pass through the fake CGI environment to subprocesses.

## 1.0.1 (2021-02-03)

* build: Exclude public_html folder from Composer package.

## 1.0.0 (2021-02-02)

* Initial release.
