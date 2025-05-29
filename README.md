# LTW Project - ltw07g06

**A comprehensive freelance marketplace platform** This platform enables users to register as clients or freelancers, create and browse services, communicate through messaging, manage orders, and administer the entire system.


## 🚀 Features

### 👥 **User Management & Authentication**
- **Secure Registration & Login** with form validation and error handling
- **Profile Management** - Edit personal information, bio, and profile pictures
- **Admin Panel** - Comprehensive administrative controls and statistics

### 💼 **For Freelancers**
- **Service Creation** - Create detailed service listings with categories, pricing, and delivery times
- **Service Management** - Edit, pause, activate, or delete service listings
- **Image Uploads** - Multiple image support for service galleries
- **Order Management** - View, track, and mark orders as completed
- **Client Communication** - Real-time messaging system with clients
- **Custom Order Requests** - Handle personalized service requests

### 🛒 **For Clients**
- **Service Discovery** - Browse and search services with advanced filtering
- **Smart Filtering** - Filter by category, price range, ratings, and keywords
- **Service Details** - Comprehensive service pages with reviews and ratings
- **Checkout System** - Simulated payment processing for service orders
- **Order Tracking** - View order history and status updates
- **Review System** - Leave star ratings and comments for completed services
- **Direct Messaging** - Communicate directly with freelancers

### ⚙️ **Advanced Features**
- **Real-time Messaging** - Instant communication between users
- **Security Features** - CSRF protection, XSS prevention, SQL injection protection

---

### **Security & Best Practices**
- **CSRF Protection** - Token-based protection against cross-site request forgery
- **XSS Prevention** - All output properly escaped with `htmlspecialchars()`
- **SQL Injection Protection** - PDO prepared statements throughout
- **Password Security** - Bcrypt hashing with `password_hash()`
- **Input Validation** - Comprehensive server-side validation
- **File Upload Security** - Validated file types and secure file handling
- **Rate Limiting** - Protection against brute force attacks

---

## 📊 **Database Schema**

The application uses a well-structured SQLite database defined in `db/database.sql`:

### **Core Tables**
- **`users`** - User accounts (id, username, password, name, email, tipo, is_admin, bio, profile_picture, joined_date)
- **`categories`** - Service categories (id, name)
- **`services`** - Service listings (id, freelancer_id, category_id, title, description, base_price, delivery_time_days, status)
- **`service_images`** - Service image gallery (id, service_id, image_path, display_order)

### **Transaction Tables**
- **`orders`** - Service orders (id, client_id, service_id, price, requirements, order_date, status, completion_date)
- **`messages`** - User communications (id, sender_id, receiver_id, order_id, content, sent_at)
- **`reviews`** - Service reviews (id, client_id, service_id, rating, comment, created_at)

---

## 🏗 **Project Structure**

```
ltw-project-ltw07g06/
├── 📁 css/                    # Stylesheets
│   ├── style.css              # Main application styles
│   └── uploads/               # Uploaded CSS assets
├── 📁 db/                     # Database files
│   ├── database.sql           # Database schema
│   └── db.php                 # Database connection
├── 📁 img/                    # Static images
│   ├── default-avatar.png     # Default user avatars
│   └── default-service.jpg    # Default service images
├── 📁 includes/               # Shared functionality
│   └── security.php           # Security functions
├── 📁 js/                     # JavaScript files
│   └── main.js                # Client-side functionality
├── 📁 logs/                   # Application logs
├── 📁 pages/                  # Application pages
│   ├── admin_panel.php        # Admin dashboard
│   ├── browse.php             # Service browsing
│   ├── checkout.php           # Order checkout
│   ├── dashboard.php          # User dashboard
│   ├── login.php              # User authentication
│   ├── register.php           # User registration
│   ├── services.php           # Service details
│   ├── messages.php           # Messaging system
│   ├── manage_services.php    # Service management
│   ├── new_service.php        # Service creation
│   ├── edit_service.php       # Service editing
│   ├── orders.php             # Order management
│   ├── my_orders.php          # Client order history
│   ├── profile.php            # User profiles
│   ├── edit_profile.php       # Profile editing
│   ├── inquiries.php          # Freelancer inquiries
│   ├── client_inquiries.php   # Client communications
│   ├── request_custom_order.php # Custom orders
│   └── logout.php             # Session termination
├── 📁 scripts/                # Utility scripts
├── 📁 templates/              # Shared templates
│   ├── bootstrap.php          # Application bootstrap
│   ├── header.php             # Common header
│   └── footer.php             # Common footer
├── 📁 uploads/                # User uploads
│   ├── profiles/              # User profile pictures
│   └── services/              # Service images
├── 🗄 database.sqlite         # SQLite database file
├── 🏠 index.php               # Landing page
└── 📖 README.md               # Project documentation
```


---

## 👥 **Team**

**Group:** ltw07g06  
**Course:** Languages and Web Technologies (LTW)  
**Institution:** FEUP - Faculdade de Engenharia da Universidade do Porto