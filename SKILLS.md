# Wangari Website — AI Development Skills Guide

> **Purpose:** This file tells any AI model (Claude, Gemini, GPT, etc.) which skills to install and reference before developing this website.
> **Skills Repo:** C:\Users\lewis\Desktop\antigravity-skills-repo
> **Last Updated:** July 16, 2026

---

## MANDATORY: Read Before Any Development

Before writing any code for this project, the AI model must:

1. **Read** `WEBSITE_DESIGN.md` in this project root for the complete design vision.
2. **Install/reference** the skills listed below from the antigravity-skills-repo.
3. **Follow** the design system, color palette, and typography defined in `WEBSITE_DESIGN.md`.
4. **Never** use placeholder "lorem ipsum" — use realistic poultry farming content.
5. **Never** produce generic/basic UI — this must look premium and modern.
6. **Never** use emojis in any UI, documentation, files, or comments in this project codebase.

---

## Required Skills (from antigravity-skills-repo)

### CRITICAL — Must Read Before ANY Development

| Priority | Skill Name | Path | Why |
|----------|-----------|------|-----|
| CRITICAL | `frontend-design` | `skills/frontend-design/SKILL.md` | Production-grade, distinctive frontend interfaces. Ensures no generic AI UI patterns. Establishes intentional aesthetic direction. |
| CRITICAL | `php-pro` | `skills/php-pro/SKILL.md` | Modern PHP 8+ development with strict typing, generators, SPL, security patterns. This is a PHP project hosted on cPanel. |
| CRITICAL | `antigravity-design-expert` | `skills/antigravity-design-expert/SKILL.md` | Glassmorphism, floating elements, spatial depth, premium UI feel. Core visual identity for the site. |
| CRITICAL | `high-end-visual-design` | `skills/high-end-visual-design/SKILL.md` | High-end visual design principles for premium look and feel. |

### IMPORTANT — Read For Specific Tasks

| Priority | Skill Name | Path | When to Use |
|----------|-----------|------|-------------|
| IMPORTANT | `frontend-developer` | `skills/frontend-developer/SKILL.md` | When building any frontend component, layout, or responsive design |
| IMPORTANT | `frontend-dev-guidelines` | `skills/frontend-dev-guidelines/SKILL.md` | For frontend coding standards and patterns |
| IMPORTANT | `database-design` | `skills/database-design/SKILL.md` | When designing/modifying the MySQL database schema |
| IMPORTANT | `security-auditor` | `skills/security-auditor/SKILL.md` | When implementing auth, forms, payment processing (M-Pesa) |
| IMPORTANT | `payment-integration` | `skills/payment-integration/SKILL.md` | When integrating M-Pesa STK Push or any payment gateway |
| IMPORTANT | `ai-seo` | `skills/ai-seo/SKILL.md` | When optimizing pages for search engines |
| IMPORTANT | `brand-guidelines` | `skills/brand-guidelines/SKILL.md` | When defining or extending brand visual identity |
| IMPORTANT | `product-design` | `skills/product-design/SKILL.md` | For UX flows, design tokens, and user journey mapping |
| IMPORTANT | `mobile-design` | `skills/mobile-design/SKILL.md` | When building mobile-responsive layouts and touch interactions |

### HELPFUL — Reference As Needed

| Priority | Skill Name | Path | When to Use |
|----------|-----------|------|-------------|
| HELPFUL | `animejs-animation` | `skills/animejs-animation/SKILL.md` | For complex CSS/JS animations (hero slider, scroll effects) |
| HELPFUL | `canvas-design` | `skills/canvas-design/SKILL.md` | For creating visual assets, posters, promotional graphics |
| HELPFUL | `performance-optimizer` | `skills/performance-optimizer/SKILL.md` | For page load performance optimization |
| HELPFUL | `accessibility-compliance-accessibility-audit` | `skills/accessibility-compliance-accessibility-audit/SKILL.md` | For WCAG compliance checks |
| HELPFUL | `clean-code` | `skills/clean-code/SKILL.md` | For maintaining code quality standards |
| HELPFUL | `code-reviewer` | `skills/code-reviewer/SKILL.md` | For reviewing completed code before deployment |
| HELPFUL | `api-design-principles` | `skills/api-design-principles/SKILL.md` | When building AJAX endpoints (cart, checkout, M-Pesa callback) |
| HELPFUL | `api-security-best-practices` | `skills/api-security-best-practices/SKILL.md` | For securing API/AJAX endpoints |
| HELPFUL | `auth-implementation-patterns` | `skills/auth-implementation-patterns/SKILL.md` | For login, registration, session management, password reset |
| HELPFUL | `email-systems` | `skills/email-systems/SKILL.md` | For transactional emails (order confirmation, password reset) |
| HELPFUL | `form-cro` | `skills/form-cro/SKILL.md` | For optimizing checkout and contact forms |
| HELPFUL | `i18n-localization` | `skills/i18n-localization/SKILL.md` | If adding Swahili/multi-language support |
| HELPFUL | `progressive-web-app` | `skills/progressive-web-app/SKILL.md` | For offline support and installable web app features |
| HELPFUL | `redesign-existing-projects` | `skills/redesign-existing-projects/SKILL.md` | Reference for the redesign methodology we're following |

---

## Tools from the Skills Repo

