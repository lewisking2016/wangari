"use client";

import * as React from "react";
import { Bird, Plus, Search } from "lucide-react";
import { PageHeader } from "@/components/shared/page-header";
import { EmptyState } from "@/components/shared/empty-state";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent } from "@/components/ui/card";

const mockFlocks = [
  { id: 1, name: "Layer Flock A", breed: "Isa Brown", count: 500, status: "active", type: "layers" },
  { id: 2, name: "Broiler Batch 12", breed: "Cobb 500", count: 800, status: "active", type: "broilers" },
  { id: 3, name: "Chick Nursery", breed: "Rhode Island Red", count: 200, status: "active", type: "chicks" },
  { id: 4, name: "Layer Flock B", breed: "Lohmann Brown", count: 450, status: "active", type: "layers" },
];

export default function FlocksPage() {
  const [search, setSearch] = React.useState("");

  const filtered = mockFlocks.filter(
    (f) =>
      f.name.toLowerCase().includes(search.toLowerCase()) ||
      f.breed.toLowerCase().includes(search.toLowerCase())
  );

  return (
    <div className="space-y-6 animate-fade-in">
      <PageHeader
        title="Flocks"
        description="Manage your poultry flocks and track their performance."
        action={
          <Button>
            <Plus className="h-4 w-4" /> Add Flock
          </Button>
        }
      />

      <div className="relative max-w-sm">
        <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-wangari-muted" />
        <Input
          placeholder="Search flocks..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          className="pl-10"
        />
      </div>

      {filtered.length === 0 ? (
        <EmptyState
          icon={<Bird className="h-8 w-8" />}
          title="No flocks yet"
          description="Add your first flock to start tracking production, health, and feeding."
          action={<Button><Plus className="h-4 w-4" /> Add First Flock</Button>}
        />
      ) : (
        <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
          {filtered.map((flock) => (
            <Card key={flock.id} className="cursor-pointer hover:border-wangari-green-300">
              <CardContent className="p-5">
                <div className="flex items-start justify-between mb-3">
                  <div>
                    <h3 className="font-bold text-wangari-heading">{flock.name}</h3>
                    <p className="text-sm text-wangari-muted">{flock.breed}</p>
                  </div>
                  <Badge variant={flock.type === "layers" ? "default" : flock.type === "broilers" ? "info" : "warning"}>
                    {flock.type}
                  </Badge>
                </div>
                <div className="flex items-center justify-between pt-3 border-t border-wangari-border">
                  <div>
                    <p className="text-2xl font-bold text-wangari-heading font-serif">{flock.count}</p>
                    <p className="text-xs text-wangari-muted">birds</p>
                  </div>
                  <Badge variant="success">Active</Badge>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      )}
    </div>
  );
}
