opnCentral v0.4.2.3 - VPN status parser fix

Changed:
- WireGuard interface and peer rows are parsed separately.
- WireGuard peer state uses peer-status and displays handshake and traffic data.
- IPsec disabled state is shown correctly; Phase 1 and Phase 2 are counted separately.
- OpenVPN session rows are no longer combined with route rows.
- Roadwarrior sessions are separated from site-to-site tunnels.
- OpenVPN route records are informational only and never counted as tunnels.
