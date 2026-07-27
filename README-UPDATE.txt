opnCentral v0.3.10 - Container security refresh

Replace:
- Dockerfile
- .dockerignore
- app/inc/header.php

This release does not replace the working VPN, firmware, alias or category PHP files.

Changes:
- Pins php:8.5-apache-trixie.
- Installs available Debian security updates during build.
- Keeps only required runtime libraries.
- Removes development packages after compiling PHP extensions.
- Cleans APT and temporary files.
- Adds HEALTHCHECK.
- Updates WebUI version to v0.3.10.

Git:
git add Dockerfile .dockerignore app/inc/header.php
git commit -m "Release v0.3.10 container security refresh"
git status
git pull --rebase origin main
git push origin main
git tag -a v0.3.10 -m "Container security refresh"
git push origin v0.3.10

For docker/build-push-action use:
  pull: true
  no-cache: true
