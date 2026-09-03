"use client";
import * as React from "react";
import { motion } from "framer-motion";
import { Settings, User, Bell, Palette, Shield, Save, CheckCircle2, Mail, Phone, MapPin, Lock, Eye, EyeOff, Download, Trash2, Leaf, PawPrint, ClipboardList, ShoppingCart, Package, DollarSign, Users, BarChart3, Calculator, Syringe, Heart, CloudSun, Sparkles } from "lucide-react";
import { Card, CardHeader, CardTitle, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Badge } from "@/components/ui/badge";
import { useToast } from "@/components/shared/toast";
import api from "@/lib/api-client";

const fadeUp = { hidden: { opacity: 0, y: 20 }, visible: { opacity: 1, y: 0, transition: { duration: 0.5 } } };
const stagger = { hidden: {}, visible: { transition: { staggerChildren: 0.06 } } };

type Tab = "profile" | "ai" | "modules" | "notifications" | "preferences" | "security" | "data";

const tabs: { id: Tab; label: string; icon: React.ReactNode }[] = [
  { id: "profile", label: "Profile", icon: <User className="h-4 w-4" /> },
  { id: "ai", label: "AI Assistant", icon: <Sparkles className="h-4 w-4" /> },
  { id: "modules", label: "Modules", icon: <Settings className="h-4 w-4" /> },
  { id: "notifications", label: "Notifications", icon: <Bell className="h-4 w-4" /> },
  { id: "preferences", label: "Preferences", icon: <Palette className="h-4 w-4" /> },
  { id: "security", label: "Security", icon: <Shield className="h-4 w-4" /> },
  { id: "data", label: "Data", icon: <Download className="h-4 w-4" /> },
];

const MODULE_ICONS: Record<string, React.ReactNode> = {
  livestock: <PawPrint className="h-4 w-4" />,
  crops: <Leaf className="h-4 w-4" />,
  production: <ClipboardList className="h-4 w-4" />,
  sales: <ShoppingCart className="h-4 w-4" />,
  customers: <Users className="h-4 w-4" />,
  inventory: <Package className="h-4 w-4" />,
  finances: <DollarSign className="h-4 w-4" />,
  workers: <Users className="h-4 w-4" />,
  vaccinations: <Syringe className="h-4 w-4" />,
  calculator: <Calculator className="h-4 w-4" />,
  weather: <CloudSun className="h-4 w-4" />,
  reports: <BarChart3 className="h-4 w-4" />,
  health: <Heart className="h-4 w-4" />,
};

const MODULES = [
  { key: "module_livestock", label: "Livestock", icon: "livestock", desc: "Animal groups and management" },
  { key: "module_crops", label: "Crops", icon: "crops", desc: "Crop fields and harvest tracking" },
  { key: "module_production", label: "Production", icon: "production", desc: "Daily output recording" },
  { key: "module_sales", label: "Sales", icon: "sales", desc: "Product sales and payments" },
  { key: "module_customers", label: "Customers", icon: "customers", desc: "Customer profiles" },
  { key: "module_inventory", label: "Inventory", icon: "inventory", desc: "Feed, seeds, and supplies" },
  { key: "module_finances", label: "Finances", icon: "finances", desc: "Income and expenses" },
  { key: "module_workers", label: "Workers", icon: "workers", desc: "Staff management" },
  { key: "module_vaccinations", label: "Vaccinations", icon: "vaccinations", desc: "Vaccination schedules" },
  { key: "module_feed_calculator", label: "Feed Calculator", icon: "calculator", desc: "Feed requirements" },
  { key: "module_weather", label: "Weather", icon: "weather", desc: "Weather forecasts" },
  { key: "module_reports", label: "Reports", icon: "reports", desc: "Analytics and reports" },
  { key: "module_farm_health", label: "Farm Health", icon: "health", desc: "Farm overview dashboard" },
];

