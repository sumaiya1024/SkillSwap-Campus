# SkillSwap Campus

A university mini-project where students can share and exchange skills with each other.

## Tech Stack

- **Frontend:** HTML5, CSS3, Bootstrap 5, JavaScript
- **Backend:** PHP (procedural, mysqli)
- **Database:** MySQL
- **Server:** XAMPP / WAMP (localhost)

## Features

- Student registration & login
- Profile management with photo upload
- Add skills you can teach
- Browse students & skills with search/filter
- Send/accept/reject skill exchange requests
- Schedule sessions
- Rating & review system
- Admin panel (manage users, categories, skills)

## Setup

1. Install [XAMPP](https://www.apachefriends.org/) or WAMP
2. Clone this repo into your `htdocs` folder:
   ```
   git clone https://github.com/sumaiya1024/SkillSwap-Campus.git
   ```
3. Start Apache and MySQL from XAMPP Control Panel
4. Import the database:
   - Open [phpMyAdmin](http://localhost/phpmyadmin)
   - Create a new database called `skillswap_campus`
   - Import `database/skillswap_campus.sql`
5. Open in browser: `http://localhost/SkillSwap-Campus/`

## Demo Accounts

| Role    | Email                    | Password    |
|---------|--------------------------|-------------|
| Admin   | admin@skillswap.com      | admin123    |
| Student | alice@university.edu     | password123 |
| Student | bob@university.edu       | password123 |
| Student | carol@university.edu     | password123 |

## Database Schema

8 tables: `users`, `students`, `skill_categories`, `skills`, `student_skills`, `skill_requests`, `sessions`, `reviews`

## Project Structure

```
SkillSwap/
├── config/db.php
├── includes/ (header, footer, auth)
├── admin/ (dashboard, manage users/categories/skills)
├── assets/css/ & assets/js/
├── uploads/profiles/
├── database/skillswap_campus.sql
├── index.php, login.php, register.php, logout.php
├── dashboard.php, profile.php, edit_profile.php
├── my_skills.php, browse.php, student_detail.php
├── my_requests.php, incoming_requests.php
├── my_sessions.php, add_review.php
```

## License

University DBMS Mini-Project © 2026
