opnCentral v0.3.6 - Background dashboard status and structured menu

Replace:
- app/index.php
- app/firewall_view.php
- app/firewall_status.php
- app/inc/header.php

Changes:
- Dashboard opens immediately.
- System and firmware status load in the background for every firewall.
- Refresh status button updates all firewalls.
- Each firewall also has its own Refresh button.
- Cards, Compact and Details views remain available.
- Navigation is grouped as Dashboard, Firewalls, Aliases, Categories and Logout.
- Alias and Category actions are placed in compact dropdown menus.
- Version display updated to v0.3.6.

Git commands:

git add app/index.php app/firewall_view.php app/firewall_status.php app/inc/header.php
git commit -m "Release v0.3.6 background dashboard and structured menu"
git pull --rebase origin main
git push origin main

git tag -a v0.3.6 -m "Background dashboard and structured menu"
git push origin v0.3.6
