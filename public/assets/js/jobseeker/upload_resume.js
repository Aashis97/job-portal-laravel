/**
 * ==========================================================
 * Online Job Portal System
 * Job Seeker — Upload Resume
 * File: assets/js/upload_resume.js
 *
 * Handles:
 *   - Click-to-browse on the dropzone
 *   - Drag & drop
 *   - Client-side extension + size validation
 *   - Preview of the selected file
 *   - Remove-selected-file
 *   - Submit guard
 * ==========================================================
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        var page = document.querySelector('.resume-upload-page');
        if (!page) return;

        var dropzone     = document.getElementById('resumeDropzone');
        var fileInput    = document.getElementById('resume_file');
        var dzContent    = document.getElementById('resumeDropzoneContent');
        var selectedBox  = document.getElementById('selectedFile');
        var nameEl       = document.getElementById('selectedFileName');
        var sizeEl       = document.getElementById('selectedFileSize');
        var iconEl       = document.getElementById('selectedFileIcon');
        var removeBtn    = document.getElementById('removeFileBtn');
        var form         = document.getElementById('resumeUploadForm');
        var submitBtn    = document.getElementById('uploadResumeBtn');

        if (!dropzone || !fileInput) return;

        /* ==================================================
           Config
           ================================================== */
        var MAX_SIZE  = 5 * 1024 * 1024;
        var ALLOWED   = ['pdf', 'doc', 'docx'];

        /* ==================================================
           Helpers
           ================================================== */

        function formatSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
        }

        function getExtension(filename) {
            var parts = String(filename || '').toLowerCase().split('.');
            return parts.length > 1 ? parts[parts.length - 1] : '';
        }

        function getIconClass(ext) {
            if (ext === 'pdf') return 'fa-file-pdf';
            if (ext === 'doc' || ext === 'docx') return 'fa-file-word';
            return 'fa-file-lines';
        }

        /* ==================================================
           Error message (injected once, toggled on demand)
           ================================================== */
        var errorBox = null;

        function showError(message) {
            clearError();
            errorBox = document.createElement('div');
            errorBox.className = 'resume-error-message';
            errorBox.setAttribute('role', 'alert');
            errorBox.innerHTML =
                '<i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>' +
                '<span></span>';
            errorBox.querySelector('span').textContent = message;
            dropzone.parentNode.insertBefore(errorBox, dropzone.nextSibling);
            dropzone.classList.add('has-error');
        }

        function clearError() {
            if (errorBox && errorBox.parentNode) {
                errorBox.parentNode.removeChild(errorBox);
            }
            errorBox = null;
            dropzone.classList.remove('has-error');
        }

        /* ==================================================
           Preview / reset
           ================================================== */

        function showPreview(file) {
            var ext = getExtension(file.name);

            dropzone.classList.add('has-file');
            dropzone.classList.remove('has-error');
            clearError();

            if (dzContent) dzContent.setAttribute('hidden', '');
            if (selectedBox) selectedBox.removeAttribute('hidden');

            if (nameEl) nameEl.textContent = file.name;
            if (sizeEl) sizeEl.textContent = formatSize(file.size);

            if (iconEl) {
                iconEl.className = 'fa-solid ' + getIconClass(ext);
                iconEl.parentNode.classList.remove('is-pdf', 'is-word');

                if (ext === 'pdf') {
                    iconEl.parentNode.classList.add('is-pdf');
                } else if (ext === 'doc' || ext === 'docx') {
                    iconEl.parentNode.classList.add('is-word');
                }
            }
        }

        function resetPreview() {
            dropzone.classList.remove('has-file', 'has-error');
            clearError();

            if (dzContent) dzContent.removeAttribute('hidden');
            if (selectedBox) selectedBox.setAttribute('hidden', '');

            if (nameEl) nameEl.textContent = 'Resume.pdf';
            if (sizeEl) sizeEl.textContent = '0 KB';

            if (iconEl) {
                iconEl.className = 'fa-solid fa-file-lines';
                iconEl.parentNode.classList.remove('is-pdf', 'is-word');
            }
        }

        /* ==================================================
           Validation
           ================================================== */

        function validate(file) {
            if (!file) {
                return { ok: false, message: 'Please select a resume file.' };
            }

            var ext = getExtension(file.name);

            if (ALLOWED.indexOf(ext) === -1) {
                return {
                    ok: false,
                    message: 'Invalid file type. Please upload a PDF, DOC, or DOCX file.'
                };
            }

            if (file.size > MAX_SIZE) {
                return {
                    ok: false,
                    message: 'File is too large. Maximum allowed size is 5 MB.'
                };
            }

            if (file.size === 0) {
                return {
                    ok: false,
                    message: 'The selected file appears to be empty.'
                };
            }

            return { ok: true };
        }

        /* ==================================================
           Attach file to the input
           --------------------------------------------------
           Browsers don't let you set input.files directly
           from a drag-and-drop event. We use DataTransfer
           to rebuild the FileList.
           ================================================== */

        function setInputFile(file) {
            try {
                var dt = new DataTransfer();
                dt.items.add(file);
                fileInput.files = dt.files;
                return true;
            } catch (e) {
                /* Older browsers — fall back to the input value only */
                console.warn('Cannot assign dropped file to input:', e);
                return false;
            }
        }

        function handleFile(file) {
            var result = validate(file);

            if (!result.ok) {
                showError(result.message);
                fileInput.value = '';
                resetPreview();
                return;
            }

            if (!setInputFile(file)) {
                showError('Your browser cannot accept a dropped file. Please click to browse instead.');
                return;
            }

            showPreview(file);
        }

        /* ==================================================
           File input change
           ================================================== */
        fileInput.addEventListener('change', function () {
            if (this.files && this.files.length > 0) {
                handleFile(this.files[0]);
            } else {
                resetPreview();
            }
        });

        /* ==================================================
           Click on dropzone opens the picker
           --------------------------------------------------
           The hidden input sits on top of the dropzone
           (z-index 2), so clicks already reach it. When a file
           is selected we hide the input and must forward the
           click ourselves.
           ================================================== */
        dropzone.addEventListener('click', function (e) {
            if (dropzone.classList.contains('has-file')) return;
            if (e.target === fileInput) return;
            fileInput.click();
        });

        /* ==================================================
           Drag & drop
           ================================================== */

        ['dragenter', 'dragover'].forEach(function (eventName) {
            dropzone.addEventListener(eventName, function (e) {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.add('is-dragover');
            });
        });

        ['dragleave', 'dragend'].forEach(function (eventName) {
            dropzone.addEventListener(eventName, function (e) {
                e.preventDefault();
                e.stopPropagation();

                /* Only remove the highlight when leaving the dropzone itself */
                if (e.target === dropzone || !dropzone.contains(e.relatedTarget)) {
                    dropzone.classList.remove('is-dragover');
                }
            });
        });

        dropzone.addEventListener('drop', function (e) {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.remove('is-dragover');

            var files = e.dataTransfer && e.dataTransfer.files;
            if (!files || files.length === 0) return;

            /* Single-file upload — take the first */
            handleFile(files[0]);
        });

        /* Prevent browser from opening the file in a new tab
           when the user misses the dropzone */
        ['dragover', 'drop'].forEach(function (eventName) {
            window.addEventListener(eventName, function (e) {
                if (!dropzone.contains(e.target)) {
                    e.preventDefault();
                }
            });
        });

        /* ==================================================
           Remove selected file
           ================================================== */
        if (removeBtn) {
            removeBtn.addEventListener('click', function (e) {
                e.stopPropagation();

                fileInput.value = '';
                resetPreview();

                /* Re-focus the input so keyboard users can re-open the picker */
                fileInput.focus();
            });
        }

        /* ==================================================
           Submit guard
           ================================================== */
        if (form) {
            form.addEventListener('submit', function (e) {

                if (!fileInput.files || fileInput.files.length === 0) {
                    e.preventDefault();
                    showError('Please select a resume file before uploading.');
                    dropzone.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return;
                }

                var check = validate(fileInput.files[0]);

                if (!check.ok) {
                    e.preventDefault();
                    showError(check.message);
                    dropzone.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return;
                }

                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML =
                        '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>' +
                        '<span>Uploading…</span>';
                }
            });
        }

    });
})();