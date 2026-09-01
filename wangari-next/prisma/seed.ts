import { PrismaClient } from "@prisma/client";
import bcrypt from "bcryptjs";

const prisma = new PrismaClient();

async function main() {
  console.log("Seeding Wangari...");
  const pw = await bcrypt.hash("password123", 10);

  // User
  const user = await prisma.user.create({
    data: { name: "Joseph Kamau", email: "joseph@kamaufarm.co.ke", password: pw, role: "farm_owner" },
  });

  // Farm (with owner)
  const farm = await prisma.farm.create({
    data: { name: "Kamau Poultry Farm", location: "Nakuru", county: "Nakuru", farmType: "poultry", ownerId: user.id },
  });

  // Farm member
  await prisma.farmMember.create({
    data: { userId: user.id, farmId: farm.id, role: "owner" },
  });

  // Flocks
  const f1 = await prisma.flock.create({
    data: { farmId: farm.id, name: "Layer Block A", type: "layers", breed: "ISA Brown", initialCount: 2500, currentCount: 2455, mortality: 45, hatchDate: new Date("2025-06-15"), status: "active", createdBy: user.id },
  });
  const f2 = await prisma.flock.create({
    data: { farmId: farm.id, name: "Broiler Batch 12", type: "broilers", breed: "Cobb 500", initialCount: 3000, currentCount: 2880, mortality: 120, hatchDate: new Date("2025-09-01"), status: "active", createdBy: user.id },
  });

  // Workers
  await prisma.worker.create({ data: { farmId: farm.id, name: "Peter Ochieng", role: "Farm Manager", phone: "+254722100200", dailyWage: 1500, status: "active" } });
  await prisma.worker.create({ data: { farmId: farm.id, name: "Mary Wanjiku", role: "Feed Attendant", phone: "+254733200300", dailyWage: 800, status: "active" } });
  // Production (30 days)
  const today = new Date();
  for (let i = 29; i >= 0; i--) {
    const d = new Date(today); d.setDate(d.getDate() - i);
    const eggs = Math.floor(2200 + Math.random() * 400 - 200);
    const mort = Math.random() > 0.9 ? Math.floor(Math.random() * 3) + 1 : 0;
    const feed = +(350 + Math.random() * 80).toFixed(1);
    await prisma.dailyProduction.create({
      data: { flockId: f1.id, farmId: farm.id, date: d, eggsCollected: eggs, mortality: mort, feedUsed: feed },
    });
  }
  console.log("Production: 30 days");

  // Inventory
  await prisma.inventory.create({ data: { farmId: farm.id, itemName: "Layers Mash", category: "feed", quantity: 120, unit: "bags", unitCost: 3200, reorderLevel: 20 } });
  await prisma.inventory.create({ data: { farmId: farm.id, itemName: "Broiler Starter", category: "feed", quantity: 8, unit: "bags", unitCost: 3500, reorderLevel: 15 } });
  await prisma.inventory.create({ data: { farmId: farm.id, itemName: "Maize Grain", category: "feed", quantity: 500, unit: "kg", unitCost: 55, reorderLevel: 100 } });
  await prisma.inventory.create({ data: { farmId: farm.id, itemName: "Newcastle Vaccine", category: "veterinary", quantity: 3000, unit: "doses", unitCost: 25, reorderLevel: 500 } });
  await prisma.inventory.create({ data: { farmId: farm.id, itemName: "Egg Trays", category: "packaging", quantity: 800, unit: "pieces", unitCost: 35, reorderLevel: 200 } });
  console.log("Inventory: 5 items");
  // Customers
  const c1 = await prisma.customer.create({ data: { farmId: farm.id, name: "Nakuru Fresh Market", phone: "+254700111222", email: "orders@nakurufresh.co.ke" } });
  const c2 = await prisma.customer.create({ data: { farmId: farm.id, name: "Mama Njeri Restaurant", phone: "+254711222333" } });
  const c3 = await prisma.customer.create({ data: { farmId: farm.id, name: "John Odhiambo", phone: "+254722333444" } });

  // Sales (30 days)
  for (let i = 29; i >= 0; i--) {
    const d = new Date(today); d.setDate(d.getDate() - i);
    if (Math.random() > 0.3) {
      const cust = [c1, c2, c3][Math.floor(Math.random() * 3)];
      const trays = Math.floor(10 + Math.random() * 40);
      const amt = trays * 380;
      const isPaid = Math.random() > 0.2;
      await prisma.sale.create({
        data: { farmId: farm.id, customerId: cust.id, saleDate: d, items: JSON.stringify([{name: "Egg Trays", qty: trays}]), totalAmount: amt, paymentStatus: isPaid ? "paid" : "pending", amountPaid: isPaid ? amt : 0, createdBy: user.id },
      });
    }
  }
  console.log("Sales: 30 days");

  // Transactions
  var exps = [
    { cat: "feed", desc: "Layers Mash", amt: 160000 },
    { cat: "labour", desc: "Worker wages", amt: 85000 },
    { cat: "veterinary", desc: "Vaccination", amt: 12500 },
    { cat: "utilities", desc: "Electricity", amt: 4500 },
  ];
  for (let i = 0; i < 30; i++) {
    const d = new Date(today); d.setDate(d.getDate() - i);
    var e = exps[Math.floor(Math.random() * exps.length)];
    await prisma.transaction.create({
      data: { farmId: farm.id, type: "expense", category: e.cat, description: e.desc, amount: Math.floor(e.amt * (0.8 + Math.random() * 0.4)), date: d, paymentMethod: "mpesa" },
    });
    if (Math.random() > 0.3) {
      await prisma.transaction.create({
        data: { farmId: farm.id, type: "income", category: "egg_sales", description: "Egg sales", amount: Math.floor(50000 + Math.random() * 30000), date: d, paymentMethod: "mpesa" },
      });
    }
  }
  console.log("Transactions: 30 days");
  console.log("");
  console.log("Seed complete!");
  console.log("Login: joseph@kamaufarm.co.ke / password123");
}

main()
  .catch((e) => { console.error(e); process.exit(1); })
  .finally(() => prisma.$disconnect());