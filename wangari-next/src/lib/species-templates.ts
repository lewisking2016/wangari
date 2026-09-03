/**
 * Species Templates — Complete farmer-focused data for ALL farm animals.
 *
 * When a user selects a species, these templates auto-fill:
 * - Breeds with details (mature weight, production rate, lifespan)
 * - Feed types and daily requirements
 * - Vaccination schedule (auto-scheduled on creation)
 * - Housing and space requirements
 * - Common health issues and vet frequency
 * - Economic data (costs, revenue, break-even)
 * - Labor requirements
 * - Purpose and gender ratio defaults
 */

export interface VaccinationEntry {
  vaccine: string;
  ageLabel: string;
  daysFromStart: number;
  description: string;
  cost: number; // KES per dose
}

export interface SpeciesTemplate {
  id: string;
  name: string;
  category: "poultry" | "livestock" | "aquaculture" | "other";

  breeds: string[];
  breedDetails: Record<
    string,
    {
      matureWeight?: string;
      productionRate?: string;
      ageAtProduction?: string;
      expectedLifespan?: string;
      genderRatio?: string;
    }
  >;

  feedTypes: string[];
  feedPerDay: string;
  waterPerDay: string;
  feedCostEstimate: number; // KES per animal per month

  vaccinationSchedule: VaccinationEntry[];

  housingType: string;
  spacePerAnimal: string;
  housingRequirements: string;

  productionMetric: string;
  productionUnit: string;
  expectedYield: string;
  growthCycleDays: number;

  commonHealthIssues: string[];
  mortalityRate: number; // expected %
  vetVisitFrequency: string;

  defaultPurpose: string; // production, breeding, dual_purpose
  defaultGender: string; // female, male, mixed
  defaultGenderRatio: string;

  costPerAnimal: number; // KES purchase price range
  revenuePerUnit: string;
  breakEvenMonths: number;

  laborPerAnimal: string;
  skillLevel: string;
  insuranceRecommended: boolean;
}

// ─── POULTRY ──────────────────────────────────────────────

const layers: SpeciesTemplate = {
  id: "layers",
  name: "Layers (Egg Production)",
  category: "poultry",
  breeds: [
    "ISA Brown",
    "Lohmann Brown",
    "Kenbro",
    "Kienyeji",
    "Rhode Island Red",
    "Leghorn",
    "Hy-Line",
  ],
  breedDetails: {
    "ISA Brown": {
      matureWeight: "2.0-2.2kg",
      productionRate: "300-320 eggs/year",
      ageAtProduction: "18-20 weeks",
      expectedLifespan: "2-3 years",
      genderRatio: "All female",
    },
    "Lohmann Brown": {
      matureWeight: "2.0-2.2kg",
      productionRate: "280-300 eggs/year",
      ageAtProduction: "18-20 weeks",
      expectedLifespan: "2-3 years",
      genderRatio: "All female",
    },
    Kenbro: {
      matureWeight: "2.5-3.0kg",
      productionRate: "250-280 eggs/year",
      ageAtProduction: "20-22 weeks",
      expectedLifespan: "2-3 years",
      genderRatio: "All female",
    },
    Kienyeji: {
      matureWeight: "2.0-2.5kg",
      productionRate: "150-200 eggs/year",
      ageAtProduction: "24-28 weeks",
      expectedLifespan: "3-5 years",
      genderRatio: "All female",
    },
    "Rhode Island Red": {
      matureWeight: "2.5-3.0kg",
      productionRate: "250-280 eggs/year",
      ageAtProduction: "20-22 weeks",
      expectedLifespan: "2-3 years",
      genderRatio: "All female",
    },
    Leghorn: {
      matureWeight: "1.8-2.0kg",
      productionRate: "280-320 eggs/year",
      ageAtProduction: "18-20 weeks",
      expectedLifespan: "2-3 years",
      genderRatio: "All female",
    },
    "Hy-Line": {
      matureWeight: "1.8-2.0kg",
      productionRate: "300-340 eggs/year",
      ageAtProduction: "18-20 weeks",
      expectedLifespan: "2-3 years",
      genderRatio: "All female",
    },
  },
  feedTypes: [
    "Starter Mash (0-6 weeks)",
    "Grower Mash (6-18 weeks)",
    "Layer Mash (18+ weeks)",
    "Pre-Lay Feed",
  ],
  feedPerDay: "110-120g per bird",
  waterPerDay: "250-500ml per bird",
  feedCostEstimate: 450,
  vaccinationSchedule: [
    { vaccine: "Marek's Disease", ageLabel: "Day 1", daysFromStart: 0, description: "Usually at hatchery", cost: 5 },
    { vaccine: "Newcastle Disease (B1)", ageLabel: "Week 1", daysFromStart: 7, description: "Hitchner B1", cost: 3 },
    { vaccine: "Infectious Bronchitis", ageLabel: "Week 2", daysFromStart: 14, description: "H120 strain", cost: 3 },
    { vaccine: "Newcastle Booster", ageLabel: "Week 3", daysFromStart: 21, description: "La Sota", cost: 3 },
    { vaccine: "Gumboro (IBD)", ageLabel: "Week 4", daysFromStart: 28, description: "Intermediate strain", cost: 4 },
    { vaccine: "Fowl Pox", ageLabel: "Week 8", daysFromStart: 56, description: "Wing web method", cost: 5 },
    { vaccine: "NDV + IBD Booster", ageLabel: "Week 10", daysFromStart: 70, description: "Combined", cost: 5 },
    { vaccine: "Newcastle Lasota", ageLabel: "Week 16", daysFromStart: 112, description: "Pre-lay booster", cost: 3 },
  ],
  housingType: "Deep litter or battery cage",
  spacePerAnimal: "0.1m² per bird (deep litter)",
  housingRequirements: "Well-ventilated coop, nest boxes (1 per 4-5 hens), perch space",
  productionMetric: "Eggs",
  productionUnit: "eggs/day",
  expectedYield: "25-30 eggs/day per 100 hens",
  growthCycleDays: 365,
  commonHealthIssues: ["Newcastle Disease", "Coccidiosis", "Fowl Typhoid", "Marek's Disease", "IB", "Worms"],
  mortalityRate: 5,
  vetVisitFrequency: "Monthly or when issues arise",
  defaultPurpose: "production",
  defaultGender: "female",
  defaultGenderRatio: "All female",
  costPerAnimal: 350,
  revenuePerUnit: "KES 12-15 per egg",
  breakEvenMonths: 6,
  laborPerAnimal: "1 worker per 500-1000 birds",
  skillLevel: "Beginner friendly",
  insuranceRecommended: false,
};

