opnCentral v0.4.4.2

- Increased GitHub update-check connect timeout from 4 to 12 seconds.
- Increased total request timeout from 8 to 20 seconds.
- Failed checks no longer count as successful 24-hour checks.
- Failed checks can retry after 15 minutes.
- Added last-attempt tracking separate from last successful check.
- Added a clearer Docker DNS hint when api.github.com cannot be resolved.
- Manual Check now still bypasses the cache and retry throttle.
