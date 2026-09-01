"use client";

import * as React from "react";
import { motion } from "framer-motion";
import {
  Settings, User, Bell, Palette, CreditCard, Shield, Save,
  CheckCircle2, Mail, Phone, MapPin, Globe, Lock, Eye, EyeOff,
} from "lucide-react";
import { Card, CardHeader, CardTitle, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Badge } from "@/components/ui/badge";

const fadeUp = { hidden: { opacity: 0, y: 20 }, visible: { opacity: 1, y: 0, transition: { duration: 0.5 } } };
const stagger = { hidden: {}, visible: { transition: { staggerChildren: 0.06 } } };

type Tab = "profile" | "notifications" | "preferences" | "billing" | "security";

const tabs: { id: Tab; label: string; icon: React.ReactNode }[] = [
  { id: "profile", label: "Profile", icon: <User className="h-4 w-4" /> },
  { id: "notifications", label: "Notifications", icon: <Bell className="h-4 w-4" /> },
  { id: "preferences", label: "Preferences", icon: <Palette className="h-4 w-4" /> },
  { id: "billing", label: "Billing", icon: <CreditCard className="h-4 w-4" /> },
  { id: "security", label: "Security", icon: <Shield className="h-4 w-4" /> },
];

export default function SettingsPage() {
  const [activeTab, setActiveTab] = React.useState<Tab>("profile");
  const [saved, setSaved] = React.useState(false);
  const [showPassword, setShowPassword] = React.useState(false);

  const handleSave = () => {
    setSaved(true);
    setTimeout(() => setSaved(false), 2000);
  };

  return (
    <div className="space-y-6">
      <motion.div initial="hidden" animate="visible" variants={fadeUp}>
        <h1 className="text-2xl font-extrabold text-[#0F172A] tracking-tight">Settings</h1>
        <p className="text-sm text-[#64748B] mt-1">Manage your farm profile, notifications, and preferences.</p>
      </motion.div>

      <div className="flex flex-col lg:flex-row gap-6">
        {/* Sidebar Tabs */}
        <motion.div initial="hidden" animate="visible" variants={fadeUp} className="lg:w-56 shrink-0">
          <Card className="border border-[#E5E7EB]">
            <CardContent className="p-2">
              {tabs.map((tab) => (
                <button
                  key={tab.id}
                  onClick={() => setActiveTab(tab.id)}
                  className={`w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all cursor-pointer ${
                    activeTab === tab.id
                      ? "bg-[#F0FDF4] text-[#166534] font-bold"
                      : "text-[#64748B] hover:bg-[#FAFBFC] hover:text-[#0F172A]"
                  }`}
                >
                  {tab.icon}
                  {tab.label}
                </button>
              ))}
            </CardContent>
          </Card>
        </motion.div>

        {/* Content */}
        <motion.div initial="hidden" animate="visible" variants={fadeUp} className="flex-1 min-w-0">
          {activeTab === "profile" && (
            <Card className="border border-[#E5E7EB] hover:shadow-lg transition-shadow">
              <CardHeader>
                <CardTitle className="flex items-center gap-2 text-base font-bold">
                  <User className="h-4 w-4 text-[#166534]" /> Farm Profile
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-5">
                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label className="text-sm font-semibold text-[#334155]">Farm Name</Label>
                    <Input defaultValue="Kamau Poultry Farm" className="h-11 rounded-xl border-[#E5E7EB] focus:border-[#166534]" />
                  </div>
                  <div className="space-y-2">
                    <Label className="text-sm font-semibold text-[#334155]">Owner Name</Label>
                    <Input defaultValue="Lewis Kamau" className="h-11 rounded-xl border-[#E5E7EB] focus:border-[#166534]" />
                  </div>
                </div>
                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label className="text-sm font-semibold text-[#334155] flex items-center gap-1.5"><Mail className="h-3.5 w-3.5" /> Email</Label>
                    <Input defaultValue="lewis@imeantech.com" className="h-11 rounded-xl border-[#E5E7EB] focus:border-[#166534]" />
                  </div>
                  <div className="space-y-2">
                    <Label className="text-sm font-semibold text-[#334155] flex items-center gap-1.5"><Phone className="h-3.5 w-3.5" /> Phone</Label>
                    <Input defaultValue="+254 712 345 678" className="h-11 rounded-xl border-[#E5E7EB] focus:border-[#166534]" />
                  </div>
                </div>
                <div className="space-y-2">
                  <Label className="text-sm font-semibold text-[#334155] flex items-center gap-1.5"><MapPin className="h-3.5 w-3.5" /> Location</Label>
                  <Input defaultValue="Nakuru, Kenya" className="h-11 rounded-xl border-[#E5E7EB] focus:border-[#166534]" />
                </div>
                <div className="space-y-2">
                  <Label className="text-sm font-semibold text-[#334155] flex items-center gap-1.5"><Globe className="h-3.5 w-3.5" /> Website</Label>
                  <Input defaultValue="https://wangari.imeantech.com" className="h-11 rounded-xl border-[#E5E7EB] focus:border-[#166534]" />
                </div>
                <Button onClick={handleSave} className="bg-[#166534] hover:bg-[#14532D] cursor-pointer">
                  {saved ? <><CheckCircle2 className="h-4 w-4 mr-2" /> Saved!</> : <><Save className="h-4 w-4 mr-2" /> Save Changes</>}
                </Button>
              </CardContent>
            </Card>
          )}

          {activeTab === "notifications" && (
            <Card className="border border-[#E5E7EB] hover:shadow-lg transition-shadow">
              <CardHeader>
                <CardTitle className="flex items-center gap-2 text-base font-bold">
                  <Bell className="h-4 w-4 text-[#166534]" /> Notification Preferences
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                {[
                  { label: "Low stock alerts", desc: "Get notified when inventory items fall below reorder level", default: true },
                  { label: "Daily production summary", desc: "Receive a summary of daily egg collection and mortality", default: true },
                  { label: "Mortality spike alerts", desc: "Alert when mortality exceeds normal thresholds", default: true },
                  { label: "Payment received", desc: "Notification when a customer makes a payment", default: true },
                  { label: "Weekly financial report", desc: "Summary of income, expenses, and profit", default: false },
                  { label: "Worker attendance alerts", desc: "Alert when a worker is absent", default: false },
                ].map((item) => (
                  <div key={item.label} className="flex items-center justify-between rounded-xl border border-[#E5E7EB] p-4 hover:bg-[#FAFBFC] transition-colors">
                    <div>
                      <p className="text-sm font-semibold text-[#0F172A]">{item.label}</p>
                      <p className="text-xs text-[#94A3B8] mt-0.5">{item.desc}</p>
                    </div>
                    <label className="relative inline-flex items-center cursor-pointer">
                      <input type="checkbox" defaultChecked={item.default} className="sr-only peer" />
                      <div className="w-10 h-5.5 bg-gray-200 peer-focus:ring-2 peer-focus:ring-[#166534]/20 rounded-full peer peer-checked:after:translate-x-[18px] after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#166534]" />
                    </label>
                  </div>
                ))}
                <Button onClick={handleSave} className="bg-[#166534] hover:bg-[#14532D] cursor-pointer">
                  {saved ? <><CheckCircle2 className="h-4 w-4 mr-2" /> Saved!</> : <><Save className="h-4 w-4 mr-2" /> Save Preferences</>}
                </Button>
              </CardContent>
            </Card>
          )}

          {activeTab === "preferences" && (
            <Card className="border border-[#E5E7EB] hover:shadow-lg transition-shadow">
              <CardHeader>
                <CardTitle className="flex items-center gap-2 text-base font-bold">
                  <Palette className="h-4 w-4 text-[#166534]" /> Preferences
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-5">
                <div className="space-y-2">
                  <Label className="text-sm font-semibold text-[#334155]">Currency</Label>
                  <select className="w-full h-11 rounded-xl border border-[#E5E7EB] px-4 text-sm focus:ring-2 focus:ring-[#166534]/20 focus:border-[#166534] transition-all">
                    <option>KES — Kenyan Shilling</option>
                    <option>USD — US Dollar</option>
                    <option>UGX — Ugandan Shilling</option>
                    <option>TZS — Tanzanian Shilling</option>
                  </select>
                </div>
                <div className="space-y-2">
                  <Label className="text-sm font-semibold text-[#334155]">Language</Label>
                  <select className="w-full h-11 rounded-xl border border-[#E5E7EB] px-4 text-sm focus:ring-2 focus:ring-[#166534]/20 focus:border-[#166534] transition-all">
                    <option>English</option>
                    <option>Swahili</option>
                  </select>
                </div>
                <div className="space-y-2">
                  <Label className="text-sm font-semibold text-[#334155]">Date Format</Label>
                  <select className="w-full h-11 rounded-xl border border-[#E5E7EB] px-4 text-sm focus:ring-2 focus:ring-[#166534]/20 focus:border-[#166534] transition-all">
                    <option>DD/MM/YYYY</option>
                    <option>MM/DD/YYYY</option>
                    <option>YYYY-MM-DD</option>
                  </select>
                </div>
                <div className="space-y-2">
                  <Label className="text-sm font-semibold text-[#334155]">Timezone</Label>
                  <select className="w-full h-11 rounded-xl border border-[#E5E7EB] px-4 text-sm focus:ring-2 focus:ring-[#166534]/20 focus:border-[#166534] transition-all">
                    <option>East Africa Time (EAT, UTC+3)</option>
                    <option>Coordinated Universal Time (UTC)</option>
                  </select>
                </div>
                <Button onClick={handleSave} className="bg-[#166534] hover:bg-[#14532D] cursor-pointer">
                  {saved ? <><CheckCircle2 className="h-4 w-4 mr-2" /> Saved!</> : <><Save className="h-4 w-4 mr-2" /> Save Preferences</>}
                </Button>
              </CardContent>
            </Card>
          )}

          {activeTab === "billing" && (
            <Card className="border border-[#E5E7EB] hover:shadow-lg transition-shadow">
              <CardHeader>
                <CardTitle className="flex items-center gap-2 text-base font-bold">
                  <CreditCard className="h-4 w-4 text-[#166534]" /> Billing & Subscription
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-6">
                <div className="rounded-xl border border-[#166534]/20 bg-[#F0FDF4] p-6">
                  <div className="flex items-center justify-between">
                    <div>
                      <p className="text-sm font-bold text-[#0F172A]">Current Plan</p>
                      <p className="text-2xl font-extrabold text-[#166534] mt-1">Starter</p>
                      <p className="text-xs text-[#64748B] mt-1">Free forever</p>
                    </div>
                    <Badge className="bg-[#F0FDF4] text-[#166534] border-[#BBF7D0] text-sm px-3 py-1">Active</Badge>
                  </div>
                </div>

                <div className="grid md:grid-cols-2 gap-4">
                  <div className="rounded-xl border border-[#E5E7EB] p-5 hover:border-[#BBF7D0] hover:shadow-md transition-all cursor-pointer">
                    <p className="text-lg font-bold text-[#0F172A]">Growth</p>
                    <p className="text-2xl font-extrabold text-[#166534] mt-1">KES 1,500<span className="text-sm font-normal text-[#64748B]">/month</span></p>
                    <ul className="mt-3 space-y-1.5 text-xs text-[#64748B]">
                      <li>✓ Up to 3 farms</li>
                      <li>✓ Unlimited flocks</li>
                      <li>✓ Full analytics</li>
                      <li>✓ AI Assistant</li>
                    </ul>
                    <Button className="mt-4 w-full bg-[#166534] hover:bg-[#14532D] cursor-pointer">Upgrade</Button>
                  </div>
                  <div className="rounded-xl border border-[#E5E7EB] p-5 hover:border-[#BBF7D0] hover:shadow-md transition-all cursor-pointer">
                    <p className="text-lg font-bold text-[#0F172A]">Enterprise</p>
                    <p className="text-2xl font-extrabold text-[#166534] mt-1">KES 5,000<span className="text-sm font-normal text-[#64748B]">/month</span></p>
                    <ul className="mt-3 space-y-1.5 text-xs text-[#64748B]">
                      <li>✓ Unlimited farms</li>
                      <li>✓ Multi-user access</li>
                      <li>✓ API access</li>
                      <li>✓ WhatsApp bot</li>
                    </ul>
                    <Button className="mt-4 w-full bg-[#166534] hover:bg-[#14532D] cursor-pointer">Upgrade</Button>
                  </div>
                </div>
              </CardContent>
            </Card>
          )}

          {activeTab === "security" && (
            <Card className="border border-[#E5E7EB] hover:shadow-lg transition-shadow">
              <CardHeader>
                <CardTitle className="flex items-center gap-2 text-base font-bold">
                  <Shield className="h-4 w-4 text-[#166534]" /> Security
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-5">
                <div className="space-y-2">
                  <Label className="text-sm font-semibold text-[#334155]">Current Password</Label>
                  <div className="relative">
                    <Input type={showPassword ? "text" : "password"} placeholder="Enter current password" className="h-11 rounded-xl border-[#E5E7EB] pr-10" />
                    <button type="button" onClick={() => setShowPassword(!showPassword)} className="absolute right-3 top-1/2 -translate-y-1/2 text-[#94A3B8] hover:text-[#64748B] cursor-pointer">
                      {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                    </button>
                  </div>
                </div>
                <div className="space-y-2">
                  <Label className="text-sm font-semibold text-[#334155]">New Password</Label>
                  <Input type="password" placeholder="Enter new password" className="h-11 rounded-xl border-[#E5E7EB]" />
                </div>
                <div className="space-y-2">
                  <Label className="text-sm font-semibold text-[#334155]">Confirm New Password</Label>
                  <Input type="password" placeholder="Confirm new password" className="h-11 rounded-xl border-[#E5E7EB]" />
                </div>
                <Button onClick={handleSave} className="bg-[#166534] hover:bg-[#14532D] cursor-pointer">
                  {saved ? <><CheckCircle2 className="h-4 w-4 mr-2" /> Updated!</> : <><Lock className="h-4 w-4 mr-2" /> Update Password</>}
                </Button>

                <div className="border-t border-[#E5E7EB] pt-5 mt-5">
                  <p className="text-sm font-bold text-[#0F172A]">Two-Factor Authentication</p>
                  <p className="text-xs text-[#64748B] mt-1">Add an extra layer of security to your account.</p>
                  <Button variant="outline" className="mt-3 border-[#E5E7EB] hover:bg-[#F0FDF4] hover:border-[#BBF7D0] cursor-pointer">
                    <Shield className="h-4 w-4 mr-2" /> Enable 2FA
                  </Button>
                </div>
              </CardContent>
            </Card>
          )}
        </motion.div>
      </div>
    </div>
  );
}
