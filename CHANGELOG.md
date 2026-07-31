# Changelog

## 0.4.6.2

- Reorganized the VPN sidebar into WireGuard and OpenVPN sections.
- Renamed the WireGuard entries to Manage and Create Site-to-Site VPN.
- Added disabled OpenVPN placeholders for Manage, Site-to-Site VPN creation, and Roadwarrior server creation.

## 0.4.6.1

- Cleaned and standardized the repository structure.
- Removed tracked `.env` files and example secrets.
- Consolidated environment configuration into `.env.example`.
- Added structured documentation under `docs/`.
- Added a complete `.gitignore` and tightened `.dockerignore`.
- Removed duplicate deployment files and generated release notes.
- Changed Docker publishing to version tags only, preventing duplicate builds from a main push followed by a tag push.
- Added semantic image tags and GitHub Actions build caching.

## 0.4.6.0

- Added opt-in anonymous installation statistics.
- Added a standalone telemetry receiver and private statistics dashboard.

## 0.4.5.0

- Added opnCentral self-backup and restore with integrity verification.

## 0.4.4.0

- Added the experimental managed WireGuard tunnel wizard.

## 0.4.3.5

- Added Settings, language selection, and light/dark themes.

## 0.4.3.0

- Added persistent OPNsense backup history and automatic pre-change backups.
