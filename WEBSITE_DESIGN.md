# Wangari Farm — Website Design & Vision Document

> **Project:** Complete rebuild of wangari.farm
> **Tech Stack:** PHP (cPanel-hosted), Vanilla CSS, JavaScript
> **Status:** Pre-development — Design Bible
> **Last Updated:** July 16, 2026

---

## Project Overview

Rebuild the Wangari Farm website from the ground up into a premium, modern poultry business website with a small e-commerce component. The current site is a basic Bootstrap 3 single-page layout with a login form — it needs to become a world-class, visually stunning agricultural brand site that rivals Kenchic.com in quality.

The admin dashboard must be designed to be simple and minimalistic, accessible enough for a Grade 7 student to operate easily, yet look extremely professional with smooth, animated charts and interactive data entry.

### Business Info
- **Company:** Wangari Farm (Wangari Systems)
- **Location:** Nasira AC sub-location, Busibwabo Location, Busia, Kenya
- **Phone:** +254-727 585599
- **Email:** info@wangari.farm
- **Services:** Poultry farming, egg production, chick sales, consulting, farm management software

---

## Inspiration Sources

### 1. Kenchic.com — PRIMARY INSPIRATION
The leading poultry supplier in East Africa. Key design elements to draw from:
- Full-screen hero slider with high-quality chicken/farm imagery
- Warm brand colors — Deep blue (#17549C) + Gold/Yellow (#FEC730) + White
- Animated navigation — Pulsing CTA buttons, smooth hover transforms
- Product showcase sections with hover effects and animated SVG icons
- Grid-based footer with social media, contact info, certifications
- Custom Slider Revolution full-width carousel
- "Kuku about you" section with hover-lift cards
- Knowledge center with animated icon grid
- Professional recipes / blog section with rounded cards and shadows
- Sustainability section with icon animations and toggle FAQs
- Corporate feel — multiple product pages (broilers, day-old chicks, butcheries)
- "Near Me" store locator functionality

### 2. Unsection.com Hero Designs — LAYOUT INSPIRATION
Modern hero section patterns to use:
- Large Typography heroes — Bold, oversized text with image/card combinations
- Card-based layouts — Clean card grids with hover effects
- Light + Minimal themes — Clean whitespace with strategic color pops
- Image-forward heroes — Full-bleed imagery with overlaid text
- Split layouts — Text on one side, featured image/card on the other
- Visible borders and subtle dividers for clean section separation
- Staggered card entrances and scroll-triggered animations

### 3. Framer Pitlane Template — TECH/SaaS LAYOUT INSPIRATION
A premium SaaS/Tech Startup template. Design patterns to borrow:
- Dark-to-light gradient transitions between sections
- Pill-shaped navigation with breadcrumbs
- Floating card UI elements with glassmorphism effects
- Modern sidebar layouts for marketplace/product pages
- Skeleton loading states for polished UX
- Clean pricing/product comparison cards
- Video hero sections with autoplay previews
- Professional marketplace-style product grid

---

## Design System

### Color Palette
```
Primary:       #1B5E20  (Deep Forest Green — represents agriculture, freshness)
Primary Light: #4CAF50  (Vibrant Green)
Accent:        #FF8F00  (Warm Amber/Gold — represents eggs, warmth, premium)
Accent Light:  #FFC107  (Golden Yellow)
Dark:          #1A1A2E  (Deep Navy — professional, trustworthy)
Surface:       #FAFDF6  (Off-white with green tint — organic feel)
White:         #FFFFFF
Text Primary:  #1A1A2E
Text Secondary:#5A6A72
Error/Alert:   #D32F2F
Success:       #388E3C
```

### Typography (Google Fonts)
```
Headings:    'Outfit', sans-serif — Modern, bold, premium feel
Body:        'Inter', sans-serif — Clean, highly readable
Accent/CTA:  'DM Sans', sans-serif — Friendly, approachable
```

### Design Tokens
```css
/* Spacing */
--space-xs: 0.25rem;    /* 4px */
--space-sm: 0.5rem;     /* 8px */
--space-md: 1rem;       /* 16px */
--space-lg: 1.5rem;     /* 24px */
--space-xl: 2rem;       /* 32px */
--space-2xl: 3rem;      /* 48px */
--space-3xl: 4rem;      /* 64px */
--space-4xl: 6rem;      /* 96px */

/* Border Radius */
--radius-sm: 8px;
--radius-md: 12px;
--radius-lg: 20px;
--radius-xl: 28px;
--radius-pill: 100px;

/* Shadows */
--shadow-card: 0 4px 24px rgba(0, 0, 0, 0.06);
--shadow-card-hover: 0 12px 40px rgba(0, 0, 0, 0.12);
--shadow-float: 0 20px 60px rgba(0, 0, 0, 0.08);
--shadow-glow-green: 0 0 40px rgba(76, 175, 80, 0.15);
--shadow-glow-gold: 0 0 40px rgba(255, 193, 7, 0.15);

/* Transitions */
--transition-fast: 0.2s ease;
--transition-base: 0.3s ease;
--transition-smooth: 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
--transition-bounce: 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
```

---

## Site Architecture & Pages

### Public Pages
```
/                           → Home (Landing Page)
/about                      → About Us / Our Story
/products                   → Products Overview
/products/broilers          → Broiler Chickens
/products/layers            → Layer Chickens & Eggs
/products/day-old-chicks    → Day-Old Chicks
/shop                       → E-commerce Store (mini shop)
/shop/product/{id}          → Single Product Page
/shop/cart                  → Shopping Cart
/shop/checkout              → Checkout (M-Pesa integration)
/services                   → Services (Consulting, Farm Management)
/recipes                    → Chicken Recipes (Blog-style)
/contact                    → Contact Us + Map
/faq                        → Frequently Asked Questions
```

### Auth Pages
```
/login                      → Login Page
/register                   → Registration Page
/forgot-password            → Password Reset
```

### Dashboard (Authenticated)
```
/dashboard                  → Farm Management Dashboard
/dashboard/flock            → Flock Management
/dashboard/production       → Egg/Meat Production Records
/dashboard/finance          → Financial Records
/dashboard/reports          → Reports & Analytics
```

---

## Page-by-Page Design Specification

### HOME PAGE — The Hero Experience

#### Section 1: Hero (Full Viewport)
**Inspiration:** Kenchic slider + Unsection large-type hero patterns
- Full-screen hero carousel (3-4 slides) with smooth crossfade transitions
- Each slide: Full-bleed background image + overlay gradient (dark bottom for text readability)
- Large headline text (60-80px on desktop, Outfit font, bold)
- Subtitle paragraph (18-20px, Inter, semi-transparent white)
- Two CTA buttons: "Shop Now" (primary filled) + "Learn More" (outlined/ghost)
- Slide indicators at bottom with animated progress bar
- Slides auto-advance every 6 seconds with pause-on-hover
- Subtle parallax on background images

```
Slide 1: "Farm Fresh. Always." — Hero image of golden chicken
Slide 2: "From Our Farm to Your Family" — Image of farm operations
Slide 3: "Quality Day-Old Chicks" — Close-up of chicks
Slide 4: "Trusted by Thousands" — Aerial farm view
```

#### Section 2: Trust Bar / Stats Strip
**Inspiration:** Kenchic's about-wrapper section
- Horizontal bar with 3-4 animated counters:
  - "10,000+ Chickens Raised"
  - "5,000+ Happy Customers"
  - "100% Quality Guaranteed"
  - "24/7 Farm Support"
- Numbers animate (count up) when scrolled into view
- Warm amber/gold background gradient
- White text with subtle icons

#### Section 3: About Preview
- Split layout: Left = large feature image (farmer with chickens), Right = text content
- Section title: "Why Wangari?"
- 3-4 key differentiators with animated icons
- "Learn More About Us" CTA link
- Image has a rounded corner with a decorative green accent border on one edge

#### Section 4: Product Showcase
**Inspiration:** Kenchic product row + Unsection card layouts
- Section title: "Our Products" with decorative line
- 3-column card grid (responsive: 1-col mobile, 2-col tablet, 3-col desktop)
- Each card:
  - Large product image (top, rounded corners)
  - Product name (Outfit bold)
  - Short description (Inter)
  - Price range indicator
  - "View Details" CTA button
  - Hover effect: Card lifts up (translateY(-8px)) + shadow deepens + image scales slightly
- Staggered entrance animation on scroll

#### Section 5: E-Commerce Featured Products
**Inspiration:** Kenchic one-stop-shop + Pitlane marketplace grid
- "Shop Fresh Chicken" heading
- Horizontal scrollable product carousel (or grid)
- Product cards with:
  - Product image
  - Name, weight, price
  - "Add to Cart" button with micro-animation
  - Quick-view hover overlay
- "View All Products" link at bottom

#### Section 6: How It Works / Farm-to-Table
- 4-step horizontal flow with connecting lines/arrows
- Step 1: "Hatching" → Step 2: "Growing" → Step 3: "Processing" → Step 4: "Delivery"
- Each step has an icon (SVG/CSS animated), title, and short description
- Steps animate in sequence on scroll

#### Section 7: Testimonials
**Inspiration:** Unsection testimonial section designs
- Carousel of customer quotes with fade transition
- Customer photo (circular avatar), name, location
- Quote text in larger italic font
- Star rating display
- Navigation dots + arrows

#### Section 8: Latest Recipes / Blog
**Inspiration:** Kenchic's nectar-post-grid recipe section
- "Delicious Recipes" section heading
- 3-card grid of latest recipe posts
- Each card: Image (top, 200px min-height, rounded top corners), title, excerpt
- Card hover: image zooms slightly, shadow deepens
- "See All Recipes" link

#### Section 9: Newsletter + CTA
- Full-width gradient background (green gradient)
- Large headline: "Stay Fresh — Get Updates"
- Email input + Subscribe button (pill-shaped)
- Privacy note text
- Background pattern: subtle chicken/egg silhouettes

#### Section 10: Footer
**Inspiration:** Kenchic's 3-column grid footer
- Dark background (#1A1A2E) with green accent line at top
- 3-column layout:
  - Col 1: Logo + Company description + Address + Phone
  - Col 2: Quick Links (Products, Services, About, Contact, FAQ)
  - Col 3: Social media icons + Newsletter mini-form
- Bottom bar: Copyright + Payment method icons + Privacy/Terms links

---

### SHOP / E-COMMERCE PAGES

#### Shop Landing
- Filter sidebar (categories, price range, availability)
- Product grid (3-4 columns) with sort dropdown
- Each product card: Image, name, price, "Add to Cart" button
- Pagination or infinite scroll

#### Single Product Page
- Split layout: Large image gallery (left) + Product details (right)
- Product name, price, weight options, quantity selector
- "Add to Cart" + "Buy Now" buttons
- Product description tabs (Description, Nutrition, Reviews)
- Related products section at bottom

#### Cart Page
- Clean table layout with product thumbnails, quantities, totals
- Update/Remove controls
- Coupon code input
- Order summary sidebar
- "Proceed to Checkout" CTA

#### Checkout
- Step-by-step form: Shipping → Payment → Confirmation
- M-Pesa payment integration (STK Push)
- Order summary alongside the form
- Form validation with real-time feedback

---

## Technical Architecture (PHP + cPanel)

### Directory Structure
```
wangari/
├── index.php                    # Home page
├── .htaccess                    # URL rewriting + security
├── config/
│   ├── database.php             # Database connection (PDO)
│   ├── constants.php            # Site-wide constants
│   └── session.php              # Session management
├── includes/
│   ├── header.php               # Global header + nav
│   ├── footer.php               # Global footer
│   ├── functions.php            # Helper functions
│   ├── auth.php                 # Authentication helpers
│   └── mailer.php               # Email functions
├── assets/
│   ├── css/
│   │   ├── style.css            # Main stylesheet (design system)
│   │   ├── components.css       # Component styles
│   │   ├── animations.css       # Animation keyframes
│   │   └── responsive.css       # Media queries
│   ├── js/
│   │   ├── main.js              # Core JavaScript
│   │   ├── carousel.js          # Hero slider
│   │   ├── cart.js              # Cart functionality
│   │   ├── animations.js        # Scroll animations (IntersectionObserver)
│   │   └── form-validation.js   # Form validation
│   ├── images/                  # Optimized images (WebP + fallbacks)
│   │   ├── hero/
│   │   ├── products/
│   │   ├── icons/
│   │   └── team/
│   └── fonts/                   # Self-hosted Google Fonts
├── pages/
│   ├── about.php
│   ├── products.php
│   ├── product-detail.php
│   ├── shop.php
│   ├── cart.php
│   ├── checkout.php
│   ├── contact.php
│   ├── recipes.php
│   ├── faq.php
│   ├── login.php
│   ├── register.php
│   └── services.php
├── dashboard/                   # Protected farm management area
│   ├── index.php
│   ├── flock.php
│   ├── production.php
│   ├── finance.php
│   └── reports.php
├── api/                         # AJAX endpoints
│   ├── cart.php
│   ├── checkout.php
│   ├── auth.php
│   └── mpesa-callback.php
└── admin/                       # Admin panel
    ├── index.php
    ├── products.php
    ├── orders.php
    └── settings.php
```

### Database Schema (MySQL)

We use a normalized relational schema built for performance, integrity, and scalability. All tables utilize the `InnoDB` storage engine and `utf8mb4_unicode_ci` collations.

```sql
-- 1. Users & Authentication
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'farm_manager', 'customer') DEFAULT 'customer',
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    phone_number VARCHAR(15),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. Products & E-commerce Catalog
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(150) NOT NULL UNIQUE,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    stock_quantity INT DEFAULT 0,
    weight_kg DECIMAL(5, 2),
    image_url VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
);

-- 3. Orders & Transactions
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    order_number VARCHAR(20) NOT NULL UNIQUE,
    status ENUM('pending', 'paid', 'processing', 'shipped', 'completed', 'cancelled') DEFAULT 'pending',
    total_amount DECIMAL(10, 2) NOT NULL,
    payment_method VARCHAR(50) DEFAULT 'mpesa',
    shipping_address TEXT NOT NULL,
    phone_contact VARCHAR(15) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price_at_purchase DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
);

CREATE TABLE mpesa_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    merchant_request_id VARCHAR(50) NOT NULL,
    checkout_request_id VARCHAR(50) NOT NULL UNIQUE,
    result_code INT,
    result_desc VARCHAR(255),
    amount DECIMAL(10, 2),
    mpesa_receipt_number VARCHAR(50),
    transaction_date TIMESTAMP NULL,
    phone_number VARCHAR(15),
    status ENUM('initiated', 'completed', 'failed') DEFAULT 'initiated',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- 4. Poultry Farm Operations & Records
CREATE TABLE flocks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    flock_name VARCHAR(100) NOT NULL,
    breed VARCHAR(100) NOT NULL,
    initial_count INT NOT NULL,
    current_count INT NOT NULL,
    hatch_date DATE NOT NULL,
    status ENUM('active', 'sold', 'archived') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE production_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    flock_id INT NOT NULL,
    record_date DATE NOT NULL,
    eggs_collected INT DEFAULT 0,
    cracked_eggs INT DEFAULT 0,
    meat_weight_kg DECIMAL(8, 2) DEFAULT 0.00,
    mortality INT DEFAULT 0,
    feed_consumed_kg DECIMAL(8, 2) DEFAULT 0.00,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (flock_id) REFERENCES flocks(id) ON DELETE CASCADE
);

CREATE TABLE vaccinations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    flock_id INT NOT NULL,
    vaccine_name VARCHAR(100) NOT NULL,
    scheduled_date DATE NOT NULL,
    administered_date DATE,
    status ENUM('scheduled', 'completed', 'missed') DEFAULT 'scheduled',
    administered_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (flock_id) REFERENCES flocks(id) ON DELETE CASCADE,
    FOREIGN KEY (administered_by) REFERENCES users(id) ON DELETE SET NULL
);

-- 5. Financial Bookkeeping
CREATE TABLE financial_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('income', 'expense') NOT NULL,
    category VARCHAR(100) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    transaction_date DATE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 6. Content Management & Feedback
CREATE TABLE recipes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    slug VARCHAR(150) NOT NULL UNIQUE,
    content TEXT NOT NULL,
    image_url VARCHAR(255),
    is_published TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE testimonials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(100) NOT NULL,
    customer_role VARCHAR(100),
    rating INT CHECK (rating BETWEEN 1 AND 5),
    content TEXT NOT NULL,
    is_approved TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

## Backend API & Security Specifications

### 1. Unified REST API Handler Structure
All AJAX endpoints are processed via stateless API endpoint controllers:
- POST /api/cart.php?action=add: Thread-safe session cart allocation.
- POST /api/checkout.php: M-Pesa STK Push execution pipeline.
- POST /api/mpesa-callback.php: Decrypts and processes Safaricom Daraja API callbacks.

### 2. cPanel-Specific Hardening
- **Root Directory Isolation**: The public index directs requests to a secure workspace folder. Database credentials reside one directory above public HTML (../secure_config.php).
- **PHP Session Isolation**: Store files dynamically with private permissions to prevent cross-site session hijacking on shared hosting.

### 3. Threat Mitigation Protocol
- **SQL Injection**: Strict parameterized queries via PDO:
  ```php
  $stmt = $pdo->prepare("SELECT * FROM products WHERE category_id = :cat_id AND is_active = 1");
  $stmt->execute(['cat_id' => $categoryId]);
  ```
- **XSS Prevention**: Safe output filters for client-submitted HTML:
  ```php
  function escape(string $raw): string {
      return htmlspecialchars($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');
  }
  ```
- **Image Upload Filter**: Validates file MIME types, enforces renaming schema, and compresses images upon upload.

---

## Admin Dashboard Design & Management System

The admin dashboard is the hub of the entire application. It must be beautiful, visual, and support lightning-fast data entry. It is designed to be simple, clean, and intuitive enough for a Grade 7 student to run, but features highly polished, smooth, and animated charts that provide a professional corporate visual aesthetic.

### 1. Dashboard Layout & Navigation
- **Persistent Sidebar Layout**: Built with custom Flexbox. Slides away on mobile. Minimalist design with text labels.
- **Top Bar**: User profile summary, notification toggle, and quick "Log Farm Record" button.
- **Micro-Animations**: Hover actions expand sidebar navigation tabs with smooth 0.2s transition.

### 2. Main Dashboard Screens

#### Screen 1: Executive Overview & Analytics
- **Live Counter Widgets**: Four major cards displaying Daily Eggs Collected, Monthly Sales (KES), Total Flocks, and Active Orders.
- **Interactive Graph Area**: Uses lightweight, highly animated SVG charts (using CSS variables and transition triggers) or a simple Chart.js configuration with custom easing functions for smooth page-load animations. Shows production yields versus expenses.
- **Pending Actions Box**: Lists upcoming vaccination alerts and pending checkout orders needing manual validation.

#### Screen 2: Catalog Manager (E-commerce)
- **Product Datagrid**: Inline product lists showing images, stock counts, category, and status toggles.
- **Dynamic Creation Portal**: Floating popup or modular sidebar form for adding new chickens/eggs. Offers instant preview of product descriptions.
- **Stock Alert Highlights**: Flags products with low stock automatically in orange.

#### Screen 3: Order Processing Hub
- **Orders List View**: Detailed rows showing Customer Details, Date, Order Status (color-coded badges), and Total Amount.
- **Order Invoice Card**: Shows detailed breakdown, payment validation state, and manual status overrides (e.g. mark as "Shipped" or "Completed").
- **Payment Verification Area**: Directly binds M-Pesa transaction reference numbers with internal user invoices.

#### Screen 4: Flock & Poultry Records Manager
- **Active Flocks Overview**: Visualization of bird counts, hatch dates, feed schedules, and calculated mortality rates.
- **Daily Logger**: Tabular layout enabling rapid logging of eggs, feed, weight, and mortality. Designed with large inputs and instant auto-save validations to keep operations extremely simple.
- **Vaccination Scheduler**: Date-picker planner generating SMS alert logs.

#### Screen 5: Bookkeeper (Finance Ledger)
- **Transaction Ledger**: Ledger listing all farm revenues and expenses with filter controls.
- **Expense Logger**: Populates ledger with dynamic dropdown category selection.

#### Screen 6: Site Content Editor (CMS)
- **Recipe Composer**: Integrated markdown compiler for managing the recipe blog.
- **Testimonial Moderator**: Triage portal for customer feedback approval.

---

## Key Technical Requirements
- **PHP 8.1+** with PDO for database
- **MySQL 8.0+** database
- **URL rewriting** via .htaccess (clean URLs)
- **Session-based authentication** with bcrypt password hashing
- **M-Pesa STK Push** integration (Daraja API)
- **Image optimization** — WebP with JPEG fallbacks
- **Responsive design** — Mobile-first approach
- **SEO optimized** — Semantic HTML5, proper meta tags, Schema.org markup
- **Security** — CSRF tokens, prepared statements, input sanitization, XSS prevention
- **Performance** — Lazy loading images, minified CSS/JS, browser caching

---

## Animations & Micro-Interactions

### Scroll Animations (IntersectionObserver)
- **Fade-up** — Elements fade in and translate up on scroll entry
- **Stagger** — Card grids animate with 100ms delay between items
- **Counter animation** — Stats count up when visible
- **Parallax** — Hero background scrolls at 0.5x speed

### Hover Effects
- **Cards:** translateY(-8px) + deeper shadow + slight image scale
- **Buttons:** Background shift + scale(1.02) + subtle glow
- **Navigation links:** Underline slides in from left
- **Product images:** Zoom 1.05x with overflow hidden

### Page Transitions
- Smooth scroll for anchor links
- Form submission loading states with spinner

### Loading States
- Skeleton screens for product grids
- Pulse animation on loading elements
- Smooth progress indicator on checkout steps

---

## Responsive Breakpoints
```css
/* Mobile First */
/* Small phones */    max-width: 479px
/* Phones */          max-width: 767px
/* Tablets */         max-width: 991px
/* Small Desktop */   max-width: 1199px
/* Desktop */         min-width: 1200px
/* Large Desktop */   min-width: 1440px
```

---

## Quality Standards
- **Performance:** LCP < 2.5s, FCP < 1.5s, CLS < 0.1
- **Accessibility:** WCAG 2.1 AA compliance
- **SEO:** Meta tags, OG tags, Schema.org, sitemap.xml
- **Security:** HTTPS, CSRF, XSS prevention, SQL injection protection
- **Browser Support:** Chrome, Firefox, Safari, Edge (last 2 versions)

---

## Image Placeholders
> **NOTE:** All images are placeholder-ready. The site owner will provide actual photographs later. During development, use generated placeholder images or solid color blocks with descriptive text (e.g., "Hero Image: Golden Chicken on Farm").

---

## Development Phases

### Phase 1: Foundation
- [ ] Project setup + directory structure
- [ ] Database schema + connection
- [ ] Design system (CSS variables, typography, colors)
- [ ] Header/Footer/Navigation components
- [ ] Home page hero section

### Phase 2: Core Pages
- [ ] Home page (all sections)
- [ ] About page
- [ ] Products pages
- [ ] Contact page with form
- [ ] FAQ page

### Phase 3: E-Commerce
- [ ] Shop page + product grid
- [ ] Product detail page
- [ ] Cart functionality
- [ ] Checkout flow
- [ ] M-Pesa integration

### Phase 4: Auth & Dashboard
- [ ] Login/Register pages
- [ ] Dashboard layout
- [ ] Farm management features
- [ ] Reports & analytics

### Phase 5: Polish
- [ ] All animations & transitions
- [ ] SEO optimization
- [ ] Performance optimization
- [ ] Security hardening
- [ ] Mobile testing & fixes
- [ ] Content integration (real images/text)
