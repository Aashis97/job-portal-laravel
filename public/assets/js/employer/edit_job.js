/*
============================================================
Online Job Portal System
Employer Panel - Edit Job
File: assets/js/employer/edit-job.js
============================================================
*/

document.addEventListener('DOMContentLoaded', () => {

    const form = document.getElementById('editJobForm');

    const updateButton =
        document.getElementById('updateJobButton');

    const skillCheckboxes =
        document.querySelectorAll(
            '.skill-checkbox input[type="checkbox"]'
        );

    const selectedSkillsCount =
        document.getElementById('selectedSkillsCount');


    /*
    |--------------------------------------------------------------------------
    | Update Selected Skill Count
    |--------------------------------------------------------------------------
    */

    const updateSkillCount = () => {

        const selected = document.querySelectorAll(
            '.skill-checkbox input[type="checkbox"]:checked'
        ).length;

        if (selectedSkillsCount) {

            selectedSkillsCount.textContent =
                `${selected} selected`;

        }

    };


    skillCheckboxes.forEach(checkbox => {

        checkbox.addEventListener(
            'change',
            updateSkillCount
        );

    });


    /*
    |--------------------------------------------------------------------------
    | Initial Count
    |--------------------------------------------------------------------------
    */

    updateSkillCount();


    /*
    |--------------------------------------------------------------------------
    | Form Validation
    |--------------------------------------------------------------------------
    */

    form?.addEventListener('submit', event => {

        const jobTitle =
            document.getElementById('job_title');

        const category =
            document.getElementById('category_id');

        const location =
            document.getElementById('location');

        const employmentType =
            document.getElementById('employment_type');

        const workplace =
            document.getElementById('workplace');

        const experienceLevel =
            document.getElementById('experience_level');


        /*
        |----------------------------------------------------------------------
        | Job Title
        |----------------------------------------------------------------------
        */

        if (!jobTitle.value.trim()) {

            event.preventDefault();

            alert('Please enter a job title.');

            jobTitle.focus();

            return;

        }


        /*
        |----------------------------------------------------------------------
        | Category
        |----------------------------------------------------------------------
        */

        if (!category.value) {

            event.preventDefault();

            alert('Please select a job category.');

            category.focus();

            return;

        }


        /*
        |----------------------------------------------------------------------
        | Location
        |----------------------------------------------------------------------
        */

        if (!location.value.trim()) {

            event.preventDefault();

            alert('Please enter the job location.');

            location.focus();

            return;

        }


        /*
        |----------------------------------------------------------------------
        | Employment Type
        |----------------------------------------------------------------------
        */

        if (!employmentType.value) {

            event.preventDefault();

            alert('Please select an employment type.');

            employmentType.focus();

            return;

        }


        /*
        |----------------------------------------------------------------------
        | Workplace
        |----------------------------------------------------------------------
        */

        if (!workplace.value) {

            event.preventDefault();

            alert('Please select a workplace.');

            workplace.focus();

            return;

        }


        /*
        |----------------------------------------------------------------------
        | Experience Level
        |----------------------------------------------------------------------
        */

        if (!experienceLevel.value) {

            event.preventDefault();

            alert('Please select an experience level.');

            experienceLevel.focus();

            return;

        }


        /*
        |----------------------------------------------------------------------
        | Submit State
        |----------------------------------------------------------------------
        */

        if (updateButton) {

            updateButton.disabled = true;

            updateButton.innerHTML = `
                <i class="fa-solid fa-spinner fa-spin"></i>
                Updating...
            `;

        }

    });

});