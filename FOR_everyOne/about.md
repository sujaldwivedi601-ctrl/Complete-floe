# Precision Architect

An intelligent timetable management system for educational institutions.

---

## Project Overview

**Precision Architect** is a comprehensive timetable management system designed for educational institutions like schools and colleges. It enables administrators and teachers to create, manage, and publish class schedules, while allowing students to view their institutional timetables and create personalized study schedules.

---

## Key Features

### For Administrators & Teachers

- Upload PDF syllabi and extract subject data automatically
- Manage teacher assignments and availability
- Generate AI-powered conflict-free timetables
- Save and manage multiple timetable versions
- Make timetables global for all college students
- Assign tasks to students or entire classes
- Export timetables to Excel or PDF

### For Students

- View published institutional timetables
- Create personalized study timetables
- Track daily tasks and progress
- Set and manage personal goals
- View streak and completion statistics
- Receive AI-powered motivation

---

## Technology Stack

| Technology | Purpose |
|------------|---------|
| MySQL | Database |
| PHP | Backend |
| JavaScript | Frontend |
| Tailwind CSS | Styling |
| Python | PDF Processing |
| AI/ML | Timetable Generation |

---

## User Roles

### Admin (Secret)

- **Username:** `admin`
- **Password:** `C04`
- Full access to all features. Can manage subjects, generate timetables, and publish to entire institution.

### Teacher

Can manage subjects, create timetables, assign tasks to students, and publish schedules.

### Student

Can view college timetables, create personal timetables, track progress, set goals, and receive motivation.

---

## How It Works

### 1. Registration with College Code
Users register with their college/school name and unique code. All users from the same institution share the same college code (e.g., `12555`).

### 2. Subject Management
Teachers upload PDF syllabi for each semester. The system automatically extracts subject names, theory hours, and practical hours.

### 3. AI Timetable Generation
Teachers assign teachers to subjects and click "Generate". The AI algorithm creates conflict-free timetables considering teacher availability and subject requirements.

### 4. Publish or Save
Teachers can save timetables privately or make them "Global" so all students with the same college code can view them.

### 5. Student Experience
Students log in, see their college's published timetable, create personal study schedules, track tasks, and monitor their progress with streaks and statistics.

---

## Database Tables

| Table | Description |
|-------|-------------|
| `user_accounts` | Stores all registered users (students, teachers) |
| `colleges` | Stores institution details and unique codes |
| `timetables` | Stores generated timetables (global and private) |
| `subjects` | Stores subjects per class with teacher assignments |
| `teacher` | Stores teacher information |
| `tasks` | Stores tasks assigned by teachers |
| `goals` | Stores personal goals set by students |
| `progress_log` | Tracks daily progress and streaks |
| `student_timetables` | Stores personalized timetables for students |

---

## Project Structure

### Frontend (FOR_everyOne)

- `index.html` - Main dashboard
- `login.html` - User login
- `registretion.html` - Registration
- `generate_timetable.html` - Create timetables
- `teacher_dashboard.html` - Teacher tools
- `personal_Timetable.html` - Student personal
- `goals.html` - Goal setting
- `view_timetable.html` - View saved timetable
- `about.html` / `about.md` - Documentation

### Backend (PHP)

- `login.php` - Authentication
- `register.php` - User registration
- `save_timetable.php` - Save timetables
- `get_timetables.php` - Fetch timetables
- `make_global_timetable.php` - Publish to all
- `assign_task.php` - Task management
- `get_progress.php` - Progress tracking
- `get_colleges.php` - College codes

### Admin (GPC)

- `GPC.html` - Subject management
- `gpc.js` - Admin JavaScript
- `gpc.css` - Admin styling
- `saveSubjects.php` - Save subjects

### Scripts

- `style.js` - Core functionality
- `dashboard.js` - Dashboard logic
- `db.php` - Database connection

---

## Quick Start

### 1. Admin Login
Access the admin dashboard with:
- **Username:** `admin`
- **Password:** `C04`

### 2. Register Users
Students and teachers register with:
- Their college/school name
- **College code** (all users from same institution use same code)

### 3. Generate Timetable
1. Upload PDF syllabi for each semester
2. Extract subjects automatically
3. Assign teachers to subjects
4. Click "Generate Timetable"
5. Save or make global

### 4. Student View
Students can:
- View college's published timetable
- Create personal timetables
- Track tasks and progress

---

## Credits

Built with love using modern web technologies.

**Version:** 1.0  
**© 2026 Precision Architect**