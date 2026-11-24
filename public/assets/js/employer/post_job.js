document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('postJobForm');
    const submitButton = document.getElementById('submitJobBtn');
    if (!form) {
        return;
    }

    const fields = {
        title: document.getElementById('job_title'),
        category: document.getElementById('category_id'),
        description: document.getElementById('job_description'),
        employmentType: document.getElementById('employment_type'),
        workplace: document.getElementById('workplace'),
        experienceLevel: document.getElementById('experience_level'),
        minimumExperience: document.getElementById('minimum_experience'),
        minimumSalary: document.getElementById('minimum_salary'),
        maximumSalary: document.getElementById('maximum_salary'),
        vacancy: document.getElementById('vacancy'),
        deadline: document.getElementById('application_deadline')
    };

    if (fields.deadline) {
        fields.deadline.min = new Date().toISOString().split('T')[0];
    }

    const fail = (message, field) => {
        window.alert(message);
        field.focus();
    };

    form.addEventListener('submit', event => {
        const title = fields.title.value.trim();
        const description = fields.description.value.trim();
        const minimumExperience = fields.minimumExperience.value.trim();
        const minimumSalary = fields.minimumSalary.value.trim();
        const maximumSalary = fields.maximumSalary.value.trim();
        const vacancy = fields.vacancy.value.trim();

        if (!title) {
            event.preventDefault();
            return fail('Please enter a job title.', fields.title);
        }
        if (title.length > 150) {
            event.preventDefault();
            return fail('Job title cannot exceed 150 characters.', fields.title);
        }
        if (!fields.category.value) {
            event.preventDefault();
            return fail('Please select a job category.', fields.category);
        }
        if (!description) {
            event.preventDefault();
            return fail('Please enter a job description.', fields.description);
        }
        if (description.length > 10000) {
            event.preventDefault();
            return fail('Job description cannot exceed 10,000 characters.', fields.description);
        }
        for (const [field, message] of [
            [fields.employmentType, 'Please select an employment type.'],
            [fields.workplace, 'Please select a workplace type.'],
            [fields.experienceLevel, 'Please select an experience level.']
        ]) {
            if (!field.value) {
                event.preventDefault();
                return fail(message, field);
            }
        }
        if (minimumExperience !== '' && (Number(minimumExperience) < 0 || Number(minimumExperience) > 99)) {
            event.preventDefault();
            return fail('Minimum experience must be between 0 and 99 years.', fields.minimumExperience);
        }
        if (minimumSalary !== '' && Number(minimumSalary) < 0) {
            event.preventDefault();
            return fail('Minimum salary cannot be negative.', fields.minimumSalary);
        }
        if (maximumSalary !== '' && Number(maximumSalary) < 0) {
            event.preventDefault();
            return fail('Maximum salary cannot be negative.', fields.maximumSalary);
        }
        if (minimumSalary !== '' && maximumSalary !== '' && Number(maximumSalary) < Number(minimumSalary)) {
            event.preventDefault();
            return fail('Maximum salary cannot be lower than minimum salary.', fields.maximumSalary);
        }
        if (vacancy !== '' && (!Number.isInteger(Number(vacancy)) || Number(vacancy) < 1)) {
            event.preventDefault();
            return fail('Number of vacancies must be at least 1.', fields.vacancy);
        }
        if (fields.deadline.value && fields.deadline.value < new Date().toISOString().split('T')[0]) {
            event.preventDefault();
            return fail('Application deadline cannot be in the past.', fields.deadline);
        }

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';
        }
    });
});
