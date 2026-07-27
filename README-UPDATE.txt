opnCentral v0.4.1.1 - Move alert settings to .env

Changes:
- Alert and SMTP values are stored in root .env and deploy/.env.
- docker-compose.yml and deploy/docker-compose.yml only reference ${VARIABLE} values.
- Matching .env.example files remain available as templates.
- .env remains excluded from Git by .gitignore because it can contain SMTP credentials.
- Version display updated to v0.4.1.1.

Important:
Edit .env before deployment and replace SMTP_HOST, SMTP_USERNAME, SMTP_PASSWORD, SMTP_FROM and NOTIFY_TO.

Git:
git add .
git commit -m "Release v0.4.1.1 move alert settings to env"
git status

Because .env is ignored, it will not be committed. Copy it separately to the deployment folder or define the same values in Portainer stack environment variables.

git pull --rebase origin main
git push origin main
git tag -a v0.4.1.1 -m "Move alert settings to env"
git push origin v0.4.1.1
