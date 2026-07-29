# opnCentral

Self-hosted central management for multiple OPNsense firewalls.

## Main features

- central firewall status and firmware information
- encrypted OPNsense API credential storage
- configuration backup history and one-click backups
- automatic backups before managed changes
- central aliases and categories
- managed WireGuard pair overview
- experimental WireGuard site-to-site tunnel wizard
- email notifications
- light and dark themes
- English, German, French and Dutch interface
- opnCentral self-backup and restore
- optional anonymous active-installation statistics
- AMD64 and ARM64 Docker images

## Quick start

```bash
cp .env.example .env
```

Set a strong administrator password and generate `APP_KEY`:

```bash
openssl rand -hex 32
```

Then start:

```bash
docker compose pull
docker compose up -d
```

Default web port:

```text
http://DOCKER-HOST:8788
```

Detailed instructions:

- [Installation](docs/installation.md)
- [Configuration](docs/configuration.md)
- [Backup and restore](docs/backup-restore.md)
- [Managed WireGuard](docs/wireguard.md)
- [Anonymous telemetry](docs/telemetry.md)
- [Troubleshooting](docs/troubleshooting.md)
- [Changelog](CHANGELOG.md)

## Persistent data

```text
/var/www/data
/var/www/backups
```

Preserve the exact `APP_KEY`; otherwise encrypted OPNsense API credentials cannot be restored.

## Container images

```text
ghcr.io/frazon11/opncentral
docker.io/frazon11/opncentral
```

Release tags publish:

- exact version, such as `0.4.6.1`
- minor version, such as `0.4`
- `latest`

## Support

Project support: `paypal.me/FrazoN11`

## License

MIT
