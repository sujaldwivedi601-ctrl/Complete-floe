import * as THREE from 'three';
import { GoogleGenAI } from "@google/genai";
import initialTasks from './tasks.json';

// --- INITIALIZATION ---
let tasks = [...initialTasks];
let productivityScore = 84;
const ai = new GoogleGenAI({ apiKey: process.env.GEMINI_API_KEY });
let currentChat = null;
let activeQuizTask = null;
let currentQuestionIndex = 0;

// --- DOM ELEMENTS ---
const taskListEl = document.getElementById('task-list');
const scoreDisplay = document.getElementById('score-display');
const pendingCounter = document.getElementById('pending-counter');
const classHoursEl = document.getElementById('class-hours');
const freeHoursEl = document.getElementById('free-hours');
const pendingTasksEl = document.getElementById('pending-tasks');
const newTaskNameInp = document.getElementById('new-task-name');
const newTaskSubjectSel = document.getElementById('new-task-subject');
const newTaskTimeInp = document.getElementById('new-task-time');
const addTaskBtn = document.getElementById('add-task-btn');

// Quiz Modal Elements
const quizModal = document.getElementById('quiz-modal');
const quizModalContent = document.getElementById('quiz-modal-content');
const quizTaskNameEl = document.getElementById('quiz-task-name');
const quizCounterEl = document.getElementById('quiz-counter');
const quizQuestionEl = document.getElementById('quiz-question');
const quizAnswerInp = document.getElementById('quiz-answer');
const quizSubmitBtn = document.getElementById('quiz-submit-btn');
const quizProgressEl = document.getElementById('quiz-progress');
const quizBody = document.getElementById('quiz-body');
const quizResult = document.getElementById('quiz-result');
const resultIcon = document.getElementById('result-icon');
const resultTitle = document.getElementById('result-title');
const resultMessage = document.getElementById('result-message');
const quizCloseBtn = document.getElementById('quiz-close-btn');

// --- THREE.JS SETUP ---
const initThree = () => {
    const container = document.getElementById('three-container');
    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(75, container.clientWidth / container.clientHeight, 0.1, 1000);
    const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    
    renderer.setSize(container.clientWidth, container.clientHeight);
    container.appendChild(renderer.domElement);

    const geometry = new THREE.OctahedronGeometry(1.2, 0);
    const material = new THREE.MeshPhongMaterial({ 
        color: 0x4f46e5, 
        wireframe: true,
        emissive: 0x4f46e5,
        emissiveIntensity: 0.5
    });
    const mesh = new THREE.Mesh(geometry, material);
    scene.add(mesh);

    const light = new THREE.PointLight(0xffffff, 1, 100);
    light.position.set(5, 5, 5);
    scene.add(light);
    scene.add(new THREE.AmbientLight(0x404040));

    camera.position.z = 3;

    window.addEventListener('resize', () => {
        camera.aspect = container.clientWidth / container.clientHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(container.clientWidth, container.clientHeight);
    });

    const animate = () => {
        requestAnimationFrame(animate);
        mesh.rotation.x += 0.01;
        mesh.rotation.y += 0.01;
        
        // Dynamic color based on score
        const hue = (productivityScore % 100) / 100;
        material.color.setHSL(hue, 0.6, 0.5);
        material.emissive.setHSL(hue, 0.6, 0.2);

        renderer.render(scene, camera);
    };
    animate();
};

// --- TASK LOGIC ---
const updateSummary = () => {
    const classHours = tasks.filter(t => t.priority === 'HIGH').reduce((acc, t) => acc + t.plannedTime, 0);
    const totalHours = tasks.reduce((acc, t) => acc + t.plannedTime, 0);
    const pendingCount = tasks.filter(t => t.status === 'Pending').length;

    classHoursEl.textContent = `${classHours.toFixed(1)} hrs`;
    freeHoursEl.textContent = `${Math.max(0, 24 - totalHours).toFixed(1)} hrs`;
    pendingTasksEl.textContent = pendingCount;
    pendingCounter.textContent = `${pendingCount} Pending`;
    scoreDisplay.textContent = productivityScore;
};

