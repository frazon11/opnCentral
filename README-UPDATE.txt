opnCentral v0.4.2.1 - Navigation placement fix

Changed:
- Language remains its own dropdown/menu item.
- Logout is now a separate standalone link directly after Language.
- Logout is no longer inside the Language dropdown.
- Version updated to v0.4.2.1.

Git:
git add .
git commit -m "Release v0.4.2.1 fix Language and Logout placement"
git status

Only when the working tree is clean:
git pull --rebase origin main
git push origin main

git tag -a v0.4.2.1 -m "Fix Language and Logout placement"
git push origin v0.4.2.1
