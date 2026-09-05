export interface FeedRecipePreset {
  id: string;
  name: string;
  category: "poultry" | "dairy" | "pigs";
  targetProtein: number;
  ingredients: { name: string; percentage: number; costPerKg: number }[];
  description: string;
}

export const PRESET_FEED_RECIPES: FeedRecipePreset[] = [
  {
    id: "chick-mash",
    name: "Chick Starter Mash (0-8 Weeks)",
    category: "poultry",
    targetProtein: 20,
    ingredients: [
      { name: "Whole Maize", percentage: 45, costPerKg: 45 },
      { name: "Wheat Pollard", percentage: 15, costPerKg: 35 },
      { name: "Soya Bean Meal", percentage: 22, costPerKg: 95 },
      { name: "Fish Meal (Omena)", percentage: 10, costPerKg: 130 },
      { name: "Lime / Calcium", percentage: 5, costPerKg: 20 },
      { name: "Vitamin & Mineral Premix", percentage: 3, costPerKg: 350 },
    ],
    description: "High-protein starter feed for young chicks during the first 8 weeks.",
  },
  {
    id: "layer-mash",
    name: "Layer Mash (High Laying Yield)",
    category: "poultry",
    targetProtein: 16.5,
    ingredients: [
      { name: "Whole Maize / Maize Germ", percentage: 50, costPerKg: 42 },
      { name: "Wheat Bran / Pollard", percentage: 18, costPerKg: 32 },
      { name: "Soya Bean Meal", percentage: 14, costPerKg: 95 },
      { name: "Sunflower Cake", percentage: 8, costPerKg: 55 },
      { name: "Limestone Grit / DCP", percentage: 8, costPerKg: 25 },
      { name: "Layer Premix & Salt", percentage: 2, costPerKg: 300 },
    ],
    description: "Formulated for high egg production and strong eggshell quality.",
  },
  {
    id: "kienyeji-mash",
    name: "Improved Kienyeji Feed Mix",
    category: "poultry",
    targetProtein: 17,
    ingredients: [
      { name: "Cracked Maize", percentage: 52, costPerKg: 40 },
      { name: "Wheat Pollard", percentage: 20, costPerKg: 34 },
      { name: "Cottonseed Cake / Soya", percentage: 15, costPerKg: 75 },
      { name: "Omena Meal", percentage: 6, costPerKg: 130 },
      { name: "Bone Meal / DCP", percentage: 5, costPerKg: 30 },
      { name: "Premix", percentage: 2, costPerKg: 300 },
    ],
    description: "Economical semi-intensive feed recipe for indigenous & dual-purpose breeds.",
  },
  {
    id: "high-yield-dairy-meal",
    name: "High-Yield Dairy Concentrate (18% Protein)",
    category: "dairy",
    targetProtein: 18,
    ingredients: [
      { name: "Maize Germ", percentage: 38, costPerKg: 38 },
      { name: "Wheat Bran", percentage: 25, costPerKg: 30 },
      { name: "Cottonseed Cake", percentage: 18, costPerKg: 70 },
      { name: "Soya Meal", percentage: 10, costPerKg: 95 },
      { name: "Dairy Mineral Salts", percentage: 5, costPerKg: 180 },
      { name: "Molasses / Binder", percentage: 4, costPerKg: 40 },
    ],
    description: "Boosts daily milk production in lactating dairy cows.",
  },
];
