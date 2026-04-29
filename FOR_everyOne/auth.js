async function fetchColleges() {
    const input = document.getElementById('regInst');
    const datalist = document.getElementById('collegesList');
    
    if (!input || !datalist) return;
    
    const search = input.value;
    
    try {
        const res = await fetch(`../PHP/get_colleges.php?q=${encodeURIComponent(search)}`);
        const colleges = await res.json();
        
        datalist.innerHTML = '';
        colleges.forEach(college => {
            const option = document.createElement('option');
            option.value = college.name;
            option.dataset.code = college.code;
            datalist.appendChild(option);
        });
    } catch (err) {
        console.error('Error fetching colleges:', err); 
    }
}

window.toggleSemester = function() {
    const role = document.getElementById('regRole').value;
    const semesterDiv = document.getElementById('semesterDiv');
    
    if (role === 'Student') {
        semesterDiv.classList.remove('hidden');
    } else {
        semesterDiv.classList.add('hidden');
    }
};

document.addEventListener('DOMContentLoaded', () => {
    const instInput = document.getElementById('regInst');
    if (instInput) {
        instInput.addEventListener('input', fetchColleges);
        fetchColleges();
    }
});