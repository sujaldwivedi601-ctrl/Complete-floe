document.addEventListener('DOMContentLoaded', () => {
    // 1. Fetch user data from the PHP backend
    fetch('../PHP/get_user_details.php')
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                // If there's an error (e.g., not logged in), redirect to login
                console.error('Session error:', data.error);
                window.location.href = 'login.html';
                return;
            }

            // 2. Map data to the UI
            updateDashboardUI(data);
            
            // 2.5 Fetch saved timetables
            fetchTimetables();
        })
        .catch(error => {
            console.error('Error fetching user data:', error);
        });

    // 3. Setup Logout
    document.getElementById('logoutBtn').addEventListener('click', () => {
        window.location.href = '../PHP/logout.php'; 
    });
});

function updateDashboardUI(user) {
    if (user.user_name) {
        document.getElementById('profileName').textContent = user.user_name;
        document.getElementById('userInitial').textContent = user.user_name.charAt(0).toUpperCase();
    }
    document.getElementById('profileEmail').textContent = user.email || "N/A";
    document.getElementById('profileRole').textContent = user.role || "User";
    document.getElementById('profileInst').textContent = user.institution || "Not Specified";
}

function fetchTimetables() {
    fetch('../PHP/get_timetables.php')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                renderTimetables(data.timetables);
            } else {
                console.error("Failed to load timetables:", data.error);
                document.getElementById('timetablesGrid').innerHTML = `<p class="col-span-full text-center text-red-500">${data.error}</p>`;
            }
        })
        .catch(err => {
            console.error(err);
            document.getElementById('timetablesGrid').innerHTML = `<p class="col-span-full text-center text-red-500">Failed to fetch timetables.</p>`;
        });
}

function renderTimetables(list) {
    const grid = document.getElementById('timetablesGrid');
    const countLabel = document.getElementById('timetableCount');
    const statTotal = document.getElementById('statTotal');

    countLabel.textContent = `${list.length} total`;
    statTotal.textContent = list.length;

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
                <div class="flex gap-1">
                    <button onclick="deleteTimetable(${item.id})" class="p-2 text-slate-300 hover:text-red-500 transition-colors">
                        <span class="material-symbols-outlined text-xl">delete</span>
                    </button>
                </div>
            </div>
            
            <h3 class="text-lg font-bold text-slate-800 mb-1 group-hover:text-[#006ADC] transition-colors">${item.title}</h3>
            <p class="text-xs font-bold text-[#006ADC] uppercase tracking-widest mb-4 bg-blue-50 inline-block px-2 py-1 rounded-lg">${item.type}</p>
            
            <div class="flex items-center justify-between pt-4 border-t border-slate-50">
                <span class="text-[10px] text-slate-400 font-medium">${new Date(item.created_at).toLocaleDateString()}</span>
                <a href="view_timetable.html?id=${item.id}" class="flex items-center gap-1 text-sm font-bold text-[#006ADC] group-hover:gap-2 transition-all">
                    View Full <span class="material-symbols-outlined text-sm">open_in_new</span>
                </a>
            </div>
        </div>
    `).join('');
}

window.deleteTimetable = function(id) {
    if (!confirm("Are you sure you want to delete this timetable?")) return;

    fetch('../PHP/delete_timetable.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            fetchTimetables(); // Refresh list
        } else {
            alert(data.error);
        }
    });
};
