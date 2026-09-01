"use client";

import * as React from "react";
import { ShoppingCart, Plus, Search } from "lucide-react";
import { PageHeader } from "@/components/shared/page-header";
import { EmptyState } from "@/components/shared/empty-state";
import { Button } from "@/components/ui/button";
import { Card, CardHeader, CardTitle, CardContent } from "@/components/ui/card";
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from "@/components/ui/table";
import { Badge } from "@/components/ui/badge";
import { formatCurrency } from "@/lib/utils";

const mockSales = [
  { id: 1, date: "2026-08-30", customer: "Mary Wanjiku", items: "50 trays eggs", total: 4500, status: "paid" },
  { id: 2, date: "2026-08-29", customer: "James Ochieng", items: "200 broilers", total: 85000, status: "paid" },
  { id: 3, date: "2026-08-28", customer: "Grace Akinyi", items: "30 trays eggs", total: 2700, status: "credit" },
  { id: 4, date: "2026-08-27", customer: "Peter Mwangi", items: "100 broilers", total: 42500, status: "partial" },
];

export default function SalesPage() {
  return (
    <div className="space-y-6 animate-fade-in">
      <PageHeader
        title="Sales"
        description="Track customer orders, payments, and credit."
        action={<Button><Plus className="h-4 w-4" /> New Sale</Button>}
      />
      <Card>
        <CardHeader><CardTitle>Recent Sales</CardTitle></CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Date</TableHead>
                <TableHead>Customer</TableHead>
                <TableHead>Items</TableHead>
                <TableHead>Total</TableHead>
                <TableHead>Status</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {mockSales.map((sale) => (
                <TableRow key={sale.id}>
                  <TableCell className="font-medium">{sale.date}</TableCell>
                  <TableCell>{sale.customer}</TableCell>
                  <TableCell>{sale.items}</TableCell>
                  <TableCell className="font-bold">{formatCurrency(sale.total)}</TableCell>
                  <TableCell>
                    <Badge variant={sale.status === "paid" ? "success" : sale.status === "credit" ? "danger" : "warning"}>
                      {sale.status}
                    </Badge>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  );
}