const broilers: SpeciesTemplate = {
  id: "broilers",
  name: "Broilers (Meat Production)",
  category: "poultry",
  breeds: ["Cobb 500", "Ross 308", "Arbor Acres", "Hubbard", "Kuroiler"],
  breedDetails: {
    "Cobb 500": {
      matureWeight: "2.5-3.0kg in 42 days",
      productionRate: "FCR 1.6-1.8",
      ageAtProduction: "6 weeks",
      expectedLifespan: "6-8 weeks",
      genderRatio: "Mixed",
    },
    "Ross 308": {
      matureWeight: "2.5-3.0kg in 42 days",
      productionRate: "FCR 1.6-1.8",
      ageAtProduction: "6 weeks",
      expectedLifespan: "6-8 weeks",
      genderRatio: "Mixed",
    },
    "Arbor Acres": {
      matureWeight: "2.0-2.5kg in 35 days",
      productionRate: "FCR 1.7-1.9",
      ageAtProduction: "5 weeks",
      expectedLifespan: "5-7 weeks",
      genderRatio: "Mixed",
    },
    Hubbard: {
      matureWeight: "2.5-3.5kg in 49 days",
      productionRate: "FCR 1.8-2.0",
      ageAtProduction: "7 weeks",
      expectedLifespan: "7-9 weeks",
      genderRatio: "Mixed",
    },
    Kuroiler: {
      matureWeight: "3.0-4.0kg in 12 weeks",
      productionRate: "FCR 2.5-3.0",
      ageAtProduction: "12 weeks",
      expectedLifespan: "12-16 weeks",
      genderRatio: "Mixed",
    },
  },
  feedTypes: [
    "Starter Crumbs (0-10 days)",
    "Grower Pellets (10-24 days)",
    "Finisher Pellets (24+ days)",
  ],
  feedPerDay: "15-150g per bird (increases weekly)",
  waterPerDay: "300-500ml per bird",
  feedCostEstimate: 600,
  vaccinationSchedule: [
    { vaccine: "Marek's Disease", ageLabel: "Day 1", daysFromStart: 0, description: "At hatchery", cost: 5 },
    { vaccine: "Newcastle Disease (B1)", ageLabel: "Day 1", daysFromStart: 0, description: "Eye drop", cost: 3 },
    { vaccine: "Gumboro (IBD)", ageLabel: "Week 2", daysFromStart: 14, description: "In water", cost: 4 },
    { vaccine: "Newcastle Booster", ageLabel: "Week 3", daysFromStart: 21, description: "La Sota", cost: 3 },
  ],
  housingType: "Deep litter or open-sided",
  spacePerAnimal: "0.08m² per bird",
  housingRequirements: "Good ventilation critical, avoid draft, proper lighting",
  productionMetric: "Live Weight",
  productionUnit: "kg",
  expectedYield: "2.5-3.0kg per bird at 6 weeks",
  growthCycleDays: 42,
  commonHealthIssues: ["Coccidiosis", "Newcastle Disease", "Colibacillosis", "Ascites", "Lameness"],
  mortalityRate: 3,
  vetVisitFrequency: "Weekly checks recommended",
  defaultPurpose: "production",
  defaultGender: "mixed",
  defaultGenderRatio: "Mixed",
  costPerAnimal: 150,
  revenuePerUnit: "KES 350-500 per kg live weight",
  breakEvenMonths: 2,
  laborPerAnimal: "1 worker per 1000-2000 birds",
  skillLevel: "Beginner friendly",
  insuranceRecommended: false,
};

