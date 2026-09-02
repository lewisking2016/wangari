import { PrismaClient } from "@prisma/client";
import bcrypt from "bcryptjs";

const prisma = new PrismaClient();

async function main() {
  console.log("🌱 Seeding database...\n");

  // Create test user
  const hashedPassword = await bcrypt.hash("password123", 12);

  const user = await prisma.user.upsert({
    where: { email: "test@wangari.com" },
    update: {},
    create: {
      name: "Test Farmer",
      email: "test@wangari.com",
      password: hashedPassword,
      role: "farm_owner",
    },
  });

  console.log(`✅ User: ${user.email} / password123`);

  // Create farm
  const farm = await prisma.farm.upsert({
    where: { id: 1 },
    update: {},
    create: {
      name: "Test Poultry Farm",
      ownerId: user.id,
      location: "Nairobi, Kenya",
      county: "Nairobi",
      farmType: "poultry",
    },
  });

  console.log(`✅ Farm: ${farm.name}`);

  // Add user as farm member
  await prisma.farmMember.upsert({
    where: { userId_farmId: { userId: user.id, farmId: farm.id } },
    update: {},
    create: {
      userId: user.id,
      farmId: farm.id,
      role: "farm_owner",
    },
  });

  // Create flocks
  const flocks = await Promise.all([
    prisma.flock.create({
      data: {
        farmId: farm.id,
        name: "Layer Flock A",
        breed: "ISA Brown",
        type: "layers",
        initialCount: 500,
        currentCount: 485,
        mortality: 15,
        hatchDate: new Date("2026-06-01"),
        createdBy: user.id,
      },
    }),
    prisma.flock.create({
      data: {
        farmId: farm.id,
        name: "Broiler Batch 1",
        breed: "Cobb 500",
        type: "broilers",
        initialCount: 300,
        currentCount: 290,
        mortality: 10,
        hatchDate: new Date("2026-07-15"),
        createdBy: user.id,
      },
    }),
  ]);

  console.log(`✅ Flocks: ${flocks.length} created`);

  // Create production records (last 14 days)
  const today = new Date();
  for (let i = 13; i >= 0; i--) {
    const date = new Date(today);
    date.setDate(date.getDate() - i);

    await prisma.dailyProduction.create({
      data: {
        flockId: flocks[0].id,
        farmId: farm.id,
        date,
        eggsCollected: 380 + Math.floor(Math.random() * 40),
        mortality: Math.floor(Math.random() * 3),
        feedUsed: 25 + Math.random() * 5,
      },
    });
  }

  console.log("✅ Production records: 14 days created");

  // Create transactions
  const transactions = [
    { type: "income", category: "eggs", amount: 45000, description: "Egg sales - week 1" },
    { type: "income", category: "eggs", amount: 52000, description: "Egg sales - week 2" },
    { type: "expense", category: "feed", amount: 35000, description: "Layer mash purchase" },
    { type: "expense", category: "medication", amount: 3500, description: "Vaccines" },
    { type: "expense", category: "labor", amount: 8000, description: "Worker wages" },
    { type: "income", category: "birds", amount: 15000, description: "Broiler sales" },
    { type: "expense", category: "infrastructure", amount: 5000, description: "Feeder repairs" },
  ];

  for (const tx of transactions) {
    await prisma.transaction.create({
      data: {
        farmId: farm.id,
        type: tx.type,
        category: tx.category,
        amount: tx.amount,
        description: tx.description,
        date: new Date(today.getTime() - Math.random() * 14 * 86400000),
        paymentMethod: "cash",
        createdBy: user.id,
      },
    });
  }

  console.log(`✅ Transactions: ${transactions.length} created`);

  // Create customers
  const customers = await Promise.all([
    prisma.customer.create({
      data: { farmId: farm.id, name: "Grace Wanjiku", phone: "+254712345678", email: "grace@email.com" },
    }),
    prisma.customer.create({
      data: { farmId: farm.id, name: "Peter Ochieng", phone: "+254723456789" },
    }),
    prisma.customer.create({
      data: { farmId: farm.id, name: "Mary Akinyi", phone: "+254734567890", email: "mary@email.com" },
    }),
  ]);

  console.log(`✅ Customers: ${customers.length} created`);

  // Create sales
  await prisma.sale.create({
    data: {
      farmId: farm.id,
      customerId: customers[0].id,
      items: [{ name: "Eggs", quantity: 10, price: 450 }],
      totalAmount: 4500,
      amountPaid: 4500,
      paymentStatus: "paid",
      createdBy: user.id,
    },
  });

  console.log("✅ Sales: 1 created");

  // Create workers
  const workers = await Promise.all([
    prisma.worker.create({
      data: { farmId: farm.id, name: "James Kiprop", role: "Farm Manager", phone: "+254745678901", dailyWage: 1500, createdBy: user.id },
    }),
    prisma.worker.create({
      data: { farmId: farm.id, name: "Faith Njeri", role: "Field Worker", phone: "+254756789012", dailyWage: 800, createdBy: user.id },
    }),
  ]);

  console.log(`✅ Workers: ${workers.length} created`);

  // Create inventory
  await Promise.all([
    prisma.inventory.create({
      data: { farmId: farm.id, itemName: "Layer Mash", category: "feed", quantity: 45, unit: "bags", unitCost: 5000, reorderLevel: 10 },
    }),
    prisma.inventory.create({
      data: { farmId: farm.id, itemName: "Broiler Finisher", category: "feed", quantity: 20, unit: "bags", unitCost: 4500, reorderLevel: 5 },
    }),
    prisma.inventory.create({
      data: { farmId: farm.id, itemName: "Newcastle Vaccine", category: "vaccine", quantity: 50, unit: "doses", unitCost: 80, reorderLevel: 20 },
    }),
  ]);

  console.log("✅ Inventory: 3 items created");

  console.log("\n🎉 Seed complete!");
  console.log("\n📋 Login credentials:");
  console.log("   Email:    test@wangari.com");
  console.log("   Password: password123");
}

main()
  .catch((e) => {
    console.error(e);
    process.exit(1);
  })
  .finally(async () => {
    await prisma.$disconnect();
  });
