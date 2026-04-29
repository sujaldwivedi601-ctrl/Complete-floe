<script>
    const STUDENT_NAV = `
        <header class="h-16 bg-white/80 backdrop-blur-md sticky top-0 z-50 w-full border-b border-slate-200">
            <div class="flex justify-between items-center w-full px-6 py-4 max-w-7xl mx-auto h-full">
                <div class="text-2xl font-extrabold text-[#006ADC] flex items-center gap-2">
                    <span class="material-symbols-outlined text-blue-600 text-3xl">architecture</span>
                    <a href="index.html" class="text-[#006ADC] hover:text-blue-700">Precision Architect</a>
                </div>
                <nav class="hidden md:flex items-center gap-2" id="studentNav">
                    <a class="nav-link font-medium text-sm text-slate-600 px-4 py-2 rounded-lg hover:bg-slate-100" href="index.html">Home</a>
                    <a class="nav-link font-medium text-sm text-slate-600 px-4 py-2 rounded-lg hover:bg-slate-100" href="student_dashboard.html">My Timetable</a>
                    <a class="nav-link font-medium text-sm text-slate-600 px-4 py-2 rounded-lg hover:bg-slate-100" href="goals.html">Goals</a>
                    <button id="logoutBtn" class="font-medium text-sm text-slate-600 px-4 py-2 rounded-lg border border-slate-200 hover:bg-slate-100">Logout</button>
                </nav>
            </div>
        </header>
    `;

    const TEACHER_NAV = `
        <header class="h-16 bg-white/80 backdrop-blur-md sticky top-0 z-50 w-full border-b border-slate-200">
            <div class="flex justify-between items-center w-full px-6 py-4 max-w-7xl mx-auto h-full">
                <div class="text-2xl font-extrabold text-[#006ADC] flex items-center gap-2">
                    <span class="material-symbols-outlined text-blue-600 text-3xl">architecture</span>
                    <a href="teacher_dashboard.html" class="text-[#006ADC] hover:text-blue-700">Precision Architect</a>
                </div>
                <nav class="hidden md:flex items-center gap-2" id="teacherNav">
                    <a class="nav-link font-medium text-sm text-slate-600 px-4 py-2 rounded-lg hover:bg-slate-100" href="teacher_dashboard.html">Dashboard</a>
                    <a class="nav-link font-medium text-sm text-slate-600 px-4 py-2 rounded-lg hover:bg-slate-100" href="generate_timetable.html">Generate Timetable</a>
                    <button id="logoutBtn" class="font-medium text-sm text-slate-600 px-4 py-2 rounded-lg border border-slate-200 hover:bg-slate-100">Logout</button>
                </nav>
            </div>
        </header>
    `;

    function insertNav(role) {
        document.body.insertAdjacentHTML('afterbegin', role === 'Teacher' ? TEACHER_NAV : STUDENT_NAV);
        setupLogout();
    }

    function setActiveNav(highlightPage) {
        const navLinks = document.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            if (link.getAttribute('href') === highlightPage) {
                link.classList.add('bg-blue-50', 'text-blue-700', 'font-semibold');
            }
        });
    }

    function setupLogout() {
        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', () => {
                window.location.href = '../PHP/logout.php';
            });
        }
    }
</script>

<style>
    .nav-link {
        transition: all 0.2s ease;
    }
    .nav-link:hover {
        background: #f1f5f9;
    }
</style>