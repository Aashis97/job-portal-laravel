'use strict';

document.addEventListener('DOMContentLoaded', () => {

    const scheduleForm = document.getElementById('scheduleInterviewForm');
    const editForm = document.getElementById('editInterviewForm');

    const form = scheduleForm || editForm;

    if (!form) {
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | Interview Type
    |--------------------------------------------------------------------------
    */

    const typeSelect = document.getElementById('interviewType');

    const typeRadios = form.querySelectorAll(
        'input[name="interview_type"]'
    );

    const meetingLinkInput =
        document.getElementById('meetingLink') ||
        document.getElementById('meeting_link');

    const locationInput =
        document.getElementById('interviewLocation') ||
        document.getElementById('location');

    const meetingLinkGroup =
        document.getElementById('meetingLinkGroup') ||
        document.getElementById('onlineMeetingSection');

    const locationGroup =
        document.getElementById('locationGroup') ||
        document.getElementById('locationSection');

    const conditionalRequired = form.querySelectorAll(
        '.conditional-required'
    );

    function getInterviewType() {

        if (typeSelect) {
            return typeSelect.value;
        }

        const checked = form.querySelector(
            'input[name="interview_type"]:checked'
        );

        return checked ? checked.value : '';
    }

    function toggleInterviewFields() {

        const type = getInterviewType();

        const isOnline = type === 'Online';
        const isInPerson = type === 'Onsite';

        if (meetingLinkGroup) {
            meetingLinkGroup.style.display = isOnline ? '' : 'none';
        }

        if (locationGroup) {
            locationGroup.style.display = isInPerson ? '' : 'none';
        }

        if (meetingLinkInput) {
            meetingLinkInput.required = isOnline;
        }

        if (locationInput) {
            locationInput.required = isInPerson;
        }

        conditionalRequired.forEach((el) => {

            const inMeeting = el.closest(
                '#meetingLinkGroup, #onlineMeetingSection'
            );

            const inLocation = el.closest(
                '#locationGroup, #locationSection'
            );

            if (inMeeting) {
                el.style.display = isOnline ? '' : 'none';
            }

            if (inLocation) {
                el.style.display = isInPerson ? '' : 'none';
            }
        });
    }

    if (typeSelect) {
        typeSelect.addEventListener('change', toggleInterviewFields);
    }

    typeRadios.forEach((radio) => {
        radio.addEventListener('change', toggleInterviewFields);
    });

    toggleInterviewFields();

    /*
    |--------------------------------------------------------------------------
    | Date Minimum
    |--------------------------------------------------------------------------
    */

    document.querySelectorAll('input[type="date"]').forEach((input) => {

        if (!input.min) {

            const date = new Date();
            const today = [
                date.getFullYear(),
                String(date.getMonth() + 1).padStart(2, '0'),
                String(date.getDate()).padStart(2, '0')
            ].join('-');

            input.min = today;
        }
    });

    /*
    |--------------------------------------------------------------------------
    | Remarks Character Counter
    |--------------------------------------------------------------------------
    */

    const remarksInput = document.getElementById('remarks');

    const remarksCounter =
        document.getElementById('remarksCounter') ||
        document.getElementById('remarksCount');

    if (remarksInput && remarksCounter) {

        const max = remarksInput.maxLength > 0
            ? remarksInput.maxLength
            : 2000;

        const updateCounter = () => {
            remarksCounter.textContent =
                `${remarksInput.value.length} / ${max}`;
        };

        remarksInput.addEventListener('input', updateCounter);

        updateCounter();
    }

    /*
    |--------------------------------------------------------------------------
    | Submit Protection
    |--------------------------------------------------------------------------
    */

    let submitting = false;

    form.addEventListener('submit', (event) => {

        if (submitting) {
            event.preventDefault();
            return;
        }

        const type = getInterviewType();

        if (
            type === 'Online' &&
            meetingLinkInput &&
            meetingLinkInput.value.trim() === ''
        ) {
            event.preventDefault();

            alert('Meeting link is required for online interviews.');

            meetingLinkInput.focus();

            return;
        }

        if (
            type === 'Onsite' &&
            locationInput &&
            locationInput.value.trim() === ''
        ) {
            event.preventDefault();

            alert('Location is required for in-person interviews.');

            locationInput.focus();

            return;
        }

        const dateInput = form.querySelector('input[type="date"]');
        const timeInput = form.querySelector('input[type="time"]');

        if (dateInput && dateInput.value) {

            const selectedDate = new Date(
                dateInput.value + 'T00:00:00'
            );

            const today = new Date();

            today.setHours(0, 0, 0, 0);

            if (selectedDate < today) {

                event.preventDefault();

                alert('Interview date cannot be in the past.');

                dateInput.focus();

                return;
            }
        }

        if (timeInput && !timeInput.value) {

            event.preventDefault();

            alert('Interview time is required.');

            timeInput.focus();

            return;
        }

        submitting = true;

        const submitButton = form.querySelector(
            'button[type="submit"]'
        );

        if (submitButton) {

            submitButton.disabled = true;

            submitButton.dataset.originalText =
                submitButton.innerHTML;

            submitButton.innerHTML =
                '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';
        }
    });
});