/**
 * Species Templates
 *
 * Each species has predefined breeds, vaccination schedules,
 * feed types, and default values. When a farmer picks a species,
 * the form auto-fills with the right options.
 */

export interface SpeciesTemplate {
  id: string;
  name: string;
  icon: string;
  emoji: string;
  category: "poultry" | "livestock" | "aquaculture" | "other";
  breeds: string[];
  feedTypes: string[];
  vaccinationSchedule: Array<{
    vaccine: string;
    ageDays: number;
    ageLabel: string;
    description: string;
  }>;
  defaultMortalityRate: number; // expected % per cycle
  growthCycleDays: number; // typical cycle length
  productionMetric: string; // what they produce (eggs, milk, meat, etc.)
  unit: string; // unit of production
  commonHealthIssues: string[];
  housingRequirements: string;
  waterNeeds: string; // liters per day per animal
  feedPerDay: string; // grams per day per animal
}

export const speciesTemplates: Record<string, SpeciesTemplate> = {
  // ─── POULTRY ────────────────────────────────────────────
  layers: {
    id: "layers",
    name: "Layers (Egg Production)",
    icon: "Bird",
    emoji: "🐔",
    category: "poultry",
    breeds: ["ISA Brown", "Lohmann Brown", "Kenbro", "Kienyeji", "Rhode Island Red", "Leghorn", "Hy-Line"],
    feedTypes: ["Starter Mash (0-6 weeks)", "Grower Mash (6-18 weeks)", "Layer Mash (18+ weeks)", "Pre-Lay Feed"],
    vaccinationSchedule: [
      { vaccine: "Marek's Disease", ageDays: 1, ageLabel: "Day 1", description: "Usually given at hatchery" },
      { vaccine: "Newcastle Disease (B1)", ageDays: 7, ageLabel: "Week 1", description: "Hitchner B1 or La Sota" },
      { vaccine: "Infectious Bronchitis (IB)", ageDays: 14, ageLabel: "Week 2", description: "H120 strain" },
      { vaccine: "Newcastle Disease Booster", ageDays: 21, ageLabel: "Week 3", description: "La Sota" },
      { vaccine: "Gumboro (IBD)", ageDays: 28, ageLabel: "Week 4", description: "Intermediate strain" },
      { vaccine: "Fowl Pox", ageDays: 56, ageLabel: "Week 8", description: "Wing web method" },
      { vaccine: "Newcastle + IBD Booster", ageDays: 70, ageLabel: "Week 10", description: "Combined" },
      { vaccine: "Newcastle Lasota (Pre-lay)", ageDays: 112, ageLabel: "Week 16", description: "Before laying starts" },
    ],
    defaultMortalityRate: 5,
    growthCycleDays: 365,
    productionMetric: "Eggs",
    unit: "eggs/day",
    commonHealthIssues: ["Newcastle Disease", "Coccidiosis", "Fowl Typhoid", "Marek's Disease", "Infectious Bronchitis", "Worms"],
    housingRequirements: "Well-ventilated coop, 1 bird per 0.1m² floor space, nest boxes (1 per 4-5 hens)",
    waterNeeds: "0.25-0.5L per bird per day",
    feedPerDay: "110-120g per bird per day",
  },

  broilers: {
    id: "broilers",
    name: "Broilers (Meat Production)",
    icon: "Bird",
    emoji: "🍗",
    category: "poultry",
    breeds: ["Cobb 500", "Ross 308", "Arbor Acres", "Hubbard", "Kuroiler"],
    feedTypes: ["Starter Crumbs (0-10 days)", "Grower Pellets (10-24 days)", "Finisher Pellets (24-35 days)", "Broiler Finisher"],
    vaccinationSchedule: [
      { vaccine: "Marek's Disease", ageDays: 1, ageLabel: "Day 1", description: "At hatchery" },
      { vaccine: "Newcastle Disease (B1)", ageDays: 1, ageLabel: "Day 1", description: "Eye drop" },
      { vaccine: "Gumboro (IBD)", ageDays: 14, ageLabel: "Week 2", description: "In drinking water" },
      { vaccine: "Newcastle Disease Booster", ageDays: 21, ageLabel: "Week 3", description: "La Sota" },
    ],
    defaultMortalityRate: 3,
    growthCycleDays: 42,
    productionMetric: "Weight",
    unit: "kg",
    commonHealthIssues: ["Coccidiosis", "Newcastle Disease", "Colibacillosis", "Ascites", "Broiler Lameness"],
    housingRequirements: "Open-sided or deep litter, 1 bird per 0.08m², good ventilation critical",
    waterNeeds: "0.3-0.5L per bird per day",
    feedPerDay: "Start: 15g → Finish: 150g per bird per day",
  },

  kienyeji: {
    id: "kienyeji",
    name: "Kienyeji (Indigenous)",
    icon: "Bird",
    emoji: "🐓",
    category: "poultry",
    breeds: ["Kienyeji", "Sasso", "Kenbro", "Industrial Kienyeji"],
    feedTypes: ["Starter Mash", "Grower Mash", "Free-range Supplement", "Layers Mash"],
    vaccinationSchedule: [
      { vaccine: "Marek's Disease", ageDays: 1, ageLabel: "Day 1", description: "At hatchery" },
      { vaccine: "Newcastle Disease (B1)", ageDays: 7, ageLabel: "Week 1", description: "Hitchner B1" },
      { vaccine: "Infectious Bronchitis", ageDays: 14, ageLabel: "Week 2", description: "H120" },
      { vaccine: "Newcastle Booster", ageDays: 28, ageLabel: "Week 4", description: "La Sota" },
      { vaccine: "Fowl Pox", ageDays: 56, ageLabel: "Week 8", description: "Wing web" },
    ],
    defaultMortalityRate: 8,
    growthCycleDays: 180,
    productionMetric: "Eggs/Meat",
    unit: "eggs/day or kg",
    commonHealthIssues: ["Newcastle Disease", "Coccidiosis", "Worms", "Mite Infestation"],
    housingRequirements: "Free-range with secure coop at night, 1 bird per 0.2m² in coop",
    waterNeeds: "0.2-0.4L per bird per day",
    feedPerDay: "80-100g per bird per day (supplemental)",
  },

  // ─── LIVESTOCK ──────────────────────────────────────────
  cattle_dairy: {
    id: "cattle_dairy",
    name: "Dairy Cattle",
    icon: "Beef",
    emoji: "🐄",
    category: "livestock",
    breeds: ["Friesian", "Ayrshire", "Jersey", "Guernsey", "Crossbreed", "Ayam Saleh"],
    feedTypes: ["Dairy Meal", "Hay", "Silage", "Mineral Licks", "Cotton Seed Cake", "Maize Silage"],
    vaccinationSchedule: [
      { vaccine: "Blackquarter", ageDays: 90, ageLabel: "3 months", description: "Annual" },
      { vaccine: "Anthrax", ageDays: 90, ageLabel: "3 months", description: "Annual" },
      { vaccine: "Brucellosis (S19)", ageDays: 180, ageLabel: "6 months", description: "Heifers only" },
      { vaccine: "FMD (Foot & Mouth)", ageDays: 90, ageLabel: "3 months", description: "Every 6 months" },
      { vaccine: "Rift Valley Fever", ageDays: 90, ageLabel: "3 months", description: "Seasonal" },
      { vaccine: "Haemorrhagic Septicemia", ageDays: 180, ageLabel: "6 months", description: "Annual" },
    ],
    defaultMortalityRate: 2,
    growthCycleDays: 365,
    productionMetric: "Milk",
    unit: "liters/day",
    commonHealthIssues: ["Mastitis", "Foot & Mouth Disease", "Tick-borne Diseases", "Milk Fever", "Bloat", "Worms"],
    housingRequirements: "Breed-specific housing, milking parlor, clean water trough, shade",
    waterNeeds: "60-100L per cow per day",
    feedPerDay: "Dairy meal: 3-6kg, Hay: 5-8kg per cow per day",
  },

  cattle_beef: {
    id: "cattle_beef",
    name: "Beef Cattle",
    icon: "Beef",
    emoji: "🐂",
    category: "livestock",
    breeds: ["Hereford", "Angus", "Boran", "Ndoromo", "Zebu", "Crossbreed"],
    feedTypes: ["Beef Meal", "Hay", "Silage", "Mineral Licks", "Crop Residues"],
    vaccinationSchedule: [
      { vaccine: "Blackquarter", ageDays: 90, ageLabel: "3 months", description: "Annual" },
      { vaccine: "Anthrax", ageDays: 90, ageLabel: "3 months", description: "Annual" },
      { vaccine: "FMD", ageDays: 90, ageLabel: "3 months", description: "Every 6 months" },
      { vaccine: "Haemorrhagic Septicemia", ageDays: 180, ageLabel: "6 months", description: "Annual" },
    ],
    defaultMortalityRate: 2,
    growthCycleDays: 730,
    productionMetric: "Weight",
    unit: "kg",
    commonHealthIssues: ["Tick-borne Diseases", "Foot & Mouth", "Worms", "Bloat", "Trypanosomiasis"],
    housingRequirements: "Open grazing with shade, secure bomas at night, water points",
    waterNeeds: "30-50L per head per day",
    feedPerDay: "Beef meal: 2-4kg, Hay: 4-6kg per head per day",
  },

  goats: {
    id: "goats",
    name: "Goats",
    icon: "Egg",
    emoji: "🐐",
    category: "livestock",
    breeds: ["Boer", "Saanen", "Alpine", "Galla", "Small East African", "Crossbreed"],
    feedTypes: ["Goat Pellets", "Hay", "Browse (leaves)", "Mineral Licks", "Maize Bran"],
    vaccinationSchedule: [
      { vaccine: "CCPP (Contagious Pleuropneumonia)", ageDays: 90, ageLabel: "3 months", description: "Annual" },
      { vaccine: "Peste des Petits Ruminants (PPR)", ageDays: 120, ageLabel: "4 months", description: "Annual" },
      { vaccine: "Anthax", ageDays: 90, ageLabel: "3 months", description: "Annual" },
      { vaccine: "Deworming", ageDays: 60, ageLabel: "2 months", description: "Every 3 months" },
    ],
    defaultMortalityRate: 5,
    growthCycleDays: 365,
    productionMetric: "Milk/Meat/Kids",
    unit: "liters/day or kg",
    commonHealthIssues: ["CCPP", "PPR", "Deworming", "Foot Rot", "Caseous Lymphadenitis"],
    housingRequirements: "Raised house with slatted floor, good ventilation, browse area",
    waterNeeds: "2-4L per goat per day",
    feedPerDay: "200-400g concentrate + browse per day",
  },

  sheep: {
    id: "sheep",
    name: "Sheep",
    icon: "Egg",
    emoji: "🐑",
    category: "livestock",
    breeds: ["Dorper", "Merino", "Hampshire", "Red Maasai", "Blackhead Persian", "Crossbreed"],
    feedTypes: ["Sheep Pellets", "Hay", "Browse", "Mineral Licks", "Maize Bran"],
    vaccinationSchedule: [
      { vaccine: "PPR", ageDays: 120, ageLabel: "4 months", description: "Annual" },
      { vaccine: "Anthrax", ageDays: 90, ageLabel: "3 months", description: "Annual" },
      { vaccine: "Clostridial (C&D)", ageDays: 90, ageLabel: "3 months", description: "Annual" },
      { vaccine: "Deworming", ageDays: 60, ageLabel: "2 months", description: "Every 3 months" },
    ],
    defaultMortalityRate: 4,
    growthCycleDays: 365,
    productionMetric: "Wool/Meat/Lambs",
    unit: "kg or fleece",
    commonHealthIssues: ["Deworming", "Foot Rot", "PPR", "Flystrike", "Wound Infection"],
    housingRequirements: "Well-drained shelter, shearing area, grazing paddocks",
    waterNeeds: "2-4L per sheep per day",
    feedPerDay: "200-300g concentrate + hay per day",
  },

  pigs: {
    id: "pigs",
    name: "Pigs",
    icon: "Beef",
    emoji: "🐷",
    category: "livestock",
    breeds: ["Large White", "Landrace", "Duroc", "Hampshire", "Crossbreed"],
    feedTypes: ["Starter Feed (0-7kg)", "Grower Feed (7-30kg)", "Finisher Feed (30-90kg)", "Sow & Piglet Feed"],
    vaccinationSchedule: [
      { vaccine: "Erysipelas", ageDays: 56, ageLabel: "8 weeks", description: "Annual" },
      { vaccine: "Mycoplasma", ageDays: 42, ageLabel: "6 weeks", description: "As needed" },
      { vaccine: "Parvovirus", ageDays: 140, ageLabel: "Pre-breeding", description: "Sows only" },
      { vaccine: "Foot & Mouth", ageDays: 90, ageLabel: "3 months", description: "Every 6 months" },
    ],
    defaultMortalityRate: 3,
    growthCycleDays: 180,
    productionMetric: "Weight",
    unit: "kg",
    commonHealthIssues: ["African Swine Fever", "Erysipelas", "Mastitis", "Parvovirus", "Worms"],
    housingRequirements: "Concrete-floored pens, drainage, cooling system, farrowing crates",
    waterNeeds: "5-15L per pig per day (varies by size)",
    feedPerDay: "Starter: 0.5kg → Finisher: 3kg per pig per day",
  },

  rabbits: {
    id: "rabbits",
    name: "Rabbits",
    icon: "Egg",
    emoji: "🐇",
    category: "livestock",
    breeds: ["New Zealand White", "California", "Chinchilla", "Flemish Giant", "Local"],
    feedTypes: ["Rabbit Pellets", "Hay", "Green Vegetables", "Maize Bran"],
    vaccinationSchedule: [
      { vaccine: "Myxomatosis", ageDays: 42, ageLabel: "6 weeks", description: "Annual" },
      { vaccine: "RHD (VHD)", ageDays: 42, ageLabel: "6 weeks", description: "Annual" },
      { vaccine: "Deworming", ageDays: 60, ageLabel: "2 months", description: "Every 3 months" },
    ],
    defaultMortalityRate: 5,
    growthCycleDays: 90,
    productionMetric: "Kits/Weight",
    unit: "kits or kg",
    commonHealthIssues: ["Myxomatosis", "RHD", "Pasteurellosis", "Coccidiosis", "Mites"],
    housingRequirements: "Wire cages with solid floor sections, hutch for breeding, shade",
    waterNeeds: "0.3-0.5L per rabbit per day",
    feedPerDay: "50-100g pellets + greens per rabbit per day",
  },

  // ─── AQUACULTURE ────────────────────────────────────────
  fish: {
    id: "fish",
    name: "Fish (Aquaculture)",
    icon: "Droplets",
    emoji: "🐟",
    category: "aquaculture",
    breeds: ["Tilapia (Nile)", "Tilapia (Red)", "Catfish", "Trout", "Carp"],
    feedTypes: ["Starter Feed (32% protein)", "Grower Feed (28% protein)", "Finisher Feed (25% protein)", "Supplementary Feeds"],
    vaccinationSchedule: [
      { vaccine: "Viral Haemorrhagic Septicemia", ageDays: 30, ageLabel: "1 month", description: "If available" },
      { vaccine: "Bacterial Vaccines", ageDays: 45, ageLabel: "6 weeks", description: "As needed" },
    ],
    defaultMortalityRate: 10,
    growthCycleDays: 180,
    productionMetric: "Weight",
    unit: "kg",
    commonHealthIssues: ["Bacterial Infections", "Parasites", "Fungal Infections", "Poor Water Quality", "Predators"],
    housingRequirements: "Ponds or tanks, proper aeration, water quality monitoring, shade nets",
    waterNeeds: "Continuous flow/recirculation, pH 6.5-8.5",
    feedPerDay: "2-5% of body weight per day",
  },

  // ─── APICULTURE ─────────────────────────────────────────
  bees: {
    id: "bees",
    name: "Bees (Apiculture)",
    icon: "Flower",
    emoji: "🐝",
    category: "other",
    breeds: ["Apis Mellifera (African)", "Italian", "Carniolan", "Buckfast"],
    feedTypes: ["Sugar Syrup (1:1)", "Pollen Patties", "Fondant", "Beeswax Supplement"],
    vaccinationSchedule: [
      { vaccine: "American Foulbrood (Antibiotics)", ageDays: 0, ageLabel: "Preventive", description: "If needed" },
      { vaccine: "Varroa Mite Treatment", ageDays: 0, ageLabel: "Monthly check", description: "Oxalic acid or Apivar" },
    ],
    defaultMortalityRate: 15,
    growthCycleDays: 365,
    productionMetric: "Honey",
    unit: "kg/hive/year",
    commonHealthIssues: ["Varroa Mites", "American Foulbrood", "Wax Moths", "Small Hive Beetle", "Absconding"],
    housingRequirements: "Hive boxes in shaded area, away from chemicals, facing east, 2m off ground",
    waterNeeds: "Small water source nearby for bees",
    feedPerDay: "Sugar syrup: 500ml per hive per week (when needed)",
  },
};

