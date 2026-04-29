import express from 'express';
import session from 'express-session';
import path from 'path';
import fs from 'fs';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const app = express();
const PORT = 3000;

// Middleware
app.use(express.urlencoded({ extended: true }));
app.use(express.json());
app.use(session({
    secret: 'secret-key-for-smart-timetable',
    resave: false,
    saveUninitialized: true,
    cookie: { secure: false } // Set to true if using HTTPS
}));

// Mock Database Files
const USERS_FILE = path.join(__dirname, 'users.json');
const TIMETABLES_FILE = path.join(__dirname, 'timetables.json');
const TASKS_FILE = path.join(__dirname, 'tasks.json');
const PERSONAL_TASKS_FILE = path.join(__dirname, 'personal_tasks.json');

// Initialize DB files if they don't exist
if (!fs.existsSync(USERS_FILE)) fs.writeFileSync(USERS_FILE, JSON.stringify([]));
if (!fs.existsSync(TIMETABLES_FILE)) fs.writeFileSync(TIMETABLES_FILE, JSON.stringify([]));
if (!fs.existsSync(TASKS_FILE)) fs.writeFileSync(TASKS_FILE, JSON.stringify([]));
if (!fs.existsSync(PERSONAL_TASKS_FILE)) fs.writeFileSync(PERSONAL_TASKS_FILE, JSON.stringify([]));

// Helper to read/write DB
const getUsers = () => JSON.parse(fs.readFileSync(USERS_FILE, 'utf-8'));
const saveUsers = (users: any) => fs.writeFileSync(USERS_FILE, JSON.stringify(users, null, 2));
const getTimetables = () => JSON.parse(fs.readFileSync(TIMETABLES_FILE, 'utf-8'));
const saveTimetables = (tabs: any) => fs.writeFileSync(TIMETABLES_FILE, JSON.stringify(tabs, null, 2));
const getTasks = () => JSON.parse(fs.readFileSync(TASKS_FILE, 'utf-8'));
const saveTasks = (tasks: any) => fs.writeFileSync(TASKS_FILE, JSON.stringify(tasks, null, 2));
const getPersonalTasks = () => JSON.parse(fs.readFileSync(PERSONAL_TASKS_FILE, 'utf-8'));
const savePersonalTasks = (tasks: any) => fs.writeFileSync(PERSONAL_TASKS_FILE, JSON.stringify(tasks, null, 2));

// --- PHP Equivalent API Routes ---

// Registration
app.post('/PHP/register.php', (req, res) => {
    const { user_name, email, password, role, institution, roll_number, branch, class_name, college_code } = req.body;
    const users = getUsers();
    
    if (users.find((u: any) => u.email === email)) {
        return res.status(400).send("User already exists");
    }

    const newUser = {
        id: users.length + 1,
        user_name,
        email,
        password, 
        role: role || 'Student',
        institution: institution || 'Unknown',
        roll_number: roll_number || '',
        branch: branch || '',
        class_name: class_name || '',
        college_code: college_code || ''
    };

    users.push(newUser);
    saveUsers(users);
    res.redirect('/FOR_everyOne/login.html');
});

// Login
app.post('/PHP/login.php', (req, res) => {
    const { user_name, password } = req.body;
    const users = getUsers();
    // Check by user_name OR email
    const user = users.find((u: any) => (u.user_name === user_name || u.email === user_name) && u.password === password);

    if (user) {
        (req.session as any).user = user;
        res.redirect('/FOR_everyOne/index.html');
    } else {
        res.status(401).send("Invalid username/email or password");
    }
});

// Get User Details
app.get('/PHP/get_user_details.php', (req, res) => {
    const user = (req.session as any).user;
    if (user) {
        res.json(user);
    } else {
        res.json({ error: "Not logged in" });
    }
});

// Logout
app.get('/PHP/logout.php', (req, res) => {
    req.session.destroy(() => {
        res.redirect('/FOR_everyOne/login.html');
    });
});

