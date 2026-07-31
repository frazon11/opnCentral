# Changelog

## 0.5.0.0

- Added the first minimal `os-opncentral-agent` source tree.
- Added signed outbound HTTPS heartbeats with per-agent credentials.
- Added timestamp and nonce replay protection.
- Added the opnCentral agent receiver API.
- Added registration, enable/disable, deletion and last-seen overview.
- Reports hostname, OPNsense version and running services.
- Added a prototype installer for controlled testing.
- Agent is read-only; production package distribution is not included yet.

## 0.4.8.0

- Added a Services menu and central active-services overview.
- Uses the OPNsense core/service/search API for every managed firewall.
- Fetches all firewalls in parallel.
- Shows only services reported as running.
- Displays service description and technical service name.
- Added persistent five-minute service caching and background refresh.
- Keeps cached service data visible during manual and automatic refreshes.
- Shows per-firewall errors without blocking results from other firewalls.

## 0.4.7.3

- Simplified firewall dashboard cards.
- Removed the redundant Reachable display.
- System now displays the OPNsense version directly.
- Removed the duplicated Current version field.
- Replaced the two-column firmware block with a single Update status field.
- A successful system or firmware response marks the firewall Online.
- Connection failures remain visible under System while the card is marked Offline.

## 0.4.7.2

- Added explicit update comparison states: behind, equal, ahead and unknown.
- Installations newer than the latest published GitHub release now show “Ahead of latest release”.
- Replaced the misleading latest-release message when the installed version is newer.
- Kept “Update available” only for installations older than the latest published release.

## 0.4.7.1

- Fixed the DNS-resolution regression introduced in 0.4.7.0.
- Normal OPNsense API requests may now use up to ten seconds for DNS resolution and connection establishment.
- WireGuard inventory requests remain limited to five seconds total.
- Preserved the session-lock and persistent-cache performance improvements from 0.4.7.0.

## 0.4.7.0

- Removed PHP session-lock blocking from long-running update, telemetry, firewall-status and WireGuard API requests.
- Changed the WireGuard Manage page to render persistent cached data immediately.
- Added stale-while-revalidate behavior: old data stays visible while a silent live refresh runs.
- Manual Refresh no longer clears the table or replaces it with Loading.
- Increased the automatic WireGuard cache refresh interval to five minutes.
- Reduced WireGuard API request timeout to five seconds.
- Moved the WireGuard inventory cache from temporary container storage to persistent application data.
- Added atomic cache writes to avoid partially written cache files.

## 0.4.6.3

- Improved VPN menu contrast in dark mode.
- Made WireGuard section labels clearly visible.
- Increased contrast for normal submenu entries.
- Kept OpenVPN placeholders visibly disabled without making them unreadable.
- Improved active-item and hierarchy colors.

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
