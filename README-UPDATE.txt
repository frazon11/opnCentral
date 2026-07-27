opnCentral v0.4.2 - WebUI notification settings

New:
- All notification settings are editable from the Notifications page.
- Alert enablement, VPN alerts, interval and failure threshold are stored in SQLite.
- SMTP host, port, security, username, encrypted password, sender and recipients are stored in SQLite.
- Existing environment variables remain the initial defaults/fallbacks until settings are saved.
- The SMTP password is encrypted with APP_KEY and is never shown in the WebUI.
- Leaving the password field empty preserves the existing password.
- The background worker always starts and reads the current WebUI settings dynamically.
- Language is now directly above Logout in the top-right dropdown.
- Browser title is fixed to opnCentral.
- Added a multi-node opnCentral favicon.

Git:
git add .
git commit -m "Release v0.4.2 WebUI notification settings"
git status

Only when clean:
git pull --rebase origin main
git push origin main

git tag -a v0.4.2 -m "WebUI notification settings"
git push origin v0.4.2
