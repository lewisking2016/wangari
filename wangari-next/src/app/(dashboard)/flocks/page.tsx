"use client";
import * as React from "react";
import { Bird, Plus, Trash2, Search } from "lucide-react";
import { PageHeader } from "@/components/shared/page-header";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { EmptyState } from "@/components/shared/empty-state";

export default function FlocksPage() {
  const [flocks, setFlocks] = React.useState<any[]>([]);
  const [loading, setLoading] = React.useState(true);
  const [search, setSearch] = React.useState("");
  const [showForm, setShowForm] = React.useState(false);
  const [form, setForm] = React.useState({ name: "", breed: "", type: "layers", initialCount: "" });

  const loadFlocks = () => {
    fetch("/api/flocks").then(r => r.json()).then(d => { setFlocks(d); setLoading(false); }).catch(() => setLoading(false));
  };
  React.useEffect(() => { loadFlocks(); }, []);

  const filtered = flocks.filter(f => f.name.toLowerCase().includes(search.toLowerCase()));

  const handleCreate = async () => {
    await fetch("/api/flocks", { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ ...form, initialCount: Number(form.initialCount) }) });
    setForm({ name: "", breed: "", type: "layers", initialCount: "" });
    setShowForm(false);
    loadFlocks();
  };

  const handleDelete = async (id: number) => {
    if (!confirm("Delete this flock?")) return;
    await fetch("/api/flocks/" + id, { method: "DELETE" });
    loadFlocks();
  };

  if (loading) return <div className="flex items-center justify-center h-64"><div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#166534]" /></div>;

  return (
    <div className="space-y-6 animate-fade-in">
      <PageHeader title="Flocks" description="Manage your flocks and poultry" action={<Button onClick={() => setShowForm(!showForm)}><Plus className="h-4 w-4 mr-2" />Add Flock</Button>} />

      {showForm && (
        <Card><CardContent className="p-6">
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
            <input placeholder="Flock name" value={form.name} onChange={e => setForm({...form, name: e.target.value})} className="rounded-xl border border-[#E5E7EB] px-4 py-2.5 text-sm" />
            <input placeholder="Breed" value={form.breed} onChange={e => setForm({...form, breed: e.target.value})} className="rounded-xl border border-[#E5E7EB] px-4 py-2.5 text-sm" />
            <select value={form.type} onChange={e => setForm({...form, type: e.target.value})} className="rounded-xl border border-[#E5E7EB] px-4 py-2.5 text-sm"><option value="layers">Layers</option><option value="broilers">Broilers</option></select>
            <input type="number" placeholder="Bird count" value={form.initialCount} onChange={e => setForm({...form, initialCount: e.target.value})} className="rounded-xl border border-[#E5E7EB] px-4 py-2.5 text-sm" />
          </div>
          <div className="mt-4 flex gap-2"><Button onClick={handleCreate}>Save</Button><Button variant="outline" onClick={() => setShowForm(false)}>Cancel</Button></div>
        </CardContent></Card>
      )}

      <div className="relative"><Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-[#94A3B8]" /><input placeholder="Search flocks..." value={search} onChange={e => setSearch(e.target.value)} className="w-full rounded-xl border border-[#E5E7EB] bg-white pl-10 pr-4 py-2.5 text-sm" /></div>

      {filtered.length === 0 ? <EmptyState title="No flocks" description="Add your first flock to get started." /> : (
        <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
          {filtered.map(f => (
            <Card key={f.id} className="hover:shadow-lg transition-all duration-300">
              <CardContent className="p-6">
                <div className="flex items-start justify-between">
                  <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-[#F0FDF4] text-[#166534]"><Bird className="h-6 w-6" /></div>
                  <Badge variant={f.status === "active" ? "default" : "secondary"}>{f.status}</Badge>
                </div>
                <h3 className="mt-4 text-lg font-bold text-[#0F172A]">{f.name}</h3>
                <p className="text-sm text-[#64748B]">{f.breed || f.type}</p>
                <div className="mt-4 grid grid-cols-2 gap-4 text-sm">
                  <div><p className="text-[#94A3B8]">Initial</p><p className="font-semibold text-[#0F172A]">{f.initialCount.toLocaleString()}</p></div>
                  <div><p className="text-[#94A3B8]">Current</p><p className="font-semibold text-[#0F172A]">{f.currentCount.toLocaleString()}</p></div>
                  <div><p className="text-[#94A3B8]">Mortality</p><p className="font-semibold text-red-600">{f.mortality}</p></div>
                  <div><p className="text-[#94A3B8]">Started</p><p className="font-semibold text-[#0F172A]">{f.hatchDate ? new Date(f.hatchDate).toLocaleDateString() : "-"}</p></div>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      )}
    </div>
  );
}