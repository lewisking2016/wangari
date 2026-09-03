"use client";

import * as React from "react";
import { motion, AnimatePresence } from "framer-motion";
import {
  X,
  ChevronRight,
  Check,
  Bird,
  Beef,
  Droplets,
  Flower,
  Calendar,
  Syringe,
  MapPin,
  DollarSign,
  Package,
  Heart,
  Shield,
  Target,
  Phone,
  FileText,
  User,
  Plus,
  ChevronDown,
  ChevronUp,
} from "lucide-react";
import { cn } from "@/lib/utils";
import {
  getSpeciesByCategory,
  getSpeciesCategories,
  type SpeciesTemplate,
} from "@/lib/species-templates";

const iconMap: Record<string, any> = {
  bird: Bird,
  beef: Beef,
  droplets: Droplets,
  flower: Flower,
};

function SpeciesIcon({ speciesId }: { speciesId: string }) {
  const Icon = Bird; // default
  return <Icon className="h-5 w-5" />;
}

function CategoryIcon({ iconId }: { iconId: string }) {
  const Icon = iconMap[iconId] || Bird;
  return <Icon className="h-4 w-4" />;
}

interface CreateFlockFormProps {
  onSubmit: (data: any) => Promise<void>;
  onCancel: () => void;
}

type Step = "species" | "details" | "vaccinations" | "review";
type FormSection = "basic" | "location" | "supply" | "feed" | "vet" | "target" | "insurance";

const allSections: FormSection[] = ["basic", "location", "supply", "feed", "vet", "target", "insurance"];

const sectionLabels: Record<FormSection, { label: string; icon: any }> = {
  basic: { label: "Basic Info", icon: User },
  location: { label: "Location & Housing", icon: MapPin },
  supply: { label: "Source & Cost", icon: Package },
  feed: { label: "Feed Plan", icon: FileText },
  vet: { label: "Veterinarian & Health", icon: Heart },
  target: { label: "Production Target", icon: Target },
  insurance: { label: "Insurance & Notes", icon: Shield },
};

