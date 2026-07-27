opnCentral v0.4.1.2 - PHP 8.5 cURL deprecation fix

Changed:
- Removed both deprecated curl_close() calls from app/inc/opnsense.php.
- No API request, timeout, TLS, authentication or response handling logic changed.
- Updated the displayed version to v0.4.1.2.

PHP 8.5 automatically releases CurlHandle objects, so curl_close() has no effect
and now produces a deprecation warning.

Git:
git add .
git commit -m "Release v0.4.1.2 fix PHP 8.5 curl deprecation"
git status

Only when the working tree is clean:
git pull --rebase origin main
git push origin main

git tag -a v0.4.1.2 -m "Fix PHP 8.5 curl deprecation"
git push origin v0.4.1.2
