"use client";
import * as React from "react";
import { motion } from "framer-motion";
import {
  Bird,
  Plus,
  PawPrint,
  Trash2,
  Search,
  Heart,
  Syringe,
  Calendar,
  RefreshCw,
  Beef,
  MapPin,
  DollarSign,
  Eye,
  ChevronRight,
  Droplets,
  Flower,
} from "lucide-react";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { EmptyState } from "@/components/shared/empty-state";
import api from "@/lib/api-client";
import { cn } from "@/lib/utils";
import { CreateFlockForm } from "@/components/flocks/CreateFlockForm";
import { speciesTemplates, getSpeciesCategories, getSpeciesIconId } from "@/lib/species-templates";

const iconMap: Record<string, any> = {
  bird: Bird,
  beef: Beef,
  droplets: Droplets,
  flower: Flower,
};

const fadeUp = { hidden: { opacity: 0, y: 16 }, visible: { opacity: 1, y: 0, transition: { duration: 0.4, ease: [0.22, 1, 0.36, 1] as [number, number, number, number] } } };
const stagger = { hidden: {}, visible: { transition: { staggerChildren: 0.06 } } };

function SpeciesIcon({ speciesId }: { speciesId: string }) {
  const iconId = getSpeciesIconId(speciesId);
  const Icon = iconMap[iconId] || Bird;
  return <Icon className="h-5 w-5" />;
}

function getMortalityRating(rate: number): { label: string; color: string; bg: string } {
  if (rate <= 1) return { label: "Normal", color: "text-emerald-700", bg: "bg-emerald-50" };
  if (rate <= 3) return { label: "Acceptable", color: "text-emerald-700", bg: "bg-emerald-50" };
  if (rate <= 5) return { label: "Watch", color: "text-amber-700", bg: "bg-amber-50" };
  return { label: "Critical", color: "text-red-700", bg: "bg-red-50" };
}

function getAge(hatchDate: string | null): string {
  if (!hatchDate) return "—";
  const days = Math.floor((Date.now() - new Date(hatchDate).getTime()) / 86400000);
  if (days < 30) return `${days}d`;
  if (days < 365) return `${Math.floor(days / 30)}mo`;
  return `${Math.floor(days / 365)}y ${Math.floor((days % 365) / 30)}mo`;
}

function getPurposeLabel(purpose: string | null): string {
  if (!purpose) return "";
  if (purpose === "production") return "Production";
  if (purpose === "breeding") return "Breeding";
  if (purpose === "dual_purpose") return "Dual Purpose";
  return purpose;
}

