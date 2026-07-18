# OpnCentral v0.1.8 update

This update package contains complete replacement files:

- app/index.php
- app/firewall_view.php

Changes:

- Dashboard no longer forces a firmware repository check on every page load.
- Firewall detail page no longer automatically loads services or upgrade status.
- Manual "Check for updates" button remains available.
- "Update now" remains available.
- Configuration backup uses:
  core/backup/download/this

Installation:

1. Copy both files into the matching `app/` directory of your local repository.
2. Commit and push:

   git add app/index.php app/firewall_view.php
   git commit -m "Release v0.1.8 performance update"
   git push origin main

3. Create the release:

   git tag -a v0.1.8 -m "Improve Web UI performance"
   git push origin v0.1.8

4. In Portainer update the stack with "Pull latest image" enabled.
