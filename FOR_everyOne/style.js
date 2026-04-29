// --- 1. GLOBAL STATE ---
let teacherList = [];
let classSubjects = {};
let assignments = [];
let teacherOccupancy = {};
const totalPeriods = 7;
const days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
const timeSlots = ["10:30-11:30", "11:30-12:30", "12:30-1:30", "1:30-2:30", "2:30-3:00", "3:00-4:00", "4:00-5:00"];

// --- 2. SEMESTER CARD LOGIC ---
document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".sem-row").forEach(card => {
        const fileInput = card.querySelector(".pdf-file-input");
        const fileNameDiv = card.querySelector(".file-name");

        card.addEventListener("click", () => { fileInput.click(); });

        fileInput.addEventListener("change", function () {
            if (this.files.length > 0) {
                card.classList.add("active");
                fileNameDiv.textContent = this.files[0].name;
            } else {
                card.classList.remove("active");
                fileNameDiv.textContent = "No file selected";
            }
        });
    });

    // --- 3. TEACHER MANAGEMENT ---
    const addTeacherBtn = document.getElementById("addTeacherBtn");
    if (addTeacherBtn) {
        addTeacherBtn.addEventListener("click", () => {
            const input = document.getElementById("teacherInput");
            const name = input.value.trim();

            if (!name) return alert("Enter teacher name");
            if (teacherList.includes(name)) return alert("Teacher already added");

            teacherList.push(name);
            input.value = "";
            renderTeacherList();
        });
    }
});

function renderTeacherList() {
    const container = document.getElementById("teacherListUI");
    if (!container) return;
    container.innerHTML = teacherList.map((t, i) => `
        <div class="flex justify-between items-center bg-slate-50 p-3 rounded-xl border border-slate-100">
            <span class="font-semibold text-slate-700">${t}</span>
            <button onclick="deleteTeacher(${i})" class="text-red-400 hover:text-red-600 w-8 h-8 flex items-center justify-center">
                <span class="material-symbols-outlined">delete</span>
            </button>
        </div>
    `).join("");
}

function deleteTeacher(index) {
    teacherList.splice(index, 1);
    renderTeacherList();
}

// --- 4. DATA EXTRACTION ---
async function uploadPDF() {
    const activeCards = document.querySelectorAll(".sem-row.active");
    const container = document.getElementById("tablesContainer");
    const loader = document.getElementById("loader");

    if (activeCards.length === 0) return alert("Please select a PDF file for at least one semester.");

    container.innerHTML = "";
    classSubjects = {}; // Reset subject data on new extract
    loader.style.display = "flex"; 

    for (const card of activeCards) {
        const semValue = card.dataset.semester;
        const fileInput = card.querySelector(".pdf-file-input");

        const formData = new FormData();
        formData.append("semester", semValue);
        formData.append("pdf", fileInput.files[0]);

        try {
            const response = await fetch("http://127.0.0.1:5000/upload", { method: "POST", body: formData });
            const data = await response.json();
            renderSubjectTable(semValue, data);
        } catch (error) {
            console.error("Extraction error:", error);
            alert(`Failed to extract data for Semester ${semValue}. Is the Python server running?`);
        }
    }
    loader.style.display = "none";
}

