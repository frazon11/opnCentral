opnCentral v0.3.8.3 - Menu visibility fix

Replace:
- app/inc/header.php
- app/assets/style.css

Fixes:
- Visible dark submenu text on white background
- No inherited left margin inside dropdowns
- Improved hover/focus states
- Better mobile header layout
- Version updated to v0.3.8.3

Git:
git add .
git commit -m "Release v0.3.8.3 fix submenu visibility"
git status
git pull --rebase origin main
git push origin main
git tag -a v0.3.8.3 -m "Fix submenu visibility"
git push origin v0.3.8.3
