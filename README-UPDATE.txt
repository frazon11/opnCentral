opnCentral v0.3.7 - AJAX firmware check

Replace:
- app/firewall_view.php
- app/firewall_status.php
- app/inc/header.php

Add:
- app/firewall_action.php

Changes:
- Check for updates runs by AJAX without reloading or blocking the details page.
- Firmware status refreshes automatically after the check completes.
- Clear progress and error messages are shown.
- The unstable automatic upgradestatus request was removed.
- Version display updated to v0.3.7.

Git commands:

git add app/firewall_view.php app/firewall_status.php app/firewall_action.php app/inc/header.php
git commit -m "Release v0.3.7 AJAX firmware check"
git pull --rebase origin main
git push origin main

git tag -a v0.3.7 -m "AJAX firmware check"
git push origin v0.3.7
