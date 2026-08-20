<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timetables - Precision Architect</title>
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <style>
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; color: #1e293b; }
        .glass-panel {
            background: rgba(255,255,255,0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255,255,255,0.5);
            box-shadow: 0 8px 32px rgba(31,38,135,0.07);
        }

        /* Dark mode */
        html.dark { color-scheme: dark; }
        html.dark body { background: #0f172a; color: #e2e8f0; }
        html.dark .bg-white { background: #1e293b !important; border-color: #334155 !important; }
        html.dark .text-slate-700 { color: #cbd5e1 !important; }
        html.dark .text-slate-500 { color: #64748b !important; }
        html.dark .border-slate-200 { border-color: #334155 !important; }
        html.dark .hover\:bg-slate-50:hover { background: #334155 !important; }
        html.dark .glass-panel { background: rgba(30,41,59,0.7); border-color: rgba(71,85,105,0.5); }
        html.dark .bg-slate-50 { background: #1e293b !important; }
        html.dark .text-slate-400 { color: #475569 !important; }
    </style>
</head>
<body class="min-h-screen">
    <header class="h-16 glass-panel sticky top-0 z-50 w-full border-b border-slate-200/50">
        <div class="flex justify-between items-center w-full px-6 py-3 max-w-7xl mx-auto h-full">
            <div class="flex items-center gap-3">
                <span class="text-xl font-extrabold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent flex items-center gap-2">
                    <span class="material-symbols-outlined text-blue-600">calendar_month</span>
                    Timetables
                </span>
            </div>
            <div class="flex items-center gap-3">
                <button id="themeToggle" class="w-9 h-9 flex items-center justify-center rounded-xl hover:bg-slate-100 transition-colors text-slate-600 cursor-pointer" title="Toggle theme">
                    <span class="material-symbols-outlined theme-icon">dark_mode</span>
                </button>
                <button id="logoutBtn" class="font-semibold text-sm text-slate-700 px-4 py-2 rounded-xl border border-slate-200/60 hover:bg-white hover:shadow-md transition-all cursor-pointer">Logout</button>
            </div>
        </div>
    </header>

    <main class="max-w-5xl mx-auto px-6 py-10">
        <h1 class="text-3xl font-extrabold text-slate-900 mb-2">All Timetables</h1>
        <p class="text-slate-500 mb-8">Browse and view generated timetables</p>

        <div id="timetableList" class="space-y-4">
            <div class="flex justify-center py-20">
                <div class="w-10 h-10 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', async () => {
            // Theme
            const html = document.documentElement;
            const themeIcon = document.querySelector('.theme-icon');
            const stored = localStorage.getItem('theme');
            if (stored === 'dark') { html.classList.add('dark'); if (themeIcon) themeIcon.textContent = 'light_mode'; }
            document.getElementById('themeToggle')?.addEventListener('click', () => {
                const isDark = html.classList.toggle('dark');
                localStorage.setItem('theme', isDark ? 'dark' : 'light');
                if (themeIcon) themeIcon.textContent = isDark ? 'light_mode' : 'dark_mode';
            });

            // Logout
            document.getElementById('logoutBtn')?.addEventListener('click', () => {
                fetch('../PHP/logout.php', { method: 'POST' }).then(() => window.location.href = '../FOR_everyOne/login.html');
            });

            // Load timetables
            try {
                const res = await fetch('../PHP/get_timetables.php');
                const data = await res.json();
                const container = document.getElementById('timetableList');

                if (!data.success || data.timetables.length === 0) {
                    container.innerHTML = `
                        <div class="text-center py-20 bg-white rounded-3xl border-2 border-dashed border-slate-200">
                            <span class="material-symbols-outlined text-5xl text-slate-200 mb-4">calendar_month</span>
                            <p class="text-slate-400 font-medium text-lg">No timetables found.</p>
                        </div>`;
                    return;
                }

                container.innerHTML = data.timetables.map(t => `
                    <a href="../FOR_everyOne/view_timetable.html?id=${t.id}" class="block bg-white p-6 rounded-2xl border border-slate-200 hover:shadow-lg hover:-translate-y-0.5 transition-all">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="font-bold text-slate-800 text-lg">${t.title}</h3>
                                <div class="flex items-center gap-3 mt-1">
                                    <span class="text-xs font-semibold text-blue-600 bg-blue-50 px-3 py-1 rounded-full">${t.type || 'Institution'}</span>
                                    <span class="text-xs text-slate-400">${new Date(t.created_at).toLocaleDateString()}</span>
                                </div>
                            </div>
                            <span class="material-symbols-outlined text-slate-400">chevron_right</span>
                        </div>
                    </a>
                `).join('');
            } catch (err) {
                console.error(err);
                document.getElementById('timetableList').innerHTML = `<p class="text-center text-red-500 py-10">Failed to load timetables.</p>`;
            }
        });
    </script>
</body>
</html>
