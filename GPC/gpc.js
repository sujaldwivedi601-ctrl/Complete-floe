// Global State
let teacherList = [];

// Initialize on Load
document.addEventListener("DOMContentLoaded", () => {
    fetchTeachers();

    // Event Listeners for Teacher Management
    document.getElementById("addTeacherBtn").addEventListener("click", addTeacher);
    document.getElementById("teacherInput").addEventListener("keypress", (e) => {
        if (e.key === "Enter") addTeacher();
    });

    // Event Listeners for Semester Cards (Replacing HTML onclick)
    document.querySelectorAll(".sem-card").forEach(card => {
        card.addEventListener("click", function() {
            const sem = this.dataset.semester;
            const fileInput = this.querySelector(".pdf-file-input");
            fileInput.click();
        });
    });
});

/**
 * REMOVED: Deprecated triggerFileUpload (Now handled by event listener)
 */

/**
 * HANDLE FILE SELECTION FOR SEMESTER CARD
 */
function handleFileSelect(sem, input) {
    const card = document.querySelectorAll(".sem-card")[sem - 1];
    const checkbox = card.querySelector(".sem-checkbox");
    const statusText = card.querySelector(".status-text");

    if (input.files.length > 0) {
        const fileName = input.files[0].name;
        checkbox.checked = true;
        card.classList.add("active");
        statusText.textContent = fileName;
        showToast(`PDF added for Sem ${sem}`);
    } else {
        checkbox.checked = false;
        card.classList.remove("active");
        statusText.textContent = "Not Selected";
    }
}

/**
 * FETCH TEACHERS FROM DATABASE
 */
async function fetchTeachers() {
    try {
        const response = await fetch("../PHP/getTeachers.php");
        if (!response.ok) throw new Error("Failed to fetch teachers");
        
        teacherList = await response.json();
        renderTeacherList();
        updateTeacherDropdowns();
    } catch (error) {
        console.error("Error fetching teachers:", error);
        showToast("Error loading teachers", "error");
    }
}

/**
 * RENDER TEACHER LIST IN SIDEBAR
 */
function renderTeacherList() {
    const container = document.getElementById("teacherListUI");
    
    if (teacherList.length === 0) {
        container.innerHTML = `
            <div class="text-center py-4 text-slate-400 text-sm italic">
                No teachers added yet
            </div>
        `;
        return;
    }

    container.innerHTML = teacherList.map(t => `
        <div class="flex justify-between items-center bg-slate-50 p-3 rounded-xl border border-slate-100 hover:bg-white hover:shadow-sm transition-all group">
            <span class="font-semibold text-slate-700 text-sm">${t.teacher_name}</span>
            <button onclick="deleteTeacher(${t.id})" class="text-slate-300 hover:text-red-500 transition-colors">
                <span class="material-symbols-outlined text-lg">delete</span>
            </button>
        </div>
    `).join("");
}

/**
 * ADD NEW TEACHER
 */
async function addTeacher() {
    const input = document.getElementById("teacherInput");
    const name = input.value.trim();

    if (!name) return showToast("Please enter a name", "error");

    try {
        const response = await fetch("../PHP/addTeacher.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ teacher_name: name })
        });

        const result = await response.json();

        if (response.ok) {
            teacherList.push({ id: result.id, teacher_name: result.teacher_name });
            input.value = "";
            renderTeacherList();
            updateTeacherDropdowns();
            showToast("Teacher added successfully!");
        } else {
            showToast(result.error || "Failed to add teacher", "error");
        }
    } catch (error) {
        console.error("Error adding teacher:", error);
        showToast("Server error", "error");
    }
}

/**
 * DELETE TEACHER
 */
async function deleteTeacher(id) {
    if (!confirm("Are you sure you want to delete this teacher?")) return;

    try {
        const response = await fetch("../PHP/deleteTeacher.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ id: id })
        });

        if (response.ok) {
            teacherList = teacherList.filter(t => t.id !== id);
            renderTeacherList();
            updateTeacherDropdowns();
            showToast("Teacher deleted");
        } else {
            showToast("Failed to delete teacher", "error");
        }
    } catch (error) {
        console.error("Error deleting teacher:", error);
        showToast("Server error", "error");
    }
}

/**
 * UPDATE ALL DROPDOWNS DYNAMICALLY
 */
function updateTeacherDropdowns() {
    const dropdowns = document.querySelectorAll(".tech-dropdown");
    dropdowns.forEach(select => {
        const currentValue = select.value;
        select.innerHTML = `<option value="">-- Select --</option>` + 
            teacherList.map(t => `<option value="${t.teacher_name}" ${t.teacher_name === currentValue ? 'selected' : ''}>${t.teacher_name}</option>`).join("");
    });
}

/**
 * EXTRACT PDF DATA (Updated for Multiple PDF Cards)
 */
