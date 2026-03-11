async function uploadPDF(){

const semesters = document.querySelectorAll("input[name='semester']")

let selectedSem = null
let selectedFile = null

semesters.forEach(sem => {

    if(sem.checked){

        selectedSem = sem.value

        const fileInput = sem.parentElement.querySelector("input[type='file']")

        if(fileInput.files.length > 0){
            selectedFile = fileInput.files[0]
        }

    }

})

if(!selectedSem){
alert("Please select a semester")
return
}

if(!selectedFile){
alert("Please upload a PDF for selected semester")
return
}

const formData = new FormData()

formData.append("semester", selectedSem)
formData.append("pdf", selectedFile)

const response = await fetch("http://127.0.0.1:5000/upload",{
method:"POST",
body:formData
})

const data = await response.json()

const tbody = document.querySelector("#resultTable tbody")

tbody.innerHTML = ""

data.forEach(subject=>{

let row = `
<tr>
<td>${subject.subject}</td>
<td>${subject.theory}</td>
<td>${subject.practical}</td>
</tr>
`

tbody.innerHTML += row

})

}