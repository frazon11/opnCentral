opnCentral v0.4.6.0

Anonymous active-installation statistics:
- Added an opt-in telemetry setting under Settings.
- Anonymous statistics remain disabled by default.
- Generates a local random installation secret and transmits only its SHA-256-derived installation hash.
- Sends only installation hash, opnCentral version, CPU architecture and platform docker.
- Sends at most once every 24 hours, with a one-hour retry interval after failure.
- TELEMETRY_URL must use HTTPS.
- Optional TELEMETRY_WRITE_TOKEN authenticates clients to the receiver.
- Telemetry failures never affect normal opnCentral operation.
- telemetry.json is included automatically in opnCentral self-backups.

Standalone telemetry-server:
- Included as a separate Docker Compose project.
- Stores anonymous installation hash, first seen, last seen, version, architecture, platform and check count in SQLite.
- Private dashboard protected with HTTP Basic authentication.
- Shows known installations and activity during the last 24 hours, 7 days and 30 days.
- Shows version and architecture distribution.
- Apache access logging is disabled in the supplied image.
- Optional write token prevents unauthenticated submissions.
- Configurable retention period removes installations that have not checked in for a chosen number of days.