const kienyeji: SpeciesTemplate = {
  id: "kienyeji",
  name: "Kienyeji (Indigenous)",
  category: "poultry",
  breeds: ["Kienyeji", "Sasso", "Kenbro", "Industrial Kienyeji"],
  breedDetails: {
    Kienyeji: {
      matureWeight: "2.0-2.5kg",
      productionRate: "150-200 eggs/year",
      ageAtProduction: "24-28 weeks",
      expectedLifespan: "3-5 years",
      genderRatio: "1:10 (rooster:hens)",
    },
    Sasso: {
      matureWeight: "2.5-3.0kg",
      productionRate: "200-250 eggs/year",
      ageAtProduction: "22-26 weeks",
      expectedLifespan: "3-5 years",
      genderRatio: "1:10",
    },
    Kenbro: {
      matureWeight: "2.5-3.0kg",
      productionRate: "250-280 eggs/year",
      ageAtProduction: "20-22 weeks",
      expectedLifespan: "2-3 years",
      genderRatio: "All female",
    },
    "Industrial Kienyeji": {
      matureWeight: "2.0-2.5kg",
      productionRate: "200-250 eggs/year",
      ageAtProduction: "22-26 weeks",
      expectedLifespan: "3-5 years",
      genderRatio: "1:10",
    },
  },
  feedTypes: ["Starter Mash", "Grower Mash", "Free-range Supplement", "Layers Mash"],
  feedPerDay: "80-100g per bird (supplemental)",
  waterPerDay: "200-400ml per bird",
  feedCostEstimate: 300,
  vaccinationSchedule: [
    { vaccine: "Marek's Disease", ageLabel: "Day 1", daysFromStart: 0, description: "At hatchery", cost: 5 },
    { vaccine: "Newcastle Disease (B1)", ageLabel: "Week 1", daysFromStart: 7, description: "Hitchner B1", cost: 3 },
    { vaccine: "Infectious Bronchitis", ageLabel: "Week 2", daysFromStart: 14, description: "H120", cost: 3 },
    { vaccine: "Newcastle Booster", ageLabel: "Week 4", daysFromStart: 28, description: "La Sota", cost: 3 },
    { vaccine: "Fowl Pox", ageLabel: "Week 8", daysFromStart: 56, description: "Wing web", cost: 5 },
  ],
  housingType: "Free-range with secure coop",
  spacePerAnimal: "0.2m² in coop, open range available",
  housingRequirements: "Secure coop for night, free-range during day, predator protection",
  productionMetric: "Eggs/Meat",
  productionUnit: "eggs/day or kg",
  expectedYield: "1-2 eggs per hen every 2-3 days",
  growthCycleDays: 180,
  commonHealthIssues: ["Newcastle Disease", "Coccidiosis", "Worms", "Mite Infestation"],
  mortalityRate: 8,
  vetVisitFrequency: "Monthly",
  defaultPurpose: "dual_purpose",
  defaultGender: "mixed",
  defaultGenderRatio: "1:10 (rooster:hens)",
  costPerAnimal: 250,
  revenuePerUnit: "KES 15-20 per egg, KES 500-800 per bird meat",
  breakEvenMonths: 8,
  laborPerAnimal: "1 worker per 200-500 birds",
  skillLevel: "Beginner friendly",
  insuranceRecommended: false,
};

// ─── LIVESTOCK ────────────────────────────────────────────

const cattleDairy: SpeciesTemplate = {
  id: "cattle_dairy",
  name: "Dairy Cattle",
  category: "livestock",
  breeds: ["Friesian", "Ayrshire", "Jersey", "Guernsey", "Crossbreed", "Ayam Saleh"],
  breedDetails: {
    Friesian: {
      matureWeight: "500-700kg",
      productionRate: "20-30 liters/day",
      ageAtProduction: "2.5 years",
      expectedLifespan: "10-12 years",
      genderRatio: "1 bull : 25-30 cows",
    },
    Ayrshire: {
      matureWeight: "450-550kg",
      productionRate: "15-25 liters/day",
      ageAtProduction: "2.5 years",
      expectedLifespan: "10-12 years",
      genderRatio: "1 bull : 25-30 cows",
    },
    Jersey: {
      matureWeight: "350-450kg",
      productionRate: "15-20 liters/day",
      ageAtProduction: "2 years",
      expectedLifespan: "10-12 years",
      genderRatio: "1 bull : 25-30 cows",
    },
    Guernsey: {
      matureWeight: "400-500kg",
      productionRate: "15-20 liters/day",
      ageAtProduction: "2.5 years",
      expectedLifespan: "10-12 years",
      genderRatio: "1 bull : 25-30 cows",
    },
    Crossbreed: {
      matureWeight: "400-600kg",
      productionRate: "15-25 liters/day",
      ageAtProduction: "2-2.5 years",
      expectedLifespan: "10-12 years",
      genderRatio: "1 bull : 25-30 cows",
    },
    "Ayam Saleh": {
      matureWeight: "300-400kg",
      productionRate: "8-12 liters/day",
      ageAtProduction: "2 years",
      expectedLifespan: "10-15 years",
      genderRatio: "1 bull : 20-25 cows",
    },
  },
  feedTypes: ["Dairy Meal", "Hay", "Silage", "Mineral Licks", "Cotton Seed Cake", "Maize Silage"],
  feedPerDay: "Dairy meal: 3-6kg, Hay: 5-8kg per cow",
  waterPerDay: "60-100L per cow",
  feedCostEstimate: 15000,
  vaccinationSchedule: [
    { vaccine: "Blackquarter", ageLabel: "3 months", daysFromStart: 90, description: "Annual", cost: 50 },
    { vaccine: "Anthrax", ageLabel: "3 months", daysFromStart: 90, description: "Annual", cost: 50 },
    { vaccine: "Brucellosis (S19)", ageLabel: "6 months", daysFromStart: 180, description: "Heifers only", cost: 100 },
    { vaccine: "FMD", ageLabel: "3 months", daysFromStart: 90, description: "Every 6 months", cost: 200 },
    { vaccine: "Rift Valley Fever", ageLabel: "3 months", daysFromStart: 90, description: "Seasonal", cost: 150 },
    { vaccine: "Haemorrhagic Septicemia", ageLabel: "6 months", daysFromStart: 180, description: "Annual", cost: 80 },
  ],
  housingType: "Breed-specific housing",
  spacePerAnimal: "4-5m² per cow (loose housing)",
  housingRequirements: "Milking parlor, clean water trough, shade, ventilation, manure management",
  productionMetric: "Milk",
  productionUnit: "liters/day",
  expectedYield: "15-30 liters/day per cow",
  growthCycleDays: 365,
  commonHealthIssues: ["Mastitis", "FMD", "Tick-borne Diseases", "Milk Fever", "Bloat", "Worms"],
  mortalityRate: 2,
  vetVisitFrequency: "Monthly for herd health",
  defaultPurpose: "production",
  defaultGender: "mixed",
  defaultGenderRatio: "1 bull : 25-30 cows",
  costPerAnimal: 80000,
  revenuePerUnit: "KES 50-60 per liter milk",
  breakEvenMonths: 24,
  laborPerAnimal: "1 worker per 10-15 cows",
  skillLevel: "Intermediate",
  insuranceRecommended: true,
};

