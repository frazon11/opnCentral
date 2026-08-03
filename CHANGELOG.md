# Changelog

## 0.6.7.0

- Added `Troubleshooting` under the Actions menu.
- Added a two-firewall OPNsense configuration comparison page.
- Lets the user choose one firewall on the left and another on the right.
- Downloads and flattens both complete OPNsense configurations.
- Lists every setting path and both values side by side.
- Added `All settings` and `Different settings only` filters.
- Added free-text filtering across setting paths and values.
- Marks missing settings and changed values clearly.
- Masks password, secret, private-key, API-key, token and PSK values.
- The comparison is read-only and remains available while opnCentral is locked.

## 0.6.7.0

- Added a per-firewall `Firewall → Settings → Advanced` page.
- Added an Advanced Settings button to every managed OPNsense firewall detail page.
- Reads the selected firewall's current configuration through the OPNsense backup API.
- Uses the same section and field names as the OPNsense `system_advanced_firewall.php` page.
- Displays Network Address Translation, Bogon Networks, Gateway Monitoring, Multi-WAN, Schedules, Logging, Miscellaneous and Anti DDOS settings.
- Handles OPNsense's inverted configuration flags for NAT reflection and logging correctly.
- Keeps the complete configuration XML in a collapsed diagnostic section.
- The page is read-only and remains available while opnCentral is locked.

## 0.6.7.0

- Fixed the lock status so it is visually anchored directly below the opnCentral title.
- Overrode older lock-status positioning styles that could move the status back to the top-right controls.
- Replaced the unreliable browser-native title tooltip with a visible custom tooltip.
- The locked tooltip now reads `Click Unlock to enable write features`.
- Added keyboard focus support for the tooltip.

## 0.6.7.0

- Moved the `Read-only mode` / `Configuration unlocked` status below the opnCentral header title.
- Removed the status badge from the top-right Unlock control area.
- Added a tooltip to the locked status: `Click Unlock to enable write features`.
- Kept Unlock/Lock and `Support me` in the top-right control stack.

## 0.6.7.0

- Rebuilt the firewall details page in the same OPNsense-inspired design used by the OpenVPN instance configuration view.
- Replaced visible raw System JSON with structured OPNsense-style label/value rows.
- Replaced visible raw Firmware JSON with formatted version and firmware detail rows.
- Moved raw API responses into collapsed `Advanced / Raw API data` sections.
- Restyled Plugins and VPN status into matching dark OPNsense panels.
- Preserved all existing status loading, firmware actions, VPN controls and raw diagnostic data.

## 0.6.7.0

- Corrected the top-bar structure so `Support me` is physically nested below Unlock/Lock.
- Unlock/Lock and the PayPal link now use one vertical control stack.
- Preserved the behavior where clicking Unlock also opens the PayPal page.

## 0.6.7.0

- Moved the PayPal support link from the sidebar footer to the top bar beside the configuration Unlock/Lock button.
- Renamed the top-bar link to `Support me`.
- Clicking Unlock now opens the PayPal support page in a new browser tab and still opens the opnCentral password dialog.
- Clicking Lock does not open the PayPal page.
- Added responsive top-bar support-link styling.

## 0.6.7.0

- Added backup downloads to the password-protected locked operations.
- Greyed and disabled stored OPNsense backup download links while locked.
- Greyed and disabled combined backup ZIP downloads while locked.
- Greyed and disabled opnCentral self-backup downloads while locked.
- Added server-side HTTP 423 enforcement to all backup download endpoints.
- Backup history remains readable and manual backup creation remains available.

## 0.6.7.0

- Added a global read-only lock for all managed OPNsense connections.
- opnCentral starts locked for every new login session.
- Added Unlock and Lock controls to the top bar.
- Added password-protected session unlock using the configured password `ThankYou`.
- Disabled and greyed remote configuration controls while locked.
- Added server-side enforcement so direct endpoint calls cannot bypass the UI lock.
- Allowed read-only searches, status calls, inventories, configuration views and backup history while locked.
- Protected WireGuard changes and tunnel creation.
- Protected OpenVPN instance actions and Roadwarrior creation.
- Protected alias and category distribution.
- Protected plug-in changes and firmware update/upgrade commands.
- Unlock state is limited to the current PHP login session and is cleared by Lock or logout.

## 0.6.7.0

- Fixed OPNsense dropdown values being displayed as raw JSON objects.
- Added generic parsing for OPNsense model option objects with `selected` flags.
- Dropdowns now show the selected OPNsense display label.
- Multi-select controls now show only selected values.
- Added normalization for object and array values returned by the OPNsense model API.
- Applied the same normalization to read-only checkbox controls.

