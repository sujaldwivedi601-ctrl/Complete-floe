# Precision Architect - Project Report

**Generated:** April 20, 2026

---

## Project Overview

- **Project Name:** Precision Architect
- **Type:** Web-based Educational Timetable Management System
- **Description:** An intelligent scheduling system designed to automate and simplify the creation of conflict-free timetables for educational institutions. The system supports multiple user roles (Student, Teacher, Admin), multi-semester management, PDF-based subject extraction, and automated timetable generation.

### Technology Stack

| Layer | Technology |
|-------|-----------|
| Frontend | HTML5, CSS3, Tailwind CSS, JavaScript |
| Backend | PHP (native) |
| Database | MySQL (auth_system database) |
| External | Google Fonts, Material Symbols |

---

## Pages & Functionality

### Frontend Pages

| Page | Status | Description |
|------|--------|-------------|
| `FOR_everyOne/login.html` | Working | User login portal. Accepts username and password, submits to PHP/login.php for authentication. |
| `FOR_everyOne/registretion.html` | Working | New user registration form. Fields: Name, Email, Role (Student/Teacher/Admin), Institution, Password. |
| `FOR_everyOne/index.html` | Working | Main dashboard after login. Shows user profile (Name, Email, Role, Institution), statistics, and saved timetables. |
| `FOR_everyOne/generate_timetable.html` | Working | Main timetable generation page. Allows input of teachers and uploading PDF files for each semester (1-6). |
| `FOR_everyOne/personal_Timetable.html` | Incomplete | Intended to show user's personal timetable. Currently shows header and basic template but lacks content. |
| `FOR_everyOne/view_timetable.html` | Working | Detailed view of a saved timetable. Fetches data via PHP API, displays in table format with days/time slots. |
| `GPC/GPC.html` | Working | Teacher & Subject Manager. Allows adding teachers, selecting semesters, uploading PDFs, extracting subjects. |
| `temp.html` | Template Only | Partial template for semester selection UI. Used as reference/layout for other pages. |

### Backend PHP Files

| File | Purpose |
|------|---------|
| `PHP/db.php` | Database connection (host: localhost, user: root, password: "", database: auth_system) |
| `PHP/login.php` | User authentication with session management |
| `PHP/register.php` | User registration with prepared statements |
| `PHP/logout.php` | Session destruction |
| `PHP/get_user_details.php` | Fetch user profile data |
| `PHP/save_timetable.php` | Save generated timetable |
| `PHP/get_timetables.php` | Retrieve all timetables |
| `PHP/get_timetable_by_id.php` | Get single timetable by ID |
| `PHP/delete_timetable.php` | Delete timetable |
| `PHP/addTeacher.php` | Add new teacher |
| `PHP/getTeachers.php` | Get all teachers |
| `PHP/deleteTeacher.php` | Remove teacher |
| `PHP/saveSubjects.php` | Save subjects extracted from PDF |
| `GPC/saveSubjects.php` | GPC-specific subject saving |
| `PHP/init_db.php` | Database initialization |
| `PHP/init_teacher_db.php` | Teacher database initialization |

---

## Features Summary

### User Authentication
- Registration with role selection
- Login with session management
- Password hashing (password_hash)
- Role-based access (Student, Teacher, Admin)

### Timetable Generation
- Multi-semester support (Sem 1-6)
- PDF upload for subjects
- Teacher management
- Automated scheduling algorithm

### Data Management
- MySQL database storage
- Save/Load timetables
- View saved timetables
- Delete timetables

### Export & Print
- Export to PDF (via print)
- Print-friendly styles
- Table formatting
- Semester-wise separation

---

## What Problem This Project Solves

1. **Manual Timetable Creation** - Creating timetables manually is extremely time-consuming, especially for institutions with multiple courses, semesters, and teachers. This system automates the scheduling process.

2. **Schedule Conflicts** - Manual scheduling often leads to conflicts such as:
   - Teacher double-booking (same teacher assigned to two classes at same time)
   - Room scheduling conflicts
   The automated system can detect and avoid such conflicts.

3. **Data Entry from PDF Syllabi** - Universities publish subject lists as PDF documents. This system allows uploading those PDFs and extracting subject data automatically instead of manual data entry.

4. **Multi-Role Management** - Different stakeholders (Students, Teachers, Administrators) need different views. The system supports role-based access.

5. **Export & Distribution** - Generated timetables need to be shared with students and staff. The export to PDF feature makes distribution easy.

6. **Centralized Storage** - All timetables stored in database for easy access, modification, and backup.

---

## Current Status Assessment

### Working Components
- User registration and login system
- Dashboard with profile display
- Timetable generation page
- PDF upload and subject extraction
- Teacher management
- Timetable viewing
- Export to PDF

### Incomplete / Needs Work
- personal_Timetable.html - Minimal content, needs implementation
- UI/UX polish on some pages
- Error handling improvements
- Mobile responsiveness refinements

---

## Project Structure

```
autheritation/
├── FOR_everyOne/           (Main user-facing pages)
│   ├── login.html
│   ├── registretion.html
│   ├── index.html           (Dashboard)
│   ├── generate_timetable.html
│   ├── personal_Timetable.html
│   ├── view_timetable.html
│   ├── style.js
│   ├── dashboard.js
│   ├── login.css
│   └── gen_timTab.css
├── GPC/                    (Teacher & Subject Manager)
│   ├── GPC.html
│   ├── gpc.js
│   ├── gpc.css
│   └── saveSubjects.php
├── PHP/                    (Backend API)
│   ├── db.php
│   ├── login.php
│   ├── register.php
│   ├── logout.php
│   ├── save_timetable.php
│   ├── get_timetables.php
│   ├── get_timetable_by_id.php
│   ├── delete_timetable.php
│   ├── addTeacher.php
│   ├── getTeachers.php
│   ├── deleteTeacher.php
│   ├── saveSubjects.php
│   ├── init_db.php
│   └── init_teacher_db.php
└── temp.md                 (This report)
```

---

*Report generated for Precision Architect Project*