"use client";

import { Settings } from "lucide-react";
import { PageHeader } from "@/components/shared/page-header";
import { Card, CardHeader, CardTitle, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";

export default function SettingsPage() {
  return (
    <div className="space-y-6 animate-fade-in">
      <PageHeader title="Settings" description="Manage your farm profile and preferences." />
      <Card className="max-w-2xl">
        <CardHeader><CardTitle>Farm Profile</CardTitle></CardHeader>
        <CardContent className="space-y-4">
          <div className="space-y-2">
            <Label>Farm Name</Label>
            <Input defaultValue="Kamau Poultry Farm" />
          </div>
          <div className="space-y-2">
            <Label>Location</Label>
            <Input defaultValue="Nyeri, Kenya" />
          </div>
          <div className="space-y-2">
            <Label>Phone</Label>
            <Input defaultValue="+254 712 345 678" />
          </div>
          <Button>Save Changes</Button>
        </CardContent>
      </Card>
    </div>
  );
}
