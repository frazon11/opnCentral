# OpnCentral

Lightweight, self-hosted central management interface for multiple OPNsense firewalls.


## Languages

opnCentral supports English, German, French and Dutch. English is the default and fallback language. Use the language selector in the top-right menu. The selection is stored in the session and in a cookie.

Optional environment variable:

```dotenv
DEFAULT_LANGUAGE=en
```

## Features

- Multiple OPNsense systems
- Encrypted API key storage
- Central status display
- Firmware and service information
- Configuration backup download
- Remote reboot
- Direct WebGUI links
- SQLite storage
- Multi-platform Docker image for AMD64 and ARM64
- Synology DSM / Container Manager compatible

## Synology deployment

Copy only these files from `deploy/` to:

```text
/volume1/docker/opncentral/
├── docker-compose.yml
└── .env
```

Edit `.env`:

```dotenv
GHCR_OWNER=frazon11
ADMIN_PASSWORD=YOUR_SECURE_PASSWORD
APP_KEY=YOUR_64_CHARACTER_HEX_KEY
```

Generate APP_KEY:

```bash
openssl rand -hex 32
```

Start:

```bash
cd /volume1/docker/opncentral
docker compose pull
docker compose up -d
```

Open:

```text
http://SYNOLOGY-IP:8788
```

Persistent data:

```text
/volume1/docker/opncentral/data
/volume1/docker/opncentral/backups
```

Do not change `APP_KEY` after saving API credentials.

__________________________________________________________________________________________

if you like my ideas and /or projects, feel free to support me:

paypal.me/FrazoN11
