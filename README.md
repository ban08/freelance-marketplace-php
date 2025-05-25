# ltw-project-ltw07g06

A lightweight freelance marketplace platform built with PHP, SQLite, HTML5, CSS3 and vanilla JavaScript.  
Allows users to register as clients or freelancers (or both), list and browse services, message each other, hire services (simulated checkout), leave ratings, and administer the system.

---


## Features

### Common to All Users
- Register, log in/out, edit profile (name, email, password, bio).  
- Responsive UI.

### Freelancers
- Create, edit, pause/activate, delete service listings (title, category, description, price, delivery time, images).  
- View and respond to client inquiries and custom order requests.  
- Mark orders as completed.

### Clients
- Browse and filter services by category, price, and rating.  
- View service details, initiate chat, request custom orders.  
- Simulate checkout and view order history.  
- Leave star ratings for completed services.

### Admins
- Promote users to admin status.  
- Manage service categories (add/delete).  
- Oversee platform data via an admin panel.

---

## Tech Stack

- **Database**: SQLite  
- **Security**:  
  - Prepared statements (PDO)  
  - Output escaped with `htmlspecialchars()`  
  - Passwords hashed via `password_hash()`  

---

## Database Schema

Defined in `db/database.sql`. Key tables:

- `users` (id, username, password, name, email, tipo, is_admin, bio, profile_picture, joined_date)  
- `categories` (id, name)  
- `services` (id, freelancer_id, category_id, title, description, base_price, delivery_time_days, status, created_at)  
- `service_images` (id, service_id, image_path, display_order)  
- `orders` (id, client_id, service_id, price, requirements, order_date, status, completion_date)  
- `messages` (id, sender_id, receiver_id, order_id, content, sent_at)  
- `reviews` (id, client_id, service_id, rating, comment, created_at)

---


## Default Accounts

- **Admin (built-in shortcut)**  
  - Email: `admin`  
  - Password: `admin`

- **Test Users**: Register via the UI or seed with sample data.

---

## Project Structure

```
/
├─ css/               # Stylesheets
├─ db/
│   ├ database.sql    # Schema
│   └ sample_data.sql
├─ pages/             # Page controllers (browse, services, login, register, etc.)
├─ templates/         # Shared header, footer, bootstrap scripts
├─ uploads/           # User and service media
├─ database.sqlite    # SQLite database file
└─ index.php          # Landing page
```

---

## Configuration

- Database file path is set in `templates/bootstrap.php`.  


 