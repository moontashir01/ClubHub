const skills = document.querySelectorAll(".skill");
const popup = document.getElementById("popup");
const form = document.getElementById("volunteerForm");

let selectedSkills = [];

// Toggle skill buttons
skills.forEach(skill => {
    skill.addEventListener("click", () => {
        skill.classList.toggle("active");

        const skillName = skill.textContent;

        if (selectedSkills.includes(skillName)) {
            selectedSkills = selectedSkills.filter(s => s !== skillName);
        } else {
            selectedSkills.push(skillName);
        }
    });
});

// Form submit
form.addEventListener("submit", function(e) {
    e.preventDefault();

    const name = document.getElementById("name").value;
    const id = document.getElementById("studentId").value;
    const email = document.getElementById("email").value;
    const event = document.getElementById("event").value;

    if (!name || !id || !email || !event) {
        alert("Please fill all required fields");
        return;
    }

    popup.classList.add("show");

    setTimeout(() => {
        popup.classList.remove("show");
    }, 3000);

    form.reset();
    skills.forEach(skill => skill.classList.remove("active"));
});