const cattleBeef: SpeciesTemplate = {
  id: "cattle_beef",
  name: "Beef Cattle",
  category: "livestock",
  breeds: ["Hereford", "Angus", "Boran", "Ndoromo", "Zebu", "Crossbreed"],
  breedDetails: {
    Hereford: {
      matureWeight: "700-900kg",
      productionRate: "1.0-1.2kg/day gain",
      ageAtProduction: "18-24 months",
      expectedLifespan: "15-20 years",
      genderRatio: "1 bull : 25-30 cows",
    },
    Angus: {
      matureWeight: "700-900kg",
      productionRate: "1.0-1.3kg/day gain",
      ageAtProduction: "18-24 months",
      expectedLifespan: "15-20 years",
      genderRatio: "1 bull : 25-30 cows",
    },
    Boran: {
      matureWeight: "400-600kg",
      productionRate: "0.5-0.8kg/day gain",
      ageAtProduction: "24-30 months",
      expectedLifespan: "15-20 years",
      genderRatio: "1 bull : 20-25 cows",
    },
    Ndoromo: {
      matureWeight: "350-500kg",
      productionRate: "0.5-0.7kg/day gain",
      ageAtProduction: "24-30 months",
      expectedLifespan: "15-20 years",
      genderRatio: "1 bull : 20-25 cows",
    },
    Zebu: {
      matureWeight: "300-500kg",
      productionRate: "0.4-0.6kg/day gain",
      ageAtProduction: "24-36 months",
      expectedLifespan: "15-20 years",
      genderRatio: "1 bull : 20-25 cows",
    },
    Crossbreed: {
      matureWeight: "400-700kg",
      productionRate: "0.6-1.0kg/day gain",
      ageAtProduction: "18-24 months",
      expectedLifespan: "15-20 years",
      genderRatio: "1 bull : 25-30 cows",
    },
  },
  feedTypes: ["Beef Meal", "Hay", "Silage", "Mineral Licks", "Crop Residues"],
  feedPerDay: "Beef meal: 2-4kg, Hay: 4-6kg per head",
  waterPerDay: "30-50L per head",
  feedCostEstimate: 8000,
  vaccinationSchedule: [
    { vaccine: "Blackquarter", ageLabel: "3 months", daysFromStart: 90, description: "Annual", cost: 50 },
    { vaccine: "Anthrax", ageLabel: "3 months", daysFromStart: 90, description: "Annual", cost: 50 },
    { vaccine: "FMD", ageLabel: "3 months", daysFromStart: 90, description: "Every 6 months", cost: 200 },
    { vaccine: "Haemorrhagic Septicemia", ageLabel: "6 months", daysFromStart: 180, description: "Annual", cost: 80 },
  ],
  housingType: "Open grazing with bomas",
  spacePerAnimal: "4-5m² in boma, 1-2 acres per head grazing",
  housingRequirements: "Shade, secure bomas at night, water points, mineral licks",
  productionMetric: "Live Weight",
  productionUnit: "kg",
  expectedYield: "350-600kg live weight at slaughter",
  growthCycleDays: 730,
  commonHealthIssues: ["Tick-borne Diseases", "FMD", "Worms", "Bloat", "Trypanosomiasis"],
  mortalityRate: 2,
  vetVisitFrequency: "Monthly tick treatment, quarterly checkups",
  defaultPurpose: "production",
  defaultGender: "mixed",
  defaultGenderRatio: "1 bull : 25-30 cows",
  costPerAnimal: 35000,
  revenuePerUnit: "KES 350-450 per kg live weight",
  breakEvenMonths: 36,
  laborPerAnimal: "1 worker per 20-30 head",
  skillLevel: "Intermediate",
  insuranceRecommended: true,
};

