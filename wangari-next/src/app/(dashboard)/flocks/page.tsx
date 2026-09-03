"use client";
import * as React from "react";
import { motion } from "framer-motion";
import {
  Bird,
  Plus,
  Trash2,
  Search,
  Heart,
  Syringe,
  Calendar,
  RefreshCw,
  Beef,
  Egg,
  Droplets,
  Flower,
} from "lucide-react";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { EmptyState } from "@/components/shared/empty-state";
import api from "@/lib/api-client";
import { CreateFlockForm } from "@/components/flocks/CreateFlockForm";
import { speciesTemplates, getSpeciesCategories, getSpeciesIcon } from "@/lib/species-templates";

const fadeUp = { hidden: { opacity: 0, y: 16 }, visible: { opacity: 1, y: 0, transition: { duration: 0.4, ease: [0.22, 1, 0.36, 1] as [number, number, number, number] } } };
const stagger = { hidden: {}, visible: { transition: { staggerChildren: 0.06 } } };

function SpeciesIcon({ speciesId }: { speciesId: string }) {
  const Icon = getSpeciesIcon(speciesId);
  return <Icon className="h-5 w-5" />;
}

function getMortalityRating(rate: number): { label: string; color: string; bg: string } {
  if (rate <= 1) return { label: "Normal", color: "text-wangari-green-700", bg: "bg-wangari-green-50" };
  if (rate <= 3) return { label: "Acceptable", color: "text-wangari-green-700", bg: "bg-wangari-green-50" };
  if (rate <= 5) return { label: "Watch", color: "text-badge-yellow-text", bg: "bg-badge-yellow-bg" };
  return { label: "Critical", color: "text-badge-red-text", bg: "bg-badge-red-bg" };
}

function getAge(hatchDate: string | null): string {
  if (!hatchDate) return "—";
  const days = Math.floor((Date.now() - new Date(hatchDate).getTime()) / 86400000);
  if (days < 30) return `${days}d`;
  if (days < 365) return `${Math.floor(days / 30)}mo`;
  return `${Math.floor(days / 365)}y ${Math.floor((days % 365) / 30)}mo`;
}

