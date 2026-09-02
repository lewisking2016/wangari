# Wangari — Farm Management Platform

A full-stack farm management platform with a Next.js frontend and PHP API backend.

## Project Structure

```
├── wangari-next/          # Next.js 16 frontend (TypeScript, Tailwind, Prisma)
│   ├── src/
│   │   ├── app/           # App Router pages & API routes
│   │   │   ├── (auth)/    # Login, Register
│   │   │   ├── (dashboard)/ # Main dashboard modules
│   │   │   ├── (marketing)/ # Landing, About, Pricing, Features
│   │   │   └── api/       # API routes
│   │   ├── components/    # React components
│   │   ├── hooks/         # Custom React hooks
│   │   ├── lib/           # Utilities, auth, DB client, validators
│   │   └── types/         # TypeScript type definitions
│   ├── prisma/            # Prisma schema & seed
│   └── public/            # Static assets
│
├── Backend/               # PHP API backend
│   ├── api/               # API endpoints
│   ├── config/            # Database, AI engine, services config
│   ├── license/           # Desktop license guard (no-op on web)
│   └── storage/           # Session storage
│
└── .gitignore
```

## Frontend (wangari-next)

- **Framework:** Next.js 16 (App Router)
- **Language:** TypeScript
- **Styling:** Tailwind CSS 4
- **Database:** Prisma ORM (PostgreSQL)
- **Auth:** NextAuth v5
- **Charts:** Recharts
- **Animations:** Framer Motion

### Development

```bash
cd wangari-next
npm install
cp .env.production.example .env.local  # configure DB URL
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

## Backend (PHP)

- **Language:** PHP 8.0+
- **Database:** MySQL (production) / SQLite (desktop mode)
- **Auth:** Session-based

### API Endpoints

All endpoints are in `Backend/api/` and served via PHP.

### Configuration

- `Backend/config/database.php` — DB connection (reads `database.local.php` if present)
- `Backend/config/openrouter.php` — AI service config
- `Backend/.env` — Environment variables

## Deployment (VPS)

### Frontend
- Build the Next.js app and serve with `npm run start` or a process manager (PM2)
- Configure Nginx reverse proxy for the frontend

### Backend
- Point Nginx to serve PHP files via PHP-FPM
- Ensure `Backend/.env` has correct production database credentials

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Frontend | Next.js 16, React 19, TypeScript, Tailwind CSS 4 |
| ORM | Prisma 6 |
| Auth | NextAuth v5 |
| Backend API | PHP 8.0+, PDO |
| Database | MySQL / PostgreSQL |
| Deployment | VPS (Nginx + PHP-FPM + PM2) |
