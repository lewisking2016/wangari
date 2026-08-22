# Bulk Import/Export Feature Guide

## Overview
The Bulk Import/Export module allows your boss to manage large amounts of data using CSV files that can be edited in Excel or Google Sheets.

## Location
Access the feature from the admin sidebar:
**https://new.decapoli.co.ke/wangariadmin** → Click "Bulk Import/Export"

## What Can Be Imported/Exported

### 1. Products
- **Export:** All products with categories, pricing, stock levels
- **Import Format:** Name, Category, Type, Price, Stock, Description
- **Behavior:** Updates existing products (by name) or creates new ones

### 2. Customers
- **Export:** Customer database with contact information
- **Import Format:** Username, Email, First Name, Last Name, Phone
- **Behavior:** Updates existing customers (by email) or creates new ones
- **Note:** New customers get default password "password123"

### 3. Raw Materials
- **Export:** All ingredients with stock and pricing
- **Import Format:** Name, Stock (tons), Reserved (kg), Price/kg, Description
- **Behavior:** Updates existing materials (by name) or creates new ones

### 4. Flocks
- **Export:** Poultry flock records
- **Import Format:** Flock Name, Breed, Total Birds, Mortality, Arrived (YYYY-MM-DD), Location, Status
- **Behavior:** Always creates new records (good for batch tracking)

### 5. Orders
- **Export Only:** Complete order history with customer details
- **Note:** Orders cannot be imported (must be created through checkout)

### 6. Expenses
- **Export:** Farm expenses with categories and vendors
- **Import Format:** Category, Description, Amount, Date (YYYY-MM-DD), Payment Method, Vendor
- **Behavior:** Always creates new expense records

## How To Use

### Exporting Data (Backup/Analysis)
1. Go to Bulk Import/Export page
2. Click the "Export [Data Type]" button
3. CSV file downloads automatically
4. Open in Excel or Google Sheets

### Importing Data (Bulk Add/Update)
1. **Get the correct format:**
   - Export existing data first to see exact CSV structure
   - OR follow the format requirements shown on the page

2. **Prepare your CSV:**
   - Use Excel or Google Sheets
   - First row = headers (don't change them)
   - Following rows = your data
   - Save as CSV format

3. **Upload:**
   - Select "Import Type" from dropdown
   - Choose your CSV file
   - Click "Upload & Import Data"
   - System shows how many records succeeded/failed

## CSV Format Examples

### Products CSV:
```
Name,Category,Type,Price,Stock,Description
Broiler Chicken 2kg,Broilers,live_animal,800,50,Fresh broiler ready for sale
Layer Feed 50kg,Feeds,feed,2500,100,Premium layer mash
```

### Customers CSV:
```
Username,Email,First Name,Last Name,Phone
johndoe,john@example.com,John,Doe,0712345678
```

### Raw Materials CSV:
```
Name,Stock (tons),Price/ton,Min Stock Level
Maize,5.5,35000,1.0
Soya Bean Cake,3.2,85000,0.5
```

### Flocks CSV:
```
Flock Name,Breed,Initial Count,Current Count,Hatch Date,Status
Batch 15 2026,Broiler,500,485,2026-01-15,active
```

### Expenses CSV:
```
Category,Description,Amount,Date,Payment Method
Feed,50 bags layer mash,125000,2026-02-01,mpesa
Veterinary,Vaccination batch 15,15000,2026-02-02,cash
```

## Important Notes

1. **Date Format:** Always use YYYY-MM-DD (e.g., 2026-02-15)
2. **Decimal Numbers:** Use period (.) not comma (e.g., 2500.50 not 2500,50)
3. **Duplicate Handling:**
   - Products, Raw Materials, Customers: Updates if exists, creates if new
   - Flocks, Expenses: Always creates new records
4. **Headers Required:** First row must be the column headers
5. **Empty Fields:** Leave blank if optional, but required fields must have values

## Use Cases

### Batch 15 2026.xlsx → Import to System
1. Export "Flocks" to get CSV template
2. Open your Batch 15 2026.xlsx
3. Copy data to match CSV format
4. Save as CSV
5. Import using "Flocks" import type

### SALES REPORT 2026.xlsx → Compare with System
1. Export "Orders" from system
2. Compare with your Excel sales report
3. Spot discrepancies

### Price Updates for All Products
1. Export "Products"
2. Update prices in Excel
3. Import back - system updates all prices automatically

### Bulk Customer Registration
1. Get customer list from marketing team
2. Format as: Username, Email, First Name, Last Name, Phone
3. Import as "Customers"
4. They can login with username and default password "password123"

## Benefits

- **Save Time:** Update hundreds of records in seconds vs manual entry
- **Excel Familiar:** Work in Excel/Sheets instead of web forms
- **Backup:** Regular exports create automatic backups
- **Analysis:** Export to Excel for charts, pivot tables, reports
- **Migration:** Easy to move data between systems
- **Batch Operations:** Update prices, stock levels, or info in bulk

## Security

- Only Super Admin and Farm Manager roles can access
- All imports are logged in system
- Failed imports don't break existing data
- Always test with small CSV first before big imports

## Troubleshooting

**Import shows many errors:**
- Check CSV format matches template exactly
- Verify dates use YYYY-MM-DD format
- Ensure no special characters in required fields
- Check category names exist in system first

**Export downloads empty:**
- Table might have no data yet
- Check database connection
- Try different data type

**Excel changes dates:**
- Save as CSV UTF-8 to preserve formatting
- Format date cells as "Text" before entering dates
