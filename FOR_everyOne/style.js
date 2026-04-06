// --- 1. GLOBAL STATE ---
let teacherList = [];
let classSubjects = {}; 
let assignments = [];
let teacherOccupancy = {}; 
const days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
const timeSlots = ["10:30-11:30", "11:30-12:30", "12:30-1:30", "LUNCH", "2:30-3:30", "3:30-4:30", "4:30-5:30"];

// --- 2. SEMESTER CARD LOGIC (NEW) ---
document.querySelectorAll(".sem-row").forEach(card => {
    const fileInput = card.querySelector(".pdf-file-input");
    const fileNameDiv = card.querySelector(".file-name");

    // Open file picker on card click
    card.addEventListener("click", () => {
        fileInput.click();
    });

    // When file is selected
    fileInput.addEventListener("change", function () {
        if (this.files.length > 0) {
            // Activate the card UI
            card.classList.add("active");
            // Show file name
            fileNameDiv.textContent = this.files[0].name;
        } else {
            // Reset if canceled
            card.classList.remove("active");
            fileNameDiv.textContent = "No file selected";
        }
    });
});

// --- 3. TEACHER MANAGEMENT ---
document.getElementById("addTeacherBtn").addEventListener("click", () => {
    const input = document.getElementById("teacherInput");
    const name = input.value.trim();

    if (!name) return alert("Enter teacher name");
    if (teacherList.includes(name)) return alert("Teacher already added");

    teacherList.push(name);
    input.value = "";
    renderTeacherList();
});

function renderTeacherList() {
    const container = document.getElementById("teacherListUI");
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
    // Look for cards that have the 'active' class (meaning a file was selected)
    const activeCards = document.querySelectorAll(".sem-row.active");
    const container = document.getElementById("tablesContainer");
    const loader = document.getElementById("loader");

    if (activeCards.length === 0) return alert("Please select a PDF file for at least one semester.");

    container.innerHTML = "";
    loader.style.display = "flex"; 

    for (const card of activeCards) {
        const semValue = card.dataset.semester; // Assuming data-semester="1" etc.
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
        }
    }
    loader.style.display = "none";
}

function renderSubjectTable(sem, data) {
    const container = document.getElementById("tablesContainer");
    const tableDiv = document.createElement("div");
    tableDiv.className = "mb-8 p-4 border border-slate-100 rounded-2xl bg-slate-50/50";
    
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
                            <select class="tech-dropdown w-full border-slate-200 rounded-lg text-xs p-1" data-sem="${sem}" data-sub="${sub.subject}">
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
document.getElementById("gen").addEventListener("click", function() {
    const dropdowns = document.querySelectorAll(".tech-dropdown");
    if (dropdowns.length === 0) return alert("Please extract subjects first!");

    assignments = [];
    classSubjects = {};
    let activeSemsSet = new Set();

    dropdowns.forEach(select => {
        const sem = select.dataset.sem;
        const subName = select.dataset.sub;
        const teacher = select.value;
        const row = select.closest("tr");

        activeSemsSet.add(sem);
        if (!classSubjects[sem]) classSubjects[sem] = [];

        classSubjects[sem].push({
            name: subName,
            theoryLeft: parseInt(row.cells[1].innerText) || 0,
            practicalLeft: parseInt(row.cells[2].innerText) || 0
        });
        
        if (teacher) {
            assignments.push({ className: sem, subject: subName, teacher: teacher });
        }
    });

    generateFinalTimetable(Array.from(activeSemsSet));
});

function generateFinalTimetable(activeSems) {
    let schedules = {};
    teacherOccupancy = {}; 
    let workingData = JSON.parse(JSON.stringify(classSubjects));

    activeSems.forEach(sem => {
        schedules[sem] = Array.from({ length: 6 }, () => Array(7).fill(null));
        for (let d = 0; d < 6; d++) schedules[sem][d][3] = "LUNCH"; 
    });

    // Simple placement logic
    for (let d = 0; d < 6; d++) {
        for (let p = 0; p < 7; p++) {
            if (p === 3) continue;
            activeSems.forEach(sem => {
                let assigned = tryPlace(sem, d, p, workingData, schedules, "T");
                if (!assigned) tryPlace(sem, d, p, workingData, schedules, "P");
            });
        }
    }
    renderOutput(schedules, activeSems);
}

function tryPlace(sem, day, period, data, schedules, type) {
    let pool = assignments.filter(a => {
        if (a.className !== sem) return false;
        let sub = data[sem].find(s => s.name === a.subject);
        return type === "P" ? sub.practicalLeft > 0 : sub.theoryLeft > 0;
    }).sort(() => Math.random() - 0.5);

    for (let c of pool) {
        let key = `${day}-${period}-${c.teacher}`;
        let sub = data[sem].find(s => s.name === c.subject);

        if (!teacherOccupancy[key] && !schedules[sem][day][period]) {
            schedules[sem][day][period] = `<b>${c.subject}</b><br><small>${c.teacher}</small>`;
            teacherOccupancy[key] = true;
            if (type === "P") sub.practicalLeft--; else sub.theoryLeft--;
            return true;
        }
    }
    return false;
}

// --- 6. SEPARATE DIV OUTPUT DISPLAY ---
function renderOutput(schedules, activeSems) {
    const cont = document.getElementById("timetableContainer");
    cont.innerHTML = ""; // Clear bottom area

    // Add a global Save Button at the top
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

    activeSems.sort().forEach(sem => {
        // Create a separate div (card) for each semester
        const semCard = document.createElement("div");
        semCard.className = "bg-white p-6 rounded-3xl shadow-xl border border-slate-100 mb-8 transition-all hover:shadow-2xl";
        
        let html = `
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-extrabold text-slate-800">Semester ${sem} Timetable</h3>
                <div class="flex gap-2">
                    <span class="px-3 py-1 bg-blue-50 text-[#006ADC] rounded-full text-[10px] font-bold border border-blue-100 uppercase">Generated</span>
                </div>
            </div>
            <div class="overflow-x-auto rounded-xl">
                <table class="w-full text-center border-collapse">
                    <thead class="bg-slate-50">
                        <tr class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
                            <th class="p-4 border-b">Day</th>
                            ${timeSlots.map(time => `<th class="p-4 border-b border-l">${time}</th>`).join('')}
                        </tr>
                    </thead>
                    <tbody>`;

        days.forEach((day, dIndex) => {
            html += `<tr>
                <td class="p-4 font-bold text-slate-700 bg-slate-50/30 border-r text-sm">${day}</td>`;
            
            schedules[sem][dIndex].forEach((val, pIndex) => {
                const isLunch = val === "LUNCH";
                const cellClass = isLunch ? "bg-amber-50 text-amber-600 font-bold italic" : "text-slate-600";
                html += `<td class="p-4 text-[11px] border-l border-slate-50 ${cellClass}">${val || "-"}</td>`;
            });
            html += `</tr>`;
        });

        html += `</tbody></table></div>`;
        semCard.innerHTML = html;
        cont.appendChild(semCard);
    });

    // Scroll to results
    cont.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// --- 7. SAVE FUNCTIONALITY ---
window.prepareSave = function(schedules, activeSems) {
    const title = prompt("Enter a title for this timetable (e.g., 'Even Semester 2024'):");
    if (!title) return;

    const dataToSave = {
        title: title,
        type: 'institution', // or user could choose
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
        } else {
            alert("Error: " + data.error);
        }
    })
    .catch(err => {
        console.error(err);
        alert("Failed to save timetable.");
    });
};