const goats: SpeciesTemplate = {
  id: "goats",
  name: "Goats",
  category: "livestock",
  breeds: ["Boer", "Saanen", "Alpine", "Galla", "Small East African", "Crossbreed"],
  breedDetails: {
    Boer: {
      matureWeight: "80-130kg",
      productionRate: "1.5-2.0 kids/breeding",
      ageAtProduction: "8-10 months",
      expectedLifespan: "8-12 years",
      genderRatio: "1 buck : 20-25 does",
    },
    Saanen: {
      matureWeight: "55-80kg",
      productionRate: "2-3 liters/day milk",
      ageAtProduction: "8-10 months",
      expectedLifespan: "10-12 years",
      genderRatio: "1 buck : 20-25 does",
    },
    Alpine: {
      matureWeight: "55-70kg",
      productionRate: "2-3 liters/day milk",
      ageAtProduction: "8-10 months",
      expectedLifespan: "10-12 years",
      genderRatio: "1 buck : 20-25 does",
    },
    Galla: {
      matureWeight: "30-50kg",
      productionRate: "1-1.5 kids/breeding",
      ageAtProduction: "10-12 months",
      expectedLifespan: "8-10 years",
      genderRatio: "1 buck : 15-20 does",
    },
    "Small East African": {
      matureWeight: "25-40kg",
      productionRate: "1-2 kids/breeding",
      ageAtProduction: "10-12 months",
      expectedLifespan: "8-10 years",
      genderRatio: "1 buck : 15-20 does",
    },
    Crossbreed: {
      matureWeight: "40-80kg",
      productionRate: "1-2 kids/breeding",
      ageAtProduction: "8-12 months",
      expectedLifespan: "8-12 years",
      genderRatio: "1 buck : 20 does",
    },
  },
  feedTypes: ["Goat Pellets", "Hay", "Browse (leaves)", "Mineral Licks", "Maize Bran"],
  feedPerDay: "200-400g concentrate + browse",
  waterPerDay: "2-4L per goat",
  feedCostEstimate: 3000,
  vaccinationSchedule: [
    { vaccine: "CCPP", ageLabel: "3 months", daysFromStart: 90, description: "Annual", cost: 30 },
    { vaccine: "PPR", ageLabel: "4 months", daysFromStart: 120, description: "Annual", cost: 40 },
    { vaccine: "Anthrax", ageLabel: "3 months", daysFromStart: 90, description: "Annual", cost: 30 },
    { vaccine: "Deworming", ageLabel: "2 months", daysFromStart: 60, description: "Every 3 months", cost: 20 },
  ],
  housingType: "Raised house with slatted floor",
  spacePerAnimal: "1.5-2m² per goat",
  housingRequirements: "Raised house, slatted floor, good ventilation, browse area",
  productionMetric: "Kids/Milk/Meat",
  productionUnit: "kids or liters/day",
  expectedYield: "1-2 kids per breeding, 1-3L milk/day",
  growthCycleDays: 365,
  commonHealthIssues: ["CCPP", "PPR", "Worms", "Foot Rot", "Caseous Lymphadenitis"],
  mortalityRate: 5,
  vetVisitFrequency: "Monthly, deworming quarterly",
  defaultPurpose: "dual_purpose",
  defaultGender: "mixed",
  defaultGenderRatio: "1 buck : 20-25 does",
  costPerAnimal: 5000,
  revenuePerUnit: "KES 3000-5000 per kid, KES 50-80/L milk",
  breakEvenMonths: 12,
  laborPerAnimal: "1 worker per 30-50 goats",
  skillLevel: "Beginner friendly",
  insuranceRecommended: false,
};

const sheep: SpeciesTemplate = {
  id: "sheep",
  name: "Sheep",
  category: "livestock",
  breeds: ["Dorper", "Merino", "Hampshire", "Red Maasai", "Blackhead Persian", "Crossbreed"],
  breedDetails: {
    Dorper: {
      matureWeight: "60-90kg",
      productionRate: "1.5-2.0 lambs/breeding",
      ageAtProduction: "8-10 months",
      expectedLifespan: "8-10 years",
      genderRatio: "1 ram : 25-30 ewes",
    },
    Merino: {
      matureWeight: "45-80kg",
      productionRate: "1.2-1.5 lambs/breeding",
      ageAtProduction: "10-12 months",
      expectedLifespan: "10-12 years",
      genderRatio: "1 ram : 25-30 ewes",
    },
    Hampshire: {
      matureWeight: "80-110kg",
      productionRate: "1.5-2.0 lambs/breeding",
      ageAtProduction: "8-10 months",
      expectedLifespan: "8-10 years",
      genderRatio: "1 ram : 25-30 ewes",
    },
    "Red Maasai": {
      matureWeight: "35-50kg",
      productionRate: "1.0-1.5 lambs/breeding",
      ageAtProduction: "12-14 months",
      expectedLifespan: "8-10 years",
      genderRatio: "1 ram : 20-25 ewes",
    },
    "Blackhead Persian": {
      matureWeight: "35-55kg",
      productionRate: "1.0-1.5 lambs/breeding",
      ageAtProduction: "10-12 months",
      expectedLifespan: "8-10 years",
      genderRatio: "1 ram : 20-25 ewes",
    },
    Crossbreed: {
      matureWeight: "40-70kg",
      productionRate: "1.2-1.8 lambs/breeding",
      ageAtProduction: "10-12 months",
      expectedLifespan: "8-10 years",
      genderRatio: "1 ram : 25 ewes",
    },
  },
  feedTypes: ["Sheep Pellets", "Hay", "Browse", "Mineral Licks", "Maize Bran"],
  feedPerDay: "200-300g concentrate + hay",
  waterPerDay: "2-4L per sheep",
  feedCostEstimate: 2500,
  vaccinationSchedule: [
    { vaccine: "PPR", ageLabel: "4 months", daysFromStart: 120, description: "Annual", cost: 40 },
    { vaccine: "Anthrax", ageLabel: "3 months", daysFromStart: 90, description: "Annual", cost: 30 },
    { vaccine: "Clostridial (C&D)", ageLabel: "3 months", daysFromStart: 90, description: "Annual", cost: 30 },
    { vaccine: "Deworming", ageLabel: "2 months", daysFromStart: 60, description: "Every 3 months", cost: 20 },
  ],
  housingType: "Shed with grazing paddocks",
  spacePerAnimal: "1.5-2m² per sheep",
  housingRequirements: "Well-drained shelter, shearing area, grazing paddocks",
  productionMetric: "Lambs/Wool/Meat",
  productionUnit: "lambs or kg",
  expectedYield: "1-2 lambs per breeding, 2-4kg wool/year",
  growthCycleDays: 365,
  commonHealthIssues: ["Worms", "Foot Rot", "PPR", "Flystrike", "Wound Infection"],
  mortalityRate: 4,
  vetVisitFrequency: "Monthly, deworming quarterly",
  defaultPurpose: "dual_purpose",
  defaultGender: "mixed",
  defaultGenderRatio: "1 ram : 25 ewes",
  costPerAnimal: 4000,
  revenuePerUnit: "KES 3000-5000 per lamb, KES 800-1500 wool",
  breakEvenMonths: 12,
  laborPerAnimal: "1 worker per 40-60 sheep",
  skillLevel: "Beginner friendly",
  insuranceRecommended: false,
};

