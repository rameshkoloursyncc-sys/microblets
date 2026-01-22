# Microbelts Inventory Management System - Project Structure

## Overview
Laravel-based inventory management system for belt products with Vue.js frontend, session-based authentication, and comprehensive transaction tracking.

## Directory Structure

```
microbelts_ima/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   ├── AuthController.php           # Authentication & user management
│   │   │   │   ├── VeeBeltController.php        # Vee belt inventory management
│   │   │   │   ├── TimingBeltController.php     # Timing belt inventory management
│   │   │   │   ├── CoggedBeltController.php     # Cogged belt inventory management
│   │   │   │   ├── PolyBeltController.php       # Poly belt inventory management
│   │   │   │   ├── TpuBeltController.php        # TPU belt inventory management
│   │   │   │   ├── SpecialBeltController.php    # Special belt inventory management
│   │   │   │   ├── DashboardController.php      # Dashboard statistics
│   │   │   │   └── RateFormulaController.php    # Rate calculation formulas
│   │   │   └── Controller.php                   # Base controller
│   │   └── Middleware/
│   │       └── CheckSession.php                 # Session validation middleware
│   ├── Mail/
│   │   └── LowStockReport.php                   # Email notifications for low stock
│   ├── Models/
│   │   ├── User.php                             # User model
│   │   ├── VeeBelt.php                          # Vee belt model
│   │   ├── TimingBelt.php                       # Timing belt model
│   │   ├── CoggedBelt.php                       # Cogged belt model
│   │   ├── PolyBelt.php                         # Poly belt model
│   │   ├── TpuBelt.php                          # TPU belt model
│   │   ├── SpecialBelt.php                      # Special belt model
│   │   └── Transaction.php                      # Transaction history model
│   └── Console/
│       └── Commands/                            # Artisan commands for maintenance
├── database/
│   ├── migrations/                              # Database schema migrations
│   └── seeders/                                 # Database seeders
├── resources/
│   ├── js/
│   │   ├── components/
│   │   │   ├── inventory/                       # Inventory management components
│   │   │   └── auth/                            # Authentication components
│   │   ├── composables/                         # Vue composables for API calls
│   │   └── lib/
│   │       └── axios.ts                         # HTTP client configuration
│   └── views/                                   # Blade templates
├── routes/
│   ├── api.php                                  # API routes
│   └── web.php                                  # Web routes
├── config/                                      # Laravel configuration files
├── storage/
│   └── logs/                                    # Application logs
└── public/                                      # Public assets
```

## Core Features

### 1. Authentication System
- Session-based authentication with fallback mechanisms
- User roles: admin, user
- Auto-login for admin users
- Session persistence across page refreshes

### 2. Inventory Management
- Multiple belt types: Vee, Timing, Cogged, Poly, TPU, Special
- Real-time stock tracking
- IN/OUT operations with transaction history
- Low stock alerts and reorder level management
- Bulk import/export functionality

### 3. Transaction Tracking
- Complete audit trail for all inventory changes
- User attribution for all transactions
- Stock level history
- Rate change tracking

### 4. Dashboard & Reporting
- Real-time inventory statistics
- Low stock notifications
- Email alerts for inventory levels
- Comprehensive filtering and search

## Database Schema

### Core Tables
- `users` - User accounts and roles
- `vee_belts` - Vee belt inventory
- `timing_belts` - Timing belt inventory
- `cogged_belts` - Cogged belt inventory
- `poly_belts` - Poly belt inventory
- `tpu_belts` - TPU belt inventory
- `special_belts` - Special belt inventory
- `transactions` - Transaction history
- `sessions` - Session storage

### Key Relationships
- All belt tables → `transactions` (one-to-many)
- `users` → `transactions` (one-to-many)
- Session-based user tracking across all operations

## Technology Stack

### Backend
- **Framework**: Laravel 12
- **Database**: MySQL
- **Authentication**: Session-based with database storage
- **Email**: SMTP (Gmail)
- **Caching**: Database cache

### Frontend
- **Framework**: Vue.js 3 with Composition API
- **Build Tool**: Vite
- **HTTP Client**: Axios with credentials
- **Styling**: Tailwind CSS
- **State Management**: Vue Composables

### Infrastructure
- **Session Storage**: Database (for reliability)
- **File Storage**: Local filesystem
- **Logging**: Laravel logs with rotation
- **Environment**: Production-ready configuration