### Open Design (for visual assets)
| Tool | Path | Use |
|------|------|-----|
| `open-design` | `tools/open-design/` | 259+ design skills, 142+ design systems. Use for generating UI components and visual patterns. |
| `graphify-dark-graph` | `tools/open-design/design-templates/html-ppt-graphify-dark-graph/SKILL.md` | Dark-themed graph/dashboard templates if needed for the admin panel. |

### Agent Browser (for testing)
| Tool | Path | Use |
|------|------|-----|
| `agent-browser` | `tools/agent-browser/` | Browser automation and testing — use for cross-browser compatibility checks. |

---

## Skill Installation Instructions

### For Claude Code / Antigravity IDE
Skills are automatically available. Reference them by reading the SKILL.md files at the paths listed above.

### For Other AI Models (Cursor, Windsurf, etc.)

1. **Copy the required SKILL.md files** into the project's `.agents/skills/` directory:
```bash
# Create skills directory in the project
mkdir -p .agents/skills

# Copy critical skills
cp "C:\Users\lewis\Desktop\antigravity-skills-repo\skills\frontend-design\SKILL.md" .agents/skills/frontend-design.md
cp "C:\Users\lewis\Desktop\antigravity-skills-repo\skills\php-pro\SKILL.md" .agents/skills/php-pro.md
cp "C:\Users\lewis\Desktop\antigravity-skills-repo\skills\antigravity-design-expert\SKILL.md" .agents/skills/antigravity-design-expert.md
cp "C:\Users\lewis\Desktop\antigravity-skills-repo\skills\high-end-visual-design\SKILL.md" .agents/skills/high-end-visual-design.md
```

2. **Or create an AGENTS.md** in the project root with consolidated rules (see below).

---

## Consolidated Rules for AGENTS.md

If your AI tool uses an AGENTS.md or similar project-level rules file, include these rules:

```markdown
# Wangari Website — Development Rules

## Tech Stack
- PHP 8.1+ (cPanel-hosted, no frameworks — vanilla PHP with PDO)
- Vanilla CSS (no Tailwind, no Bootstrap — custom design system)
- Vanilla JavaScript (no React, no jQuery — modern ES6+)
- MySQL 8.0+ database

## Design Principles
- MUST follow the design system in WEBSITE_DESIGN.md
- MUST use the defined color palette (Forest Green + Amber Gold + Navy)
- MUST use Google Fonts: Outfit (headings), Inter (body), DM Sans (accents)
- MUST implement smooth animations and micro-interactions
- MUST be mobile-first responsive
- MUST look premium — no basic/generic UI allowed
- MUST use semantic HTML5 elements
- MUST NOT use emojis in any UI, files, comments, or documentation

## Security Requirements
- All database queries use PDO prepared statements
- All forms include CSRF tokens
- All user input is sanitized and validated
- Passwords hashed with bcrypt (password_hash/password_verify)
- Session management with regeneration on login
- HTTP-only, secure cookies
- Content Security Policy headers

## Code Quality
- PHP code follows PSR-12 coding standard
- Functions and variables use descriptive names
- All PHP files start with <?php (no short tags)
- Error handling with try/catch blocks
- No inline SQL — use parameterized queries
- CSS uses BEM naming convention where applicable
- JavaScript uses const/let (never var), async/await for async ops

## Performance
- Images optimized to WebP format with JPEG fallbacks
- CSS and JS minified for production
- Lazy loading for below-fold images
- Browser caching via .htaccess headers
- Gzip compression enabled

## File Organization
- Follow the directory structure defined in WEBSITE_DESIGN.md
- Shared components in /includes/
- Page-specific files in /pages/
- All assets in /assets/ with proper subdirectories
- API/AJAX endpoints in /api/

## E-Commerce
- M-Pesa integration via Safaricom Daraja API
- Shopping cart stored in session + database
- Order management with status tracking
- Product catalog with categories and search
```

---

## Quick Start for New AI Conversations

When starting a new conversation with any AI about this project, use this prompt template:

```
I'm building the Wangari Farm website. Before doing anything:

1. Read WEBSITE_DESIGN.md for the complete design vision and specifications
2. Read SKILLS.md for the required development skills and rules
3. The project uses PHP (for cPanel hosting), vanilla CSS, and vanilla JavaScript
4. Follow the design system strictly — colors, fonts, spacing, animations
5. Reference skills from: C:\Users\lewis\Desktop\antigravity-skills-repo
6. Ensure absolutely no emojis are used in any code, files, or UI.

Now, [your specific task here].
```

---

## Skills Summary Table

| Category | Skills Count | Key Skills |
|----------|-------------|------------|
| Frontend Design | 5 | frontend-design, antigravity-design-expert, high-end-visual-design, mobile-design, product-design |
| Backend (PHP) | 3 | php-pro, auth-implementation-patterns, api-design-principles |
| Database | 1 | database-design |
| Security | 3 | security-auditor, api-security-best-practices, auth-implementation-patterns |
| E-Commerce | 2 | payment-integration, form-cro |
| SEO & Performance | 3 | ai-seo, performance-optimizer, accessibility-compliance |
| Code Quality | 2 | clean-code, code-reviewer |
| Bonus | 4 | animejs-animation, canvas-design, email-systems, i18n-localization |
| Total | ~23 | — |
