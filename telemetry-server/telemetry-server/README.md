# opnCentral Telemetry

A small optional receiving service for anonymous opnCentral active-installation statistics.

## Data stored

- SHA-256 anonymous installation hash
- first and last seen timestamps
- opnCentral version
- CPU architecture
- platform (`docker`)
- number of accepted checks

The application does not store firewall names, addresses, credentials, networks, VPN data, usernames, email addresses, or APP_KEY.

Apache access logging is disabled in the supplied image to avoid retaining client IP addresses. Reverse proxies placed in front of this container may still log IP addresses; disable or anonymise those logs separately.

## Deployment

1. Copy `.env-example` to `.env`.
2. Set a strong `DASHBOARD_PASSWORD`.
3. Set `TELEMETRY_WRITE_TOKEN` to a long random value.
4. Run:

```bash
docker compose up -d --build
```

5. Publish the service through an HTTPS reverse proxy.
6. Set the opnCentral environment:

```text
TELEMETRY_URL=https://telemetry.example.com/api.php
TELEMETRY_WRITE_TOKEN=the-same-long-random-value
```

7. Recreate opnCentral, then enable **Settings → Anonymous installation statistics**.

Dashboard:

```text
https://telemetry.example.com/
```

The browser prompts for `DASHBOARD_USER` and `DASHBOARD_PASSWORD`.