export default function FlocksPage() {
  const [flocks, setFlocks] = React.useState<any[]>([]);
  const [loading, setLoading] = React.useState(true);
  const [showForm, setShowForm] = React.useState(false);
  const [filterSpecies, setFilterSpecies] = React.useState<string>("all");
  const [search, setSearch] = React.useState("");
  const [selectedFlock, setSelectedFlock] = React.useState<any>(null);

  const loadFlocks = () => {
    api.get("/api/flocks").then(d => {
      setFlocks(Array.isArray(d) ? d : []);
      setLoading(false);
    }).catch(() => setLoading(false));
  };
  React.useEffect(() => { loadFlocks(); }, []);

  const filtered = flocks.filter((f: any) => {
    const matchesSearch = !search || f.name.toLowerCase().includes(search.toLowerCase()) || (f.breed || "").toLowerCase().includes(search.toLowerCase());
    const matchesSpecies = filterSpecies === "all" || f.category === filterSpecies;
    return matchesSearch && matchesSpecies;
  });

  // KPI calculations
  const totalAnimals = flocks.reduce((s: number, f: any) => s + (f.currentCount || 0), 0);
  const totalInitial = flocks.reduce((s: number, f: any) => s + (f.initialCount || 0), 0);
  const totalMortality = flocks.reduce((s: number, f: any) => s + (f.mortality || 0), 0);
  const mortalityRate = totalInitial > 0 ? ((totalMortality / totalInitial) * 100).toFixed(1) : "0";
  const activeFlocks = flocks.filter((f: any) => f.status === "active").length;
  const uniqueCategories = [...new Set(flocks.map((f: any) => f.category))].length;
  const totalInvestment = flocks.reduce((s: number, f: any) => s + (Number(f.totalInvestment) || 0), 0);

  const categories = getSpeciesCategories();

  const handleCreate = async (data: any) => {
    await api.post("/api/flocks", data);
    setShowForm(false);
    loadFlocks();
  };

  const handleDelete = async (id: number) => {
    if (!confirm("Delete this flock? This will also delete all associated vaccinations and records.")) return;
    await api.delete("/api/flocks/" + id);
    loadFlocks();
    if (selectedFlock?.id === id) setSelectedFlock(null);
  };

  if (loading) {
    return (
      <div className="flex flex-col items-center justify-center h-96 gap-4">
        <div className="relative">
          <div className="h-12 w-12 rounded-full border-[3px] border-emerald-200 border-t-emerald-600 animate-spin" />
          <PawPrint className="absolute left-1/2 top-1/2 h-5 w-5 -translate-x-1/2 -translate-y-1/2 text-emerald-600" />
        </div>
        <p className="text-sm text-gray-400">Loading your livestock...</p>
      </div>
    );
  }

  // Detail View
  if (selectedFlock) {
    const flock = flocks.find((f: any) => f.id === selectedFlock.id) || selectedFlock;
    const species = speciesTemplates[flock.type];
    const mortality = flock.initialCount > 0 ? ((flock.mortality / flock.initialCount) * 100).toFixed(1) : "0";
    const vaccinations = flock.vaccinations || [];
    const pendingVax = vaccinations.filter((v: any) => v.status === "pending");
    const completedVax = vaccinations.filter((v: any) => v.status === "completed");

    return (
      <div className="space-y-6">
        {/* Back Button */}
        <motion.div initial="hidden" animate="visible" variants={fadeUp}>
          <button
            onClick={() => setSelectedFlock(null)}
            className="flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 cursor-pointer"
          >
            <ChevronRight className="h-4 w-4 rotate-180" />
            Back to Livestock
          </button>
        </motion.div>

        {/* Header */}
        <motion.div initial="hidden" animate="visible" variants={fadeUp} className="flex items-center justify-between">
          <div className="flex items-center gap-4">
            <div className="flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700">
              <SpeciesIcon speciesId={flock.type} />
            </div>
            <div>
              <h1 className="text-2xl font-bold text-gray-900 tracking-tight">{flock.name}</h1>
              <p className="text-sm text-gray-400 mt-0.5">
                {flock.breed || species?.name || flock.type}
                {flock.purpose && ` — ${getPurposeLabel(flock.purpose)}`}
                {flock.location && ` • ${flock.location}`}
              </p>
            </div>
          </div>
          <div className="flex items-center gap-2">
            <Badge variant={flock.status === "active" ? "default" : "outline"} className={
              flock.status === "active" ? "bg-emerald-50 text-emerald-700 border-emerald-200" : ""
            }>
              {flock.status}
            </Badge>
            <button
              onClick={() => handleDelete(flock.id)}
              className="p-2 rounded-lg hover:bg-red-50 text-gray-400 hover:text-red-500 transition-colors cursor-pointer"
            >
              <Trash2 className="h-4 w-4" />
            </button>
          </div>
        </motion.div>

        {/* Quick Stats */}
        <motion.div initial="hidden" animate="visible" variants={stagger} className="grid grid-cols-2 lg:grid-cols-5 gap-4">
          {[
            { label: "Current Count", value: (flock.currentCount || 0).toLocaleString(), icon: <PawPrint className="h-5 w-5" /> },
            { label: "Deaths", value: (flock.mortality || 0).toString(), icon: <Heart className="h-5 w-5" />, color: "text-red-600" },
            { label: "Mortality", value: `${mortality}%`, icon: <Syringe className="h-5 w-5" /> },
            { label: "Age", value: getAge(flock.hatchDate), icon: <Calendar className="h-5 w-5" /> },
            { label: "Investment", value: flock.totalInvestment ? `KES ${Number(flock.totalInvestment).toLocaleString()}` : "—", icon: <DollarSign className="h-5 w-5" /> },
          ].map((stat) => (
            <motion.div key={stat.label} variants={fadeUp}>
              <Card className="border border-gray-100 bg-white p-4">
                <div className="flex items-start justify-between">
                  <div>
                    <p className="text-[10px] font-bold uppercase tracking-widest text-gray-400">{stat.label}</p>
                    <p className={`mt-1.5 text-2xl font-bold ${stat.color || "text-gray-900"}`}>{stat.value}</p>
                  </div>
                  <div className="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">
                    {stat.icon}
                  </div>
                </div>
              </Card>
            </motion.div>
          ))}
        </motion.div>

        {/* Detail Sections */}
        <div className="grid md:grid-cols-2 gap-4">
          {/* Basic Info */}
          <motion.div initial="hidden" animate="visible" variants={fadeUp}>
            <Card className="border border-gray-100 bg-white">
              <CardContent className="p-5">
                <h3 className="text-xs font-bold uppercase text-gray-400 tracking-wider mb-4">Basic Information</h3>
                <div className="space-y-3">
                  {[
                    { label: "Species", value: species?.name || flock.type },
                    { label: "Breed", value: flock.breed || "—" },
                    { label: "Purpose", value: getPurposeLabel(flock.purpose) || "—" },
                    { label: "Gender", value: flock.gender ? `${flock.gender}${flock.genderRatio ? ` (${flock.genderRatio})` : ""}` : "—" },
                    { label: "Start Date", value: flock.hatchDate ? new Date(flock.hatchDate).toLocaleDateString() : "—" },
                    { label: "Status", value: flock.status },
                  ].map((item) => (
                    <div key={item.label} className="flex items-center justify-between">
                      <span className="text-xs text-gray-400">{item.label}</span>
                      <span className="text-sm font-medium text-gray-700">{item.value}</span>
                    </div>
                  ))}
                </div>
              </CardContent>
            </Card>
          </motion.div>

          {/* Location & Housing */}
          <motion.div initial="hidden" animate="visible" variants={fadeUp}>
            <Card className="border border-gray-100 bg-white">
              <CardContent className="p-5">
                <h3 className="text-xs font-bold uppercase text-gray-400 tracking-wider mb-4 flex items-center gap-1.5">
                  <MapPin className="h-3.5 w-3.5" /> Location & Housing
                </h3>
                <div className="space-y-3">
                  {[
                    { label: "Location", value: flock.location || "Not assigned" },
                    { label: "Housing Type", value: species?.housingType || "—" },
                    { label: "Space Required", value: species?.spacePerAnimal || "—" },
                    { label: "Requirements", value: species?.housingRequirements || "—" },
                  ].map((item) => (
                    <div key={item.label} className="flex items-center justify-between">
                      <span className="text-xs text-gray-400">{item.label}</span>
                      <span className="text-sm font-medium text-gray-700 text-right max-w-[60%]">{item.value}</span>
                    </div>
                  ))}
                </div>
              </CardContent>
            </Card>
          </motion.div>

          {/* Supply & Cost */}
          <motion.div initial="hidden" animate="visible" variants={fadeUp}>
            <Card className="border border-gray-100 bg-white">
              <CardContent className="p-5">
                <h3 className="text-xs font-bold uppercase text-gray-400 tracking-wider mb-4 flex items-center gap-1.5">
                  <DollarSign className="h-3.5 w-3.5" /> Supply & Cost
                </h3>
                <div className="space-y-3">
                  {[
                    { label: "Source", value: flock.source || "—" },
                    { label: "Supplier Phone", value: flock.supplierContact || "—" },
                    { label: "Cost per Animal", value: flock.costPerAnimal ? `KES ${Number(flock.costPerAnimal).toLocaleString()}` : "—" },
                    { label: "Total Investment", value: flock.totalInvestment ? `KES ${Number(flock.totalInvestment).toLocaleString()}` : "—" },
                    { label: "Target Market", value: flock.targetMarket || "—" },
                  ].map((item) => (
                    <div key={item.label} className="flex items-center justify-between">
                      <span className="text-xs text-gray-400">{item.label}</span>
                      <span className="text-sm font-medium text-gray-700">{item.value}</span>
                    </div>
                  ))}
                </div>
              </CardContent>
            </Card>
          </motion.div>

          {/* Feed Plan */}
          <motion.div initial="hidden" animate="visible" variants={fadeUp}>
            <Card className="border border-gray-100 bg-white">
              <CardContent className="p-5">
                <h3 className="text-xs font-bold uppercase text-gray-400 tracking-wider mb-4">Feed Plan</h3>
                <div className="space-y-3">
                  {[
                    { label: "Feed Type", value: flock.feedType || "—" },
                    { label: "Feed Supplier", value: flock.feedSupplier || "—" },
                    { label: "Feed Cost/Month", value: flock.feedCostPerMonth ? `KES ${Number(flock.feedCostPerMonth).toLocaleString()}` : "—" },
                    { label: "Daily Requirement", value: species?.feedPerDay || "—" },
                    { label: "Water Requirement", value: species?.waterPerDay || "—" },
                  ].map((item) => (
                    <div key={item.label} className="flex items-center justify-between">
                      <span className="text-xs text-gray-400">{item.label}</span>
                      <span className="text-sm font-medium text-gray-700">{item.value}</span>
                    </div>
                  ))}
                </div>
              </CardContent>
            </Card>
          </motion.div>

          {/* Vet & Health */}
          <motion.div initial="hidden" animate="visible" variants={fadeUp}>
            <Card className="border border-gray-100 bg-white">
              <CardContent className="p-5">
                <h3 className="text-xs font-bold uppercase text-gray-400 tracking-wider mb-4 flex items-center gap-1.5">
                  <Heart className="h-3.5 w-3.5" /> Veterinarian & Health
                </h3>
                <div className="space-y-3">
                  {[
                    { label: "Veterinarian", value: flock.vetName || "Not assigned" },
                    { label: "Vet Phone", value: flock.vetPhone || "—" },
                    { label: "Health on Arrival", value: flock.healthOnArrival || "—" },
                    { label: "Common Issues", value: species?.commonHealthIssues?.slice(0, 3).join(", ") || "—" },
                    { label: "Expected Mortality", value: species ? `${species.mortalityRate}%` : "—" },
                  ].map((item) => (
                    <div key={item.label} className="flex items-center justify-between">
                      <span className="text-xs text-gray-400">{item.label}</span>
                      <span className="text-sm font-medium text-gray-700 text-right max-w-[60%]">{item.value}</span>
                    </div>
                  ))}
                </div>
              </CardContent>
            </Card>
          </motion.div>

          {/* Production Targets */}
          <motion.div initial="hidden" animate="visible" variants={fadeUp}>
            <Card className="border border-gray-100 bg-white">
              <CardContent className="p-5">
                <h3 className="text-xs font-bold uppercase text-gray-400 tracking-wider mb-4">Production Targets</h3>
                <div className="space-y-3">
                  {[
                    { label: "Metric", value: species ? `${species.productionMetric} (${species.productionUnit})` : "—" },
                    { label: "Expected Yield", value: flock.expectedYield || species?.expectedYield || "—" },
                    { label: "Expected Weight", value: flock.expectedWeight || species?.breedDetails[flock.breed]?.matureWeight || "—" },
                    { label: "Expected Revenue", value: flock.expectedRevenue ? `KES ${Number(flock.expectedRevenue).toLocaleString()}` : species?.revenuePerUnit || "—" },
                    { label: "Break-even", value: species ? `~${species.breakEvenMonths} months` : "—" },
                  ].map((item) => (
                    <div key={item.label} className="flex items-center justify-between">
                      <span className="text-xs text-gray-400">{item.label}</span>
                      <span className="text-sm font-medium text-gray-700">{item.value}</span>
                    </div>
                  ))}
                </div>
              </CardContent>
            </Card>
          </motion.div>
        </div>

        {/* Vaccination Schedule */}
        {vaccinations.length > 0 && (
          <motion.div initial="hidden" animate="visible" variants={fadeUp}>
            <Card className="border border-gray-100 bg-white">
              <CardContent className="p-5">
                <div className="flex items-center justify-between mb-4">
                  <h3 className="text-xs font-bold uppercase text-gray-400 tracking-wider flex items-center gap-1.5">
                    <Syringe className="h-3.5 w-3.5" /> Vaccination Schedule
                  </h3>
                  <div className="flex items-center gap-3 text-[10px] text-gray-400">
                    <span>{completedVax.length} completed</span>
                    <span>{pendingVax.length} pending</span>
                  </div>
                </div>
                <div className="space-y-2">
                  {vaccinations.map((vax: any) => (
                    <div
                      key={vax.id}
                      className="flex items-center gap-4 p-3 rounded-xl border border-gray-50 bg-gray-50/50"
                    >
                      <div className={cn(
                        "flex h-8 w-8 items-center justify-center rounded-lg flex-shrink-0",
                        vax.status === "completed" ? "bg-emerald-100 text-emerald-600" : "bg-amber-50 text-amber-600"
                      )}>
                        <Syringe className="h-4 w-4" />
                      </div>
                      <div className="flex-1 min-w-0">
                        <div className="text-sm font-semibold text-gray-900">{vax.vaccineName}</div>
                        {vax.notes && <div className="text-[11px] text-gray-400">{vax.notes}</div>}
                      </div>
                      <div className="text-right flex-shrink-0">
                        <div className="text-xs font-semibold text-gray-700">
                          {new Date(vax.scheduledDate).toLocaleDateString("en-GB", { day: "numeric", month: "short" })}
                        </div>
                        {vax.completedDate && (
                          <div className="text-[10px] text-emerald-600">
                            Done {new Date(vax.completedDate).toLocaleDateString("en-GB", { day: "numeric", month: "short" })}
                          </div>
                        )}
                      </div>
                      <Badge
                        variant={vax.status === "completed" ? "default" : "outline"}
                        className={cn(
                          "text-[10px] flex-shrink-0",
                          vax.status === "completed" ? "bg-emerald-50 text-emerald-700" : "bg-amber-50 text-amber-700"
                        )}
                      >
                        {vax.status}
                      </Badge>
                    </div>
                  ))}
                </div>
              </CardContent>
            </Card>
          </motion.div>
        )}

        {/* Notes */}
        {flock.notes && (
          <motion.div initial="hidden" animate="visible" variants={fadeUp}>
            <Card className="border border-gray-100 bg-white">
              <CardContent className="p-5">
                <h3 className="text-xs font-bold uppercase text-gray-400 tracking-wider mb-2">Notes</h3>
                <p className="text-sm text-gray-700">{flock.notes}</p>
              </CardContent>
            </Card>
          </motion.div>
        )}
      </div>
    );
  }

  // List View
  return (
    <div className="space-y-6">
      {/* Header */}
      <motion.div initial="hidden" animate="visible" variants={fadeUp} className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 tracking-tight">Livestock</h1>
          <p className="text-sm text-gray-400 mt-0.5">
            Manage all your farm animals — poultry, cattle, goats, fish & more
          </p>
        </div>
        <div className="flex items-center gap-2">
          <Button onClick={loadFlocks} variant="ghost" size="sm" className="gap-1.5 text-gray-400">
            <RefreshCw className="h-4 w-4" />
          </Button>
          <Button onClick={() => setShowForm(true)} className="bg-emerald-700 hover:bg-emerald-800 cursor-pointer">
            <Plus className="h-4 w-4 mr-2" />Add Livestock
          </Button>
        </div>
      </motion.div>

      {/* KPI Cards */}
      <motion.div initial="hidden" animate="visible" variants={stagger} className="grid grid-cols-2 lg:grid-cols-4 gap-4">
        {[
          { label: "Total Animals", value: totalAnimals.toLocaleString(), icon: <PawPrint className="h-5 w-5" />, change: `${activeFlocks} active groups`, positive: true },
          { label: "Species Types", value: uniqueCategories.toString(), icon: <Beef className="h-5 w-5" />, change: `${flocks.length} total flocks`, positive: true },
          { label: "Mortality Rate", value: `${mortalityRate}%`, icon: <Heart className="h-5 w-5" />, change: `${totalMortality} total deaths`, positive: Number(mortalityRate) <= 3 },
          { label: "Total Investment", value: totalInvestment > 0 ? `KES ${(totalInvestment / 1000).toFixed(0)}k` : "—", icon: <DollarSign className="h-5 w-5" />, change: `${flocks.length} flocks`, positive: true },
        ].map((kpi) => (
          <motion.div key={kpi.label} variants={fadeUp}>
            <Card className="border border-gray-100 bg-white p-5">
              <div className="flex items-start justify-between">
                <div className="flex-1">
                  <p className="text-[11px] font-bold uppercase tracking-widest text-gray-400">{kpi.label}</p>
                  <p className="mt-2 text-3xl font-bold text-gray-900">{kpi.value}</p>
                  <Badge variant="default" className={`mt-2 ${kpi.positive ? "bg-emerald-50 text-emerald-700" : "bg-amber-50 text-amber-700"}`}>
                    {kpi.change}
                  </Badge>
                </div>
                <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-800">
                  {kpi.icon}
                </div>
              </div>
            </Card>
          </motion.div>
        ))}
      </motion.div>

      {/* Category Filter Tabs */}
      <motion.div initial="hidden" animate="visible" variants={fadeUp} className="flex flex-wrap items-center gap-2">
        <button
          onClick={() => setFilterSpecies("all")}
          className={`px-4 py-2 rounded-full text-sm font-medium transition-all cursor-pointer ${
            filterSpecies === "all" ? "bg-emerald-700 text-white shadow-md" : "bg-gray-100 text-gray-700 hover:bg-gray-200"
          }`}
        >
          All ({flocks.length})
        </button>
        {categories.map((cat) => {
          const count = flocks.filter((f: any) => f.category === cat.id).length;
          if (count === 0) return null;
          return (
            <button
              key={cat.id}
              onClick={() => setFilterSpecies(cat.id)}
              className={`px-4 py-2 rounded-full text-sm font-medium transition-all cursor-pointer ${
                filterSpecies === cat.id ? "bg-emerald-700 text-white shadow-md" : "bg-gray-100 text-gray-700 hover:bg-gray-200"
              }`}
            >
              {cat.label} ({count})
            </button>
          );
        })}
      </motion.div>

      {/* Search */}
      <motion.div initial="hidden" animate="visible" variants={fadeUp} className="relative">
        <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
        <input
          placeholder="Search by name, breed, or species..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          className="w-full h-12 rounded-xl border border-gray-200 pl-10 pr-4 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
        />
      </motion.div>

      {/* Flock Grid */}
      {filtered.length === 0 ? (
        <EmptyState
          title="No livestock yet"
          description="Add your first flock to start tracking your livestock."
        />
      ) : (
        <motion.div initial="hidden" animate="visible" variants={stagger} className="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
          {filtered.map((f: any) => {
            const species = speciesTemplates[f.type];
            const mortality = f.initialCount > 0 ? ((f.mortality / f.initialCount) * 100).toFixed(1) : "0";
            const mortalityRating = getMortalityRating(Number(mortality));
            const pendingVax = (f.vaccinations || []).filter((v: any) => v.status === "pending").length;

            return (
              <motion.div key={f.id} variants={fadeUp} whileHover={{ y: -4 }}>
                <Card
                  className="border border-gray-100 hover:shadow-xl hover:border-emerald-200 transition-all duration-300 cursor-pointer"
                  onClick={() => setSelectedFlock(f)}
                >
                  <CardContent className="p-5">
                    {/* Header */}
                    <div className="flex items-start justify-between">
                      <div className="flex items-center gap-3">
                        <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">
                          <SpeciesIcon speciesId={f.type} />
                        </div>
                        <div>
                          <h3 className="text-base font-bold text-gray-900">{f.name}</h3>
                          <p className="text-xs text-gray-400">{f.breed || species?.name || f.type}</p>
                        </div>
                      </div>
                      <div className="flex items-center gap-1">
                        <Badge variant={f.status === "active" ? "default" : "outline"} className={
                          f.status === "active" ? "bg-emerald-50 text-emerald-700 border-emerald-200" : ""
                        }>
                          {f.status}
                        </Badge>
                        <button
                          onClick={(e) => { e.stopPropagation(); handleDelete(f.id); }}
                          className="p-1.5 rounded-lg hover:bg-red-50 text-gray-300 hover:text-red-500 transition-colors cursor-pointer"
                        >
                          <Trash2 className="h-3.5 w-3.5" />
                        </button>
                      </div>
                    </div>

                    {/* Stats Grid */}
                    <div className="mt-4 grid grid-cols-3 gap-3">
                      <div className="rounded-lg bg-gray-50 p-2.5 text-center">
                        <p className="text-lg font-bold text-gray-900">{(f.currentCount || 0).toLocaleString()}</p>
                        <p className="text-[10px] font-semibold uppercase text-gray-400">Current</p>
                      </div>
                      <div className="rounded-lg bg-gray-50 p-2.5 text-center">
                        <p className="text-lg font-bold text-red-600">{f.mortality || 0}</p>
                        <p className="text-[10px] font-semibold uppercase text-gray-400">Deaths</p>
                      </div>
                      <div className="rounded-lg bg-gray-50 p-2.5 text-center">
                        <p className="text-lg font-bold text-gray-900">{getAge(f.hatchDate)}</p>
                        <p className="text-[10px] font-semibold uppercase text-gray-400">Age</p>
                      </div>
                    </div>

                    {/* Mortality Bar */}
                    <div className="mt-3">
                      <div className="flex items-center justify-between mb-1">
                        <span className="text-[10px] font-semibold uppercase text-gray-400">Mortality</span>
                        <Badge variant="default" className={`text-[10px] ${mortalityRating.bg} ${mortalityRating.color}`}>
                          {mortality}% — {mortalityRating.label}
                        </Badge>
                      </div>
                      <div className="h-1.5 overflow-hidden rounded-full bg-gray-100">
                        <div
                          className={`h-full rounded-full transition-all ${
                            Number(mortality) <= 3 ? "bg-emerald-500" : Number(mortality) <= 5 ? "bg-amber-500" : "bg-red-500"
                          }`}
                          style={{ width: `${Math.min(Number(mortality) * 10, 100)}%` }}
                        />
                      </div>
                    </div>

                    {/* Quick Info */}
                    <div className="mt-3 flex items-center justify-between text-[11px] text-gray-400">
                      <div className="flex items-center gap-3">
                        {f.location && (
                          <span className="flex items-center gap-1">
                            <MapPin className="h-3 w-3" />{f.location}
                          </span>
                        )}
                        {f.hatchDate && (
                          <span className="flex items-center gap-1">
                            <Calendar className="h-3 w-3" />{new Date(f.hatchDate).toLocaleDateString()}
                          </span>
                        )}
                      </div>
                      {pendingVax > 0 && (
                        <span className="flex items-center gap-1 text-amber-600">
                          <Syringe className="h-3 w-3" />{pendingVax} due
                        </span>
                      )}
                    </div>

                    {/* Cost & Purpose */}
                    {(f.costPerAnimal || f.purpose) && (
                      <div className="mt-2 flex items-center gap-3 text-[10px] text-gray-400">
                        {f.costPerAnimal && (
                          <span className="flex items-center gap-1">
                            <DollarSign className="h-3 w-3" />KES {Number(f.costPerAnimal).toLocaleString()}/head
                          </span>
                        )}
                        {f.purpose && (
                          <span className="px-1.5 py-0.5 rounded bg-gray-50">{getPurposeLabel(f.purpose)}</span>
                        )}
                      </div>
                    )}

                    {/* View Detail Hint */}
                    <div className="mt-3 flex items-center justify-end text-[10px] text-emerald-600 font-medium">
                      <Eye className="h-3 w-3 mr-1" />
                      View Details
                    </div>
                  </CardContent>
                </Card>
              </motion.div>
            );
          })}
        </motion.div>
      )}

      {/* Create Flock Form Modal */}
      {showForm && (
        <CreateFlockForm
          onSubmit={handleCreate}
          onCancel={() => setShowForm(false)}
        />
      )}
    </div>
  );
}
