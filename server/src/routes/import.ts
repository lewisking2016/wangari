import { Router, Request, Response } from "express";
import { prisma } from "../db.js";
import { authMiddleware } from "../middleware/auth.js";

const router = Router();
router.use(authMiddleware);

// POST /api/import/:type — bulk import records
// Supported types: livestock, production, sales, customers, inventory, finances
router.post("/:type", async (req: Request, res: Response) => {
  try {
    const farmId = req.user!.farmId!;
    const { type } = req.params;
    const { rows } = req.body;

    if (!Array.isArray(rows) || rows.length === 0) {
      return res.status(400).json({ error: "No data to import" });
    }

    let created = 0;
    let skipped = 0;

    if (type === "livestock") {
      for (const row of rows) {
        try {
          await prisma.flock.create({
            data: {
              farmId,
              name: row.name || "Imported Group",
              type: row.type || row.species || "layers",
              category: row.category || "poultry",
              initialCount: Number(row.count || row.initialCount || 0),
              currentCount: Number(row.count || row.currentCount || row.initialCount || 0),
              breed: row.breed || null,
              hatchDate: row.hatchDate ? new Date(row.hatchDate) : null,
              costPerAnimal: row.costPerAnimal ? Number(row.costPerAnimal) : null,
              status: "active",
            },
          });
          created++;
        } catch { skipped++; }
      }
    } else if (type === "production") {
      for (const row of rows) {
        try {
          // Find flock by name
          const flock = await prisma.flock.findFirst({ where: { farmId, name: row.group || row.flockName || row.name } });
          if (!flock) { skipped++; continue; }
          await prisma.dailyProduction.create({
            data: {
              farmId,
              flockId: flock.id,
              date: row.date ? new Date(row.date) : new Date(),
              eggsCollected: Number(row.eggs || row.eggsCollected || 0),
              milkCollected: Number(row.milk || row.milkCollected || 0),
              weightGain: Number(row.weight || row.weightGain || 0),
              mortality: Number(row.mortality || row.deaths || 0),
              feedUsed: Number(row.feed || row.feedUsed || 0),
            },
          });
          created++;
        } catch { skipped++; }
      }
    } else if (type === "sales") {
      for (const row of rows) {
        try {
          // Find or create customer
          let customerId = null;
          if (row.customer || row.customerName) {
            let cust = await prisma.customer.findFirst({ where: { farmId, name: row.customer || row.customerName } });
            if (!cust) {
              cust = await prisma.customer.create({ data: { farmId, name: row.customer || row.customerName, phone: row.phone || null } });
            }
            customerId = cust.id;
          }
          await prisma.sale.create({
            data: {
              farmId,
              customerId,
              totalAmount: Number(row.amount || row.totalAmount || 0),
              amountPaid: Number(row.paid || row.amountPaid || row.amount || row.totalAmount || 0),
              paymentStatus: row.status || row.paymentStatus || "paid",
              items: row.product ? [{ name: row.product, quantity: Number(row.qty || 1), price: Number(row.amount || 0) }] : [],
              saleDate: row.date ? new Date(row.date) : new Date(),
            },
          });
          created++;
        } catch { skipped++; }
      }
    } else if (type === "customers") {
      for (const row of rows) {
        try {
          const existing = await prisma.customer.findFirst({ where: { farmId, name: row.name } });
          if (existing) { skipped++; continue; }
          await prisma.customer.create({
            data: {
              farmId,
              name: row.name,
              phone: row.phone || null,
              email: row.email || null,
              address: row.address || null,
            },
          });
          created++;
        } catch { skipped++; }
      }
    } else if (type === "inventory") {
      for (const row of rows) {
        try {
          await prisma.inventory.create({
            data: {
              farmId,
              itemName: row.name || row.itemName || "Imported Item",
              category: row.category || "other",
              quantity: Number(row.quantity || row.qty || 0),
              unit: row.unit || "kg",
              unitCost: Number(row.cost || row.unitCost || 0),
              reorderLevel: Number(row.reorderLevel || row.reorder || 0),
              supplier: row.supplier || null,
            },
          });
          created++;
        } catch { skipped++; }
      }
    } else if (type === "finances") {
      for (const row of rows) {
        try {
          await prisma.transaction.create({
            data: {
              farmId,
              type: row.type || "expense",
              category: row.category || "other",
              amount: Number(row.amount || 0),
              description: row.description || row.note || null,
              date: row.date ? new Date(row.date) : new Date(),
            },
          });
          created++;
        } catch { skipped++; }
      }
    } else {
      return res.status(400).json({ error: `Unknown type: ${type}. Supported: livestock, production, sales, customers, inventory, finances` });
    }

    res.json({ created, skipped, total: rows.length });
  } catch (error) {
    res.status(500).json({ error: "Import failed" });
  }
});

// GET /api/import/templates — download CSV template for each type
router.get("/templates/:type", async (req: Request, res: Response) => {
  const type = String(req.params.type);
  const templates: Record<string, string> = {
    livestock: "name,type,species,count,breed,hatchDate,costPerAnimal\Layers,layers,poultry,500,ISA Brown,2025-01-15,350\nDairy Cows,dairy_cattle,livestock,10,Friesian,2024-06-01,80000",
    production: "date,group,eggs,milk,weight,mortality,feed\n2025-01-15,Layers,450,0,0,2,25\n2025-01-15,Dairy Cows,0,250,0,0,120",
    sales: "date,customer,product,amount,paid,status,phone\n2025-01-15,Grace Wanjiku,Eggs,12000,12000,paid,+254712345678\n2025-01-15,John Kamau,Milk,8500,5000,partial,+254723456789",
    customers: "name,phone,email,address\nGrace Wanjiku,+254712345678,,Nairobi\nJohn Kamau,+254723456789,,Kiambu",
    inventory: "name,category,quantity,unit,cost,reorderLevel,supplier\nNPK Fertilizer,fertilizer,50,kg,450,10,Agro Dealers Ltd\nLayer Mash,animal_feed,20,bags,3500,5,Feed Masters",
    finances: "date,type,category,amount,description\n2025-01-15,expense,animal_feed,15000,Purchase of layer mash\n2025-01-15,income,eggs,25000,Egg sales to Grace",
  };

  const template = templates[type];
  if (!template) return res.status(404).json({ error: "Unknown template type" });

  res.setHeader("Content-Type", "text/csv");
  res.setHeader("Content-Disposition", `attachment; filename="${type}_template.csv"`);
  res.send(template);
});

export default router;
