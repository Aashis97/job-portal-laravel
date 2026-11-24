/**
 * ==========================================================
 * Online Job Portal System
 * Jobseeker — My Resume
 * File: assets/js/jobseeker/resume.js
 * ==========================================================
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        var form         = document.getElementById('resumeUploadForm');
        var input        = document.getElementById('resume_file');
        var dropzone     = document.getElementById('resumeDropzone');
        var dropContent  = document.getElementById('dropzoneContent');
        var selectedBox  = document.getElementById('selectedFile');
        var selectedName = document.getElementById('selectedFileName');
        var selectedSize = document.getElementById('selectedFileSize');
        var removeBtn    = document.getElementById('removeFileBtn');
        var submitBtn    = document.getElementById('uploadSubmitBtn');

        if (!form || !input) return;

        var maxSize = 5 * 1024 * 1024;
        var allowed = ['application/pdf', 'application/msword',
                       'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        var allowedExt = ['pdf', 'doc', 'docx'];

        /* ---------------- File formatting ---------------- */

        function formatSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
        }

        /* ---------------- Validate + show ---------------- */

        function isAllowed(file) {
            var ext = (file.name.split('.').pop() || '').toLowerCase();
            if (allowedExt.indexOf(ext) === -1) return false;
            if (file.type && allowed.indexOf(file.type) === -1) return false;
            return true;
        }

        function handleFile(file) {
            if (!file) return;

            if (!isAllowed(file)) {
                alert('Invalid file type. Only PDF, DOC and DOCX are allowed.');
                input.value = '';
                return;
            }

            if (file.size > maxSize) {
                alert('File must not exceed 5 MB.');
                input.value = '';
                return;
            }

            selectedName.textContent = file.name;
            selectedSize.textContent = formatSize(file.size);
            dropContent.hidden = true;
            selectedBox.hidden = false;
        }

        /* ---------------- Input change ---------------- */

        input.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                handleFile(this.files[0]);
            }
        });

        /* ---------------- Remove ---------------- */

        if (removeBtn) {
            removeBtn.addEventListener('click', function (event) {
                event.preventDefault();
                input.value = '';
                selectedBox.hidden = true;
                dropContent.hidden = false;
            });
        }

        /* ---------------- Drag & drop ---------------- */

        if (dropzone) {

            ['dragenter', 'dragover'].forEach(function (evt) {
                dropzone.addEventListener(evt, function (e) {
                    e.preventDefault();
                    dropzone.classList.add('dragging');
                });
            });

            ['dragleave', 'drop'].forEach(function (evt) {
                dropzone.addEventListener(evt, function (e) {
                    e.preventDefault();
                    dropzone.classList.remove('dragging');
                });
            });

            dropzone.addEventListener('drop', function (event) {
                var files = event.dataTransfer && event.dataTransfer.files;
                if (!files || !files.length) return;

                var file = files[0];

                try {
                    var dt = new DataTransfer();
                    dt.items.add(file);
                    input.files = dt.files;
                } catch (err) {
                    /* Fallback — some browsers block this */
                }

                handleFile(file);
            });
        }

        /* ---------------- Submit guard ---------------- */

        form.addEventListener('submit', function (event) {

            if (!input.files || !input.files.length) {
                event.preventDefault();
                alert('Please select a resume file before uploading.');
                return;
            }

            var file = input.files[0];

            if (!isAllowed(file) || file.size > maxSize) {
                event.preventDefault();
                alert('Please select a valid file under 5 MB.');
                return;
            }

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> Uploading...';
            }
        });

        /* ---------------- Confirm dialogs ---------------- */

        document.querySelectorAll('form[data-confirm]').forEach(function (f) {
            f.addEventListener('submit', function (event) {
                var msg = f.getAttribute('data-confirm') || '';
                if (msg && !window.confirm(msg)) {
                    event.preventDefault();
                }
            });
        });
    });

})();