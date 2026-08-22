# SkillSwap Campus

A university mini-project where students can share and exchange skills with each other.

## Tech Stack

- **Frontend:** HTML5, CSS3, Bootstrap 5, Bootstrap Icons, JavaScript
- **Backend:** PHP (using PDO with prepared statements)
- **Database:** MySQL 8 (Normalized to 3NF)
- **Server:** XAMPP / WAMP / Apache (localhost)

## Features

### Authentication & Student Profile
- User Registration, Secure Login, and Logout
- Profile management with university ID, department, bio, and optional avatar upload

### Skills
- List teaching skills with proficiency levels (Beginner, Intermediate, Advanced)
- Remove teaching skills
- Full campus skill directory grouped by category

### Student Search & Requests
- Filter and search fellow students by skill keyword, category, name, or department
- Send skill exchange / learning requests with custom messages
- Accept or reject incoming requests

### Sessions & Reviews
- Book 1-on-1 learning sessions with date, time, duration, and campus location
- Mark sessions completed or cancel sessions
- Submit 1–5 star ratings and reviews for completed sessions
- View received feedback and average rating metrics

### Admin Panel
- **Dashboard:** Campus-wide statistics, recent registrations, requests, and sessions
- **Manage Students:** View student accounts, metrics, and remove accounts
- **Manage Skills & Categories:** Create/delete skill categories and individual skills
- **Manage Requests:** Filter by status (pending, accepted, rejected) and moderate requests
- **Manage Sessions:** Track, update statuses, and moderate all scheduled/completed sessions

---

## Setup & Installation

1. Install [XAMPP](https://www.apachefriends.org/)
2. Clone or copy this project into your `htdocs` directory:
   ```bash
   git clone https://github.com/sumaiya1024/SkillSwap-Campus.git
   ```
3. Start **Apache** and **MySQL** in the XAMPP Control Panel
4. Import the database schema:
   - Open **phpMyAdmin** (`http://localhost/phpmyadmin`)
   - Create a database called `skillswap_campus` or directly import `database.sql`
5. Open the project in your browser:
   ```
   http://localhost/SkillSwap/
   ```

---

## Demo Accounts

| Role    | Email                    | Password    | Description |
|---------|--------------------------|-------------|-------------|
| Admin   | admin@skillswap.com      | admin123    | Full system administration |
| Student | alice@university.edu     | password123 | CS student (teaches Python, JS, Web Dev) |
| Student | bob@university.edu       | password123 | EE student (teaches Guitar, Physics, C++) |
| Student | carol@university.edu     | password123 | BBA student (teaches UI/UX, Graphic Design, English) |
| Student | david@university.edu     | password123 | Literature student (teaches Spanish, Japanese, Essay Writing) |

---

## Project Structure

```
SkillSwap/
├── config/
│   └── db.php                  # PDO MySQL connection
├── includes/
│   ├── auth.php                # Authentication helper functions
│   ├── header.php              # Shared head & navbar
│   └── footer.php              # Shared footer & scripts
├── admin/
│   ├── dashboard.php           # Admin metrics & activity overview
│   ├── students.php            # Moderate students
│   ├── skills.php              # Moderate categories & catalog skills
│   ├── requests.php            # Moderate exchange requests
│   └── sessions.php            # Moderate learning sessions
├── assets/
│   ├── css/style.css           # Modern custom CSS & theme tokens
│   └── js/script.js            # JavaScript UI helpers
├── uploads/
│   └── profiles/               # Uploaded avatar images
├── database.sql                # Complete MySQL 8 schema with 3NF & seed data
├── index.php                   # Public landing page
├── login.php                   # User login
├── register.php                # Student registration
├── logout.php                  # Logout handler
├── dashboard.php               # Student dashboard & overview
├── profile.php                 # View & edit student profile
├── skills.php                  # Manage own teaching skills & directory
├── browse_students.php         # Search students by skill & send requests
├── requests.php                # Incoming & outgoing skill requests
├── sessions.php                # Book, manage & complete learning sessions
└── reviews.php                 # Rate completed sessions & view feedback
```

## Database Schema (3NF)

- `users` (user_id, email, password, role, created_at)
- `students` (student_id, full_name, university_id, department, bio, profile_picture, phone)
- `skill_categories` (category_id, category_name)
- `skills` (skill_id, skill_name, category_id)
- `student_skills` (student_skill_id, student_id, skill_id, proficiency_level)
- `skill_requests` (request_id, requester_id, provider_id, skill_id, status, message, created_at)
- `sessions` (session_id, request_id, session_date, session_time, duration_minutes, location, status, created_at)
- `reviews` (review_id, session_id, reviewer_id, reviewee_id, rating, comment, created_at)

---

## License

University DBMS Mini-Project © 2026