const pigs: SpeciesTemplate = {
  id: "pigs",
  name: "Pigs",
  category: "livestock",
  breeds: ["Large White", "Landrace", "Duroc", "Hampshire", "Crossbreed"],
  breedDetails: {
    "Large White": {
      matureWeight: "200-300kg",
      productionRate: "10-14 piglets/sow",
      ageAtProduction: "8-10 months",
      expectedLifespan: "6-8 years",
      genderRatio: "1 boar : 10-12 sows",
    },
    Landrace: {
      matureWeight: "200-280kg",
      productionRate: "10-14 piglets/sow",
      ageAtProduction: "8-10 months",
      expectedLifespan: "6-8 years",
      genderRatio: "1 boar : 10-12 sows",
    },
    Duroc: {
      matureWeight: "200-280kg",
      productionRate: "8-12 piglets/sow",
      ageAtProduction: "8-10 months",
      expectedLifespan: "6-8 years",
      genderRatio: "1 boar : 10-12 sows",
    },
    Hampshire: {
      matureWeight: "200-280kg",
      productionRate: "10-12 piglets/sow",
      ageAtProduction: "8-10 months",
      expectedLifespan: "6-8 years",
      genderRatio: "1 boar : 10-12 sows",
    },
    Crossbreed: {
      matureWeight: "200-300kg",
      productionRate: "10-14 piglets/sow",
      ageAtProduction: "8-10 months",
      expectedLifespan: "6-8 years",
      genderRatio: "1 boar : 10-12 sows",
    },
  },
  feedTypes: ["Starter Feed (0-7kg)", "Grower Feed (7-30kg)", "Finisher Feed (30-90kg)", "Sow & Piglet Feed"],
  feedPerDay: "0.5-3kg per pig (varies by size)",
  waterPerDay: "5-15L per pig",
  feedCostEstimate: 6000,
  vaccinationSchedule: [
    { vaccine: "Erysipelas", ageLabel: "8 weeks", daysFromStart: 56, description: "Annual", cost: 30 },
    { vaccine: "Mycoplasma", ageLabel: "6 weeks", daysFromStart: 42, description: "As needed", cost: 40 },
    { vaccine: "Parvovirus", ageLabel: "Pre-breeding", daysFromStart: 240, description: "Sows only", cost: 50 },
    { vaccine: "FMD", ageLabel: "3 months", daysFromStart: 90, description: "Every 6 months", cost: 200 },
  ],
  housingType: "Concrete-floored pens",
  spacePerAnimal: "1.5-2m² per pig (grower), 4m² per sow",
  housingRequirements: "Concrete floor, drainage, cooling, farrowing crates, feeders",
  productionMetric: "Live Weight",
  productionUnit: "kg",
  expectedYield: "90-110kg at 6 months",
  growthCycleDays: 180,
  commonHealthIssues: ["ASF", "Erysipelas", "Mastitis", "Parvovirus", "Worms"],
  mortalityRate: 3,
  vetVisitFrequency: "Monthly, farrowing assistance as needed",
  defaultPurpose: "production",
  defaultGender: "mixed",
  defaultGenderRatio: "1 boar : 10-12 sows",
  costPerAnimal: 8000,
  revenuePerUnit: "KES 400-500 per kg live weight",
  breakEvenMonths: 8,
  laborPerAnimal: "1 worker per 20-30 pigs",
  skillLevel: "Intermediate",
  insuranceRecommended: false,
};

