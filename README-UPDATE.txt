opnCentral v0.3.8 - Firmware versions, actions and PHP 8.5

Replace:
- Dockerfile
- app/index.php
- app/firewall_view.php
- app/firewall_status.php
- app/firewall_action.php
- app/inc/header.php

Add:
- app/inc/firmware.php

Changes:
- Dashboard cards show current OPNsense version.
- Dashboard cards show available update/upgrade version.
- Update now / Upgrade now appears only when OPNsense offers the action.
- Details view shows current and available versions.
- Check for updates remains asynchronous.
- Update and major upgrade use separate OPNsense API actions.
- Docker base image updated from PHP 8.3 to PHP 8.5.
- Version display updated to v0.3.8.

Safe Git order:

git add .
git commit -m "Release v0.3.8 firmware versions and PHP 8.5"
git status

Only continue when the working tree is clean:

git pull --rebase origin main
git push origin main

git tag -a v0.3.8 -m "Firmware versions and PHP 8.5"
git push origin v0.3.8
