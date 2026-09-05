export interface VaccineTemplate {
  name: string;
  dayOrWeek: string;
  daysFromHatch: number;
  disease: string;
  method: string;
  notes: string;
}

export const VACCINATION_SCHEDULES: Record<string, VaccineTemplate[]> = {
  layers: [
    { name: "Mareks Vaccine", dayOrWeek: "Day 1", daysFromHatch: 1, disease: "Marek's Disease", method: "Injection (Hatchery)", notes: "Usually given at hatchery on hatch day" },
    { name: "Newcastle + IB (1st Dose)", dayOrWeek: "Day 7", daysFromHatch: 7, disease: "Newcastle & Infectious Bronchitis", method: "Eye Drop / Drinking Water", notes: "Withhold water 2 hours before administration" },
    { name: "Gumboro IBD (1st Dose)", dayOrWeek: "Day 14", daysFromHatch: 14, disease: "Infectious Bursal Disease", method: "Drinking Water", notes: "Use skimmed milk powder as vaccine stabilizer" },
    { name: "Gumboro IBD (2nd Dose)", dayOrWeek: "Day 21", daysFromHatch: 21, disease: "Infectious Bursal Disease Booster", method: "Drinking Water", notes: "Booster for full immunity" },
    { name: "Newcastle + IB Booster", dayOrWeek: "Day 28", daysFromHatch: 28, disease: "Newcastle & IB Booster", method: "Drinking Water", notes: "Repeat Newcastle protection" },
    { name: "Fowl Pox", dayOrWeek: "Week 6 (Day 42)", daysFromHatch: 42, disease: "Fowl Pox", method: "Wing Web Puncture", notes: "Check for 'take' scab 7 days post-vaccination" },
    { name: "Fowl Typhoid", dayOrWeek: "Week 8 (Day 56)", daysFromHatch: 56, disease: "Fowl Typhoid / Salmonella", method: "Intramuscular Injection", notes: "Inject in breast muscle" },
    { name: "Newcastle Oil Killed (3-in-1)", dayOrWeek: "Week 16 (Day 112)", daysFromHatch: 112, disease: "ND + IB + Egg Drop Syndrome", method: "Subcutaneous Injection", notes: "Protects flock during laying period" },
  ],
  broilers: [
    { name: "Mareks Vaccine", dayOrWeek: "Day 1", daysFromHatch: 1, disease: "Marek's Disease", method: "Injection (Hatchery)", notes: "Given at hatchery" },
    { name: "Newcastle + IB", dayOrWeek: "Day 7", daysFromHatch: 7, disease: "Newcastle Disease", method: "Eye Drop / Drinking Water", notes: "First ND protection" },
    { name: "Gumboro IBD", dayOrWeek: "Day 14", daysFromHatch: 14, disease: "Gumboro Disease", method: "Drinking Water", notes: "Critical for broiler immune system" },
    { name: "Newcastle Booster", dayOrWeek: "Day 21", daysFromHatch: 21, disease: "Newcastle Booster", method: "Drinking Water", notes: "Booster prior to finishing period" },
  ],
  cattle: [
    { name: "Foot & Mouth (FMD)", dayOrWeek: "Month 3", daysFromHatch: 90, disease: "Foot & Mouth Disease", method: "Subcutaneous Injection", notes: "Repeat every 6 months" },
    { name: "Anthrax & Blackquarter", dayOrWeek: "Month 4", daysFromHatch: 120, disease: "Anthrax / Blackleg", method: "Subcutaneous Injection", notes: "Annual vaccination" },
    { name: "Lumpy Skin Disease (LSD)", dayOrWeek: "Month 6", daysFromHatch: 180, disease: "Lumpy Skin Virus", method: "Subcutaneous Injection", notes: "Annual vaccination before rainy season" },
    { name: "East Coast Fever (ECF)", dayOrWeek: "Month 8", daysFromHatch: 240, disease: "Theileriosis (Tick-borne)", method: "ITM Vaccination", notes: "Lifetime immunity after ITM" },
  ],
};