const renderTasks = () => {
    taskListEl.innerHTML = '';
    
    const sorted = [...tasks].sort((a, b) => {
        if (a.priority === 'HIGH' && b.priority !== 'HIGH') return -1;
        if (a.priority !== 'HIGH' && b.priority === 'HIGH') return 1;
        if (a.status === 'Pending' && b.status === 'Completed') return -1;
        if (a.status === 'Completed' && b.status === 'Pending') return 1;
        return b.createdAt - a.createdAt;
    });

    sorted.forEach(task => {
        const div = document.createElement('div');
        div.className = `p-4 flex items-center hover:bg-slate-50 transition-colors ${task.priority === 'HIGH' ? 'bg-red-50/30 border-l-4 border-red-500' : ''}`;
        
        div.innerHTML = `
            <div class="flex-1">
                <div class="flex items-center space-x-2 mb-1">
                    ${task.priority === 'HIGH' ? 
                        '<span class="px-2 py-0.5 bg-red-100 text-red-700 text-[10px] font-bold rounded uppercase tracking-wider">📌 Teacher Task</span>' : 
                        '<span class="px-2 py-0.5 bg-slate-100 text-slate-600 text-[10px] font-bold rounded uppercase tracking-wider">Personal</span>'
                    }
                    <span class="text-xs font-semibold text-slate-500">${task.subject}</span>
                </div>
                <h3 class="font-bold text-slate-800 ${task.status === 'Completed' ? 'line-through text-slate-400 opacity-60' : ''}">
                    ${task.name}
                </h3>
            </div>
            <div class="flex items-center space-x-6">
                <div class="text-right">
                    <p class="text-[10px] text-slate-400 uppercase font-bold tracking-tighter">Planned</p>
                    <p class="text-sm font-mono text-slate-700">${task.plannedTime.toFixed(1)} hrs</p>
                </div>
                <div class="flex items-center gap-2">
                    ${task.status === 'Pending' ? 
                        `<button data-id="${task.id}" class="mark-done-btn px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded shadow-lg shadow-indigo-100 transition-all active:scale-95">Mark as Done</button>` :
                        '<div class="px-4 py-2 bg-emerald-50 text-emerald-600 text-xs font-bold rounded flex items-center gap-1.5 border border-emerald-100">Verified</div>'
                    }
                    ${task.priority !== 'HIGH' ? 
                        `<button data-id="${task.id}" class="delete-btn p-2 hover:bg-red-50 rounded text-slate-300 hover:text-red-500 transition-colors">🗑️</button>` : 
                        ''
                    }
                </div>
            </div>
        `;
        taskListEl.appendChild(div);
    });

    updateSummary();
};

// --- QUIZ LOGIC ---
const openQuiz = async (task) => {
    activeQuizTask = task;
    currentQuestionIndex = 0;
    quizTaskNameEl.textContent = `Verifying: ${task.name}`;
    quizCounterEl.textContent = `Q: 00 / 10`;
    quizQuestionEl.textContent = "Preparing interactive session...";
    quizAnswerInp.value = '';
    quizAnswerInp.disabled = true;
    quizSubmitBtn.disabled = true;
    
    // Reset progress bar
    quizProgressEl.innerHTML = '';
    for(let i=0; i<10; i++) {
        const dot = document.createElement('div');
        dot.className = 'h-1.5 flex-1 rounded bg-slate-100 transition-all duration-300';
        quizProgressEl.appendChild(dot);
    }

    // Show modal
    quizModal.classList.remove('opacity-0', 'pointer-events-none');
    quizModalContent.classList.remove('scale-95');
    quizBody.classList.remove('hidden');
    quizResult.classList.add('hidden');

    try {
        const systemInstruction = `You are a strict but friendly teacher. The student said they studied ${task.name} (${task.subject}). 
        Ask them exactly 10 short questions one by one about this topic. 
        For the first message, just ask the FIRST question. 
        Do not ask multiple questions at once. 
        Wait for their answer before asking the next. 
        After all 10, if they got 7 or more correct, respond with exactly: PASS. Otherwise respond with exactly: FAIL.`;

        currentChat = ai.chats.create({
            model: "gemini-3-flash-preview",
            config: { systemInstruction }
        });

        const response = await currentChat.sendMessage({ message: `I have finished studying ${task.name}. Please start the quiz.` });
        
        quizQuestionEl.textContent = response.text || "Could not load question.";
        quizAnswerInp.disabled = false;
        quizSubmitBtn.disabled = false;
        currentQuestionIndex = 1;
        updateQuizProgress();

    } catch (error) {
        console.error(error);
        quizQuestionEl.textContent = "Error connecting to AI teacher. Please try again.";
    }
};