## 0.6.7.0

- Rebuilt OpenVPN Config as an OPNsense-style Edit Instance dialog.
- Added the dark modal title bar, toolbar, collapsible section headers, alternating rows and fixed footer.
- Added OPNsense-style advanced mode and full help switches.
- Added read-only dropdown, text, textarea and checkbox controls matching the OPNsense visual layout.
- Added information icons beside every field label.
- Added collapsible General Settings, Trust, Authentication, Routing and Miscellaneous sections.
- Replaced Save with Close because the opnCentral Config view remains read-only.
- Continued deriving field structure from the bundled OPNsense dialogInstance definition.

## 0.6.7.0

- Removed the remaining hard-coded OpenVPN form schema from the browser code.
- Bundled the OPNsense OpenVPN `dialogInstance.xml` structural definition.
- Added runtime XML parsing through `openvpn_form_schema.php`.
- The Config view now derives section order, field order, labels, types, advanced flags and role/device style rules from the bundled OPNsense form definition.
- Preserved OPNsense Basic/Advanced behavior, reference-name resolution and secret masking.
- Kept operational Details and Config as separate per-firewall views.

## 0.6.7.0

- Replaced the manually grouped OpenVPN Config view with the actual OPNsense `dialogInstance.xml` field structure.
- Matched OPNsense section order: General Settings, Trust, Authentication, Routing and Miscellaneous.
- Matched OPNsense field order and labels.
- Added Basic/Advanced mode behavior matching the OPNsense instance dialog.
- Added server/client and TUN/TAP/DCO-dependent field visibility.
- Added OPNsense-style read-only label/control rows.
- Resolved CA, certificate, TLS static-key and authentication-provider references to display names.
- Added display mappings for OPNsense option values such as protocol, device type, certificate depth and verbosity.
- Kept secrets masked and retained the separate operational Details view.

## 0.6.7.0

- Added separate Details and Config controls for each OPNsense row in OpenVPN Manage.
- Added complete read-only retrieval of every OpenVPN instance through `openvpn/instances/get/<uuid>`.
- Added an OPNsense-style grouped configuration view for General, Connection, Routing, Certificates and Authentication, Cryptography, Client Settings and Advanced options.
- Added an Additional Options section for fields returned by OPNsense that are not yet explicitly mapped.
- Masked passwords, authentication token secrets, private-key fields and secret material.
- Preserved instance actions, sessions, one-firewall-per-row layout and existing backup behavior.

## 0.6.7.0

- Added complete live alias inventory from every managed OPNsense.
- Added complete live category inventory from every managed OPNsense.
- Included aliases and categories that were not distributed by opnCentral.
- Marked aliases assigned to the opnCentral category as managed.
- Marked all other aliases as unmanaged.
- Marked categories known to opnCentral central definitions as managed.
- Marked all other remote categories as unmanaged.
- Added managed, unmanaged and total counts per firewall.
- Kept one OPNsense per compact row with expandable complete inventories.
- Kept explicit Add to this OPNsense and Add to all OPNsense actions.
- Kept takeover opt-in; unmanaged aliases are never claimed automatically.

## 0.6.7.0

- Changed Alias Overview to one OPNsense firewall per compact row.
- Changed Category Overview to one OPNsense firewall per compact row.
- Added expandable alias and category detail tables per firewall.
- Added per-firewall synchronized and issue summaries.
- Added “Add to this OPNsense” actions inside expanded details.
- Replaced multi-checkbox target selection with explicit One OPNsense or All OPNsense options.
- Preserved alias create, replace, merge and takeover behavior.
- Preserved category create and replace behavior.
- Preserved pre-change backups and synchronization checks.

## 0.6.7.0

- Changed compact VPN management to one OPNsense firewall per row.
- Grouped all managed WireGuard connections under their respective firewall.
- Grouped all OpenVPN instances and sessions under their respective firewall.
- Removed the single-firewall selector from OpenVPN Manage.
- Added per-firewall counts and status summaries.
- Kept detailed connections, instances, sessions and actions expandable.

## 0.6.7.0

- Redesigned Manage WireGuard with compact connection summaries.
- Added expandable WireGuard connection details and controls.
- Redesigned Manage OpenVPN with compact instance summaries.
- Added expandable OpenVPN instance details and actions.
- Added compact active-session summary with expandable session table.
- Kept VPN details and action controls collapsed by default.
- Preserved existing API, backup, refresh and action behavior.

## 0.6.7.0