function renderSubjectTable(sem, data) {
    const container = document.getElementById("tablesContainer");
    const tableDiv = document.createElement("div");
    tableDiv.className = "mb-8 p-4 border border-slate-100 rounded-2xl bg-slate-50/50";

    // Store subject data in global classSubjects for the generation engine
    if (!classSubjects[sem]) classSubjects[sem] = [];
    data.forEach(sub => {
        classSubjects[sem].push({
            name: sub.subject,
            theoryLeft: parseInt(sub.theory) || 0,
            practicalLeft: parseInt(sub.practical) || 0
        });
    });
    
    tableDiv.innerHTML = `
        <h3 class="font-bold text-[#006ADC] mb-3">Semester ${sem} Assignments</h3>
        <table class="w-full bg-white rounded-xl overflow-hidden shadow-sm">
            <thead class="bg-slate-800 text-white text-[10px] uppercase">
                <tr>
                    <th class="p-3">Subject</th>
                    <th class="p-3 text-center">T</th>
                    <th class="p-3 text-center">P</th>
                    <th class="p-3">Assign Teacher</th>
                </tr>
            </thead>
            <tbody>
                ${data.map(sub => `
                    <tr class="border-b border-slate-50">
                        <td class="p-3 text-sm font-medium">${sub.subject}</td>
                        <td class="p-3 text-center text-sm">${sub.theory}</td>
                        <td class="p-3 text-center text-sm">${sub.practical}</td>
                        <td class="p-3">
                            <select class="dynamic-teacher-input w-full border-slate-200 rounded-lg text-xs p-1"
                                    data-sem="${sem}" data-subject="${sub.subject}">
                                <option value="">-- Select --</option>
                                ${teacherList.map(t => `<option value="${t}">${t}</option>`).join("")}
                            </select>
                        </td>
                    </tr>
                `).join("")}
            </tbody>
        </table>
    `;
    container.appendChild(tableDiv);
}

// --- 5. GENERATION ENGINE ---
document.addEventListener("DOMContentLoaded", () => {
    const generateBtn = document.getElementById("generateBtn");
    if (generateBtn) {
        generateBtn.onclick = () => {
            const dropdowns = document.querySelectorAll(".dynamic-teacher-input");
            assignments = [];
            let activeSemesters = new Set();

            dropdowns.forEach(sel => {
                if (sel.value) {
                    activeSemesters.add(sel.dataset.sem);
                    assignments.push({
                        className: sel.dataset.sem,
                        subject: sel.dataset.subject,
                teacher: sel.value
            });
        }
    });

    if (dropdowns.length === 0) return alert("Please extract subjects first!");
    if (assignments.length === 0) return alert("Please assign at least one teacher!");

    let classSchedules = {};
    teacherOccupancy = {};
    let workingSubjects = JSON.parse(JSON.stringify(classSubjects));

    activeSemesters.forEach(c => {
        classSchedules[c] = Array.from({ length: 6 }, () => Array(totalPeriods).fill(null));
        for (let d = 0; d < 6; d++) classSchedules[c][d][4] = "LUNCH";
    });

    for (let d = 0; d < 6; d++) {
        for (let p = 0; p < totalPeriods; p++) {
            if (p === 4) continue;

            let shuffledClasses = Array.from(activeSemesters).sort(() => Math.random() - 0.5);

            shuffledClasses.forEach(className => {
                if (classSchedules[className][d][p] !== null) return;

                let assigned = false;
                let usedTheoryToday = new Set();

                classSchedules[className][d].forEach(entry => {
                    if (entry && typeof entry === 'string' && entry.includes("(T-"))
                        usedTheoryToday.add(entry.split("<br>")[0]);
                });

                // Priority 1: Theory in the morning (periods 0-3)
                if (p < 4) {
                    assigned = tryAssign(className, d, p, workingSubjects, classSchedules, "T", usedTheoryToday);
                }

                // Priority 2: Practical (double block if possible, fallback single)
                if (!assigned) {
                    assigned = tryAssign(className, d, p, workingSubjects, classSchedules, "P");
                }

                // Priority 3: Fallback — remaining theory hours
                if (!assigned) {
                    assigned = tryAssign(className, d, p, workingSubjects, classSchedules, "T", new Set());
                }
            });
        }
    }
    display(classSchedules, Array.from(activeSemesters));
        };
    }
});

