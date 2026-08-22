# Wangari Farm Admin Expansion Plan

## Goal
Build a user-friendly farm management admin system suitable for a Grade 7 student.
Use simple language, short module names, clear buttons, and the current Wangari Admin design style.

## New module names
- Home
- Farm Items
- Sales
- Feed Stock
- Herds
- Health
- Spending
- Reports
- Staff
- Setup
- Animals
- Breeding
- Tasks
- Calendar
- Payments
- Alerts
- Logs
- Messages

## Modules and screens

### 1. Animals
1. Animals list
   - Shows all animals with type, tag, name, age, herd, status.
   - Search and filter by type, status, herd.
   - Buttons: Add, Edit, View, Delete.
2. Add animal
   - Fields: animal type, breed, tag, name, gender, birth date, parent, herd, status.
   - Save record and show success or error.
3. Edit animal
   - Load current animal data and update it.
4. Animal detail
   - Show history, health, production, breeding, and sale status.

### 2. Herds
1. Herd list
   - Show herds with species, size, location, status.
   - Filter by species and location.
   - Buttons: Add, Edit, View, Archive.
2. Add herd
   - Fields: herd name, species, location, description, size.
3. Edit herd
   - Update name, location, size, status.
4. Herd detail
   - Show members, feed plan, output, health summary.

### 3. Feed Stock
1. Feed list
   - Show feed items and raw material stock.
   - Show amount, unit, low stock warning.
   - Buttons: Add, Edit, Use, View.
2. Add feed
   - Fields: item name, feed type, unit, quantity, cost, supplier.
3. Edit feed
   - Update quantity, price, supplier.
4. Feed detail
   - Show stock, use history, recipe links, reorder suggestion.
5. Feed plan
   - Assign recipes to species or herd.
   - Show required ingredients and stock impact.

### 4. Health
1. Health list
   - Show health records for animals and herds.
   - Buttons: Add, Edit, View.
2. Add health
   - Fields: animal/herd, health type, medicine/vaccine, date, next date, notes.
3. Edit health
   - Update health event details.
4. Health detail
   - Show health history and next due dates.
5. Vaccine plan
   - Manage vaccine settings and schedule.

### 5. Breeding
1. Breeding list
   - Show mating and pregnancy records.
   - Buttons: Add, Edit, View.
2. Add breeding
   - Fields: animal/herd, type, date, male parent, due date, notes.
3. Edit breeding
   - Update breeding details and status.
4. Birth record
   - Save birth outcomes and offspring counts.
5. Breeding detail
   - Show mating history, births, and offspring.

### 6. Milk
1. Milk list
   - Show milk production records.
   - Buttons: Add, Edit, View.
2. Add milk
   - Fields: animal/herd, date, quantity, quality note.
3. Edit milk
   - Update record data.
4. Milk report
   - Show totals, averages, and filters by date.

### 7. Wool
1. Wool list
   - Show wool production records.
   - Buttons: Add, Edit, View.
2. Add wool
   - Fields: herd, date, weight, quality.
3. Edit wool
   - Update record details.
4. Wool report
   - Show totals by herd and date.

### 8. Tasks
1. Task list
   - Show farm tasks and due status.
   - Buttons: Add, Edit, Complete, View.
2. Add task
   - Fields: task name, type, date, time, staff, notes.
3. Edit task
   - Update task details.
4. Task detail
   - Show task notes and completion status.
5. Task history
   - Show completed tasks.

### 9. Calendar
1. Month view
   - Show tasks and events by month.
2. Week view
   - Show weekly schedule and assignments.
3. Day view
   - Show daily work and time slots.

### 10. Payments
1. Payment list
   - Show payment records and status.
   - Buttons: Add, Approve, View.
2. Add payment
   - Fields: type, amount, supplier/staff, date, method.
3. Approve payment
   - Manage pending approvals.
4. Payment detail
   - Show history, notes, proof.
5. Statement
   - Show totals and methods.

### 11. Alerts
1. Alert list
   - Show warnings and actions needed.
   - Buttons: View, Clear.
2. Add alert
   - Fields: type, related item, date, message.
3. Alert detail
   - Show action and resolution.

### 12. Logs
1. Log list
   - Show system actions and user activity.
   - Buttons: View.
2. Log detail
   - Show full action text and item.
3. Log filter
   - Filter by user, type, and date.

### 13. Messages
1. Message list
   - Show sent messages and status.
   - Buttons: Send, View.
2. Send message
   - Fields: recipient, message text, send method.
3. Message detail
   - Show read status and replies.

### 14. Staff
1. Staff list
   - Show staff names, roles, phone, status.
   - Buttons: Add, Edit, View.
2. Add staff
   - Fields: name, role, phone, email, status.
3. Edit staff
   - Update staff details.
4. Staff detail
   - Show assignments and leave.
5. Leave request
   - Approve or reject leave.

### 15. Reports
1. Report list
   - Show report types and open actions.
2. Herd report
   - Show herd totals and health.
3. Feed report
   - Show feed use, stock, and cost.
4. Milk report
   - Show milk totals and averages.
5. Wool report
   - Show wool totals by herd.
6. Sales report
   - Show income and top items.
7. Health report
   - Show vaccines and sickness events.
8. Mortality report
   - Show dead animals and reasons.
9. Audit report
   - Show system actions and edits.

### 16. Setup
1. Setup list
   - Show setup items and edit actions.
2. Species setup
   - Add animal types.
3. Breed setup
   - Add breeds per species.
4. Vaccine setup
   - Add vaccine and medicine names.
5. Feed type setup
   - Add feed types and units.
6. Role setup
   - Add roles and permissions.

### 17. Farm Items
1. Item list
   - Show all sellable items.
   - Buttons: Add, Edit, View.
2. Add item
   - Fields: name, type, species, price, stock.
3. Edit item
   - Update price, stock, details.
4. Item detail
   - Show sales and stock history.

### 18. Sales
1. Sales list
   - Show all customer sales.
   - Buttons: View, Print.
2. Add sale
   - Fields: customer, item, quantity, price, payment.
3. Sale detail
   - Show sale items and status.
4. Invoice
   - Printable sale summary.

## Design and logic rules
- Use simple, short English.
- Keep labels in one or two words.
- Keep buttons clear: Add, Save, Change, Remove.
- Keep forms short.
- Use one main action per page.
- Use list screens for quick actions.
- Use detail screens for full history.
- Use report screens for totals and summary.
- Keep the interface friendly for a Grade 7 student.

## Next steps
1. Add new admin menu items in the sidebar.
2. Create new admin pages for the modules above.
3. Use the current admin design and button styles.
4. Test every page for PHP syntax and basic load.
