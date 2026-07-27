opnCentral v0.4.1 - Basic email alerts

New:
- Background alert worker independent of dashboard visits
- Firewall offline and recovery emails
- WireGuard, IPsec and OpenVPN tunnel down/recovery emails
- Consecutive failure threshold to reduce false alarms
- SMTP test-email page under Notifications
- Recent notification-attempt log
- English remains the default and fallback language

Required environment variables:
ALERTS_ENABLED=true
ALERT_VPN=true
ALERT_CHECK_INTERVAL=300
ALERT_FAILURE_THRESHOLD=2
SMTP_HOST=smtp.example.com
SMTP_PORT=587
SMTP_SECURITY=tls
SMTP_USERNAME=opncentral@example.com
SMTP_PASSWORD=CHANGE_ME
SMTP_FROM=opncentral@example.com
SMTP_FROM_NAME=opnCentral
NOTIFY_TO=admin@example.com

SMTP_SECURITY accepts: tls, ssl, none
NOTIFY_TO accepts comma-separated recipients.

Important:
- The first healthy check creates a baseline and sends no alert.
- An initial outage is reported only after the configured consecutive-failure threshold.
- A recovery email is sent when a previously alerted state becomes healthy again.
- Missing VPN plug-ins/API endpoints do not generate false alerts.

Git:
git add .
git commit -m "Release v0.4.1 basic email alerts"
git status
git pull --rebase origin main
git push origin main
git tag -a v0.4.1 -m "Basic email alerts"
git push origin v0.4.1
