opnCentral v0.3.8.1 - JSON endpoint fix

Replace these complete files:

- app/firewall_status.php
- app/firewall_action.php
- app/firewall_view.php
- app/index.php
- app/inc/header.php
- app/inc/firmware.php

Fixes:

- GET API calls now pass null instead of an empty payload array.
- JSON endpoints disable HTML-formatted PHP error output.
- Fatal PHP errors are returned as clean JSON.
- Existing output buffers are cleared before JSON is sent.
- Dashboard and Details read the response as text first.
- Invalid server responses now show the actual returned text instead of only:
  Unexpected token '<'
- Version display updated to v0.3.8.1.

Safe Git sequence:

git add .
git commit -m "Release v0.3.8.1 fix JSON API responses"
git status

Continue only when working tree is clean:

git pull --rebase origin main
git push origin main

git tag -a v0.3.8.1 -m "Fix JSON API responses"
git push origin v0.3.8.1
