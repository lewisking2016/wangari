/**
 * Module gating middleware for Express.
 * Checks if user has access to a module based on subscription/trial status.
 */
const jwt = require('jsonwebtoken');
const { PrismaClient } = require('@prisma/client');

const prisma = new PrismaClient();
const JWT_SECRET = process.env.JWT_SECRET || 'dev-secret';

const HUB_MODULES = {
  poultry: ['livestock', 'production', 'vaccinations'],
  crops: ['crops'],
  inventory: ['inventory'],
  money: ['finances', 'transactions'],
  sales: ['sales', 'customers', 'invoices'],
  team: ['workers', 'attendance'],
};

const FREE_MODULES = ['dashboard', 'settings', 'feed-calculator', 'weather', 'reports', 'inventory'];

async function getUserAccess(userId) {
  const user = await prisma.user.findUnique({
    where: { id: userId },
    select: { selectedHubs: true, trialEndsAt: true, trialStartsAt: true },
  });
  if (!user) return { hasAccess: false, hubs: [] };

  const now = new Date();
  const trialActive = user.trialEndsAt && new Date(user.trialEndsAt) > now;

  const sub = await prisma.subscription.findFirst({
    where: { userId, status: 'active', expiresAt: { gt: now } },
    orderBy: { createdAt: 'desc' },
  }).catch(() => null);

  if (trialActive || sub) {
    const hubs = (user.selectedHubs || '').split(',').filter(Boolean);
    return { hasAccess: true, hubs, isTrial: !!trialActive, plan: sub?.planName };
  }

  return { hasAccess: false, hubs: [] };
}

function requireModule(moduleKey) {
  return async (req, res, next) => {
    try {
      if (FREE_MODULES.includes(moduleKey)) return next();

      const authHeader = req.headers.authorization;
      if (!authHeader || !authHeader.startsWith('Bearer ')) {
        return res.status(401).json({ error: 'Unauthorized' });
      }

      const token = authHeader.slice(7);
      const decoded = jwt.verify(token, JWT_SECRET);
      
      const access = await getUserAccess(decoded.userId || decoded.id);
      if (!access.hasAccess) {
        return res.status(403).json({
          error: 'Subscription required',
          message: 'Subscribe to access this module',
          module: moduleKey,
        });
      }

      const userHubs = access.hubs;
      let moduleAllowed = false;

      for (const [hub, modules] of Object.entries(HUB_MODULES)) {
        if (modules.includes(moduleKey) && userHubs.includes(hub)) {
          moduleAllowed = true;
          break;
        }
        if (moduleKey === 'inventory') {
          moduleAllowed = true;
          break;
        }
      }

      if (!moduleAllowed && access.plan !== 'enterprise') {
        return res.status(403).json({
          error: 'Module not included in your plan',
          message: 'Upgrade your plan to access this module',
          module: moduleKey,
        });
      }

      next();
    } catch (err) {
      next(err);
    }
  };
}

module.exports = { requireModule, FREE_MODULES };
