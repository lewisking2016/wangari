---
name: database-design
description: >
  Database schema design, query optimization, indexing strategy, and Prisma ORM
  patterns for the Wangari project's PostgreSQL database. Use when designing new
  models, writing migrations, optimizing slow queries, or reviewing schema.
---
# Database Design

Full skill source: `../../skills/antigravity/database-design.md`

## Stack
- **ORM:** Prisma
- **Database:** PostgreSQL
- **Schema:** `wangari-next/prisma/schema.prisma`

## Schema Design Rules

### Naming Conventions
- Models: PascalCase (`FlockRecord`, `ProductionRecord`)
- Fields: camelCase (`eggsCollected`, `farmId`)
- Relations: descriptive name (`farm`, `flock`, `user`)
- Junction tables: combine both model names

### Required Fields on Every Model
```prisma
model Resource {
  id        Int      @id @default(autoincrement())
  createdAt DateTime @default(now())
  updatedAt DateTime @updatedAt
  farmId    Int      // tenant isolation
  farm      Farm     @relation(fields: [farmId], references: [id])
}
```

### Multi-Tenancy Rule (Critical)
Every model that belongs to a farm MUST have `farmId`. Every API query MUST filter by `farmId` from the authenticated user's session. Never allow cross-farm data leakage.

```typescript
// ✅ Correct — always scope to farm
const records = await prisma.sale.findMany({
  where: { farmId: req.user.farmId }
});

// ❌ Wrong — no farm scope
const records = await prisma.sale.findMany();
```

### Indexing
```prisma
// Index foreign keys and frequently queried fields
@@index([farmId])
@@index([farmId, createdAt])
@@index([farmId, type])
```

### Soft Delete Pattern
Use `deletedAt DateTime?` rather than hard delete for important records (flocks, sales, financial transactions). Allows audit trail.

### Decimal vs Float
Use `Decimal` for monetary values and production quantities — `Float` has precision issues.

```prisma
totalAmount Decimal @db.Decimal(12, 2)
```

## Query Patterns

### Pagination
```typescript
const items = await prisma.resource.findMany({
  where: { farmId },
  orderBy: { createdAt: "desc" },
  take: 20,
  skip: page * 20,
});
```

### Include Relations
```typescript
// Include only what you need
const sales = await prisma.sale.findMany({
  where: { farmId },
  include: {
    customer: { select: { id: true, name: true } },
    items: true,
  },
});
```

### Aggregations
```typescript
const totals = await prisma.sale.aggregate({
  where: { farmId, paymentStatus: "paid" },
  _sum: { totalAmount: true, amountPaid: true },
});
```

## Migration Commands
```bash
# Create migration
npx prisma migrate dev --name "add_feature"

# Apply to production
npx prisma migrate deploy

# Reset dev DB (careful!)
npx prisma migrate reset

# Regenerate client after schema change
npx prisma generate
```