export default function FlocksPage() {
  const [flocks, setFlocks] = React.useState<any[]>([]);
  const [loading, setLoading] = React.useState(true);
  const [showForm, setShowForm] = React.useState(false);
  const [filterSpecies, setFilterSpecies] = React.useState<string>("all");
  const [search, setSearch] = React.useState("");

  const loadFlocks = () => {
    api.get("/api/flocks").then(d => {
      setFlocks(Array.isArray(d) ? d : []);
      setLoading(false);
    }).catch(() => setLoading(false));
  };
  React.useEffect(() => { loadFlocks(); }, []);

  const filtered = flocks.filter((f: any) => {
    const matchesSearch = !search || f.name.toLowerCase().includes(search.toLowerCase());
    const matchesSpecies = filterSpecies === "all" || f.type === filterSpecies;
    return matchesSearch && matchesSpecies;
  });

  // KPI calculations
  const totalAnimals = flocks.reduce((s: number, f: any) => s + (f.currentCount || 0), 0);
  const totalInitial = flocks.reduce((s: number, f: any) => s + (f.initialCount || 0), 0);
  const totalMortality = flocks.reduce((s: number, f: any) => s + (f.mortality || 0), 0);
  const mortalityRate = totalInitial > 0 ? ((totalMortality / totalInitial) * 100).toFixed(1) : "0";
  const activeFlocks = flocks.filter((f: any) => f.status === "active").length;
  const uniqueSpecies = [...new Set(flocks.map((f: any) => f.type))].length;

  const categories = getSpeciesCategories();

  const handleCreate = async (data: any) => {
    await api.post("/api/flocks", {
      name: data.name,
      breed: data.breed,
      type: data.type,
      initialCount: data.initialCount,
      hatchDate: data.hatchDate,
    });
    setShowForm(false);
    loadFlocks();
  };

  const handleDelete = async (id: number) => {
    if (!confirm("Delete this flock? This action cannot be undone.")) return;
    await api.delete("/api/flocks/" + id);
    loadFlocks();
  };

  if (loading) {
    return (
      <div className="flex flex-col items-center justify-center h-96 gap-4">
        <div className="relative">
          <div className="h-12 w-12 rounded-full border-[3px] border-wangari-green-200 border-t-wangari-green-600 animate-spin" />
          <Bird className="absolute left-1/2 top-1/2 h-5 w-5 -translate-x-1/2 -translate-y-1/2 text-wangari-green-600" />
        </div>
        <p className="text-sm text-wangari-muted">Loading your flocks...</p>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <motion.div initial="hidden" animate="visible" variants={fadeUp} className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-wangari-heading tracking-tight">Livestock</h1>
          <p className="text-sm text-wangari-muted mt-0.5">
            Manage all your farm animals — poultry, cattle, goats, fish & more
          </p>
        </div>
        <div className="flex items-center gap-2">
          <Button onClick={loadFlocks} variant="ghost" size="sm" className="gap-1.5 text-wangari-muted">
            <RefreshCw className="h-4 w-4" />
          </Button>
          <Button onClick={() => setShowForm(true)} className="bg-wangari-green-800 hover:bg-wangari-green-900 cursor-pointer">
            <Plus className="h-4 w-4 mr-2" />Add Flock
          </Button>
        </div>
      </motion.div>

      {/* KPI Cards */}
      <motion.div initial="hidden" animate="visible" variants={stagger} className="grid grid-cols-2 lg:grid-cols-4 gap-4">
        {[
          { label: "Total Animals", value: totalAnimals.toLocaleString(), icon: <Bird className="h-5 w-5" />, change: `${activeFlocks} active groups`, positive: true },
          { label: "Species Types", value: uniqueSpecies.toString(), icon: <Beef className="h-5 w-5" />, change: `${flocks.length} total flocks`, positive: true },
          { label: "Mortality Rate", value: `${mortalityRate}%`, icon: <Heart className="h-5 w-5" />, change: `${totalMortality} total deaths`, positive: Number(mortalityRate) <= 3 },
          { label: "Health Status", value: Number(mortalityRate) <= 3 ? "Good" : "Watch", icon: <Syringe className="h-5 w-5" />, change: Number(mortalityRate) <= 3 ? "All flocks healthy" : "Needs attention", positive: Number(mortalityRate) <= 3 },
        ].map((kpi) => (
          <motion.div key={kpi.label} variants={fadeUp}>
            <Card className="border border-wangari-border bg-white p-5">
              <div className="flex items-start justify-between">
                <div className="flex-1">
                  <p className="text-[11px] font-bold uppercase tracking-widest text-wangari-muted">{kpi.label}</p>
                  <p className="mt-2 text-3xl font-bold text-wangari-heading">{kpi.value}</p>
                  <Badge variant="default" className={`mt-2 ${kpi.positive ? "bg-wangari-green-50 text-wangari-green-700" : "bg-badge-yellow-bg text-badge-yellow-text"}`}>
                    {kpi.change}
                  </Badge>
                </div>
                <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-wangari-green-50 text-wangari-green-800">
                  {kpi.icon}
                </div>
              </div>
            </Card>
          </motion.div>
        ))}
      </motion.div>

      {/* Species Filter Tabs */}
      <motion.div initial="hidden" animate="visible" variants={fadeUp} className="flex flex-wrap items-center gap-2">
        <button
          onClick={() => setFilterSpecies("all")}
          className={`px-4 py-2 rounded-full text-sm font-medium transition-all cursor-pointer ${
            filterSpecies === "all" ? "bg-wangari-green-800 text-white shadow-md" : "bg-gray-100 text-wangari-heading hover:bg-gray-200"
          }`}
        >
          All ({flocks.length})
        </button>
        {categories.map((cat) => {
          const count = flocks.filter((f: any) => {
            const tmpl = speciesTemplates[f.type];
            return tmpl?.category === cat.id;
          }).length;
          if (count === 0) return null;
          return (
            <button
              key={cat.id}
              onClick={() => setFilterSpecies(cat.id)}
              className={`px-4 py-2 rounded-full text-sm font-medium transition-all cursor-pointer ${
                filterSpecies === cat.id ? "bg-wangari-green-800 text-white shadow-md" : "bg-gray-100 text-wangari-heading hover:bg-gray-200"
              }`}
            >
              {cat.label} ({count})
            </button>
          );
        })}
      </motion.div>

      {/* Search */}
      <motion.div initial="hidden" animate="visible" variants={fadeUp} className="relative">
        <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-wangari-muted" />
        <input
          placeholder="Search flocks by name, breed, or species..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          className="w-full h-12 rounded-xl border border-wangari-border pl-10 pr-4 text-sm focus:ring-2 focus:ring-wangari-green-500/20 focus:border-wangari-green-500 transition-all"
        />
      </motion.div>

      {/* Flock Grid */}
      {filtered.length === 0 ? (
        <EmptyState
          title="No flocks yet"
          description="Add your first flock to start tracking your livestock."
        />
      ) : (
        <motion.div initial="hidden" animate="visible" variants={stagger} className="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
          {filtered.map((f: any) => {
            const Icon = getSpeciesIcon(f.type);
            const species = speciesTemplates[f.type];
            const mortality = f.initialCount > 0 ? ((f.mortality / f.initialCount) * 100).toFixed(1) : "0";
            const mortalityRating = getMortalityRating(Number(mortality));

            return (
              <motion.div key={f.id} variants={fadeUp} whileHover={{ y: -4 }}>
                <Card className="border border-wangari-border hover:shadow-xl hover:border-wangari-green-300 transition-all duration-300">
                  <CardContent className="p-5">
                    {/* Header */}
                    <div className="flex items-start justify-between">
                      <div className="flex items-center gap-3">
                        <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-wangari-green-50 text-wangari-green-700">
                          <span className="text-xl"><SpeciesIcon speciesId={f.type} /></span>
                        </div>
                        <div>
                          <h3 className="text-base font-bold text-wangari-heading">{f.name}</h3>
                          <p className="text-xs text-wangari-muted">{f.breed || species?.name || f.type}</p>
                        </div>
                      </div>
                      <div className="flex items-center gap-1">
                        <Badge variant={f.status === "active" ? "default" : "outline"} className={
                          f.status === "active" ? "bg-wangari-green-50 text-wangari-green-700 border-wangari-green-200" : ""
                        }>
                          {f.status}
                        </Badge>
                        <button onClick={() => handleDelete(f.id)} className="p-1.5 rounded-lg hover:bg-red-50 text-wangari-muted hover:text-red-500 transition-colors cursor-pointer">
                          <Trash2 className="h-3.5 w-3.5" />
                        </button>
                      </div>
                    </div>

                    {/* Stats Grid */}
                    <div className="mt-4 grid grid-cols-3 gap-3">
                      <div className="rounded-lg bg-gray-50 p-2.5 text-center">
                        <p className="text-lg font-bold text-wangari-heading">{(f.currentCount || 0).toLocaleString()}</p>
                        <p className="text-[10px] font-semibold uppercase text-wangari-muted">Current</p>
                      </div>
                      <div className="rounded-lg bg-gray-50 p-2.5 text-center">
                        <p className="text-lg font-bold text-red-600">{f.mortality || 0}</p>
                        <p className="text-[10px] font-semibold uppercase text-wangari-muted">Deaths</p>
                      </div>
                      <div className="rounded-lg bg-gray-50 p-2.5 text-center">
                        <p className="text-lg font-bold text-wangari-heading">{getAge(f.hatchDate)}</p>
                        <p className="text-[10px] font-semibold uppercase text-wangari-muted">Age</p>
                      </div>
                    </div>

                    {/* Mortality Bar */}
                    <div className="mt-3">
                      <div className="flex items-center justify-between mb-1">
                        <span className="text-[10px] font-semibold uppercase text-wangari-muted">Mortality</span>
                        <Badge variant="default" className={`text-[10px] ${mortalityRating.bg} ${mortalityRating.color}`}>
                          {mortality}% — {mortalityRating.label}
                        </Badge>
                      </div>
                      <div className="h-1.5 overflow-hidden rounded-full bg-wangari-border">
                        <div
                          className={`h-full rounded-full transition-all ${
                            Number(mortality) <= 3 ? "bg-wangari-green-500" : Number(mortality) <= 5 ? "bg-yellow-500" : "bg-red-500"
                          }`}
                          style={{ width: `${Math.min(Number(mortality) * 10, 100)}%` }}
                        />
                      </div>
                    </div>

                    {/* Quick Info */}
                    <div className="mt-3 flex items-center gap-3 text-[11px] text-wangari-muted">
                      {f.hatchDate && (
                        <span className="flex items-center gap-1">
                          <Calendar className="h-3 w-3" />
                          Started {new Date(f.hatchDate).toLocaleDateString()}
                        </span>
                      )}
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
