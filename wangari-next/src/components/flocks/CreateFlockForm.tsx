"use client";

import * as React from "react";
import { motion, AnimatePresence } from "framer-motion";
import {
  X,
  ChevronRight,
  Check,
  Calendar,
  Syringe,
  Wheat,
  Info,
} from "lucide-react";
import { cn } from "@/lib/utils";
import {
  speciesTemplates,
  getSpeciesByCategory,
  getSpeciesCategories,
  getSpeciesTemplate,
  type SpeciesTemplate,
} from "@/lib/species-templates";

interface CreateFlockFormProps {
  onSubmit: (data: any) => Promise<void>;
  onCancel: () => void;
}

type Step = "species" | "details" | "review";

export function CreateFlockForm({ onSubmit, onCancel }: CreateFlockFormProps) {
  const [step, setStep] = React.useState<Step>("species");
  const [selectedSpecies, setSelectedSpecies] = React.useState<SpeciesTemplate | null>(null);
  const [selectedCategory, setSelectedCategory] = React.useState<string>("poultry");
  const [loading, setLoading] = React.useState(false);

  // Form fields
  const [form, setForm] = React.useState({
    name: "",
    breed: "",
    initialCount: "",
    hatchDate: new Date().toISOString().split("T")[0],
    source: "", // where they got the animals from
    cost: "", // cost per animal
    targetMarket: "", // where they plan to sell
    notes: "",
    autoScheduleVaccines: true,
  });

  const categories = getSpeciesCategories();
  const speciesInCategory = getSpeciesByCategory(selectedCategory);

  const handleSpeciesSelect = (species: SpeciesTemplate) => {
    setSelectedSpecies(species);
    setForm((prev) => ({
      ...prev,
      breed: species.breeds[0] || "",
    }));
    setStep("details");
  };

  const handleSubmit = async () => {
    if (!selectedSpecies) return;
    setLoading(true);
    try {
      await onSubmit({
        name: form.name,
        breed: form.breed,
        type: selectedSpecies.id,
        speciesCategory: selectedSpecies.category,
        initialCount: Number(form.initialCount),
        hatchDate: form.hatchDate,
        source: form.source,
        cost: Number(form.cost) || 0,
        targetMarket: form.targetMarket,
        notes: form.notes,
        autoScheduleVaccines: form.autoScheduleVaccines,
        vaccinationSchedule: form.autoScheduleVaccines ? selectedSpecies.vaccinationSchedule : [],
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
        className="bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-hidden flex flex-col"
        onClick={(e) => e.stopPropagation()}
      >
        {/* Header */}
        <div className="flex items-center justify-between px-6 py-4 border-b border-wangari-border">
          <div>
            <h2 className="text-lg font-bold text-wangari-heading">
              {step === "species" && "Choose Species"}
              {step === "details" && `Add ${selectedSpecies?.emoji} ${selectedSpecies?.name}`}
              {step === "review" && "Review & Create"}
            </h2>
            <p className="text-xs text-wangari-muted mt-0.5">
              {step === "species" && "Select the type of animal you're adding"}
              {step === "details" && "Fill in the details — some fields are auto-filled"}
              {step === "review" && "Review your flock details before creating"}
            </p>
          </div>
          <button onClick={onCancel} className="p-2 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer">
            <X className="h-5 w-5 text-wangari-muted" />
          </button>
        </div>

        {/* Progress Steps */}
        <div className="flex items-center gap-2 px-6 py-3 bg-gray-50 border-b border-wangari-border">
          {(["species", "details", "review"] as Step[]).map((s, i) => (
            <React.Fragment key={s}>
              <div className="flex items-center gap-1.5">
                <div
                  className={cn(
                    "flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold",
                    step === s
                      ? "bg-wangari-green-800 text-white"
                      : (["species", "details", "review"].indexOf(step) > i)
                      ? "bg-wangari-green-100 text-wangari-green-800"
                      : "bg-gray-200 text-gray-500"
                  )}
                >
                  {(["species", "details", "review"].indexOf(step) > i) ? (
                    <Check className="h-3.5 w-3.5" />
                  ) : (
                    i + 1
                  )}
                </div>
                <span className="text-xs font-medium text-wangari-heading capitalize">{s}</span>
              </div>
              {i < 2 && <ChevronRight className="h-3.5 w-3.5 text-wangari-muted" />}
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
                          ? "bg-wangari-green-800 text-white shadow-md"
                          : "bg-gray-100 text-wangari-heading hover:bg-gray-200"
                      )}
                    >
                      <span>{cat.emoji}</span>
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
                          ? "border-wangari-green-500 bg-wangari-green-50"
                          : "border-wangari-border hover:border-wangari-green-300 hover:bg-wangari-green-50/50"
                      )}
                    >
                      <div className="flex items-center gap-2 mb-2">
                        <span className="text-2xl">{species.emoji}</span>
                        <span className="text-sm font-bold text-wangari-heading">{species.name}</span>
                      </div>
                      <p className="text-[11px] text-wangari-muted">
                        {species.breeds.slice(0, 3).join(", ")}
                        {species.breeds.length > 3 && ` +${species.breeds.length - 3}`}
                      </p>
                      <div className="flex items-center gap-3 mt-2 text-[10px] text-wangari-subtle">
                        <span>📅 {species.growthCycleDays} days</span>
                        <span>💉 {species.vaccinationSchedule.length} vaccines</span>
                      </div>
                    </button>
                  ))}
                </div>
              </motion.div>
            )}

            {/* Step 2: Details */}
            {step === "details" && selectedSpecies && (
              <motion.div
                key="details"
                initial={{ opacity: 0, x: 20 }}
                animate={{ opacity: 1, x: 0 }}
                exit={{ opacity: 0, x: -20 }}
                className="space-y-5"
              >
                {/* Species Info Card */}
                <div className="rounded-xl bg-wangari-green-50 border border-wangari-green-200 p-4">
                  <div className="flex items-center gap-2 mb-2">
                    <span className="text-xl">{selectedSpecies.emoji}</span>
                    <span className="text-sm font-bold text-wangari-green-800">{selectedSpecies.name}</span>
                  </div>
                  <div className="grid grid-cols-2 gap-2 text-[11px] text-wangari-green-700">
                    <span>🏠 {selectedSpecies.housingRequirements}</span>
                    <span>💧 {selectedSpecies.waterNeeds}</span>
                    <span>🌾 {selectedSpecies.feedPerDay}</span>
                    <span>📊 Produces: {selectedSpecies.productionMetric}</span>
                  </div>
                </div>

                {/* Form Fields */}
                <div className="grid grid-cols-2 gap-4">
                  <div className="col-span-2">
                    <label className="text-xs font-semibold text-wangari-heading block mb-1.5">Flock Name *</label>
                    <input
                      placeholder={`e.g., ${selectedSpecies.emoji} Layer Block A`}
                      value={form.name}
                      onChange={(e) => setForm({ ...form, name: e.target.value })}
                      className="w-full rounded-xl border border-wangari-border px-4 py-2.5 text-sm focus:ring-2 focus:ring-wangari-green-500/20 focus:border-wangari-green-500 transition-all"
                    />
                  </div>

                  <div>
                    <label className="text-xs font-semibold text-wangari-heading block mb-1.5">Breed *</label>
                    <select
                      value={form.breed}
                      onChange={(e) => setForm({ ...form, breed: e.target.value })}
                      className="w-full rounded-xl border border-wangari-border px-4 py-2.5 text-sm focus:ring-2 focus:ring-wangari-green-500/20 focus:border-wangari-green-500 transition-all"
                    >
                      {selectedSpecies.breeds.map((b) => (
                        <option key={b} value={b}>{b}</option>
                      ))}
                    </select>
                  </div>

                  <div>
                    <label className="text-xs font-semibold text-wangari-heading block mb-1.5">Number of Animals *</label>
                    <input
                      type="number"
                      placeholder="e.g., 500"
                      value={form.initialCount}
                      onChange={(e) => setForm({ ...form, initialCount: e.target.value })}
                      className="w-full rounded-xl border border-wangari-border px-4 py-2.5 text-sm focus:ring-2 focus:ring-wangari-green-500/20 focus:border-wangari-green-500 transition-all"
                    />
                  </div>

                  <div>
                    <label className="text-xs font-semibold text-wangari-heading block mb-1.5">
                      <Calendar className="inline h-3 w-3 mr-1" />
                      Start Date
                    </label>
                    <input
                      type="date"
                      value={form.hatchDate}
                      onChange={(e) => setForm({ ...form, hatchDate: e.target.value })}
                      className="w-full rounded-xl border border-wangari-border px-4 py-2.5 text-sm focus:ring-2 focus:ring-wangari-green-500/20 focus:border-wangari-green-500 transition-all"
                    />
                  </div>

                  <div>
                    <label className="text-xs font-semibold text-wangari-heading block mb-1.5">Cost per Animal (KES)</label>
                    <input
                      type="number"
                      placeholder="e.g., 350"
                      value={form.cost}
                      onChange={(e) => setForm({ ...form, cost: e.target.value })}
                      className="w-full rounded-xl border border-wangari-border px-4 py-2.5 text-sm focus:ring-2 focus:ring-wangari-green-500/20 focus:border-wangari-green-500 transition-all"
                    />
                  </div>

                  <div className="col-span-2">
                    <label className="text-xs font-semibold text-wangari-heading block mb-1.5">Source / Supplier</label>
                    <input
                      placeholder="e.g., Kienyeji Hatchery, Kiambu"
                      value={form.source}
                      onChange={(e) => setForm({ ...form, source: e.target.value })}
                      className="w-full rounded-xl border border-wangari-border px-4 py-2.5 text-sm focus:ring-2 focus:ring-wangari-green-500/20 focus:border-wangari-green-500 transition-all"
                    />
                  </div>

                  <div className="col-span-2">
                    <label className="text-xs font-semibold text-wangari-heading block mb-1.5">Target Market</label>
                    <input
                      placeholder="e.g., Local market, Supermarket, Butchery"
                      value={form.targetMarket}
                      onChange={(e) => setForm({ ...form, targetMarket: e.target.value })}
                      className="w-full rounded-xl border border-wangari-border px-4 py-2.5 text-sm focus:ring-2 focus:ring-wangari-green-500/20 focus:border-wangari-green-500 transition-all"
                    />
                  </div>
                </div>

                {/* Vaccination Auto-Schedule */}
                <div className="rounded-xl border border-wangari-border p-4">
                  <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                      <Syringe className="h-4 w-4 text-wangari-green-700" />
                      <span className="text-sm font-semibold text-wangari-heading">Auto-schedule vaccinations</span>
                    </div>
                    <button
                      onClick={() => setForm({ ...form, autoScheduleVaccines: !form.autoScheduleVaccines })}
                      className={cn(
                        "relative w-10 h-6 rounded-full transition-colors cursor-pointer",
                        form.autoScheduleVaccines ? "bg-wangari-green-600" : "bg-gray-300"
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
                  {form.autoScheduleVaccines && (
                    <div className="mt-3 space-y-1.5">
                      <p className="text-[11px] text-wangari-muted mb-2">
                        {selectedSpecies.vaccinationSchedule.length} vaccinations will be scheduled:
                      </p>
                      {selectedSpecies.vaccinationSchedule.map((v, i) => (
                        <div key={i} className="flex items-center gap-2 text-xs text-wangari-heading">
                          <span className="text-wangari-green-600">✓</span>
                          <span className="font-medium">{v.ageLabel}</span>
                          <span className="text-wangari-muted">—</span>
                          <span>{v.vaccine}</span>
                        </div>
                      ))}
                    </div>
                  )}
                </div>

                {/* Notes */}
                <div>
                  <label className="text-xs font-semibold text-wangari-heading block mb-1.5">Notes</label>
                  <textarea
                    placeholder="Any additional notes about this flock..."
                    value={form.notes}
                    onChange={(e) => setForm({ ...form, notes: e.target.value })}
                    rows={2}
                    className="w-full rounded-xl border border-wangari-border px-4 py-2.5 text-sm focus:ring-2 focus:ring-wangari-green-500/20 focus:border-wangari-green-500 transition-all resize-none"
                  />
                </div>
              </motion.div>
            )}

            {/* Step 3: Review */}
            {step === "review" && selectedSpecies && (
              <motion.div
                key="review"
                initial={{ opacity: 0, x: 20 }}
                animate={{ opacity: 1, x: 0 }}
                exit={{ opacity: 0, x: -20 }}
                className="space-y-4"
              >
                <div className="rounded-xl border border-wangari-border divide-y divide-wangari-border">
                  <div className="flex items-center justify-between px-4 py-3">
                    <span className="text-xs font-semibold text-wangari-muted uppercase">Species</span>
                    <span className="text-sm font-medium text-wangari-heading">
                      {selectedSpecies.emoji} {selectedSpecies.name}
                    </span>
                  </div>
                  <div className="flex items-center justify-between px-4 py-3">
                    <span className="text-xs font-semibold text-wangari-muted uppercase">Name</span>
                    <span className="text-sm font-medium text-wangari-heading">{form.name}</span>
                  </div>
                  <div className="flex items-center justify-between px-4 py-3">
                    <span className="text-xs font-semibold text-wangari-muted uppercase">Breed</span>
                    <span className="text-sm font-medium text-wangari-heading">{form.breed}</span>
                  </div>
                  <div className="flex items-center justify-between px-4 py-3">
                    <span className="text-xs font-semibold text-wangari-muted uppercase">Count</span>
                    <span className="text-sm font-bold text-wangari-heading">{Number(form.initialCount).toLocaleString()} animals</span>
                  </div>
                  <div className="flex items-center justify-between px-4 py-3">
                    <span className="text-xs font-semibold text-wangari-muted uppercase">Start Date</span>
                    <span className="text-sm font-medium text-wangari-heading">{new Date(form.hatchDate).toLocaleDateString()}</span>
                  </div>
                  {form.cost && (
                    <div className="flex items-center justify-between px-4 py-3">
                      <span className="text-xs font-semibold text-wangari-muted uppercase">Total Investment</span>
                      <span className="text-sm font-bold text-wangari-green-800">
                        KES {(Number(form.cost) * Number(form.initialCount)).toLocaleString()}
                      </span>
                    </div>
                  )}
                  {form.autoScheduleVaccines && (
                    <div className="px-4 py-3">
                      <span className="text-xs font-semibold text-wangari-muted uppercase block mb-2">Vaccination Schedule</span>
                      <div className="space-y-1">
                        {selectedSpecies.vaccinationSchedule.map((v, i) => (
                          <div key={i} className="flex items-center gap-2 text-xs text-wangari-heading">
                            <span className="text-wangari-green-600">💉</span>
                            <span className="font-medium">{v.ageLabel}</span>
                            <span className="text-wangari-muted">— {v.vaccine}</span>
                          </div>
                        ))}
                      </div>
                    </div>
                  )}
                </div>
              </motion.div>
            )}
          </AnimatePresence>
        </div>

        {/* Footer */}
        <div className="flex items-center justify-between px-6 py-4 border-t border-wangari-border bg-gray-50">
          <button
            onClick={() => {
              if (step === "details") setStep("species");
              else if (step === "review") setStep("details");
              else onCancel();
            }}
            className="px-4 py-2 rounded-xl text-sm font-medium text-wangari-muted hover:text-wangari-heading hover:bg-white transition-colors cursor-pointer"
          >
            Back
          </button>
          <div className="flex gap-2">
            <button
              onClick={onCancel}
              className="px-4 py-2 rounded-xl text-sm font-medium text-wangari-muted hover:bg-white border border-wangari-border transition-colors cursor-pointer"
            >
              Cancel
            </button>
            {step === "review" ? (
              <button
                onClick={handleSubmit}
                disabled={loading}
                className="px-6 py-2 rounded-xl text-sm font-semibold bg-wangari-green-800 text-white hover:bg-wangari-green-900 shadow-md transition-all cursor-pointer disabled:opacity-50"
              >
                {loading ? "Creating..." : "Create Flock"}
              </button>
            ) : (
              <button
                onClick={() => setStep(step === "species" ? "details" : "review")}
                disabled={!isStepValid()}
                className="px-6 py-2 rounded-xl text-sm font-semibold bg-wangari-green-800 text-white hover:bg-wangari-green-900 shadow-md transition-all cursor-pointer disabled:opacity-50"
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
