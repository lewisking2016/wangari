"use client";

import * as React from "react";
import { Users, Plus } from "lucide-react";
import { PageHeader } from "@/components/shared/page-header";
import { Button } from "@/components/ui/button";
import { Card, CardHeader, CardTitle, CardContent } from "@/components/ui/card";
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from "@/components/ui/table";
import { Badge } from "@/components/ui/badge";
import { Avatar } from "@/components/ui/avatar";
import { formatCurrency } from "@/lib/utils";

const mockWorkers = [
  { id: 1, name: "John Kipchoge", role: "Caretaker", wage: 800, status: "active" },
  { id: 2, name: "Mary Njeri", role: "Supervisor", wage: 1200, status: "active" },
  { id: 3, name: "Peter Otieno", role: "Driver", wage: 1000, status: "active" },
  { id: 4, name: "Grace Wambui", role: "Casual", wage: 600, status: "active" },
];

export default function WorkersPage() {
  return (
    <div className="space-y-6 animate-fade-in">
      <PageHeader
        title="Workers"
        description="Manage farm workers, attendance, and wages."
        action={<Button><Plus className="h-4 w-4" /> Add Worker</Button>}
      />
      <Card>
        <CardHeader><CardTitle>Team Members</CardTitle></CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Name</TableHead>
                <TableHead>Role</TableHead>
                <TableHead>Daily Wage</TableHead>
                <TableHead>Status</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {mockWorkers.map((w) => (
                <TableRow key={w.id}>
                  <TableCell>
                    <div className="flex items-center gap-3">
                      <Avatar name={w.name} size="sm" />
                      <span className="font-medium">{w.name}</span>
                    </div>
                  </TableCell>
                  <TableCell>{w.role}</TableCell>
                  <TableCell>{formatCurrency(w.wage)}</TableCell>
                  <TableCell><Badge variant="success">Active</Badge></TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  );
}