// ─── Icon Mapping ────────────────────────────────────────
import { Bird, Beef, Egg, Droplets, Flower } from "lucide-react";

const speciesIconMap: Record<string, any> = {
  layers: Egg,
  broilers: Bird,
  kienyeji: Bird,
  cattle_dairy: Beef,
  cattle_beef: Beef,
  goats: Beef,
  sheep: Beef,
  pigs: Beef,
  rabbits: Egg,
  fish: Droplets,
  bees: Flower,
};

export function getSpeciesIcon(speciesId: string): any {
  return speciesIconMap[speciesId] || Bird;
}

// ─── Helper Functions ─────────────────────────────────────

export function getSpeciesByCategory(category: string): SpeciesTemplate[] {
  return Object.values(speciesTemplates).filter((s) => s.category === category);
}

export function getAllSpecies(): SpeciesTemplate[] {
  return Object.values(speciesTemplates);
}

export function getSpeciesTemplate(speciesId: string): SpeciesTemplate | undefined {
  return speciesTemplates[speciesId];
}

export function getSpeciesCategories(): Array<{ id: string; label: string; emoji: string }> {
  return [
    { id: "poultry", label: "Poultry", emoji: "🐔" },
    { id: "livestock", label: "Livestock", emoji: "🐄" },
    { id: "aquaculture", label: "Aquaculture", emoji: "🐟" },
    { id: "other", label: "Other", emoji: "🐝" },
  ];
}
