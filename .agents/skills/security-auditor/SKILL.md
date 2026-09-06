---
name: security-auditor
description: >
  Security audit for web applications. Reviews authentication, authorization,
  input validation, API security, data exposure, dependency vulnerabilities,
  and common web vulnerabilities (XSS, CSRF, SQL injection, IDOR). Use when
  reviewing code for security issues or hardening an existing app.
---
# Security Auditor

Full skill source: `../../skills/antigravity/security-auditor.md`

## When to Use
- Code review for security vulnerabilities
- Pre-deployment security checklist
- Auditing API routes and authentication flows
- Reviewing data handling and exposure

## Critical Checks (Wangari Context)

### Authentication
- [ ] JWT tokens validated on every API route
- [ ] Token expiry enforced
- [ ] Refresh tokens stored securely (httpOnly cookie, not localStorage)
- [ ] Auth middleware applied to all protected routes
- [ ] Passwords hashed with bcrypt (minimum 12 rounds)

### Authorization
- [ ] Role-based access control: `super_admin`, `farm_owner`, `worker` etc.
- [ ] Users can only access their own farm's data
- [ ] Worker role cannot access financial data of other workers
- [ ] Super admin routes protected by role check

### Input Validation
- [ ] All user inputs validated on the server (not just client)
- [ ] SQL injection impossible (use parameterized queries / Prisma ORM)
- [ ] File upload types/sizes validated
- [ ] Number inputs clamped to valid ranges
- [ ] Strings sanitized before storage (strip HTML)

### API Security
- [ ] CORS configured for specific origins (not `*`)
- [ ] Rate limiting on auth endpoints (login, register)
- [ ] No sensitive data in URL params (tokens, passwords)
- [ ] Error messages don't expose internal details
- [ ] API responds consistently to prevent user enumeration

### Data Exposure
- [ ] No passwords/hashes returned in API responses
- [ ] No internal IDs exposed unnecessarily
- [ ] Prisma `select` used to limit fields returned
- [ ] No `console.log(user)` with sensitive data in production

### Client-Side
- [ ] No secrets in client-side code or `.env.local` committed
- [ ] Content Security Policy headers set
- [ ] `dangerouslySetInnerHTML` never used with user content
- [ ] `eval()` never used

## Common Vulnerabilities Checklist
| Vulnerability | Check |
|--------------|-------|
| IDOR | Can user access resource with another user's ID? |
| XSS | User input ever rendered as raw HTML? |
| CSRF | State-changing requests require auth token? |
| SQL Injection | All DB queries use Prisma/parameterized? |
| Mass Assignment | `...req.body` spread directly to DB update? |
| Sensitive Data Exposure | API returning more fields than needed? |

## Severity Levels
- **Critical** — exploitable now, fix before merge
- **High** — likely to be exploited, fix this sprint
- **Medium** — fix in next iteration
- **Low** — minor hardening, fix opportunistically
