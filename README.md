# 🏢 GestionSalles - Room Reservation Management System

A modern, bilingual (French & Arabic) web application for managing room/hall reservations. Built with PHP, MySQL, and secure authentication.

**Languages:** 🇫🇷 French | 🇸🇦 Arabic

---

## ✨ Features

### 👥 User Features
- **User Authentication**
  - Register new account
  - Secure login with password hashing (bcrypt)
  - Role-based access (Admin/User)

- **Room Management**
  - Browse available rooms with capacity information
  - View detailed room information
  - Real-time availability checking

- **Reservations**
  - Reserve rooms for specific dates and times
  - Prevent double-booking (time conflict detection)
  - View personal reservation history
  - Cancel/manage existing reservations

### 🔐 Admin Features
- **Dashboard**
  - System statistics (total rooms, total reservations)
  - Admin control panel

- **Room Management**
  - Add new rooms with capacity and description
  - Edit existing room information
  - Delete rooms and associated reservations

- **User Management**
  - View all registered users
  - Manage user accounts
  - Delete user accounts if needed

---

## 🛠️ Tech Stack

| Technology | Purpose |
|-----------|---------|
| **PHP 8.2+** | Backend server-side logic |
| **MySQL 5.7+** | Database (MariaDB 10.4+) |
| **PDO** | Secure database abstraction layer |
| **HTML5/CSS** | Frontend interface |
| **Sessions** | Secure user authentication |

---

## 📂 Project Structure

```
ملفات/
├── admin.php                 # Admin user management
├── admin_dashboard.php       # Admin dashboard (statistics)
├── index.php                 # Home page
├── login.php                 # User login
├── logout.php                # User logout
├── register.php              # User registration
├── reservation.php           # Reservation system
├── salles.php                # Room listing
├── salle_details.php         # Room details view
├── lang.php                  # Language/i18n configuration
├── config.php                # Database configuration
├── test_db.php               # Database connection test
├── gestionlesall.sql         # Database schema & seed data
├── gestionlesall_production.sql  # Production backup
├── asstes/                   # Assets & screenshots
│   └── Screenshot_*.png      # UI screenshots
└── README.md                 # This file
```

---

## 🗄️ Database Schema

### Tables

#### `authentification`
User accounts with authentication data
```sql
- id (PK)
- nom (Name)
- email (Unique)
- mot_de_passe (Hashed password)
- role (ENUM: 'admin', 'user')
```

#### `gestion`
Available rooms/halls for reservation
```sql
- id (PK)
- nom (Room name)
- capacite (Capacity/max persons)
- description (Room details)
```

#### `reservation`
Booking records
```sql
- id (PK)
- id_user (FK → authentification)
- id_salle (FK → gestion)
- date (Booking date)
- heure_debut (Start time)
- heure_fin (End time)
```

---

## 🚀 Installation & Setup

### Prerequisites
- PHP 8.2 or higher
- MySQL 5.7+ or MariaDB 10.4+
- XAMPP, WAMP, or similar local development environment

### Local Development Setup

1. **Clone or download the project**
   ```bash
   cd c:\xampp\htdocs
   # Place project folder here
   ```

2. **Import database**
   - Open phpMyAdmin: `http://localhost/phpmyadmin`
   - Create new database: `gestionlesall`
   - Import: `gestionlesall.sql`

3. **Configure database credentials** (if needed)
   - Edit `config.php`
   - Local environment uses:
     - Host: `localhost`
     - Username: `root`
     - Password: (empty by default)
     - Database: `gestionlesall`

4. **Test database connection**
   ```
   http://localhost/test_db.php
   ```

5. **Start the application**
   ```
   http://localhost/
   ```

### Default Test Credentials

| User | Email | Password | Role |
|------|-------|----------|------|
| Admin | mohasbt77@gmail.com | 1234 | Admin |
| Admin | djebiriabdrazak@gmail.com | 1234 | Admin |
| User | sara@email.com | 323255 | User |

⚠️ **Note:** Change these credentials in production!

---

## 🔐 Security Features

### ✅ Implemented Security
- **Password Hashing**: bcrypt (password_hash/verify)
- **Prepared Statements**: PDO with parameterized queries (SQL injection prevention)
- **CSRF Protection**: Token-based validation on forms
- **Session Security**:
  - HttpOnly cookies
  - SameSite=Lax
  - HTTPS support in production
  - 1-hour session timeout
