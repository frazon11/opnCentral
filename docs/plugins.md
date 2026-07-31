# Plugin management

The Plugins page queries `GET /api/core/firmware/info` on every managed firewall.
Only package names beginning with `os-` are exposed for actions.

Supported single-firewall actions:

- install
- reinstall
- remove
- lock
- unlock

Install, reinstall and remove first create an opnCentral pre-change configuration
backup. OPNsense returns an asynchronous firmware message UUID for package jobs.
The first release records this UUID and displays submitted jobs; detailed final
job-result interpretation should be verified against real OPNsense responses
before bulk operations are added.