// Get Timetables (User's own + Global one if student)
app.get('/PHP/get_timetables.php', (req, res) => {
    const user = (req.session as any).user;
    if (!user) return res.json({ success: false, error: "Not logged in" });

    const allTabs = getTimetables();
    let visibleTabs = allTabs.filter((t: any) => t.user_id === user.id);

    // If student, also show global timetables from their institution
    if (user.role === 'Student') {
        const globalTabs = allTabs.filter((t: any) => t.is_global && t.institution === user.institution);
        // Avoid duplicates if user already has it
        globalTabs.forEach((gt: any) => {
            if (!visibleTabs.find(vt => vt.id === gt.id)) {
                visibleTabs.push(gt);
            }
        });
    }

    res.json({ success: true, timetables: visibleTabs });
});

// Get Timetable by ID
app.get('/PHP/get_timetable_by_id.php', (req, res) => {
    const { id } = req.query;
    const user = (req.session as any).user;
    if (!user) return res.json({ success: false, error: "Not logged in" });

    const allTabs = getTimetables();
    const timetable = allTabs.find((t: any) => t.id == id && (t.user_id === user.id || (t.is_global && t.institution === user.institution)));
    
    if (timetable) {
        res.json({ success: true, timetable });
    } else {
        res.json({ success: false, error: "Timetable not found" });
    }
});

// Save Timetable
app.post('/PHP/save_timetable.php', (req, res) => {
    const user = (req.session as any).user;
    if (!user) return res.json({ success: false, error: "Not logged in" });

    const { id, title, type, data, timetable_data, is_global } = req.body;
    const allTabs = getTimetables();
    const content = timetable_data || data;

    if (id) {
        // Update existing (check ownership or global if updating own copy)
        const idx = allTabs.findIndex((t: any) => t.id == id && t.user_id === user.id);
        if (idx !== -1) {
            allTabs[idx].title = title || allTabs[idx].title;
            allTabs[idx].timetable_data = content;
            allTabs[idx].content = content;
            allTabs[idx].is_global = is_global !== undefined ? is_global : allTabs[idx].is_global;
            allTabs[idx].created_at = new Date().toISOString(); 
        } else {
            return res.json({ success: false, error: "Cannot update: Not owner" });
        }
    } else {
        // Create new
        const newTab = {
            id: Date.now(),
            user_id: user.id,
            title,
            type,
            institution: user.institution, // Track institution for global distribution
            is_global: !!is_global,
            created_at: new Date().toISOString(),
            timetable_data: content,
            content: content
        };
        allTabs.push(newTab);
    }

    saveTimetables(allTabs);
    res.json({ success: true });
});

// Make Timetable Global
app.post('/PHP/make_global.php', (req, res) => {
    const user = (req.session as any).user;
    if (!user || user.role !== 'Teacher') return res.json({ success: false, error: "Only teachers can make timetables global" });

    const { id } = req.body;
    const allTabs = getTimetables();
    const idx = allTabs.findIndex((t: any) => t.id == id && t.user_id === user.id);

    if (idx !== -1) {
        allTabs[idx].is_global = true;
        saveTimetables(allTabs);
        res.json({ success: true });
    } else {
        res.json({ success: false, error: "Timetable not found or access denied" });
    }
});

// Delete Timetable
app.post('/PHP/delete_timetable.php', (req, res) => {
    const user = (req.session as any).user;
    if (!user) return res.json({ success: false, error: "Not logged in" });

    const { id } = req.body;
    const allTabs = getTimetables();
    const tabToDelete = allTabs.find((t: any) => t.id == id);

    if (!tabToDelete) return res.json({ success: false, error: "Not found" });
    
    // Students cannot delete global timetables
    if (tabToDelete.is_global && user.role === 'Student') {
        return res.json({ success: false, error: "Students cannot delete global timetables provided by teachers" });
    }
    
    if (tabToDelete.user_id !== user.id) return res.json({ success: false, error: "Access denied" });

    const newList = allTabs.filter((t: any) => t.id != id);
    saveTimetables(newList);
    res.json({ success: true });
});

