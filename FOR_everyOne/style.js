// ==========================================
// TIMETABLE GENERATION & SCHEDULING LOGIC
// ==========================================

const classes = ["1", "2", "3", "4", "5", "6"];
const days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
const totalPeriods = 7;
const timeSlots = ["10:30-11:30", "11:30-12:30", "12:30-1:30", "1:30-2:30", "2:30-3:00", "3:00-4:00", "4:00-5:00"];

function getLunchPeriodIdx(sem) {
    return 4; // Period index 4 is LUNCH (2:30-3:00)
}

function getTimeSlots(sem) {
    return timeSlots;
}

let teacherOccupancy = {};
let classSubjects = {};
let assignments = [];

function generateTimetable(assignmentsList, activeSemesters) {
    const sems = Array.from(activeSemesters).sort();
    let classSchedules = {};
    teacherOccupancy = {};

    let workingSubjects = JSON.parse(JSON.stringify(classSubjects));

    sems.forEach(c => {
        if (!workingSubjects[c]) workingSubjects[c] = [];
        assignmentsList.forEach(a => {
            if (a.className === c) {
                let existing = workingSubjects[c].find(s => s.name === a.subject);
                if (existing) {
                    existing.teacher = a.teacher;
                    if (a.theoryLeft !== undefined) existing.theoryLeft = a.theoryLeft;
                    if (a.practicalLeft !== undefined) existing.practicalLeft = a.practicalLeft;
                } else {
                    workingSubjects[c].push({
                        name: a.subject,
                        theoryLeft: a.theoryLeft || 0,
                        practicalLeft: a.practicalLeft || 0,
                        teacher: a.teacher
                    });
                }
            }
        });

        classSchedules[c] = Array.from({ length: 6 }, () => Array(totalPeriods).fill(null));
        for (let d = 0; d < 6; d++) {
            classSchedules[c][d][4] = "LUNCH";
        }
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
                    if (entry && typeof entry === 'string' && entry.includes("(T-")) {
                        usedTheoryToday.add(entry.split("<br>")[0]);
                    } else if (entry && typeof entry === 'object' && entry.type === "T") {
                        usedTheoryToday.add(entry.subject);
                    }
                });

                if (p < 4) {
                    assigned = tryAssign(className, d, p, workingSubjects, classSchedules, "T", usedTheoryToday);
                }

                if (!assigned) {
                    assigned = tryAssign(className, d, p, workingSubjects, classSchedules, "P");
                }

                if (!assigned) {
                    assigned = tryAssign(className, d, p, workingSubjects, classSchedules, "T", new Set());
                }
            });
        }
    }

    const fillerSubjects = ["LIBRARY / STUDY", "SEMINAR / REVISION", "MENTORING / TUTORIAL", "TECHNICAL FORUM"];
    sems.forEach(c => {
        for (let d = 0; d < 6; d++) {
            for (let p = 0; p < totalPeriods; p++) {
                if (classSchedules[c][d][p] === null) {
                    const fallbackName = fillerSubjects[(d + p) % fillerSubjects.length];
                    const label = `${fallbackName}<br><small>(T-Faculty)</small>`;
                    classSchedules[c][d][p] = {
                        subject: fallbackName,
                        teacher: "Faculty",
                        type: "T",
                        label: label
                    };
                }
            }
        }
    });

    if (typeof displayTimetable === "function") {
        displayTimetable(classSchedules, sems);
    } else if (typeof display === "function") {
        display(classSchedules, sems);
    }
    return classSchedules;
}

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

            if (nextP < totalPeriods && nextP !== 4 &&
                schedules[className][dayIdx][nextP] === null &&
                !teacherOccupancy[teacherKey1] && !teacherOccupancy[teacherKey2] &&
                subData.practicalLeft >= 2) {

                const label = `${candidate.subject}<br><small>(P-${candidate.teacher})</small>`;
                const cellObj = {
                    subject: candidate.subject,
                    teacher: candidate.teacher,
                    type: "P",
                    label: label
                };
                schedules[className][dayIdx][periodIdx] = cellObj;
                schedules[className][dayIdx][nextP] = cellObj;
                teacherOccupancy[teacherKey1] = true;
                teacherOccupancy[teacherKey2] = true;
                subData.practicalLeft -= 2;
                return true;
            }

            if (!teacherOccupancy[teacherKey1] && subData.practicalLeft > 0) {
                const label = `${candidate.subject}<br><small>(P-${candidate.teacher})</small>`;
                schedules[className][dayIdx][periodIdx] = {
                    subject: candidate.subject,
                    teacher: candidate.teacher,
                    type: "P",
                    label: label
                };
                teacherOccupancy[teacherKey1] = true;
                subData.practicalLeft--;
                return true;
            }
        } else {
            if (!teacherOccupancy[teacherKey1]) {
                const label = `${candidate.subject}<br><small>(T-${candidate.teacher})</small>`;
                schedules[className][dayIdx][periodIdx] = {
                    subject: candidate.subject,
                    teacher: candidate.teacher,
                    type: "T",
                    label: label
                };
                teacherOccupancy[teacherKey1] = true;
                subData.theoryLeft--;
                return true;
            }
        }
    }
    return false;
}

window.generateTimetable = generateTimetable;
window.tryAssign = tryAssign;