const rabbits: SpeciesTemplate = {
  id: "rabbits",
  name: "Rabbits",
  category: "livestock",
  breeds: ["New Zealand White", "California", "Chinchilla", "Flemish Giant", "Local"],
  breedDetails: {
    "New Zealand White": {
      matureWeight: "4-5kg",
      productionRate: "6-10 kits/litter",
      ageAtProduction: "5-6 months",
      expectedLifespan: "5-8 years",
      genderRatio: "1 buck : 8-10 does",
    },
    California: {
      matureWeight: "3.5-4.5kg",
      productionRate: "6-8 kits/litter",
      ageAtProduction: "5-6 months",
      expectedLifespan: "5-8 years",
      genderRatio: "1 buck : 8-10 does",
    },
    Chinchilla: {
      matureWeight: "4-5kg",
      productionRate: "6-8 kits/litter",
      ageAtProduction: "5-6 months",
      expectedLifespan: "5-8 years",
      genderRatio: "1 buck : 8-10 does",
    },
    "Flemish Giant": {
      matureWeight: "6-8kg",
      productionRate: "4-8 kits/litter",
      ageAtProduction: "8-10 months",
      expectedLifespan: "5-8 years",
      genderRatio: "1 buck : 5-8 does",
    },
    Local: {
      matureWeight: "2-3kg",
      productionRate: "4-6 kits/litter",
      ageAtProduction: "4-5 months",
      expectedLifespan: "3-5 years",
      genderRatio: "1 buck : 8-10 does",
    },
  },
  feedTypes: ["Rabbit Pellets", "Hay", "Green Vegetables", "Maize Bran"],
  feedPerDay: "50-100g pellets + greens",
  waterPerDay: "300-500ml per rabbit",
  feedCostEstimate: 800,
  vaccinationSchedule: [
    { vaccine: "Myxomatosis", ageLabel: "6 weeks", daysFromStart: 42, description: "Annual", cost: 10 },
    { vaccine: "RHD (VHD)", ageLabel: "6 weeks", daysFromStart: 42, description: "Annual", cost: 10 },
    { vaccine: "Deworming", ageLabel: "2 months", daysFromStart: 60, description: "Every 3 months", cost: 10 },
  ],
  housingType: "Wire cages with solid floor sections",
  spacePerAnimal: "0.5m² per rabbit",
  housingRequirements: "Wire cages, hutch for breeding, shade, protection from predators",
  productionMetric: "Kits/Meat",
  productionUnit: "kits or kg",
  expectedYield: "6-10 kits per litter, 8-12 litters/year",
  growthCycleDays: 90,
  commonHealthIssues: ["Myxomatosis", "RHD", "Pasteurellosis", "Coccidiosis", "Mites"],
  mortalityRate: 5,
  vetVisitFrequency: "Monthly",
  defaultPurpose: "production",
  defaultGender: "mixed",
  defaultGenderRatio: "1 buck : 8-10 does",
  costPerAnimal: 500,
  revenuePerUnit: "KES 300-500 per rabbit",
  breakEvenMonths: 4,
  laborPerAnimal: "1 worker per 50-100 rabbits",
  skillLevel: "Beginner friendly",
  insuranceRecommended: false,
};

// ─── AQUACULTURE ──────────────────────────────────────────

const fish: SpeciesTemplate = {
  id: "fish",
  name: "Fish (Aquaculture)",
  category: "aquaculture",
  breeds: ["Tilapia (Nile)", "Tilapia (Red)", "Catfish", "Trout", "Carp"],
  breedDetails: {
    "Tilapia (Nile)": {
      matureWeight: "0.5-1.5kg",
      productionRate: "2-3g/day growth",
      ageAtProduction: "6-8 months",
      expectedLifespan: "5-6 years",
      genderRatio: "1:1 or mono-sex",
    },
    "Tilapia (Red)": {
      matureWeight: "0.5-1.0kg",
      productionRate: "1.5-2.5g/day growth",
      ageAtProduction: "6-8 months",
      expectedLifespan: "5-6 years",
      genderRatio: "1:1 or mono-sex",
    },
    Catfish: {
      matureWeight: "1-3kg",
      productionRate: "3-5g/day growth",
      ageAtProduction: "6-12 months",
      expectedLifespan: "8-15 years",
      genderRatio: "1:1",
    },
    Trout: {
      matureWeight: "0.5-1.5kg",
      productionRate: "1-2g/day growth",
      ageAtProduction: "12-18 months",
      expectedLifespan: "5-7 years",
      genderRatio: "1:1",
    },
    Carp: {
      matureWeight: "2-5kg",
      productionRate: "2-4g/day growth",
      ageAtProduction: "12-18 months",
      expectedLifespan: "10-20 years",
      genderRatio: "1:1",
    },
  },
  feedTypes: ["Starter Feed (32% protein)", "Grower Feed (28% protein)", "Finisher Feed (25% protein)", "Supplementary Feeds"],
  feedPerDay: "2-5% of body weight",
  waterPerDay: "Continuous flow/recirculation",
  feedCostEstimate: 2000,
  vaccinationSchedule: [
    { vaccine: "VHS Vaccine", ageLabel: "1 month", daysFromStart: 30, description: "If available", cost: 50 },
    { vaccine: "Bacterial Vaccines", ageLabel: "6 weeks", daysFromStart: 42, description: "As needed", cost: 100 },
  ],
  housingType: "Ponds or tanks",
  spacePerAnimal: "5-10m² per 1000 fish (ponds)",
  housingRequirements: "Ponds/tanks, aeration, water quality monitoring, shade nets",
  productionMetric: "Live Weight",
  productionUnit: "kg",
  expectedYield: "0.5-1.5kg per fish in 6-8 months",
  growthCycleDays: 180,
  commonHealthIssues: ["Bacterial Infections", "Parasites", "Fungal Infections", "Poor Water Quality", "Predators"],
  mortalityRate: 10,
  vetVisitFrequency: "Weekly water quality checks",
  defaultPurpose: "production",
  defaultGender: "mixed",
  defaultGenderRatio: "1:1",
  costPerAnimal: 20,
  revenuePerUnit: "KES 300-500 per kg",
  breakEvenMonths: 10,
  laborPerAnimal: "1 worker per 5000-10000 fish",
  skillLevel: "Intermediate",
  insuranceRecommended: false,
};

