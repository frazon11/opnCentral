opnCentral v0.4.2.8

Performance changes:
- OPNsense VPN API requests are now executed concurrently with curl_multi.
- WireGuard client/server inventories for all managed firewalls are fetched concurrently.
- Managed WireGuard inventory is cached for 30 seconds.
- The cache is invalidated immediately after a peer-pair enable/disable action.
- Browser requests for VPN runtime status and managed-peer matching now start in parallel.
- System, firmware and VPN panels still load independently, so a slow panel does not block the others.