const NOTIFICATIONS = [
  { key: "notif_low_stock", label: "Low stock alerts", desc: "When inventory falls below reorder level", default: true },
  { key: "notif_daily_production", label: "Daily production summary", desc: "Summary of daily output", default: true },
  { key: "notif_mortality", label: "Mortality spike alerts", desc: "When mortality exceeds thresholds", default: true },
  { key: "notif_payment", label: "Payment received", desc: "When a customer pays", default: true },
  { key: "notif_weekly_report", label: "Weekly financial report", desc: "Income, expenses, profit summary", default: false },
  { key: "notif_attendance", label: "Worker absence alerts", desc: "When a worker is absent", default: false },
  { key: "notif_vaccination", label: "Vaccination reminders", desc: "Upcoming vaccination schedules", default: true },
  { key: "notif_weather", label: "Weather alerts", desc: "Frost, rain, extreme heat warnings", default: true },
];

export default function SettingsPage() {
  const [activeTab, setActiveTab] = React.useState<Tab>("profile");
  const [loading, setLoading] = React.useState(true);
  const [saved, setSaved] = React.useState(false);
  const [showPassword, setShowPassword] = React.useState(false);
  const { showToast, ToastComponent } = useToast();

  // Profile state
  const [farmName, setFarmName] = React.useState("");
  const [userName, setUserName] = React.useState("");
  const [email, setEmail] = React.useState("");
  const [phone, setPhone] = React.useState("");
  const [location, setLocation] = React.useState("");
  const [county, setCounty] = React.useState("");

  // Settings state
  const [settings, setSettings] = React.useState<Record<string, string>>({});

  // Password state
  const [currentPw, setCurrentPw] = React.useState("");
  const [newPw, setNewPw] = React.useState("");
  const [confirmPw, setConfirmPw] = React.useState("");

  React.useEffect(() => {
    api.get("/api/settings").then((d: any) => {
      setSettings(d.settings || {});
      setFarmName(d.farm?.name || "");
      setUserName(d.user?.name || "");
      setEmail(d.user?.email || "");
      setPhone(d.user?.phone || "");
      setLocation(d.farm?.location || "");
      setCounty(d.farm?.county || "");
      setLoading(false);
    }).catch(() => setLoading(false));
  }, []);

  const getSetting = (key: string, defaultVal = "true") => settings[key] ?? defaultVal;
  const setSetting = (key: string, value: string) => setSettings(prev => ({ ...prev, [key]: value }));

  const handleSaveProfile = async () => {
    try {
      await api.put("/api/settings/profile", { farmName, name: userName, email, phone, location, county });
      showToast("Profile updated!");
      setSaved(true); setTimeout(() => setSaved(false), 2000);
    } catch { showToast("Failed to update profile"); }
  };

  const handleSaveSettings = async () => {
    try {
      await api.put("/api/settings", { settings });
      showToast("Settings saved!");
      setSaved(true); setTimeout(() => setSaved(false), 2000);
    } catch { showToast("Failed to save settings"); }
  };

  const handleChangePassword = async () => {
    if (newPw !== confirmPw) return showToast("Passwords do not match");
    if (newPw.length < 6) return showToast("Password must be at least 6 characters");
    try {
      await api.put("/api/settings/password", { currentPassword: currentPw, newPassword: newPw });
      showToast("Password changed!");
      setCurrentPw(""); setNewPw(""); setConfirmPw("");
      setSaved(true); setTimeout(() => setSaved(false), 2000);
    } catch { showToast("Failed — check current password"); }
  };

  const handleExportAll = () => {
    window.open("/api/export", "_blank");
    showToast("Opening farm data export...");
  };

  if (loading) return <div className="flex items-center justify-center h-64"><div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#166534]" /></div>;

  return (
    <div className="space-y-6">
      <motion.div initial="hidden" animate="visible" variants={fadeUp}>
        <h1 className="text-2xl font-extrabold text-[#0F172A] tracking-tight">Settings</h1>
        <p className="text-sm text-[#64748B] mt-1">Manage your farm, modules, and account</p>
      </motion.div>

      <div className="flex flex-col lg:flex-row gap-6">
        {/* Sidebar Tabs */}
        <motion.div initial="hidden" animate="visible" variants={fadeUp} className="lg:w-56 shrink-0">
          <Card className="border border-[#E5E7EB]">
            <CardContent className="p-2">
              {tabs.map(tab => (
                <button key={tab.id} onClick={() => setActiveTab(tab.id)}
                  className={`w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all cursor-pointer ${activeTab === tab.id ? "bg-[#F0FDF4] text-[#166534] font-bold" : "text-[#64748B] hover:bg-[#FAFBFC]"}`}>
                  {tab.icon}{tab.label}
                </button>
              ))}
            </CardContent>
          </Card>
        </motion.div>

        {/* Content */}
        <motion.div initial="hidden" animate="visible" variants={fadeUp} className="flex-1 min-w-0 space-y-4">

          {/* Profile */}
          {activeTab === "profile" && (
            <Card className="border border-[#E5E7EB]">
              <CardHeader><CardTitle className="flex items-center gap-2 text-base font-bold"><User className="h-4 w-4 text-[#166534]" /> Farm Profile</CardTitle></CardHeader>
              <CardContent className="space-y-4">
                <div className="space-y-1"><Label className="text-xs font-semibold text-[#64748B]">Farm Name</Label><Input value={farmName} onChange={e => setFarmName(e.target.value)} className="h-11 rounded-xl" /></div>
                <div className="space-y-1"><Label className="text-xs font-semibold text-[#64748B]">Your Name</Label><Input value={userName} onChange={e => setUserName(e.target.value)} className="h-11 rounded-xl" /></div>
                <div className="grid grid-cols-2 gap-3">
                  <div className="space-y-1"><Label className="text-xs font-semibold text-[#64748B]"><Mail className="h-3 w-3 inline mr-1" />Email</Label><Input value={email} onChange={e => setEmail(e.target.value)} className="h-11 rounded-xl" /></div>
                  <div className="space-y-1"><Label className="text-xs font-semibold text-[#64748B]"><Phone className="h-3 w-3 inline mr-1" />Phone</Label><Input value={phone} onChange={e => setPhone(e.target.value)} className="h-11 rounded-xl" /></div>
                </div>
                <div className="grid grid-cols-2 gap-3">
                  <div className="space-y-1"><Label className="text-xs font-semibold text-[#64748B]"><MapPin className="h-3 w-3 inline mr-1" />Location</Label><Input value={location} onChange={e => setLocation(e.target.value)} className="h-11 rounded-xl" /></div>
                  <div className="space-y-1"><Label className="text-xs font-semibold text-[#64748B]">County</Label><Input value={county} onChange={e => setCounty(e.target.value)} className="h-11 rounded-xl" /></div>
                </div>
                <Button onClick={handleSaveProfile} className="bg-[#166534] hover:bg-[#14532D] cursor-pointer">{saved ? <><CheckCircle2 className="h-4 w-4 mr-2" /> Saved!</> : <><Save className="h-4 w-4 mr-2" /> Save Profile</>}</Button>
              </CardContent>
            </Card>
          )}

          {/* AI Assistant */}
          {activeTab === "ai" && (
            <Card className="border border-[#E5E7EB]">
              <CardHeader><CardTitle className="flex items-center gap-2 text-base font-bold"><Sparkles className="h-4 w-4 text-[#166534]" /> AI Assistant Setup</CardTitle></CardHeader>
              <CardContent className="space-y-4">
                <div className="rounded-xl bg-[#F0FDF4] border border-[#BBF7D0] p-4">
                  <p className="text-sm font-bold text-[#0F172A] mb-1">Connect your AI provider</p>
                  <p className="text-xs text-[#64748B] mb-3">Choose a free AI provider to power your farm assistant. No credit card needed.</p>
                  <div className="space-y-2">
                    {[
                      { name: "Google Gemini", env: "AI_PROVIDER=gemini AI_API_KEY=your-key AI_MODEL=gemini-2.0-flash", free: "Free tier: 15 req/min" },
                      { name: "Groq (Fast)", env: "AI_PROVIDER=groq AI_API_KEY=your-key AI_MODEL=llama-3.3-70b-versatile", free: "Free tier: 30 req/min" },
                      { name: "Cerebras", env: "AI_PROVIDER=cerebras AI_API_KEY=your-key AI_MODEL=llama-3.3-70b", free: "Free tier: 30 req/min" },
                      { name: "OpenRouter", env: "AI_PROVIDER=openrouter AI_API_KEY=your-key AI_MODEL=google/gemini-2.0-flash", free: "Free models available" },
                      { name: "Ollama (Local)", env: "AI_PROVIDER=ollama OLLAMA_URL=http://127.0.0.1:11434 AI_MODEL=qwen2.5:1.5b", free: "Free — runs on your computer" },
                    ].map(p => (
                      <div key={p.name} className="rounded-lg bg-white border border-[#E5E7EB] p-3">
                        <div className="flex items-center justify-between mb-1">
                          <p className="text-xs font-bold text-[#0F172A]">{p.name}</p>
                          <span className="text-[9px] font-bold text-[#166534] bg-[#F0FDF4] px-2 py-0.5 rounded-full">{p.free}</span>
                        </div>
                        <p className="text-[10px] text-[#94A3B8] font-mono bg-[#F8FAFC] rounded px-2 py-1">{p.env}</p>
                      </div>
                    ))}
                  </div>
                </div>
                <div className="rounded-xl border border-[#E5E7EB] p-4">
                  <p className="text-xs font-bold text-[#0F172A] mb-2">How to connect</p>
                  <div className="space-y-1.5 text-[11px] text-[#64748B]">
                    <p>1. Get a free API key from one of the providers above</p>
                    <p>2. Add the environment variables to your server</p>
                    <p>3. Restart the server</p>
                    <p>4. Open the AI Assistant and start chatting</p>
                  </div>
                  <a href="/ai" className="mt-3 inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-[#166534] text-white text-xs font-bold hover:bg-[#14532D] cursor-pointer">Open AI Assistant</a>
                </div>
              </CardContent>
            </Card>
          )}

          {/* Modules */}
          {activeTab === "modules" && (
            <Card className="border border-[#E5E7EB]">
              <CardHeader><CardTitle className="flex items-center gap-2 text-base font-bold"><Settings className="h-4 w-4 text-[#166534]" /> Module Control</CardTitle></CardHeader>
              <CardContent className="space-y-2">
                <p className="text-xs text-[#94A3B8] mb-3">Toggle modules on or off. Disabled modules hide from the sidebar.</p>
                {MODULES.map(m => {
                  const enabled = getSetting(m.key, "true") === "true";
                  return (
                    <div key={m.key} className="flex items-center justify-between p-3 rounded-xl border border-[#E5E7EB] hover:bg-[#FAFBFC] transition-colors">
                      <div className="flex items-center gap-3">
                        <div className={enabled ? "flex h-9 w-9 items-center justify-center rounded-xl bg-[#F0FDF4] text-[#166534]" : "flex h-9 w-9 items-center justify-center rounded-xl bg-gray-100 text-gray-400"}>{MODULE_ICONS[m.icon as string]}</div>
                        <div><p className="text-sm font-bold text-[#0F172A]">{m.label}</p><p className="text-[10px] text-[#94A3B8]">{m.desc}</p></div>
                      </div>
                      <button onClick={() => setSetting(m.key, enabled ? "false" : "true")}
                        className={`relative w-11 h-6 rounded-full transition-colors cursor-pointer ${enabled ? "bg-[#166534]" : "bg-gray-300"}`}>
                        <div className={`absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-white shadow transition-transform ${enabled ? "translate-x-5" : ""}`} />
                      </button>
                    </div>
                  );
                })}
                <Button onClick={handleSaveSettings} className="w-full mt-3 bg-[#166534] hover:bg-[#14532D] cursor-pointer">{saved ? "Saved!" : "Save Module Settings"}</Button>
              </CardContent>
            </Card>
          )}

          {/* Notifications */}
          {activeTab === "notifications" && (
            <Card className="border border-[#E5E7EB]">
              <CardHeader><CardTitle className="flex items-center gap-2 text-base font-bold"><Bell className="h-4 w-4 text-[#166534]" /> Notifications</CardTitle></CardHeader>
              <CardContent className="space-y-2">
                {NOTIFICATIONS.map(n => {
                  const enabled = getSetting(n.key, String(n.default)) === "true";
                  return (
                    <div key={n.key} className="flex items-center justify-between p-3 rounded-xl border border-[#E5E7EB] hover:bg-[#FAFBFC] transition-colors">
                      <div><p className="text-sm font-bold text-[#0F172A]">{n.label}</p><p className="text-[10px] text-[#94A3B8]">{n.desc}</p></div>
                      <button onClick={() => setSetting(n.key, enabled ? "false" : "true")}
                        className={`relative w-11 h-6 rounded-full transition-colors cursor-pointer ${enabled ? "bg-[#166534]" : "bg-gray-300"}`}>
                        <div className={`absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-white shadow transition-transform ${enabled ? "translate-x-5" : ""}`} />
                      </button>
                    </div>
                  );
                })}
                <Button onClick={handleSaveSettings} className="w-full mt-3 bg-[#166534] hover:bg-[#14532D] cursor-pointer">{saved ? <><CheckCircle2 className="h-4 w-4 mr-2" /> Saved!</> : <><Save className="h-4 w-4 mr-2" /> Save Notifications</>}</Button>
              </CardContent>
            </Card>
          )}

          {/* Preferences */}
          {activeTab === "preferences" && (
            <Card className="border border-[#E5E7EB]">
              <CardHeader><CardTitle className="flex items-center gap-2 text-base font-bold"><Palette className="h-4 w-4 text-[#166534]" /> Preferences</CardTitle></CardHeader>
              <CardContent className="space-y-4">
                <div className="space-y-1"><Label className="text-xs font-semibold text-[#64748B]">Currency</Label>
                  <select value={getSetting("currency", "KES")} onChange={e => setSetting("currency", e.target.value)} className="w-full h-11 rounded-xl border border-[#E5E7EB] px-3 text-sm">
                    <option value="KES">KES — Kenyan Shilling</option><option value="USD">USD — US Dollar</option><option value="UGX">UGX — Ugandan Shilling</option><option value="TZS">TZS — Tanzanian Shilling</option>
                  </select></div>
                <div className="space-y-1"><Label className="text-xs font-semibold text-[#64748B]">Language</Label>
                  <select value={getSetting("language", "en")} onChange={e => setSetting("language", e.target.value)} className="w-full h-11 rounded-xl border border-[#E5E7EB] px-3 text-sm">
                    <option value="en">English</option><option value="sw">Swahili</option>
                  </select></div>
                <div className="space-y-1"><Label className="text-xs font-semibold text-[#64748B]">Date Format</Label>
                  <select value={getSetting("date_format", "DD/MM/YYYY")} onChange={e => setSetting("date_format", e.target.value)} className="w-full h-11 rounded-xl border border-[#E5E7EB] px-3 text-sm">
                    <option>DD/MM/YYYY</option><option>MM/DD/YYYY</option><option>YYYY-MM-DD</option>
                  </select></div>
                <div className="space-y-1"><Label className="text-xs font-semibold text-[#64748B]">Timezone</Label>
                  <select value={getSetting("timezone", "EAT")} onChange={e => setSetting("timezone", e.target.value)} className="w-full h-11 rounded-xl border border-[#E5E7EB] px-3 text-sm">
                    <option value="EAT">East Africa Time (EAT, UTC+3)</option><option value="UTC">Coordinated Universal Time (UTC)</option>
                  </select></div>
                <Button onClick={handleSaveSettings} className="bg-[#166534] hover:bg-[#14532D] cursor-pointer">{saved ? <><CheckCircle2 className="h-4 w-4 mr-2" /> Saved!</> : <><Save className="h-4 w-4 mr-2" /> Save Preferences</>}</Button>
              </CardContent>
            </Card>
          )}

          {/* Security */}
          {activeTab === "security" && (
            <Card className="border border-[#E5E7EB]">
              <CardHeader><CardTitle className="flex items-center gap-2 text-base font-bold"><Shield className="h-4 w-4 text-[#166534]" /> Security</CardTitle></CardHeader>
              <CardContent className="space-y-4">
                <div className="space-y-1"><Label className="text-xs font-semibold text-[#64748B]">Current Password</Label>
                  <div className="relative"><Input type={showPassword ? "text" : "password"} value={currentPw} onChange={e => setCurrentPw(e.target.value)} placeholder="Current password" className="h-11 rounded-xl pr-10" />
                    <button type="button" onClick={() => setShowPassword(!showPassword)} className="absolute right-3 top-1/2 -translate-y-1/2 text-[#94A3B8] cursor-pointer">{showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}</button></div></div>
                <div className="space-y-1"><Label className="text-xs font-semibold text-[#64748B]">New Password</Label><Input type="password" value={newPw} onChange={e => setNewPw(e.target.value)} placeholder="New password" className="h-11 rounded-xl" /></div>
                <div className="space-y-1"><Label className="text-xs font-semibold text-[#64748B]">Confirm New Password</Label><Input type="password" value={confirmPw} onChange={e => setConfirmPw(e.target.value)} placeholder="Confirm password" className="h-11 rounded-xl" /></div>
                <Button onClick={handleChangePassword} className="bg-[#166534] hover:bg-[#14532D] cursor-pointer"><Lock className="h-4 w-4 mr-2" /> Update Password</Button>
              </CardContent>
            </Card>
          )}

          {/* Data Management */}
          {activeTab === "data" && (
            <Card className="border border-[#E5E7EB]">
              <CardHeader><CardTitle className="flex items-center gap-2 text-base font-bold"><Download className="h-4 w-4 text-[#166534]" /> Data Management</CardTitle></CardHeader>
              <CardContent className="space-y-4">
                <div className="rounded-xl border border-[#E5E7EB] p-4">
                  <p className="text-sm font-bold text-[#0F172A]">Export All Farm Data</p>
                  <p className="text-xs text-[#94A3B8] mt-1">Download a complete backup of your farm data for loan applications or records.</p>
                  <Button onClick={handleExportAll} variant="outline" className="mt-3 border-[#166534] text-[#166534] hover:bg-[#F0FDF4] cursor-pointer"><Download className="h-4 w-4 mr-2" />Export Data</Button>
                </div>
                <div className="rounded-xl border border-[#E5E7EB] p-4">
                  <p className="text-sm font-bold text-[#0F172A]">Import Data</p>
                  <p className="text-xs text-[#94A3B8] mt-1">Upload CSV files to import livestock, production, sales, and more.</p>
                  <Button onClick={() => window.location.href = "/import"} variant="outline" className="mt-3 border-[#166534] text-[#166534] hover:bg-[#F0FDF4] cursor-pointer"><Download className="h-4 w-4 mr-2" />Import Data</Button>
                </div>
                <div className="rounded-xl border border-red-200 bg-red-50 p-4">
                  <p className="text-sm font-bold text-red-700">Danger Zone</p>
                  <p className="text-xs text-red-500 mt-1">Permanently delete your account and all farm data. This cannot be undone.</p>
                  <Button variant="outline" className="mt-3 border-red-300 text-red-600 hover:bg-red-100 cursor-pointer"><Trash2 className="h-4 w-4 mr-2" />Delete Account</Button>
                </div>
              </CardContent>
            </Card>
          )}
        </motion.div>
      </div>
      {ToastComponent}
    </div>
  );
}
