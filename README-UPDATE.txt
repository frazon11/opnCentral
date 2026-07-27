opnCentral v0.4.0 - Multi-language support

Full replacement project based on the uploaded current repository.

Languages:
- English (default and fallback)
- German
- French
- Dutch

New files:
- app/inc/i18n.php
- app/lang/en.php
- app/lang/de.php
- app/lang/fr.php
- app/lang/nl.php

Optional environment variable:
DEFAULT_LANGUAGE=en

Git:
git add .
git commit -m "Release v0.4.0 multilingual support"
git status
git pull --rebase origin main
git push origin main
git tag -a v0.4.0 -m "Multilingual support"
git push origin v0.4.0