const updateQuizProgress = () => {
    quizCounterEl.textContent = `Q: ${String(currentQuestionIndex).padStart(2, '0')} / 10`;
    const dots = quizProgressEl.children;
    for(let i=0; i<10; i++) {
        if(i < currentQuestionIndex - 1) dots[i].className = 'h-1.5 flex-1 rounded bg-emerald-500';
        else if(i === currentQuestionIndex - 1) dots[i].className = 'h-1.5 flex-1 rounded bg-indigo-500 animate-pulse';
        else dots[i].className = 'h-1.5 flex-1 rounded bg-slate-100';
    }
};

const handleQuizResponse = (text) => {
    if(text.includes('PASS')) {
        showResult(true);
        markTaskCompleted(activeQuizTask.id);
    } else if(text.includes('FAIL')) {
        showResult(false);
    } else {
        quizQuestionEl.textContent = text;
        currentQuestionIndex++;
        updateQuizProgress();
    }
};

const showResult = (passed) => {
    quizBody.classList.add('hidden');
    quizResult.classList.remove('hidden');
    
    if(passed) {
        resultIcon.className = 'w-20 h-20 bg-emerald-100 rounded-full flex items-center justify-center mx-auto text-emerald-600';
        resultIcon.innerHTML = '✔️';
        resultTitle.textContent = "Verification Passed";
        resultMessage.textContent = "Excellent performance. Your productivity score has been updated.";
        productivityScore++;
        scoreDisplay.textContent = productivityScore;
    } else {
        resultIcon.className = 'w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto text-red-600';
        resultIcon.innerHTML = '❌';
        resultTitle.textContent = "Verification Failed";
        resultMessage.textContent = "Study focus wasn't sufficient. Please review and try again later.";
    }
};

const markTaskCompleted = (id) => {
    tasks = tasks.map(t => t.id === id ? { ...t, status: 'Completed' } : t);
    renderTasks();
};

// --- EVENT LISTENERS ---
taskListEl.addEventListener('click', (e) => {
    const markBtn = e.target.closest('.mark-done-btn');
    const deleteBtn = e.target.closest('.delete-btn');
    
    if(markBtn) {
        const task = tasks.find(t => t.id === markBtn.dataset.id);
        openQuiz(task);
    }
    
    if(deleteBtn) {
        tasks = tasks.filter(t => t.id !== deleteBtn.dataset.id);
        renderTasks();
    }
});

addTaskBtn.addEventListener('click', () => {
    const name = newTaskNameInp.value.trim();
    const subject = newTaskSubjectSel.value;
    const time = parseFloat(newTaskTimeInp.value);
    
    if(!name) return;

    const newTask = {
        id: crypto.randomUUID(),
        name,
        subject,
        plannedTime: time,
        status: 'Pending',
        priority: 'Normal',
        createdAt: Date.now()
    };
    
    tasks.unshift(newTask);
    newTaskNameInp.value = '';
    renderTasks();
});

quizSubmitBtn.addEventListener('click', async () => {
    const answer = quizAnswerInp.value.trim();
    if(!answer || !currentChat) return;

    quizAnswerInp.disabled = true;
    quizSubmitBtn.disabled = true;
    quizSubmitBtn.textContent = "Analyzing...";

    try {
        const response = await currentChat.sendMessage({ message: answer });
        quizAnswerInp.value = '';
        quizAnswerInp.disabled = false;
        quizSubmitBtn.disabled = false;
        quizSubmitBtn.textContent = "Next Question →";
        handleQuizResponse(response.text);
    } catch (error) {
        console.error(error);
        quizAnswerInp.disabled = false;
        quizSubmitBtn.disabled = false;
        quizSubmitBtn.textContent = "Next Question →";
    }
});

quizCloseBtn.addEventListener('click', () => {
    quizModal.classList.add('opacity-0', 'pointer-events-none');
    quizModalContent.classList.add('scale-95');
});

// --- GO ---
document.addEventListener('DOMContentLoaded', () => {
    renderTasks();
    initThree();
});
