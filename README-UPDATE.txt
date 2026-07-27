opnCentral v0.3.9 - Site-to-site VPN status

Replace:
- app/firewall_view.php
- app/inc/header.php
- app/assets/style.css

Add:
- app/vpn_status.php

New:
- Site-to-site VPN section in firewall Details
- WireGuard service and peer/tunnel status
- IPsec service, Phase 1 and Phase 2 status
- OpenVPN sessions and routes
- All VPN calls load asynchronously in the background
- Failure of one VPN technology does not block the others
- Raw API data remains available under expandable sections
- Version updated to v0.3.9

The API user needs effective privileges for the relevant VPN endpoints.

Safe Git sequence:

git add .
git commit -m "Release v0.3.9 site-to-site VPN status"
git status

Continue only when working tree is clean:

git pull --rebase origin main
git push origin main

git tag -a v0.3.9 -m "Site-to-site VPN status"
git push origin v0.3.9
