# Wangari Admin Dashboard Redesign and Admin Module Repair

## Objective

Rebuild the `wangariadmin` dashboard into a high-visibility operations console and fix the admin modules that are currently mismatched, incomplete, or time-incorrect. The result should feel like a live production control center, not a basic admin page.

## What We Are Fixing

### 1. Dashboard presentation

- Replace the current light stat-card layout with a more premium executive dashboard.
- Add stronger visual hierarchy, clearer KPIs, and better spacing.
- Add advanced charting so the dashboard shows trends, not only totals.
- Make the layout responsive on desktop and mobile.

### 2. Real data integrity

- Keep all dashboard figures sourced from the backend.
- Remove any dummy, fallback, placeholder, or hardcoded operational numbers from admin screens.
- If a module has no data, show a deliberate empty state instead of fake values.

### 3. Module repair

- Fix the frontend/backend action mismatches in tickets, emergency, and settings.
- Verify user management, license management, revenue, subscriptions, activity, and support flows use the same backend contract as the UI.
- Ensure the desktop license generator is tied to an existing registered user account.

### 4. Time correction

- Display platform time using Africa/Nairobi consistently in the admin UI.
- Remove raw timestamp presentation where it makes the system look incorrect.
- Standardize timestamps in dashboard cards, tables, activity log, tickets, and any “last updated” areas.

## Current Problems Observed

- The dashboard is data-backed, but the UI is too shallow for a live admin center.
- Ticket actions in the frontend do not match the backend API names.
- Emergency controls in the frontend do not match backend actions.
- Settings read/save calls in the frontend do not match backend endpoints.
- Ticket lookup uses a `code` parameter in the UI, while the backend expects an `id`.
- Raw timestamps are shown directly, which makes the system time look wrong.
- Google OAuth still has hardcoded fallback credentials in config.
- A legacy license server still exists alongside the newer licensing path.

## Target UX

The dashboard should feel like a control room with four layers:

1. Top-line KPI cards
2. Trend charts and operational health panels
3. Recent activity and support queues
4. Module health, alerts, and settings shortcuts

The visual style should be:

- Clean but premium
- Dense enough for administrators
- High contrast and easy to scan
- More mature than a standard “cards on white background” admin shell

## Dashboard Content

### KPI cards

Show the most important live metrics at the top:

- Total users
- Active subscriptions
- Trial users
- Expired users
- Total revenue
- Month revenue
- Available licenses
- Open tickets
- Critical tickets

Each card should clearly show:

- Primary value
- Supporting trend or delta where available
- Status color that matches the metric state

### Charts

Add chart sections for:

- Revenue by month
- Subscription status distribution
- User growth over time
- Ticket volume by priority/status
- License utilization and expiry pressure

Charts should be backed by live API data. If the backend does not yet provide a good shape for a chart, the backend should be extended rather than faking the chart on the frontend.

### Operational panels

Add panels for:

- Recent users
- Recent tickets
- Recent revenue activity
- License expiry and usage health
- Platform settings snapshot
- Module sync health

## Module Fixes

### Tickets

- Align frontend calls with backend support ticket actions.
- Support view, reply, and close flows consistently.
- Use the correct identifier field for ticket lookup.

### Emergency

- Align emergency frontend actions with the backend contract.
- Show real emergency contacts and real report actions.
- Avoid dead buttons or fake “lockdown” actions if the backend does not truly support them.

### Settings

- Align settings fetch/update calls.
- Save settings through the actual backend action names.
- Surface validation errors clearly.

### Users

- Keep user CRUD connected to real platform users.
- Continue enforcing the allowed email policy for manual registration.
- Keep Google-linked and manually registered accounts consistent.

### Desktop licenses

- Keep license generation tied to an existing registered user account.
- Ensure expiry and device count are visible and enforceable.
- Make license status and expiry obvious in the admin table.

## Time Handling

The admin UI should display dates and time using a single consistent rule:

- Use Africa/Nairobi for display formatting.
- Prefer human-readable timestamps for tables and activity logs.
- Use a relative format only where it helps scanning, such as “5 min ago”.

If server-side timestamps are drifting, we should correct the backend timezone configuration as part of the implementation, not only patch the UI.

## Backend Data Expectations

The dashboard should continue to use live counts from:

- `platform_users`
- `platform_subscriptions`
- `platform_revenue`
- `wangari_licenses`
- `support_tickets`
- `platform_activity_log`
- `platform_settings`

If a chart needs more granular data, the backend should provide it through the existing platform API instead of the frontend inventing synthetic data.

## Security and Cleanup Notes

This work should also keep an eye on:

- Hardcoded OAuth fallback credentials
- Duplicate license-server logic
- Unsafe fallback values in production config
- Any API response that makes the UI depend on stale or fake defaults

## Acceptance Criteria

The work is complete when:

- The dashboard has a visibly improved layout with stronger cards and clearer information hierarchy.
- At least the main dashboard charts are connected to live backend data.
- Tickets, emergency, settings, and license actions no longer rely on mismatched frontend calls.
- The dashboard time and timestamps display correctly for Nairobi time.
- No admin screen is using fake operational data as if it were real.
- The platform admin can navigate all modules without dead actions or obvious contract mismatches.

## Verification

We will verify by:

- Checking the dashboard render with live backend data
- Exercising the repaired admin actions end to end
- Confirming timestamps show the expected Nairobi time format
- Confirming empty states appear when real data is missing
- Confirming the license generator only uses registered accounts