- Redesigned Services to use a dashboard-like compact default view.
- Added one compact summary row per firewall.
- Added firewall status and active-service count summaries.
- Added expandable service details per firewall.
- Added service name, technical name and running status in the expanded view.
- Kept service details collapsed by default.
- Preserved existing cache and refresh behavior.

## 0.6.7.0

- Republished the consistent management-layout release as version 0.6.7.0.
- No functional changes from version 0.6.2.0.

## 0.6.7.0

- Standardized the layout of Manage WireGuard, Manage OpenVPN, Services and Agents.
- Added one shared page-header and toolbar pattern.
- Added consistent summary bars, card headers, tables, action groups and empty states.
- Added consistent spacing and responsive behavior.
- Reorganized Agents into inventory, registration and installation sections.
- Preserved existing functionality and API behavior.

## 0.6.7.0

- Enabled VPN → OpenVPN → Manage.
- Added per-firewall OpenVPN instance inventory.
- Added active-session overview.
- Added enable, disable, start, stop, restart and delete actions.
- Added automatic backups before enable, disable and delete.
- Added OpenVPN service reconfiguration after instance state changes.
- Added direct access to the Roadwarrior server wizard.

## 0.6.7.0

- Restored the PayPal support link.
- Moved PayPal directly below the Logout button in the sidebar.
- Kept “Buy me a coffee” beside the application version.

## 0.6.7.0

- Replaced the sidebar PayPal support link with “Buy me a coffee”.
- Added the support URL `https://buymeacoffee.com/frazon11`.

## 0.6.7.0

- Released the OpenVPN Roadwarrior server wizard as version 0.6.7.0.
- No functional changes from the prepared 0.6.0.0 build.

## 0.6.7.0

- Added Create OpenVPN Roadwarrior Server under VPN → OpenVPN.
- Added target-firewall discovery for CAs, certificates, TLS static keys and authentication providers.
- Added automatic selection of the next available OpenVPN instance ID.
- Added server settings for protocol, port, bind address, tunnel network and maximum clients.
- Added trust, authentication, pushed local networks, DNS, redirect-gateway and cipher settings.
- Added duplicate instance-ID and listener-collision checks.
- Added CIDR, IP, port and option validation.
- Added mandatory pre-change configuration backup.
- Added OpenVPN instance creation through the official Instances API.
- Added service reconfiguration and rollback attempt if applying the instance fails.
- Firewall rules, certificate creation and client export remain manual in this first release.

## 0.5.1.4

- Removed the redundant dashboard Details view selector.
- Kept the per-firewall Details button for the actual detailed page.
- Rebuilt Compact as a true condensed row layout.
- Compact shows firewall identity, online state, system version, update status, Details and Plugins.
- Compact hides Refresh, Backup and Edit to reduce clutter; those remain available in Cards and Details pages.
- Saved Details view preferences automatically fall back to Cards.

## 0.5.1.3

- Removed all firewall names and plugin links from the sidebar.
- Restored a static navigation menu.
- Added a Plugins button to every firewall dashboard card.
- Added Plugins access and a dedicated plugin-management card to firewall details.
- Plugin pages now require an explicitly selected firewall.
- Removed the firewall selector and artificial firmware tabs from the plugin page.
- Added a direct Back to details action.
- Preserved plugin inventory, actions, backups and job history.

## 0.5.1.2

- Moved Plugins from the global menu below each managed firewall.
- Added one dedicated plugin page per firewall.
- Added a firewall selector for quick switching.
- Redesigned the plugin inventory similar to the OPNsense Firmware Plugins page.
- Added compact columns for name, version, comment, status and row actions.
- Added client-side plugin search.
- Filtered recent plugin jobs to the selected firewall.
- Preserved automatic backups and all existing plugin operations.

## 0.5.1.1

- Fixed the Plugins page returning HTML instead of JSON.
- Replaced the invalid combined `ksort()` flags with case-insensitive natural sorting using `uksort()`.
- Added fatal-error and output-buffer protection to the plugin inventory endpoint.
- Hardened package-field normalization for varying OPNsense firmware responses.
- Improved frontend diagnostics when a server response is not valid JSON.

## 0.5.1.0

- Added central plugin inventory across all managed firewalls.
- Added single-firewall install, reinstall, remove, lock and unlock actions.
- Restricted management actions to OPNsense plugin package names beginning with `os-`.
- Added mandatory pre-change backups for install, reinstall and remove.
- Added persistent plugin job records with OPNsense message UUIDs.
- Added cached, parallel inventory loading.
- Bulk plugin operations remain disabled pending real-firewall validation.

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
