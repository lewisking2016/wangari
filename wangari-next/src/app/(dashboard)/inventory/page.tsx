"use client";

import * as React from "react";
import { Package, Plus, AlertTriangle } from "lucide-react";
import { PageHeader } from "@/components/shared/page-header";
import { Button } from "@/components/ui/button";
import { Card, CardHeader, CardTitle, CardContent } from "@/components/ui/card";
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from "@/components/ui/table";
import { Badge } from "@/components/ui/badge";

const mockInventory = [
  { id: 1, item: "Kienyeji Mash", category: "Feed", qty: 120, unit: "bags", reorder: 50 },
  { id: 2, item: "Layers Mash", category: "Feed", qty: 85, unit: "bags", reorder: 40 },
  { id: 3, item: "IBD Vaccine", category: "Medication", qty: 5, unit: "bottles", reorder: 10 },
  { id: 4, item: "Egg Trays", category: "Packaging", qty: 200, unit: "pieces", reorder: 50 },
  { id: 5, item: "Dewormer", category: "Medication", qty: 3, unit: "bottles", reorder: 5 },
];

export default function InventoryPage() {
  return (
    <div className="space-y-6 animate-fade-in">
      <PageHeader
        title="Inventory"
        description="Manage feed, medication, equipment, and packaging stock."
        action={<Button><Plus className="h-4 w-4" /> Add Item</Button>}
      />
      <Card>
        <CardHeader className="flex flex-row items-center justify-between">
          <CardTitle>Stock Items</CardTitle>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Item</TableHead>
                <TableHead>Category</TableHead>
                <TableHead>Quantity</TableHead>
                <TableHead>Status</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {mockInventory.map((item) => (
                <TableRow key={item.id}>
                  <TableCell className="font-medium">{item.item}</TableCell>
                  <TableCell>{item.category}</TableCell>
                  <TableCell>{item.qty} {item.unit}</TableCell>
                  <TableCell>
                    {item.qty <= item.reorder ? (
                      <Badge variant="danger"><AlertTriangle className="h-3 w-3 mr-1" /> Low Stock</Badge>
                    ) : (
                      <Badge variant="success">In Stock</Badge>
                    )}
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