export function CreateFlockForm({ onSubmit, onCancel }: CreateFlockFormProps) {
  const [step, setStep] = React.useState<Step>("species");
  const [selectedSpecies, setSelectedSpecies] = React.useState<SpeciesTemplate | null>(null);
  const [selectedCategory, setSelectedCategory] = React.useState<string>("poultry");
  const [loading, setLoading] = React.useState(false);
  const [expandedSections, setExpandedSections] = React.useState<Set<FormSection>>(
    new Set(["basic"])
  );

  // Comprehensive form fields
  const [form, setForm] = React.useState({
    // Basic
    name: "",
    breed: "",
    initialCount: "",
    hatchDate: new Date().toISOString().split("T")[0],
    purpose: "production",
    gender: "mixed",
    genderRatio: "",

    // Location
    location: "",

    // Supply
    source: "",
    supplierContact: "",
    costPerAnimal: "",
    targetMarket: "",

    // Feed
    feedType: "",
    feedSupplier: "",
    feedCostPerMonth: "",

    // Vet
    vetName: "",
    vetPhone: "",
    healthOnArrival: "",

    // Target
    expectedYield: "",
    expectedRevenue: "",
    expectedWeight: "",

    // Insurance
    insurancePolicy: "",
    notes: "",

    // Vaccination
    autoScheduleVaccines: true,
    customVaccines: [] as Array<{ vaccine: string; ageLabel: string; daysFromStart: number; description: string }>,
  });

  const categories = getSpeciesCategories();
  const speciesInCategory = getSpeciesByCategory(selectedCategory);

  // Auto-fill when species is selected
  const handleSpeciesSelect = (species: SpeciesTemplate) => {
    setSelectedSpecies(species);
    setForm((prev) => ({
      ...prev,
      breed: species.breeds[0] || "",
      purpose: species.defaultPurpose,
      gender: species.defaultGender,
      genderRatio: species.defaultGenderRatio,
      feedType: species.feedTypes[0] || "",
      costPerAnimal: species.costPerAnimal.toString(),
      expectedYield: species.expectedYield,
      expectedRevenue: species.revenuePerUnit,
    }));
    setStep("details");
  };

  // Auto-fill when breed changes
  const handleBreedChange = (breed: string) => {
    setForm((prev) => ({ ...prev, breed }));
    if (selectedSpecies?.breedDetails[breed]) {
      const details = selectedSpecies.breedDetails[breed];
      setForm((prev) => ({
        ...prev,
        breed,
        genderRatio: details.genderRatio || prev.genderRatio,
        expectedWeight: details.matureWeight || prev.expectedWeight,
        expectedYield: details.productionRate || prev.expectedYield,
      }));
    }
  };

  // Auto-fill total investment when count or cost changes
  const totalInvestment = React.useMemo(() => {
    const count = Number(form.initialCount) || 0;
    const cost = Number(form.costPerAnimal) || 0;
    return count * cost;
  }, [form.initialCount, form.costPerAnimal]);

  // Auto-fill estimated feed cost
  const estimatedFeedCost = React.useMemo(() => {
    if (!selectedSpecies || !form.initialCount) return 0;
    const count = Number(form.initialCount) || 0;
    return selectedSpecies.feedCostEstimate * count;
  }, [selectedSpecies, form.initialCount]);

  // Auto-fill estimated vaccination cost
  const estimatedVaccinationCost = React.useMemo(() => {
    if (!selectedSpecies || !form.initialCount) return 0;
    const count = Number(form.initialCount) || 0;
    return selectedSpecies.vaccinationSchedule.reduce((sum, v) => sum + v.cost, 0) * count;
  }, [selectedSpecies, form.initialCount]);

  // Auto-fill break-even estimate
  const breakEvenInfo = React.useMemo(() => {
    if (!selectedSpecies) return null;
    return {
      months: selectedSpecies.breakEvenMonths,
      labor: selectedSpecies.laborPerAnimal,
      skill: selectedSpecies.skillLevel,
      insurance: selectedSpecies.insuranceRecommended,
    };
  }, [selectedSpecies]);

  const toggleSection = (section: FormSection) => {
    setExpandedSections((prev) => {
      const next = new Set(prev);
      if (next.has(section)) {
        next.delete(section);
      } else {
        next.add(section);
      }
      return next;
    });
  };

  const handleSubmit = async () => {
    if (!selectedSpecies) return;
    setLoading(true);
    try {
      await onSubmit({
        name: form.name,
        breed: form.breed,
        type: selectedSpecies.id,
        category: selectedSpecies.category,
        initialCount: Number(form.initialCount),
        hatchDate: form.hatchDate,

        // Extended
        purpose: form.purpose,
        gender: form.gender,
        genderRatio: form.genderRatio,
        location: form.location,
        source: form.source,
        supplierContact: form.supplierContact,
        costPerAnimal: Number(form.costPerAnimal) || null,
        totalInvestment: totalInvestment || null,
        targetMarket: form.targetMarket,
        feedType: form.feedType,
        feedSupplier: form.feedSupplier,
        feedCostPerMonth: Number(form.feedCostPerMonth) || estimatedFeedCost || null,
        vetName: form.vetName,
        vetPhone: form.vetPhone,
        healthOnArrival: form.healthOnArrival,
        insurancePolicy: form.insurancePolicy,
        expectedYield: form.expectedYield,
        expectedRevenue: Number(form.expectedRevenue) || null,
        expectedWeight: form.expectedWeight,
        notes: form.notes,

        vaccinationSchedule: form.autoScheduleVaccines
          ? selectedSpecies.vaccinationSchedule
          : form.customVaccines,
      });
    } finally {
      setLoading(false);
    }
  };

  const isStepValid = () => {
    if (step === "species") return !!selectedSpecies;
    if (step === "details") return !!form.name && !!form.initialCount;
    return true;
  };

  const updateForm = (key: string, value: string) => {
    setForm((prev) => ({ ...prev, [key]: value }));
  };

  return (
    <motion.div
      initial={{ opacity: 0 }}
      animate={{ opacity: 1 }}
      exit={{ opacity: 0 }}
      className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
      onClick={onCancel}
    >
      <motion.div
        initial={{ opacity: 0, scale: 0.95, y: 20 }}
        animate={{ opacity: 1, scale: 1, y: 0 }}
        exit={{ opacity: 0, scale: 0.95, y: 20 }}
        className="bg-white rounded-2xl shadow-2xl w-full max-w-3xl mx-4 max-h-[92vh] overflow-hidden flex flex-col"
        onClick={(e) => e.stopPropagation()}
      >
        {/* Header */}
        <div className="flex items-center justify-between px-6 py-4 border-b border-gray-100">
          <div>
            <h2 className="text-lg font-bold text-gray-900">
              {step === "species" && "Choose Species"}
              {step === "details" && `Add ${selectedSpecies?.name}`}
              {step === "vaccinations" && "Vaccination Schedule"}
              {step === "review" && "Review & Create"}
            </h2>
            <p className="text-xs text-gray-400 mt-0.5">
              {step === "species" && "Select the type of animal you're adding"}
              {step === "details" && "Fill in the details — some fields auto-fill based on species and breed"}
              {step === "vaccinations" && "Review and customize the auto-scheduled vaccination plan"}
              {step === "review" && "Review everything before creating"}
            </p>
          </div>
          <button onClick={onCancel} className="p-2 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer">
            <X className="h-5 w-5 text-gray-400" />
          </button>
        </div>

        {/* Progress Steps */}
        <div className="flex items-center gap-2 px-6 py-3 bg-gray-50 border-b border-gray-100">
          {(["species", "details", "vaccinations", "review"] as Step[]).map((s, i) => (
            <React.Fragment key={s}>
              <div className="flex items-center gap-1.5">
                <div
                  className={cn(
                    "flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold",
                    step === s
                      ? "bg-emerald-700 text-white"
                      : ["species", "details", "vaccinations", "review"].indexOf(step) > i
                      ? "bg-emerald-100 text-emerald-700"
                      : "bg-gray-200 text-gray-500"
                  )}
                >
                  {(["species", "details", "vaccinations", "review"].indexOf(step) > i) ? (
                    <Check className="h-3.5 w-3.5" />
                  ) : (
                    i + 1
                  )}
                </div>
                <span className="text-xs font-medium text-gray-700 capitalize hidden sm:inline">{s}</span>
              </div>
              {i < 3 && <ChevronRight className="h-3.5 w-3.5 text-gray-300" />}
            </React.Fragment>
          ))}
        </div>

        {/* Content */}
        <div className="flex-1 overflow-y-auto px-6 py-6">
          <AnimatePresence mode="wait">
            {/* Step 1: Species Selection */}
            {step === "species" && (
              <motion.div
                key="species"
                initial={{ opacity: 0, x: 20 }}
                animate={{ opacity: 1, x: 0 }}
                exit={{ opacity: 0, x: -20 }}
              >
                {/* Category Tabs */}
                <div className="flex gap-2 mb-6">
                  {categories.map((cat) => (
                    <button
                      key={cat.id}
                      onClick={() => setSelectedCategory(cat.id)}
                      className={cn(
                        "flex items-center gap-1.5 px-4 py-2 rounded-full text-sm font-medium transition-all cursor-pointer",
                        selectedCategory === cat.id
                          ? "bg-emerald-700 text-white shadow-md"
                          : "bg-gray-100 text-gray-700 hover:bg-gray-200"
                      )}
                    >
                      <CategoryIcon iconId={cat.icon} />
                      {cat.label}
                    </button>
                  ))}
                </div>

                {/* Species Grid */}
                <div className="grid grid-cols-2 gap-3">
                  {speciesInCategory.map((species) => (
                    <button
                      key={species.id}
                      onClick={() => handleSpeciesSelect(species)}
                      className={cn(
                        "flex flex-col items-start p-4 rounded-xl border-2 transition-all text-left cursor-pointer",
                        selectedSpecies?.id === species.id
                          ? "border-emerald-500 bg-emerald-50"
                          : "border-gray-100 hover:border-emerald-300 hover:bg-emerald-50/50"
                      )}
                    >
                      <div className="flex items-center gap-2 mb-2">
                        <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">
                          <CategoryIcon iconId={species.category === "poultry" ? "bird" : species.category === "livestock" ? "beef" : species.category === "aquaculture" ? "droplets" : "flower"} />
                        </div>
                        <span className="text-sm font-bold text-gray-900">{species.name}</span>
                      </div>
                      <p className="text-[11px] text-gray-400">
                        {species.breeds.slice(0, 3).join(", ")}
                        {species.breeds.length > 3 && ` +${species.breeds.length - 3} more`}
                      </p>
                      <div className="flex items-center gap-3 mt-2 text-[10px] text-gray-400">
                        <span className="flex items-center gap-1"><Calendar className="h-3 w-3" />{species.growthCycleDays} days</span>
                        <span className="flex items-center gap-1"><Syringe className="h-3 w-3" />{species.vaccinationSchedule.length} vaccines</span>
                        <span className="flex items-center gap-1"><DollarSign className="h-3 w-3" />KES {species.costPerAnimal.toLocaleString()}</span>
                      </div>
                    </button>
                  ))}
                </div>
              </motion.div>
            )}

            {/* Step 2: Comprehensive Details Form */}
            {step === "details" && selectedSpecies && (
              <motion.div
                key="details"
                initial={{ opacity: 0, x: 20 }}
                animate={{ opacity: 1, x: 0 }}
                exit={{ opacity: 0, x: -20 }}
                className="space-y-3"
              >
                {/* Species Info Card */}
                <div className="rounded-xl bg-emerald-50 border border-emerald-100 p-4 mb-4">
                  <div className="flex items-center gap-2 mb-2">
                    <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">
                      <CategoryIcon iconId={selectedSpecies.category === "poultry" ? "bird" : selectedSpecies.category === "livestock" ? "beef" : selectedSpecies.category === "aquaculture" ? "droplets" : "flower"} />
                    </div>
                    <span className="text-sm font-bold text-emerald-800">{selectedSpecies.name}</span>
                    <span className="text-[10px] px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-600 font-medium capitalize">{selectedSpecies.category}</span>
                  </div>
                  <div className="grid grid-cols-2 gap-x-4 gap-y-1 text-[11px] text-emerald-700">
                    <span className="flex items-center gap-1"><Package className="h-3 w-3" />{selectedSpecies.housingType}</span>
                    <span className="flex items-center gap-1"><Droplets className="h-3 w-3" />{selectedSpecies.waterPerDay}</span>
                    <span className="flex items-center gap-1"><FileText className="h-3 w-3" />{selectedSpecies.feedPerDay}</span>
                    <span className="flex items-center gap-1"><Target className="h-3 w-3" />{selectedSpecies.expectedYield}</span>
                  </div>
                  {breakEvenInfo && (
                    <div className="mt-2 flex items-center gap-3 text-[10px] text-emerald-600">
                      <span>Break-even: ~{breakEvenInfo.months} months</span>
                      <span>•</span>
                      <span>Skill: {breakEvenInfo.skill}</span>
                      <span>•</span>
                      <span>Labor: {breakEvenInfo.labor}</span>
                      {breakEvenInfo.insurance && (
                        <>
                          <span>•</span>
                          <span className="text-amber-600 font-medium">Insurance recommended</span>
                        </>
                      )}
                    </div>
                  )}
                </div>

                {/* Collapsible Sections */}
                {allSections.map((sectionKey) => {
                  const section = sectionLabels[sectionKey];
                  const SectionIcon = section.icon;
                  const isExpanded = expandedSections.has(sectionKey);

                  return (
                    <div key={sectionKey} className="border border-gray-100 rounded-xl overflow-hidden">
                      <button
                        onClick={() => toggleSection(sectionKey)}
                        className="w-full flex items-center justify-between px-4 py-3 bg-gray-50 hover:bg-gray-100 transition-colors cursor-pointer"
                      >
                        <div className="flex items-center gap-2">
                          <SectionIcon className="h-4 w-4 text-gray-500" />
                          <span className="text-sm font-semibold text-gray-800">{section.label}</span>
                        </div>
                        {isExpanded ? (
                          <ChevronUp className="h-4 w-4 text-gray-400" />
                        ) : (
                          <ChevronDown className="h-4 w-4 text-gray-400" />
                        )}
                      </button>

                      {isExpanded && (
                        <motion.div
                          initial={{ height: 0, opacity: 0 }}
                          animate={{ height: "auto", opacity: 1 }}
                          exit={{ height: 0, opacity: 0 }}
                          className="px-4 py-4"
                        >
                          {/* Basic Info */}
                          {sectionKey === "basic" && (
                            <div className="grid grid-cols-2 gap-4">
                              <div className="col-span-2">
                                <label className="text-xs font-semibold text-gray-700 block mb-1.5">
                                  Flock Name *
                                </label>
                                <input
                                  placeholder="e.g., Layer Block A, Dairy Herd 1, Fish Pond B"
                                  value={form.name}
                                  onChange={(e) => updateForm("name", e.target.value)}
                                  className="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                />
                              </div>

                              <div>
                                <label className="text-xs font-semibold text-gray-700 block mb-1.5">Breed *</label>
                                <select
                                  value={form.breed}
                                  onChange={(e) => handleBreedChange(e.target.value)}
                                  className="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                >
                                  {selectedSpecies.breeds.map((b) => (
                                    <option key={b} value={b}>{b}</option>
                                  ))}
                                </select>
                                {selectedSpecies.breedDetails[form.breed] && (
                                  <div className="mt-1.5 p-2 rounded-lg bg-gray-50 text-[10px] text-gray-500 space-y-0.5">
                                    {selectedSpecies.breedDetails[form.breed]?.matureWeight && (
                                      <div>Weight: {selectedSpecies.breedDetails[form.breed].matureWeight}</div>
                                    )}
                                    {selectedSpecies.breedDetails[form.breed]?.productionRate && (
                                      <div>Production: {selectedSpecies.breedDetails[form.breed].productionRate}</div>
                                    )}
                                    {selectedSpecies.breedDetails[form.breed]?.ageAtProduction && (
                                      <div>First production: {selectedSpecies.breedDetails[form.breed].ageAtProduction}</div>
                                    )}
                                  </div>
                                )}
                              </div>

                              <div>
                                <label className="text-xs font-semibold text-gray-700 block mb-1.5">
                                  Number of Animals *
                                </label>
                                <input
                                  type="number"
                                  placeholder="e.g., 500"
                                  value={form.initialCount}
                                  onChange={(e) => updateForm("initialCount", e.target.value)}
                                  className="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                />
                              </div>

                              <div>
                                <label className="text-xs font-semibold text-gray-700 block mb-1.5">
                                  <Calendar className="inline h-3 w-3 mr-1" />
                                  Start Date
                                </label>
                                <input
                                  type="date"
                                  value={form.hatchDate}
                                  onChange={(e) => updateForm("hatchDate", e.target.value)}
                                  className="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                />
                              </div>

                              <div>
                                <label className="text-xs font-semibold text-gray-700 block mb-1.5">Purpose</label>
                                <select
                                  value={form.purpose}
                                  onChange={(e) => updateForm("purpose", e.target.value)}
                                  className="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                >
                                  <option value="production">Production (eggs/milk/meat)</option>
                                  <option value="breeding">Breeding</option>
                                  <option value="dual_purpose">Dual Purpose</option>
                                </select>
                              </div>

                              <div>
                                <label className="text-xs font-semibold text-gray-700 block mb-1.5">Gender</label>
                                <select
                                  value={form.gender}
                                  onChange={(e) => updateForm("gender", e.target.value)}
                                  className="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                >
                                  <option value="female">All Female</option>
                                  <option value="male">All Male</option>
                                  <option value="mixed">Mixed</option>
                                </select>
                              </div>

                              {form.gender === "mixed" && (
                                <div>
                                  <label className="text-xs font-semibold text-gray-700 block mb-1.5">Gender Ratio</label>
                                  <input
                                    placeholder={selectedSpecies.defaultGenderRatio || "e.g., 1:10"}
                                    value={form.genderRatio}
                                    onChange={(e) => updateForm("genderRatio", e.target.value)}
                                    className="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                  />
                                </div>
                              )}
                            </div>
                          )}

                          {/* Location */}
                          {sectionKey === "location" && (
                            <div className="space-y-4">
                              <div>
                                <label className="text-xs font-semibold text-gray-700 block mb-1.5">
                                  <MapPin className="inline h-3 w-3 mr-1" />
                                  Pen / Barn / Pond Location
                                </label>
                                <input
                                  placeholder="e.g., Pen A, Barn 2, Pond 3, Coop behind house"
                                  value={form.location}
                                  onChange={(e) => updateForm("location", e.target.value)}
                                  className="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                />
                              </div>
                              <div className="p-3 rounded-lg bg-gray-50 text-xs text-gray-500">
                                <strong>Housing:</strong> {selectedSpecies.housingType} — {selectedSpecies.housingRequirements}
                              </div>
                              <div className="p-3 rounded-lg bg-gray-50 text-xs text-gray-500">
                                <strong>Space needed:</strong> {selectedSpecies.spacePerAnimal}
                              </div>
                            </div>
                          )}

                          {/* Supply & Cost */}
                          {sectionKey === "supply" && (
                            <div className="grid grid-cols-2 gap-4">
                              <div className="col-span-2">
                                <label className="text-xs font-semibold text-gray-700 block mb-1.5">
                                  Source / Supplier Name
                                </label>
                                <input
                                  placeholder="e.g., Kienyeji Hatchery, Kiambu"
                                  value={form.source}
                                  onChange={(e) => updateForm("source", e.target.value)}
                                  className="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                />
                              </div>

                              <div>
                                <label className="text-xs font-semibold text-gray-700 block mb-1.5">
                                  <Phone className="inline h-3 w-3 mr-1" />
                                  Supplier Phone
                                </label>
                                <input
                                  placeholder="+254 712 345 678"
                                  value={form.supplierContact}
                                  onChange={(e) => updateForm("supplierContact", e.target.value)}
                                  className="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                />
                              </div>

                              <div>
                                <label className="text-xs font-semibold text-gray-700 block mb-1.5">
                                  <DollarSign className="inline h-3 w-3 mr-1" />
                                  Cost per Animal (KES)
                                </label>
                                <input
                                  type="number"
                                  placeholder={selectedSpecies.costPerAnimal.toString()}
                                  value={form.costPerAnimal}
                                  onChange={(e) => updateForm("costPerAnimal", e.target.value)}
                                  className="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                />
                              </div>

                              <div className="col-span-2">
                                <label className="text-xs font-semibold text-gray-700 block mb-1.5">
                                  Target Market
                                </label>
                                <input
                                  placeholder="e.g., Local market, Supermarket, Butchery, Restaurant"
                                  value={form.targetMarket}
                                  onChange={(e) => updateForm("targetMarket", e.target.value)}
                                  className="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                />
                              </div>

                              {/* Investment Summary */}
                              {totalInvestment > 0 && (
                                <div className="col-span-2 p-3 rounded-lg bg-emerald-50 border border-emerald-100">
                                  <div className="text-xs font-semibold text-emerald-800 mb-1">Investment Summary</div>
                                  <div className="grid grid-cols-3 gap-2 text-[11px] text-emerald-700">
                                    <div>
                                      <span className="text-emerald-500">Purchase:</span>{" "}
                                      KES {totalInvestment.toLocaleString()}
                                    </div>
                                    <div>
                                      <span className="text-emerald-500">Vaccines:</span>{" "}
                                      KES {estimatedVaccinationCost.toLocaleString()}
                                    </div>
                                    <div>
                                      <span className="text-emerald-500">Monthly feed:</span>{" "}
                                      KES {estimatedFeedCost.toLocaleString()}
                                    </div>
                                  </div>
                                </div>
                              )}
                            </div>
                          )}

                          {/* Feed Plan */}
                          {sectionKey === "feed" && (
                            <div className="grid grid-cols-2 gap-4">
                              <div>
                                <label className="text-xs font-semibold text-gray-700 block mb-1.5">Feed Type</label>
                                <select
                                  value={form.feedType}
                                  onChange={(e) => updateForm("feedType", e.target.value)}
                                  className="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                >
                                  {selectedSpecies.feedTypes.map((ft) => (
                                    <option key={ft} value={ft}>{ft}</option>
                                  ))}
                                </select>
                              </div>

                              <div>
                                <label className="text-xs font-semibold text-gray-700 block mb-1.5">
                                  Feed Supplier
                                </label>
                                <input
                                  placeholder="e.g., Unga Feeds, Kevian Kenya"
                                  value={form.feedSupplier}
                                  onChange={(e) => updateForm("feedSupplier", e.target.value)}
                                  className="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                />
                              </div>

                              <div>
                                <label className="text-xs font-semibold text-gray-700 block mb-1.5">
                                  Feed Cost / Month (KES)
                                </label>
                                <input
                                  type="number"
                                  placeholder={estimatedFeedCost.toString()}
                                  value={form.feedCostPerMonth}
                                  onChange={(e) => updateForm("feedCostPerMonth", e.target.value)}
                                  className="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                />
                              </div>

                              <div className="p-3 rounded-lg bg-gray-50 text-xs text-gray-500 self-start">
                                <strong>Daily feed:</strong> {selectedSpecies.feedPerDay}
                                <br />
                                <strong>Water:</strong> {selectedSpecies.waterPerDay}
                              </div>
                            </div>
                          )}

                          {/* Vet & Health */}
                          {sectionKey === "vet" && (
                            <div className="grid grid-cols-2 gap-4">
                              <div>
                                <label className="text-xs font-semibold text-gray-700 block mb-1.5">
                                  <User className="inline h-3 w-3 mr-1" />
                                  Veterinarian Name
                                </label>
                                <input
                                  placeholder="e.g., Dr. Kamau"
                                  value={form.vetName}
                                  onChange={(e) => updateForm("vetName", e.target.value)}
                                  className="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                />
                              </div>

                              <div>
                                <label className="text-xs font-semibold text-gray-700 block mb-1.5">
                                  <Phone className="inline h-3 w-3 mr-1" />
                                  Vet Phone
                                </label>
                                <input
                                  placeholder="+254 722 111 222"
                                  value={form.vetPhone}
                                  onChange={(e) => updateForm("vetPhone", e.target.value)}
                                  className="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                />
                              </div>

                              <div className="col-span-2">
                                <label className="text-xs font-semibold text-gray-700 block mb-1.5">
                                  Health Condition on Arrival
                                </label>
                                <input
                                  placeholder="e.g., Vaccinated at hatchery, healthy, no visible issues"
                                  value={form.healthOnArrival}
                                  onChange={(e) => updateForm("healthOnArrival", e.target.value)}
                                  className="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                />
                              </div>

                              <div className="col-span-2 p-3 rounded-lg bg-gray-50 text-xs text-gray-500">
                                <strong>Common health issues:</strong> {selectedSpecies.commonHealthIssues.join(", ")}
                                <br />
                                <strong>Vet visits:</strong> {selectedSpecies.vetVisitFrequency}
                                <br />
                                <strong>Expected mortality:</strong> {selectedSpecies.mortalityRate}% (industry standard)
                              </div>
                            </div>
                          )}

                          {/* Production Target */}
                          {sectionKey === "target" && (
                            <div className="grid grid-cols-2 gap-4">
                              <div className="col-span-2">
                                <label className="text-xs font-semibold text-gray-700 block mb-1.5">
                                  Expected Yield
                                </label>
                                <input
                                  placeholder={selectedSpecies.expectedYield}
                                  value={form.expectedYield}
                                  onChange={(e) => updateForm("expectedYield", e.target.value)}
                                  className="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                />
                              </div>

                              <div>
                                <label className="text-xs font-semibold text-gray-700 block mb-1.5">
                                  Expected Weight / Size
                                </label>
                                <input
                                  placeholder={selectedSpecies.breedDetails[form.breed]?.matureWeight || "e.g., 2.5kg"}
                                  value={form.expectedWeight}
                                  onChange={(e) => updateForm("expectedWeight", e.target.value)}
                                  className="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                />
                              </div>

                              <div>
                                <label className="text-xs font-semibold text-gray-700 block mb-1.5">
                                  Expected Revenue
                                </label>
                                <input
                                  placeholder={selectedSpecies.revenuePerUnit}
                                  value={form.expectedRevenue}
                                  onChange={(e) => updateForm("expectedRevenue", e.target.value)}
                                  className="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                />
                              </div>

                              <div className="col-span-2 p-3 rounded-lg bg-gray-50 text-xs text-gray-500">
                                <strong>Production metric:</strong> {selectedSpecies.productionMetric} ({selectedSpecies.productionUnit})
                                <br />
                                <strong>Growth cycle:</strong> {selectedSpecies.growthCycleDays} days
                              </div>
                            </div>
                          )}

                          {/* Insurance & Notes */}
                          {sectionKey === "insurance" && (
                            <div className="space-y-4">
                              <div>
                                <label className="text-xs font-semibold text-gray-700 block mb-1.5">
                                  <Shield className="inline h-3 w-3 mr-1" />
                                  Insurance Policy Number
                                </label>
                                <input
                                  placeholder="e.g., POL-2026-00123"
                                  value={form.insurancePolicy}
                                  onChange={(e) => updateForm("insurancePolicy", e.target.value)}
                                  className="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                                />
                                {breakEvenInfo?.insurance && (
                                  <p className="mt-1 text-[10px] text-amber-600 font-medium">
                                    Insurance is recommended for {selectedSpecies?.name}
                                  </p>
                                )}
                              </div>

                              <div>
                                <label className="text-xs font-semibold text-gray-700 block mb-1.5">
                                  <FileText className="inline h-3 w-3 mr-1" />
                                  Additional Notes
                                </label>
                                <textarea
                                  placeholder="Any additional notes about this flock..."
                                  value={form.notes}
                                  onChange={(e) => updateForm("notes", e.target.value)}
                                  rows={3}
                                  className="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all resize-none"
                                />
                              </div>
                            </div>
                          )}
                        </motion.div>
                      )}
                    </div>
                  );
                })}
              </motion.div>
            )}

            {/* Step 3: Vaccination Schedule */}
            {step === "vaccinations" && selectedSpecies && (
              <motion.div
                key="vaccinations"
                initial={{ opacity: 0, x: 20 }}
                animate={{ opacity: 1, x: 0 }}
                exit={{ opacity: 0, x: -20 }}
                className="space-y-4"
              >
                <div className="flex items-center justify-between">
                  <div>
                    <h3 className="text-sm font-bold text-gray-900">Auto-Scheduled Vaccinations</h3>
                    <p className="text-xs text-gray-400 mt-0.5">
                      Based on {selectedSpecies.name} best practices. Vaccinations are calculated from your start date ({form.hatchDate}).
                    </p>
                  </div>
                  <div className="flex items-center gap-2">
                    <span className="text-xs text-gray-500">Auto-schedule</span>
                    <button
                      onClick={() => setForm({ ...form, autoScheduleVaccines: !form.autoScheduleVaccines })}
                      className={cn(
                        "relative w-10 h-6 rounded-full transition-colors cursor-pointer",
                        form.autoScheduleVaccines ? "bg-emerald-600" : "bg-gray-300"
                      )}
                    >
                      <div
                        className={cn(
                          "absolute top-0.5 w-5 h-5 rounded-full bg-white shadow transition-transform",
                          form.autoScheduleVaccines ? "translate-x-4.5" : "translate-x-0.5"
                        )}
                      />
                    </button>
                  </div>
                </div>

                {form.autoScheduleVaccines && (
                  <div className="space-y-2">
                    {selectedSpecies.vaccinationSchedule.map((v, i) => {
                      const scheduledDate = new Date(form.hatchDate);
                      scheduledDate.setDate(scheduledDate.getDate() + v.daysFromStart);

                      return (
                        <div
                          key={i}
                          className="flex items-center gap-4 p-3 rounded-xl border border-gray-100 bg-white"
                        >
                          <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700 flex-shrink-0">
                            <Syringe className="h-4 w-4" />
                          </div>
                          <div className="flex-1 min-w-0">
                            <div className="text-sm font-semibold text-gray-900">{v.vaccine}</div>
                            <div className="text-[11px] text-gray-400">{v.description}</div>
                          </div>
                          <div className="text-right flex-shrink-0">
                            <div className="text-xs font-semibold text-gray-700">{v.ageLabel}</div>
                            <div className="text-[10px] text-gray-400">
                              {scheduledDate.toLocaleDateString("en-GB", {
                                day: "numeric",
                                month: "short",
                                year: "numeric",
                              })}
                            </div>
                          </div>
                          <div className="text-right flex-shrink-0">
                            <div className="text-xs font-semibold text-emerald-700">
                              KES {(v.cost * (Number(form.initialCount) || 1)).toLocaleString()}
                            </div>
                            <div className="text-[10px] text-gray-400">
                              KES {v.cost}/dose
                            </div>
                          </div>
                        </div>
                      );
                    })}

                    <div className="p-3 rounded-xl bg-emerald-50 border border-emerald-100">
                      <div className="flex items-center justify-between">
                        <span className="text-xs font-semibold text-emerald-800">Total Vaccination Cost</span>
                        <span className="text-sm font-bold text-emerald-800">
                          KES {estimatedVaccinationCost.toLocaleString()}
                        </span>
                      </div>
                      <p className="text-[10px] text-emerald-600 mt-1">
                        For {Number(form.initialCount).toLocaleString()} animals × {selectedSpecies.vaccinationSchedule.length} vaccines
                      </p>
                    </div>
                  </div>
                )}
              </motion.div>
            )}

            {/* Step 4: Review */}
            {step === "review" && selectedSpecies && (
              <motion.div
                key="review"
                initial={{ opacity: 0, x: 20 }}
                animate={{ opacity: 1, x: 0 }}
                exit={{ opacity: 0, x: -20 }}
                className="space-y-4"
              >
                {/* Summary Cards */}
                <div className="grid grid-cols-2 gap-3">
                  <div className="p-3 rounded-xl bg-emerald-50 border border-emerald-100">
                    <div className="text-[10px] font-bold uppercase text-emerald-600 tracking-wider">Species</div>
                    <div className="text-sm font-bold text-emerald-800 mt-1">{selectedSpecies.name}</div>
                    <div className="text-xs text-emerald-600">{form.breed}</div>
                  </div>

                  <div className="p-3 rounded-xl bg-blue-50 border border-blue-100">
                    <div className="text-[10px] font-bold uppercase text-blue-600 tracking-wider">Count</div>
                    <div className="text-sm font-bold text-blue-800 mt-1">{Number(form.initialCount).toLocaleString()} animals</div>
                    <div className="text-xs text-blue-600 capitalize">{form.purpose.replace("_", " ")}</div>
                  </div>

                  {totalInvestment > 0 && (
                    <div className="p-3 rounded-xl bg-amber-50 border border-amber-100">
                      <div className="text-[10px] font-bold uppercase text-amber-600 tracking-wider">Total Investment</div>
                      <div className="text-sm font-bold text-amber-800 mt-1">KES {totalInvestment.toLocaleString()}</div>
                      <div className="text-xs text-amber-600">+ KES {estimatedVaccinationCost.toLocaleString()} vaccines</div>
                    </div>
                  )}

                  <div className="p-3 rounded-xl bg-gray-50 border border-gray-100">
                    <div className="text-[10px] font-bold uppercase text-gray-500 tracking-wider">Started</div>
                    <div className="text-sm font-bold text-gray-800 mt-1">
                      {new Date(form.hatchDate).toLocaleDateString("en-GB", { day: "numeric", month: "short", year: "numeric" })}
                    </div>
                    <div className="text-xs text-gray-500">{selectedSpecies.growthCycleDays} day cycle</div>
                  </div>
                </div>

                {/* Detailed Summary */}
                <div className="rounded-xl border border-gray-100 divide-y divide-gray-50">
                  {[
                    { label: "Location", value: form.location || "—" },
                    { label: "Purpose", value: form.purpose.replace("_", " ") },
                    { label: "Gender", value: `${form.gender}${form.genderRatio ? ` (${form.genderRatio})` : ""}` },
                    { label: "Source", value: form.source || "—" },
                    { label: "Feed Type", value: form.feedType || "—" },
                    { label: "Feed Cost/Month", value: form.feedCostPerMonth ? `KES ${Number(form.feedCostPerMonth).toLocaleString()}` : `~KES ${estimatedFeedCost.toLocaleString()} (est.)` },
                    { label: "Vet", value: form.vetName ? `${form.vetName}${form.vetPhone ? ` (${form.vetPhone})` : ""}` : "—" },
                    { label: "Target Market", value: form.targetMarket || "—" },
                    { label: "Expected Yield", value: form.expectedYield || "—" },
                    { label: "Insurance", value: form.insurancePolicy || "—" },
                  ].map((item) => (
                    <div key={item.label} className="flex items-center justify-between px-4 py-2.5">
                      <span className="text-xs font-semibold text-gray-400 uppercase">{item.label}</span>
                      <span className="text-sm font-medium text-gray-700">{item.value}</span>
                    </div>
                  ))}
                </div>

                {/* Vaccination Summary */}
                {form.autoScheduleVaccines && selectedSpecies.vaccinationSchedule.length > 0 && (
                  <div className="rounded-xl border border-gray-100 p-4">
                    <div className="flex items-center gap-2 mb-3">
                      <Syringe className="h-4 w-4 text-emerald-600" />
                      <span className="text-xs font-bold uppercase text-gray-500 tracking-wider">
                        {selectedSpecies.vaccinationSchedule.length} Vaccinations Scheduled
                      </span>
                    </div>
                    <div className="grid grid-cols-2 gap-1.5">
                      {selectedSpecies.vaccinationSchedule.map((v, i) => (
                        <div key={i} className="flex items-center gap-2 text-xs text-gray-700">
                          <Check className="h-3 w-3 text-emerald-500" />
                          <span className="font-medium">{v.ageLabel}</span>
                          <span className="text-gray-400">— {v.vaccine}</span>
                        </div>
                      ))}
                    </div>
                  </div>
                )}

                {form.notes && (
                  <div className="rounded-xl border border-gray-100 p-4">
                    <div className="text-xs font-bold uppercase text-gray-400 mb-1">Notes</div>
                    <p className="text-sm text-gray-700">{form.notes}</p>
                  </div>
                )}
              </motion.div>
            )}
          </AnimatePresence>
        </div>

        {/* Footer */}
        <div className="flex items-center justify-between px-6 py-4 border-t border-gray-100 bg-gray-50">
          <button
            onClick={() => {
              if (step === "details") setStep("species");
              else if (step === "vaccinations") setStep("details");
              else if (step === "review") setStep("vaccinations");
              else onCancel();
            }}
            className="px-4 py-2 rounded-xl text-sm font-medium text-gray-500 hover:text-gray-700 hover:bg-white transition-colors cursor-pointer"
          >
            Back
          </button>
          <div className="flex gap-2">
            <button
              onClick={onCancel}
              className="px-4 py-2 rounded-xl text-sm font-medium text-gray-500 hover:bg-white border border-gray-200 transition-colors cursor-pointer"
            >
              Cancel
            </button>
            {step === "review" ? (
              <button
                onClick={handleSubmit}
                disabled={loading}
                className="px-6 py-2 rounded-xl text-sm font-semibold bg-emerald-700 text-white hover:bg-emerald-800 shadow-md transition-all cursor-pointer disabled:opacity-50"
              >
                {loading ? "Creating..." : `Create ${selectedSpecies?.name || "Flock"}`}
              </button>
            ) : (
              <button
                onClick={() => {
                  if (step === "species") setStep("details");
                  else if (step === "details") setStep("vaccinations");
                  else setStep("review");
                }}
                disabled={!isStepValid()}
                className="px-6 py-2 rounded-xl text-sm font-semibold bg-emerald-700 text-white hover:bg-emerald-800 shadow-md transition-all cursor-pointer disabled:opacity-50"
              >
                Continue
              </button>
            )}
          </div>
        </div>
      </motion.div>
    </motion.div>
  );
}
