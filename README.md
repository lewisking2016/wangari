# Wangari — Farm Management Platform

A full-stack farm management platform built with Next.js, deployed on Vercel at [wangari.imeantech.com](https://wangari.imeantech.com).

## Project Structure

```
wangari-next/          # Next.js 16 frontend (TypeScript, Tailwind, Prisma)
├── src/
│   ├── app/           # App Router pages & API routes
│   │   ├── (auth)/    # Login, Register
│   │   ├── (dashboard)/ # Main dashboard modules
│   │   ├── (marketing)/ # Landing, About, Pricing, Features
│   │   └── api/       # API routes (Prisma → PostgreSQL)
│   ├── components/    # React components
│   ├── hooks/         # Custom React hooks
│   ├── lib/           # Utilities, auth, DB client, validators
│   └── types/         # TypeScript type definitions
├── prisma/            # Prisma schema & seed
└── public/            # Static assets
```

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Framework | Next.js 16 (App Router) |
| Language | TypeScript |
| Styling | Tailwind CSS 4 |
| ORM | Prisma 6 |
| Database | PostgreSQL |
| Auth | NextAuth v5 |
| Charts | Recharts |
| Animations | Framer Motion |
| Deployment | Vercel |

## Development

```bash
cd wangari-next
npm install
cp .env.example .env.local   # configure env vars
npx prisma generate
npm run dev
```

### Available Scripts

| Command | Description |
|---------|-------------|
| `npm run dev` | Start dev server |
| `npm run build` | Production build |
| `npm run start` | Start production server |
| `npm run lint` | Run linter |
| `npx prisma db push` | Push schema to DB |
| `npx prisma migrate dev` | Run migrations |
| `npx prisma studio` | Open Prisma Studio |

## Deployment

- Deployed on **Vercel** at `wangari.imeantech.com`
- Database: **PostgreSQL** (set `DATABASE_URL` in Vercel env vars)
- Auth: **NextAuth v5** (set `NEXTAUTH_URL` and `NEXTAUTH_SECRET` in Vercel)
