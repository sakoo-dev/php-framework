# 0. Acunetix Pen Test

## 1. Authentication

- Enforce user authentication before accessing any resources.
- Use MFA (Multi-Factor Authentication).
- Store passwords securely (bcrypt, Argon2).
- Apply strong password policy (min 8 chars, uppercase, lowercase, digits, special chars).
- Enforce password change policy (first login, periodic, history checks).
- Lock accounts after failed login attempts (Lockout / Slowdown / Captcha).
- Do not disclose whether user or password is invalid.
- Require re-authentication for sensitive pages/actions.
- Use unique user IDs (no shared or anonymous accounts).

## 2. Authorization & Access Control

- Implement RBAC or ABAC.
- Enforce least privilege principle.
- Separate roles and duties.
- Check access permissions server-side.
- Prevent direct URL access to unauthorized resources.
- Prevent directory listing.
- Test for Broken Access Control (OWASP A01).

## 3. Session Management

- Use strong, unpredictable session IDs.
- Enforce session expiration (15–30 minutes inactivity).
- Rotate session IDs after login or privilege change.
- Do not store sensitive info in persistent cookies.
- Encrypt session cookies (HttpOnly, Secure, SameSite).
- Do not transmit session IDs via query strings.
- Provide secure logout functionality.

## 4. Input & Output Validation

- Validate all input (type, length, range, format).
- Do not rely on client-side validation only.
- Prevent SQL Injection using prepared statements.
- Prevent XSS via escaping and CSP.
- Protect against CSRF (tokens + SameSite cookies).
- Initialize variables properly.
- Sanitize file uploads (type, size, AV scan).
- Prevent Path Traversal and File Inclusion attacks.
- Test for Injection vulnerabilities (SQL, NoSQL, OS Command, Template).

## 5. Cryptography & Data Protection

- Use approved algorithms (AES, RSA, SHA-256).
- Encrypt sensitive data at rest (DB, files, backups).
- Use TLS 1.2/1.3 with HSTS for data in transit.
- Disable weak protocols and ciphers (SSLv3, TLS 1.0, MD5, SHA1).
- Do not hardcode secrets, keys, or passwords in code/config.
- Use secure, temporary cookies for sensitive data.

## 6. Availability & Reliability

- Implement regular and secure backups.
- Test backup restore procedures.
- Apply rate limiting and throttling to APIs.
- Mitigate DoS/DDoS attacks.
- Use load balancing and failover (HA).

## 7. Error & Exception Handling

- Centralize error handling.
- Do not expose stack traces or sensitive details to end-users.
- Log all errors/exceptions securely.
- Classify logs by severity (info, warn, error, fatal, debug, trace).
- Ensure application failures do not lead to insecure states.

## 8. Secure Design, Architecture & Coding

- Perform threat modeling before design.
- Use multi-layered architecture (App / Web / DB separation).
- Apply secure coding standards (OWASP ASVS).
- Remove unused functions, endpoints, and components.
- Do not use insecure services (e.g., Telnet).
- Review and assess third-party/open-source components.
- Do not store sensitive data (passwords, keys) in source code.
- Avoid hardcoded credentials or constants.
- Ensure secure initialization of all variables.

## 9. Logging & Auditing

- Log all security-relevant events (login, failed login, privilege changes, access denial).
- Store logs in append-only, read-only format.
- Send logs to centralized SIEM or monitoring system.
- Provide real-time monitoring and alerts.
- Include timestamp, user ID, source IP, event type.

## 10. Installation & Configuration

- Provide secure installation & configuration documentation.
- Remove or change all default accounts/passwords.
- Remove unnecessary DB data, test accounts, and demo pages.
- Apply secure OS and DB hardening.
- Enable logging at all layers (Web server, DB, Application).
- Apply defense-in-depth for deployment.

## 11. VPS / Server Hardening

### Users & Access

- Disable root login.
- Use non-root accounts + sudo.
- Enforce least privilege principle.

### SSH

- Change default SSH port.
- Restrict SSH access to specific IPs.
- Enable key-based authentication.
- Disable password-based login where possible.

### Firewall & Network

- Close all unnecessary ports (only 80, 443, SSH).
- Configure iptables / ufw / firewalld properly.
- Use VPN or bastion host for sensitive access.

### Monitoring & Protection

- Install and configure fail2ban.
- Monitor system logs (auth.log, syslog).
- Enable alerts for suspicious login attempts.

### OS & Software

- Regularly update OS and packages.
- Remove unused services and software.
- Enable SELinux or AppArmor.

### Backup & Disaster Recovery

- Perform scheduled backups of configs and data.
- Store backups securely offsite or in secure cloud.
- Test recovery and restoration procedures.