- **Input Validation**: Server-side validation for all inputs
- **Error Handling**: Safe error messages (no detailed DB errors exposed)

### 🔧 Production Deployment
The system detects environment:
- **Local** (localhost): Shows detailed error messages for debugging
- **Production** (ezyro.com): Hides sensitive error details, logs internally

---

## 📝 File Descriptions

| File | Description |
|------|-------------|
| `index.php` | Home page with room preview and reservation summary |
| `login.php` | User login form |
| `register.php` | New user registration |
| `salles.php` | Browse all available rooms |
| `salle_details.php` | View detailed room information |
| `reservation.php` | Create new reservation with date/time picker |
| `admin.php` | Admin panel for user management |
| `admin_dashboard.php` | Dashboard with statistics |
| `config.php` | Database connection & session configuration |
| `lang.php` | Multilingual support (FR/AR) |
| `test_db.php` | Database connection diagnostics |

---

## 🌐 Multilingual Support

The system supports **French** and **Arabic** with RTL support:

- Language selection in `lang.php`
- All UI text translated
- RTL (Right-to-Left) layout for Arabic
- Locale: `fr_FR` (French) or `ar_SA` (Arabic)

---

## 📱 User Workflows

### 1️⃣ **First-Time User**
```
Register → Login → View Rooms → Make Reservation → Manage Bookings
```

### 2️⃣ **Making a Reservation**
```
Select Room → Choose Date/Time → Verify Availability → Confirm → Success
```

### 3️⃣ **Admin Panel**
```
Login as Admin → Access Dashboard → Manage Rooms/Users → View Statistics
```

---

## ⚡ API-like Features

### Key Operations

**Reserve a Room:**
- POST to `reservation.php`
- Required: Room ID, Date, Start/End Time
- Checks: Date validity, time range, room existence, booking conflicts

**Add Room (Admin):**
- POST to `admin_dashboard.php`
- Required: Name, Capacity
- Optional: Description

**View Reservations:**
- Logged-in users see only their reservations
- Admins can view system-wide data

---

## 🐛 Known Issues & TODOs

- [ ] Email confirmation for new registrations
- [ ] Password reset functionality
- [ ] Cancellation/modification of reservations
- [ ] Notification system
- [ ] Payment integration (if needed)
- [ ] Advanced filtering and search
- [ ] Export reports (PDF/Excel)
- [ ] Mobile responsive design optimization

---

## 📊 Screenshots

Project includes UI preview screenshots in the `asstes/` folder:

### Login Interface
![Login Interface](asstes/Screenshot%20(27).png)

### Room Browsing
![Room Browsing](asstes/Screenshot%20(28).png)

### Reservation Form
![Reservation Form](asstes/Screenshot%20(30).png)

### Admin Dashboard
![Admin Dashboard](asstes/Screenshot%20(31).png)

### User Profile & Reservations
![User Profile](asstes/Screenshot%20(32).png)

---

## 🧪 Testing

### Manual Testing Checklist
- [ ] User registration and login
- [ ] Room viewing and filtering
- [ ] Make reservation (valid dates)
- [ ] Prevent double-booking
- [ ] Admin add/edit/delete rooms
- [ ] Admin user management
- [ ] Language switching (FR/AR)
- [ ] Session timeout after 1 hour
- [ ] Database backup and restore

### Test Connection
```
http://localhost/ملفات/test_db.php
```

---

## 📞 Support & Contact

- **Project Type**: Room/Hall Reservation System
- **Version**: 1.0
- **Last Updated**: May 2026
- **Database**: MariaDB 10.4.32

---

## 📄 License

This project is provided as-is for educational and development purposes.

---

## 🚀 Future Enhancements

1. **Email Notifications**: Automatic confirmation emails
2. **Calendar View**: Month/week calendar interface
3. **Analytics**: Usage reports and statistics
4. **Payment Gateway**: Online payment processing
5. **Mobile App**: Native iOS/Android applications
6. **API**: RESTful API for third-party integrations
7. **Availability Calendar**: Visual availability display
8. **User Roles**: Extended role system (Manager, SuperAdmin)

---

**Built with ❤️ for efficient room management**