// ─── APICULTURE ───────────────────────────────────────────

const bees: SpeciesTemplate = {
  id: "bees",
  name: "Bees (Apiculture)",
  category: "other",
  breeds: ["Apis Mellifera (African)", "Italian", "Carniolan", "Buckfast"],
  breedDetails: {
    "Apis Mellifera (African)": {
      matureWeight: "N/A",
      productionRate: "10-20kg honey/hive/year",
      ageAtProduction: "N/A",
      expectedLifespan: "3-5 years (colony)",
      genderRatio: "1 queen : 200-300 drones : 20000-50000 workers",
    },
    Italian: {
      matureWeight: "N/A",
      productionRate: "15-30kg honey/hive/year",
      ageAtProduction: "N/A",
      expectedLifespan: "3-5 years (colony)",
      genderRatio: "1 queen : 100-200 drones : 30000-50000 workers",
    },
    Carniolan: {
      matureWeight: "N/A",
      productionRate: "15-25kg honey/hive/year",
      ageAtProduction: "N/A",
      expectedLifespan: "3-5 years (colony)",
      genderRatio: "1 queen : 100-200 drones : 20000-40000 workers",
    },
    Buckfast: {
      matureWeight: "N/A",
      productionRate: "15-30kg honey/hive/year",
      ageAtProduction: "N/A",
      expectedLifespan: "3-5 years (colony)",
      genderRatio: "1 queen : 100-200 drones : 30000-50000 workers",
    },
  },
  feedTypes: ["Sugar Syrup (1:1)", "Pollen Patties", "Fondant", "Beeswax Supplement"],
  feedPerDay: "Sugar syrup: 500ml per hive/week (when needed)",
  waterPerDay: "Small water source nearby",
  feedCostEstimate: 200,
  vaccinationSchedule: [
    { vaccine: "AFB Treatment", ageLabel: "Preventive", daysFromStart: 0, description: "If needed", cost: 500 },
    { vaccine: "Varroa Treatment", ageLabel: "Monthly", daysFromStart: 30, description: "Oxalic acid/Apivar", cost: 200 },
  ],
  housingType: "Hive boxes (Langstroth or Kenya Top Bar)",
  spacePerAnimal: "2m between hives",
  housingRequirements: "Shaded area, away from chemicals, facing east, 2m off ground",
  productionMetric: "Honey",
  productionUnit: "kg/hive/year",
  expectedYield: "10-30kg honey per hive per year",
  growthCycleDays: 365,
  commonHealthIssues: ["Varroa Mites", "AFB", "Wax Moths", "Small Hive Beetle", "Absconding"],
  mortalityRate: 15,
  vetVisitFrequency: "Weekly hive inspections",
  defaultPurpose: "production",
  defaultGender: "mixed",
  defaultGenderRatio: "1 queen : 200-300 drones : 20000-50000 workers",
  costPerAnimal: 5000,
  revenuePerUnit: "KES 800-1500 per kg honey",
  breakEvenMonths: 12,
  laborPerAnimal: "1 beekeeper per 10-20 hives",
  skillLevel: "Specialized training needed",
  insuranceRecommended: false,
};

// ─── Registry ─────────────────────────────────────────────

export const speciesTemplates: Record<string, SpeciesTemplate> = {
  layers,
  broilers,
  kienyeji,
  cattle_dairy: cattleDairy,
  cattle_beef: cattleBeef,
  goats,
  sheep,
  pigs,
  rabbits,
  fish,
  bees,
};

// ─── Helper Functions ─────────────────────────────────────

export function getSpeciesIconId(speciesId: string): string {
  const cat = speciesTemplates[speciesId]?.category;
  if (cat === "poultry") return "bird";
  if (cat === "livestock") return "beef";
  if (cat === "aquaculture") return "droplets";
  return "flower";
}

export function getSpeciesByCategory(category: string): SpeciesTemplate[] {
  return Object.values(speciesTemplates).filter((s) => s.category === category);
}

export function getAllSpecies(): SpeciesTemplate[] {
  return Object.values(speciesTemplates);
}

export function getSpeciesTemplate(speciesId: string): SpeciesTemplate | undefined {
  return speciesTemplates[speciesId];
}

export function getSpeciesCategories(): Array<{ id: string; label: string; icon: string }> {
  return [
    { id: "poultry", label: "Poultry", icon: "bird" },
    { id: "livestock", label: "Livestock", icon: "beef" },
    { id: "aquaculture", label: "Aquaculture", icon: "droplets" },
    { id: "other", label: "Other", icon: "flower" },
  ];
}