async function uploadPDF() {
    const checkedBoxes = document.querySelectorAll(".sem-checkbox:checked");
    const container = document.getElementById("tablesContainer");

    if (checkedBoxes.length === 0) return showToast("Please select at least one semester card with a PDF", "error");

    container.innerHTML = `
        <div class="flex flex-col items-center justify-center min-h-[400px] space-y-4">
            <div class="w-12 h-12 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
            <p class="font-bold text-slate-600">Processing multiple extractions...</p>
        </div>
    `;

    for (const checkbox of checkedBoxes) {
        const sem = checkbox.value;
        const card = checkbox.closest(".sem-card");
        const fileInput = card.querySelector(".pdf-file-input");

        if (fileInput.files.length === 0) continue;

        const formData = new FormData();
        formData.append("semester", sem);
        formData.append("pdf", fileInput.files[0]);

        try {
            const response = await fetch("http://127.0.0.1:5000/upload", {
                method: "POST",
                body: formData,
            });

            if (!response.ok) throw new Error(`Sem ${sem} extraction failed`);

            const data = await response.json();
            
            // Remove spinner if first table is added
            if (container.querySelector(".animate-spin")) {
                container.innerHTML = "";
            }

            renderSubjectTable(sem, data);
        } catch (error) {
            console.error(error);
            showToast(`Error extracting Sem ${sem}`, "error");
        }
    }
    
    showToast("All extractions completed!");
}

/**
 * RENDER SUBJECT TABLE WITH DROPDOWNS
 */
function renderSubjectTable(sem, data) {
    const container = document.getElementById("tablesContainer");
    const tableDiv = document.createElement("div");
    tableDiv.className = "mb-8 p-6 border border-slate-200 rounded-3xl bg-white shadow-sm transition-all hover:shadow-md";
    
    tableDiv.innerHTML = `
        <div class="flex justify-between items-center mb-4 px-2">
            <h3 class="font-extrabold text-[#006ADC] text-lg">Semester ${sem} Subjects</h3>
            <span class="px-3 py-1 bg-blue-50 text-blue-600 rounded-full text-[10px] font-bold border border-blue-100 uppercase italic">Extracted</span>
        </div>
        <div class="overflow-x-auto rounded-2xl border border-slate-100">
            <table class="w-full text-left border-collapse semester-table" data-semester="${sem}">
                <thead class="bg-slate-50 text-[10px] uppercase font-black text-slate-400 tracking-widest border-b border-slate-100">
                    <tr>
                        <th class="p-4">Subject</th>
                        <th class="p-4 text-center">T</th>
                        <th class="p-4 text-center">P</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    ${data.map(sub => `
                        <tr>
                            <td class="p-4 text-sm font-semibold text-slate-700">${sub.subject}</td>
                            <td class="p-4 text-center text-sm font-bold text-blue-600">${sub.theory}</td>
                            <td class="p-4 text-center text-sm font-bold text-indigo-600">${sub.practical}</td>
                        </tr>
                    `).join("")}
                </tbody>
            </table>
        </div>
    `;
    container.appendChild(tableDiv);
}

/**
 * SAVE CHANGES TO DATABASE
 */
async function saveChanges() {
    const tables = document.querySelectorAll(".semester-table");
    if (tables.length === 0) return showToast("Nothing to save!", "error");

    let allData = [];

    tables.forEach((table) => {
        const semester = table.dataset.semester;
        const rows = table.querySelectorAll("tbody tr");

        rows.forEach((row) => {
            const subject = row.cells[0].innerText;
            const theory = row.cells[1].innerText;
            const practical = row.cells[2].innerText;
            const teacherSelect = row.querySelector(".tech-dropdown");
            const teacher = teacherSelect ? teacherSelect.value : "";

            allData.push({
                semester: semester,
                subject: subject,
                theory: theory,
                practical: practical,
                teacher: teacher
            });
        });
    });

    try {
        const response = await fetch("saveSubjects.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(allData),
        });

        if (response.ok) {
            showToast("Success! Subjects & Assignments Saved.");
        } else {
            throw new Error("Failed to save");
        }
    } catch (error) {
        console.error("Save error:", error);
        showToast("Error saving changes", "error");
    }
}

/**
 * UTILITY: SHOW TOAST NOTIFICATION
 */
function showToast(message, type = "success") {
    const toast = document.getElementById("toast");
    const msg = document.getElementById("toastMsg");
    const icon = document.getElementById("toastIcon");

    msg.innerText = message;
    
    if (type === "error") {
        icon.innerText = "error";
        icon.className = "material-symbols-outlined text-red-500";
    } else {
        icon.innerText = "check_circle";
        icon.className = "material-symbols-outlined text-green-400";
    }

    toast.classList.remove("translate-y-20", "opacity-0");
    setTimeout(() => {
        toast.classList.add("translate-y-20", "opacity-0");
    }, 3000);
}
