opnCentral v0.4.5.1

- Fixed false 'Update available' status after upgrading opnCentral.
- Cached update_available values are no longer trusted.
- Update status is recalculated against the currently running opnCentral version on every load.
- Corrected the application version constant used by the GitHub update checker.
- Settings now displays the installed version returned by the server instead of a hardcoded value.
