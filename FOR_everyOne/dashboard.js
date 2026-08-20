let currentUserRole = '';

document.addEventListener('DOMContentLoaded', () => {
    // 1. Fetch user data from the PHP backend
    fetch('../PHP/get_user_details.php', { credentials: 'same-origin' })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                // If there's an error (e.g., not logged in), redirect to login
                console.error('Session error:', data.error);
                const isInGPC = window.location.pathname.includes('/GPC/');
                window.location.href = isInGPC ? '../FOR_everyOne/login.html' : 'login.html';
                return;
            }
            
            // Validate Role vs Page
            const isTeacherPage = window.location.pathname.includes('teacher_dashboard.html');
            const isStudentPage = window.location.pathname.includes('student_dashboard.html');
            
            if (isTeacherPage && data.role !== 'Teacher' && data.role !== 'Admin') {
                const isInGPC = window.location.pathname.includes('/GPC/');
                window.location.href = isInGPC ? '../FOR_everyOne/student_dashboard.html' : 'student_dashboard.html';
                return;
            }
            if (isStudentPage && data.role !== 'Student' && data.role !== 'Admin') {
                const isInGPC = window.location.pathname.includes('/GPC/');
                window.location.href = isInGPC ? '../FOR_everyOne/teacher_dashboard.html' : 'teacher_dashboard.html';
                return;
            }

            currentUserRole = data.role;

            // 2. Map data to the UI
            updateDashboardUI(data);
            
            // 2.5 Fetch saved timetables
            fetchTimetables();
            
            // 3. Fetch progress for students
            if (data.role === 'Student') {
                fetchProgress();
            }
        })
        .catch(error => {
            console.error('Error fetching user data:', error);
        });

    // 4. Setup Logout
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', () => {
            fetch('../PHP/logout.php', { method: 'POST', credentials: 'same-origin' })
                .then(() => window.location.href = 'login.html');
        });
    }
});

function updateDashboardUI(user) {
    const nameEl = document.getElementById('profileName');
    const initEl = document.getElementById('userInitial');
    const emailEl = document.getElementById('profileEmail');
    const roleEl = document.getElementById('profileRole');
    const instEl = document.getElementById('profileInst');
    
    if (nameEl) nameEl.textContent = user.user_name || "User";
    if (initEl) initEl.textContent = user.user_name ? user.user_name.charAt(0).toUpperCase() : "U";
    if (emailEl) emailEl.textContent = user.email || "N/A";
    if (roleEl) roleEl.textContent = user.role || "User";
    if (instEl) instEl.textContent = user.institution || "Not Specified";

    // Show college timetable for students
    const role = (user.role || '').toLowerCase();
    if (role === 'student') {
        const section = document.getElementById('collegeTimetableSection');
        if (section) {
            section.style.display = 'block';
            if (typeof loadCollegeTimetable === 'function') {
                loadCollegeTimetable();
            }
        }
    }
}

function fetchTimetables() {
    const grid = document.getElementById('timetablesGrid');
    fetch('../PHP/get_timetables.php', { credentials: 'same-origin' })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                renderTimetables(data.timetables);
            } else {
                console.error("Failed to load timetables:", data.error);
                if (grid) grid.innerHTML = `<p class="col-span-full text-center text-red-500">${data.error}</p>`;
            }
        })
        .catch(err => {
            console.error(err);
            if (grid) grid.innerHTML = `<p class="col-span-full text-center text-red-500">Failed to fetch timetables.</p>`;
        });
}

function renderTimetables(list) {
    const grid = document.getElementById('timetablesGrid');
    const countLabel = document.getElementById('timetableCount');
    const statTotal = document.getElementById('statTotal');

    if (countLabel) countLabel.textContent = `${list.length} total`;
    if (statTotal) statTotal.textContent = list.length;

    if (!grid) return;

    if (list.length === 0) {
        grid.innerHTML = `
            <div class="col-span-full py-16 text-center bg-white rounded-3xl border-2 border-dashed border-slate-200">
                <span class="material-symbols-outlined text-5xl text-slate-200 mb-4">folder_open</span>
                <p class="text-slate-400 font-medium text-lg">No timetables saved yet.</p>
                <a href="generate_timetable.html" class="mt-4 inline-flex items-center text-[#006ADC] font-bold hover:underline">
                    Create your first one <span class="material-symbols-outlined ml-1">arrow_forward</span>
                </a>
            </div>
        `;
        return;
    }

    grid.innerHTML = list.map(item => `
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 group hover:shadow-xl hover:border-blue-100 transition-all duration-300">
            <div class="flex justify-between items-start mb-4">
                <div class="p-3 rounded-2xl bg-blue-50 text-[#006ADC] group-hover:bg-[#006ADC] group-hover:text-white transition-colors">
                    <span class="material-symbols-outlined">calendar_today</span>
                </div>
                <div class="flex gap-1 items-center">
                    ${
                        (currentUserRole === 'Teacher' || currentUserRole === 'Admin')
                        ? (item.is_public == 1
                            ? `<button onclick="makePrivate(${item.id})" title="Make Private (click to undo global)"
                                    class="p-2 text-green-500 hover:text-orange-500 transition-colors" >
                                    <span class="material-symbols-outlined text-xl">public</span>
                                </button>`
                            : `<button onclick="makeGlobal(${item.id})" title="Make Global for all students"
                                    class="p-2 text-slate-300 hover:text-green-500 transition-colors">
                                    <span class="material-symbols-outlined text-xl">public</span>
                                </button>`
                        ) : ''
                    }
                    <button onclick="deleteTimetable(${item.id}, ${item.is_public})" title="Delete timetable"
                            class="p-2 text-slate-300 hover:text-red-500 transition-colors">
                        <span class="material-symbols-outlined text-xl">delete</span>
                    </button>
                </div>
            </div>

            <h3 class="text-lg font-bold text-slate-800 mb-1 group-hover:text-[#006ADC] transition-colors">${item.title}</h3>
            <div class="flex items-center gap-2 mb-4">
                <p class="text-xs font-bold text-[#006ADC] uppercase tracking-widest bg-blue-50 inline-block px-2 py-1 rounded-lg">${item.type}</p>
                ${item.is_public == 1 ? '<span class="text-xs font-bold text-green-600 bg-green-50 px-2 py-1 rounded-lg">🌐 Global</span>' : '<span class="text-xs font-bold text-slate-400 bg-slate-50 px-2 py-1 rounded-lg">Private</span>'}
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-slate-50">
                <span class="text-[10px] text-slate-400 font-medium">${new Date(item.created_at).toLocaleDateString()}</span>
                <div class="flex items-center gap-2">
                    <a href="view_timetable.html?id=${item.id}" class="flex items-center gap-1 text-sm font-bold text-[#006ADC] group-hover:gap-2 transition-all">
                        View <span class="material-symbols-outlined text-sm">open_in_new</span>
                    </a>
                </div>
            </div>
        </div>
    `).join('');
}