// --- 6. HELPER: TRY ASSIGN ---
function tryAssign(className, dayIdx, periodIdx, data, schedules, type, usedTheoryToday = new Set()) {
    let candidates = assignments.filter(a => {
        if (a.className !== className) return false;
        let subData = data[className]?.find(s => s.name === a.subject);
        if (!subData) return false;
        return type === "T"
            ? (subData.theoryLeft > 0 && !usedTheoryToday.has(a.subject))
            : subData.practicalLeft > 0;
    });

    candidates.sort(() => Math.random() - 0.5);

    for (let candidate of candidates) {
        let teacherKey1 = `${dayIdx}-${periodIdx}-${candidate.teacher}`;
        let subData = data[className].find(s => s.name === candidate.subject);

        if (type === "P") {
            let nextP = periodIdx + 1;
            let teacherKey2 = `${dayIdx}-${nextP}-${candidate.teacher}`;

            // Try double-block practical
            if (nextP < totalPeriods && nextP !== 4 &&
                schedules[className][dayIdx][nextP] === null &&
                !teacherOccupancy[teacherKey1] && !teacherOccupancy[teacherKey2] &&
                subData.practicalLeft >= 2) {

                const label = `${candidate.subject}<br><small>(P-${candidate.teacher})</small>`;
                schedules[className][dayIdx][periodIdx] = label;
                schedules[className][dayIdx][nextP] = label;
                teacherOccupancy[teacherKey1] = true;
                teacherOccupancy[teacherKey2] = true;
                subData.practicalLeft -= 2;
                return true;
            }

            // Single practical block fallback
            if (!teacherOccupancy[teacherKey1] && subData.practicalLeft > 0) {
                schedules[className][dayIdx][periodIdx] = `${candidate.subject}<br><small>(P-${candidate.teacher})</small>`;
                teacherOccupancy[teacherKey1] = true;
                subData.practicalLeft--;
                return true;
            }
        } else {
            if (!teacherOccupancy[teacherKey1]) {
                schedules[className][dayIdx][periodIdx] = `${candidate.subject}<br><small>(T-${candidate.teacher})</small>`;
                teacherOccupancy[teacherKey1] = true;
                subData.theoryLeft--;
                return true;
            }
        }
    }
    return false;
}

// --- 7. DISPLAY GENERATED TIMETABLE ---
function display(schedules, activeSems) {
    const cont = document.getElementById("timetableContainer");
    cont.innerHTML = "";

    // Save button
    const saveBtnContainer = document.createElement("div");
    saveBtnContainer.className = "flex justify-end mb-6";
    saveBtnContainer.innerHTML = `
        <button onclick='prepareSave(${JSON.stringify(schedules)}, ${JSON.stringify(activeSems)})'
                class="bg-[#006ADC] hover:bg-blue-700 text-white px-6 py-3 rounded-2xl font-bold shadow-lg flex items-center gap-2 transform transition-all active:scale-95">
            <span class="material-symbols-outlined">save</span>
            Save All Timetables
        </button>
    `;
    cont.appendChild(saveBtnContainer);

    [...activeSems].sort().forEach(sem => {
        const semCard = document.createElement("div");
        semCard.className = "bg-white p-8 rounded-[2rem] shadow-xl border border-slate-100 mb-10 transition-all hover:shadow-2xl overflow-x-auto";

        let html = `
            <div class="mb-8">
                <h3 class="text-xl font-bold text-slate-800">Semester ${sem} Timetable</h3>
                <p class="text-[10px] font-bold text-slate-300 tracking-[0.3em] uppercase mt-2">GENERATED</p>
            </div>
            <table class="w-full text-center border-collapse min-w-[900px]">
                <thead class="bg-[#f8fafc] text-slate-500 border-b border-slate-100">
                    <tr class="text-[10px] font-bold uppercase tracking-[0.15em] text-slate-400">
                        <th class="p-4 w-28 font-bold">DAY</th>
                        ${timeSlots.map(time => `<th class="p-4 border-l border-slate-100 font-bold">${time}</th>`).join('')}
                    </tr>
                </thead>
                <tbody>`;

        days.forEach((day, dIndex) => {
            html += `<tr class="hover:bg-slate-50/50 transition-colors border-b border-slate-50">
                <td class="p-4 font-bold text-slate-900 bg-white border-r border-slate-100 text-xs whitespace-nowrap">${day.toUpperCase()}</td>`;

            schedules[sem][dIndex].forEach(val => {
                if (val === "LUNCH") {
                    html += `<td class="p-3 border-l border-slate-50"><span class="lunch-cell">LUNCH</span></td>`;
                } else if (val && typeof val === 'string') {
                    const isPractical = val.includes("(P-");
                    const slotClass = isPractical ? "practical-slot" : "theory-slot";
                    html += `<td class="p-2 border-l border-slate-50"><div class="${slotClass}" style="font-size:10px;word-break:break-word;overflow-wrap:break-word;">${val}</div></td>`;
                } else {
                    html += `<td class="p-4 border-l border-slate-50 text-slate-300 text-lg">—</td>`;
                }
            });
            html += `</tr>`;
        });

        html += `</tbody></table>`;
        semCard.innerHTML = html;
        cont.appendChild(semCard);
    });

    cont.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// --- 8. SAVE FUNCTIONALITY ---
window.prepareSave = function(schedules, activeSems) {
    const title = prompt("Enter a title for this timetable (e.g., 'Even Semester 2024'):");
    if (!title) return;

    const dataToSave = {
        title: title,
        type: 'institution',
        is_public: 0,
        timetable_data: {
            schedules: schedules,
            activeSems: activeSems,
            days: days,
            timeSlots: timeSlots
        }
    };

    fetch('../PHP/save_timetable.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(dataToSave)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert("✓ Timetable saved successfully!");
            if (data.timetable_id) {
                window.lastTimetableId = data.timetable_id;
                document.getElementById('publishBtn').classList.remove('hidden');
            }
            // Redirect to dashboard to see the saved timetable
            window.location.href = 'teacher_dashboard.html';
        } else {
            alert("Error: " + data.error);
        }
    })
    .catch(err => {
        console.error(err);
        alert("Failed to save timetable.");
    });
};

