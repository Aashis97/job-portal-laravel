/**
 * ==========================================================
 * Online Job Portal System
 * Jobseeker — Apply for Job
 * File: assets/js/jobseeker/apply_job.js
 * ==========================================================
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        var form       = document.getElementById('applyJobForm');
        if (!form) return;

        var textarea   = document.getElementById('cover_letter');
        var counter    = document.getElementById('characterCount');
        var fileInput  = document.getElementById('cover_letter_file');
        var fileName   = document.getElementById('coverLetterFileName');
        var submitBtn  = document.getElementById('submitApplicationBtn');

        /* ---------------- Character counter ---------------- */

        if (textarea && counter) {
            textarea.addEventListener('input', function () {
                counter.textContent = textarea.value.length;
            });
            counter.textContent = textarea.value.length;
        }

        /* ---------------- File selection ---------------- */

        if (fileInput && fileName) {

            fileInput.addEventListener('change', function () {

                if (!this.files || !this.files.length) {
                    fileName.textContent = 'No file selected';
                    return;
                }

                var file = this.files[0];

                var maxSize = 5 * 1024 * 1024;
                var allowedExt = ['pdf', 'doc', 'docx'];
                var ext = (file.name.split('.').pop() || '').toLowerCase();

                if (allowedExt.indexOf(ext) === -1) {
                    alert('Cover letter must be PDF, DOC or DOCX.');
                    this.value = '';
                    fileName.textContent = 'No file selected';
                    return;
                }

                if (file.size > maxSize) {
                    alert('Cover letter must not exceed 5 MB.');
                    this.value = '';
                    fileName.textContent = 'No file selected';
                    return;
                }

                fileName.textContent = file.name + ' (' + formatSize(file.size) + ')';
            });
        }

        /* ---------------- Submit guard ---------------- */

        form.addEventListener('submit', function () {

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML =
                    '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> Submitting...';
            }
        });

        /* ---------------- Size formatter ---------------- */

        function formatSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
        }
    });

})();