// Task Management
app.post('/PHP/send_task.php', (req, res) => {
    const user = (req.session as any).user;
    if (!user || user.role !== 'Teacher') return res.json({ success: false, error: "Forbidden" });

    const { target_type, target_value, description, deadline } = req.body;
    const tasks = getTasks();
    const users = getUsers();

    let targetIds: number[] = [];

    if (target_type === 'individual') {
        // Find by roll number or name
        const target = users.find((u: any) => u.roll_number === target_value || u.user_name === target_value);
        if (target) targetIds.push(target.id);
        else return res.json({ success: false, error: "Student not found" });
    } else if (target_type === 'branch') {
        const branchMembers = users.filter((u: any) => u.branch === target_value && u.role === 'Student');
        targetIds = branchMembers.map(m => m.id);
    } else if (target_type === 'college') {
        const collegeMembers = users.filter((u: any) => u.institution === user.institution && u.role === 'Student');
        targetIds = collegeMembers.map(m => m.id);
    }

    const newTask = {
        id: Date.now(),
        teacher_id: user.id,
        teacher_name: user.user_name,
        target_type,
        target_value,
        description,
        deadline,
        created_at: new Date().toISOString(),
        student_ids: targetIds // Direct list of students who receive it
    };

    tasks.push(newTask);
    saveTasks(tasks);
    res.json({ success: true });
});

app.get('/PHP/get_tasks.php', (req, res) => {
    const user = (req.session as any).user;
    if (!user) return res.json({ success: false, error: "Not logged in" });

    const allTasks = getTasks();
    let visibleTasks = [];

    if (user.role === 'Teacher') {
        visibleTasks = allTasks.filter((t: any) => t.teacher_id === user.id);
    } else {
        visibleTasks = allTasks.filter((t: any) => t.student_ids.includes(user.id));
    }

    res.json({ success: true, tasks: visibleTasks });
});

// Personal Study Goals
app.post('/PHP/save_personal_task.php', (req, res) => {
    const user = (req.session as any).user;
    if (!user || user.role !== 'Student') return res.json({ success: false, error: "Unauthorized" });

    const { description } = req.body;
    const tasks = getPersonalTasks();
    
    const newTask = {
        id: Date.now(),
        student_id: user.id,
        description,
        status: 'pending',
        points: 0,
        created_at: new Date().toISOString()
    };

    tasks.push(newTask);
    savePersonalTasks(tasks);
    res.json({ success: true });
});

app.get('/PHP/get_personal_tasks.php', (req, res) => {
    const user = (req.session as any).user;
    if (!user) return res.json({ success: false, error: "Not logged in" });

    const tasks = getPersonalTasks();
    const myTasks = tasks.filter((t: any) => t.student_id === user.id);
    res.json({ success: true, tasks: myTasks });
});

app.post('/PHP/complete_personal_task.php', (req, res) => {
    const user = (req.session as any).user;
    if (!user) return res.json({ success: false, error: "Not logged in" });

    const { id, score } = req.body; // score from AI interview
    const tasks = getPersonalTasks();
    const taskIdx = tasks.findIndex((t: any) => t.id == id && t.student_id === user.id);

    if (taskIdx === -1) return res.json({ success: false, error: "Task not found" });

    tasks[taskIdx].status = 'completed';
    tasks[taskIdx].points = score;
    savePersonalTasks(tasks);

    // Update user's aggregate productivity points
    const users = getUsers();
    const userIdx = users.findIndex((u: any) => u.id === user.id);
    if (userIdx !== -1) {
        users[userIdx].productivity_points = (users[userIdx].productivity_points || 0) + score;
        saveUsers(users);
        (req.session as any).user = users[userIdx]; // Sync session
    }

    res.json({ success: true });
});

// Teacher: Get Institutional Productivity Stats
app.get('/PHP/get_productivity_stats.php', (req, res) => {
    const user = (req.session as any).user;
    if (!user || user.role !== 'Teacher') return res.json({ success: false, error: "Forbidden" });

    const users = getUsers();
    const pTasks = getPersonalTasks();

    const students = users.filter((u: any) => u.role === 'Student' && u.institution === user.institution);
    
    const stats = students.map((s: any) => {
        const studentTasks = pTasks.filter((t: any) => t.student_id === s.id && t.status === 'completed');
        return {
            id: s.id,
            name: s.user_name,
            roll_no: s.roll_number,
            branch: s.branch,
            total_points: s.productivity_points || 0,
            completed_count: studentTasks.length
        };
    }).sort((a: any, b: any) => b.total_points - a.total_points);

    res.json({ success: true, stats });
});

// Static files
app.use(express.static(__dirname));

// Default route
app.get('/', (req, res) => {
    if ((req.session as any).user) {
        res.redirect('/FOR_everyOne/index.html');
    } else {
        res.redirect('/FOR_everyOne/login.html');
    }
});

app.listen(PORT, '0.0.0.0', () => {
    console.log(`Server running on http://localhost:${PORT}`);
});
