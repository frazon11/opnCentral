opnCentral v0.4.4.0 - Experimental managed WireGuard tunnel creator

- Added Create WG tunnel wizard.
- Creates a dedicated WireGuard instance on each selected managed OPNsense.
- Uses OPNsense-generated keypairs and reciprocal peers.
- Creates both sides disabled first, attaches peers, then enables and reconfigures.
- Creates verified automatic backups of both firewalls before any changes.
- Creates/reuses firewall category WireGuard.
- Creates optional WAN UDP rules and LAN/WireGuard traffic rules.
- Every generated firewall rule includes managed by opnCentral [WG-xxxxxx].
- Attempts rollback of generated rules, peers and instances if any stage fails.
- Requires explicit confirmation of networks, endpoints, ports and interface keys.
- MSS normalization is not automated in this first version because it uses a different OPNsense subsystem.