window.publishTimetable = function(timetableId) {
    const id = timetableId || window.lastTimetableId;
    if (!id) {
        alert("Please save the timetable first.");
        return;
    }

    fetch('../PHP/publish_timetable.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ timetable_id: id })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert("✓ Timetable published to students!");
            document.getElementById('publishBtn').classList.add('hidden');
        } else {
            alert("Error: " + data.error);
        }
    })
    .catch(err => {
        console.error(err);
        alert("Failed to publish timetable.");
    });
};

// --- 9. COLLEGE TIMETABLE (For Students) ---
window.loadCollegeTimetable = async function() {
    const container = document.getElementById('collegeTimetable');
    if (!container) return;

    try {
        const res = await fetch('../PHP/get_college_timetable.php');
        const data = await res.json();

        if (data.success && data.timetable) {
            renderCollegeTimetable(container, data.timetable);
        } else {
            container.innerHTML = '<p class="text-slate-500">No timetable published yet.</p>';
        }
    } catch (err) {
        console.error(err);
        container.innerHTML = '<p class="text-red-500">Failed to load timetable.</p>';
    }
};

function renderCollegeTimetable(container, timetable) {
    const td = timetable.timetable_data;
    const schedules = td.schedules;
    const activeSems = td.activeSems;
    const daysList = td.days || days;
    const timeSlotsList = td.timeSlots || timeSlots;

    let html = `<h3 class="text-xl font-bold mb-4">${timetable.title}</h3>`;
    
    activeSems.sort().forEach(sem => {
        html += `<div class="mb-8"><h4 class="font-bold mb-2">Semester ${sem}</h4>`;
        html += `<div class="overflow-x-auto"><table class="w-full border-collapse text-sm">`;
        html += `<thead><tr><th class="border p-2 bg-slate-100"></th>`;
        
        timeSlotsList.forEach(slot => {
            html += `<th class="border p-2 bg-slate-100">${slot}</th>`;
        });
        html += `</tr></thead><tbody>`;

        daysList.forEach((day, dIndex) => {
            html += `<tr><td class="border p-2 font-bold">${day}</td>`;
            const daySchedule = schedules[sem]?.[dIndex] || [];
            timeSlotsList.forEach((_, tIndex) => {
                const slot = daySchedule[tIndex];
                if (!slot || slot === 'LUNCH') {
                    html += `<td class="border p-2 ${slot === 'LUNCH' ? 'bg-yellow-50' : ''}">${slot === 'LUNCH' ? 'LUNCH' : '—'}</td>`;
                } else {
                    html += `<td class="border p-2 bg-blue-50">${slot}</td>`;
                }
            });
            html += `</tr>`;
        });
        
        html += `</tbody></table></div></div>`;
    });

    container.innerHTML = html;
}