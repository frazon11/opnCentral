opnCentral v0.3.8.2 - Restore GET payload compatibility

This fixes the regression introduced in v0.3.8.1 where GET calls used null
as the payload. The existing opn_request() implementation expects an array,
causing all remote OPNsense systems to appear offline.

Replace these complete files:

- app/firewall_status.php
- app/firewall_action.php
- app/firewall_view.php
- app/index.php
- app/inc/firmware.php
- app/inc/header.php

Fixes:

- GET requests again use an empty array payload: []
- Keeps JSON endpoint hardening from v0.3.8.1
- Keeps useful invalid-JSON error reporting
- Version display updated to v0.3.8.2

Emergency rollback in Portainer:
Use image ghcr.io/frazon11/opncentral:0.3.8 and redeploy.

Safe Git sequence:

git add .
git commit -m "Release v0.3.8.2 restore GET payload compatibility"
git status

Only continue when the working tree is clean:

git pull --rebase origin main
git push origin main

git tag -a v0.3.8.2 -m "Restore GET payload compatibility"
git push origin v0.3.8.2
