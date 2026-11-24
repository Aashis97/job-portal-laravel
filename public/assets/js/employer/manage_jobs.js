/*
============================================================
Online Job Portal System
Employer Panel - Manage Jobs
File: assets/js/employer/manage-jobs.js
============================================================
*/

document.addEventListener('DOMContentLoaded', () => {

    const deleteModal = document.getElementById('deleteModal');
    const deleteJobForm = document.getElementById('deleteJobForm');
    const deleteJobId = document.getElementById('deleteJobId');
    const deleteJobTitle = document.getElementById('deleteJobTitle');

    const closeDeleteModal =
        document.getElementById('closeDeleteModal');

    const cancelDelete =
        document.getElementById('cancelDelete');


    /*
    |--------------------------------------------------------------------------
    | Open Delete Modal
    |--------------------------------------------------------------------------
    */

    document.querySelectorAll('.job-action.delete').forEach(button => {

        button.addEventListener('click', () => {

            const jobId = button.dataset.jobId;
            const jobTitle = button.dataset.jobTitle;

            if (!jobId) {
                return;
            }

            deleteJobId.value = jobId;
            deleteJobTitle.textContent = jobTitle || 'this job';

            deleteModal.classList.add('show');
            deleteModal.setAttribute('aria-hidden', 'false');

            document.body.style.overflow = 'hidden';

        });

    });


    /*
    |--------------------------------------------------------------------------
    | Close Modal
    |--------------------------------------------------------------------------
    */

    const closeModal = () => {

        deleteModal.classList.remove('show');
        deleteModal.setAttribute('aria-hidden', 'true');

        document.body.style.overflow = '';

        deleteJobId.value = '';

    };


    closeDeleteModal?.addEventListener('click', closeModal);

    cancelDelete?.addEventListener('click', closeModal);


    /*
    |--------------------------------------------------------------------------
    | Close When Clicking Overlay
    |--------------------------------------------------------------------------
    */

    document
        .querySelector('.delete-modal-overlay')
        ?.addEventListener('click', closeModal);


    /*
    |--------------------------------------------------------------------------
    | ESC Key
    |--------------------------------------------------------------------------
    */

    document.addEventListener('keydown', event => {

        if (event.key === 'Escape' &&
            deleteModal.classList.contains('show')) {

            closeModal();

        }

    });


    /*
    |--------------------------------------------------------------------------
    | Delete Form Protection
    |--------------------------------------------------------------------------
    */

    deleteJobForm?.addEventListener('submit', event => {

        const jobId = deleteJobId.value;

        if (!jobId) {

            event.preventDefault();

            return;

        }

    });

});