window.deleteTimetable = function(id, isPublic) {
    const warningMsg = isPublic == 1
        ? 'This timetable is currently GLOBAL. Deleting it will remove it from all student dashboards.\n\nAre you sure?'
        : 'Are you sure you want to delete this timetable?';

    if (!confirm(warningMsg)) return;

    fetch('../PHP/delete_timetable.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            fetchTimetables();
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(err => {
        console.error(err);
        alert('Failed to delete timetable.');
    });
};

window.makeGlobal = function(id) {
    if (!confirm('Make this timetable global?\n\nAll students in your college will see it in their College Timetable tab.')) return;

    fetch('../PHP/make_global_timetable.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            fetchTimetables();
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(err => {
        console.error(err);
        alert('Failed to make timetable global.');
    });
};

window.makePrivate = function(id) {
    if (!confirm('Remove this timetable from students\' view?\n\nIt will become private and students will no longer see it.')) return;

    fetch('../PHP/unmake_global_timetable.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            fetchTimetables();
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(err => {
        console.error(err);
        alert('Failed to make timetable private.');
    });
};

// --- PROGRESS TRACKING ---

async function fetchProgress() {
    try {
        const res = await fetch('../PHP/get_progress.php');
        const data = await res.json();
        
        if (data.success) {
            renderProgressCards(data);
        }
    } catch (err) {
        console.error('Error fetching progress:', err);
    }
}

window.renderProgressCards = function(data) {
    const section = document.getElementById('progress-section');
    if (!section) return;
    
    section.style.display = 'block';
    
    // Update streak
    const streakEl = document.getElementById('streakCount');
    if (streakEl) streakEl.textContent = data.streak || 0;
    
    // Update week done
    const weekDoneEl = document.getElementById('weekDone');
    if (weekDoneEl) weekDoneEl.textContent = data.tasks_this_week || 0;
    
    // Update completion rate
    const completionEl = document.getElementById('completionRate');
    if (completionEl) completionEl.textContent = (data.completion_rate || 0) + '%';
};

window.askAIMotivation = async function() {
    const card = document.getElementById('motivationCard');
    const text = document.getElementById('motivationText');
    const btn = card?.parentElement?.querySelector('button');
    
    if (!card || !text) return;
    
    if (btn) btn.disabled = true;
    card.classList.remove('hidden');
    text.textContent = 'Getting your motivation...';
    
    try {
        const res = await fetch('../PHP/get_progress.php');
        const data = await res.json();
        
        // Build a simple motivation based on stats
        let motivation = '';
        const streak = data.streak || 0;
        const weekDone = data.tasks_this_week || 0;
        const rate = data.completion_rate || 0;
        
        if (streak >= 7) {
            motivation = `Incredible! ${streak} days straight! You're on fire! 🔥 Keep this momentum going!`;
        } else if (streak >= 3) {
            motivation = `Great work! ${streak} day streak! You've built a solid habit. ${weekDone} tasks this week - ${rate}% completion. Keep it up!`;
        } else if (weekDone > 0) {
            motivation = `Nice progress! You completed ${weekDone} tasks this week. ${rate}% of your tasks done. Start a streak by doing one more task tomorrow!`;
        } else {
            motivation = `Every expert was once a beginner! Complete one task today to start your streak. You've got this! 💪`;
        }
        
        text.textContent = motivation;
    } catch (err) {
        console.error(err);
        text.textContent = 'Keep pushing forward! One step at a time.';
    } finally {
        if (btn) btn.disabled = false;
    }
};
