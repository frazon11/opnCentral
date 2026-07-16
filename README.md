# OpnCentral

Lightweight, self-hosted central management interface for multiple OPNsense firewalls.

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

## GitHub project setup

1. Create a GitHub repository named `OpnCentral`.
2. Upload the complete project.
3. Push to `main`.
4. GitHub Actions builds and publishes:

```text
ghcr.io/YOUR_GITHUB_USERNAME/opncentral:edge
```

Create release version 1.0.0:

```bash
git tag v1.0.0
git push origin v1.0.0
```

This publishes:

```text
ghcr.io/YOUR_GITHUB_USERNAME/opncentral:1.0.0
ghcr.io/YOUR_GITHUB_USERNAME/opncentral:1.0
ghcr.io/YOUR_GITHUB_USERNAME/opncentral:1
ghcr.io/YOUR_GITHUB_USERNAME/opncentral:latest
```

## Synology deployment

Copy only these files from `deploy/` to:

```text
/volume1/docker/opncentral/
├── docker-compose.yml
└── .env
```

Edit `.env`:

```dotenv
GHCR_OWNER=YOUR_GITHUB_USERNAME
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
