/* ==========================================================================
   Online Job Portal System
   Employer — Profile Image Uploader
   File: assets/js/employer/profile_image.js
   ========================================================================== */

(function () {
    'use strict';

    /* ----------------------------------------------------------------------
       Config
       ---------------------------------------------------------------------- */

    const MAX_SIZE = 5 * 1024 * 1024; // 5 MB

    const ALLOWED_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp'
    ];

    const ALLOWED_EXTENSIONS = ['.jpg', '.jpeg', '.png', '.webp'];

    const MIN_DIMENSION = 100;     // px
    const MAX_DIMENSION = 6000;    // px

    function hasAllowedType(file) {
        return !file.type || ALLOWED_TYPES.indexOf(file.type) !== -1;
    }

    /* ----------------------------------------------------------------------
       DOM refs
       ---------------------------------------------------------------------- */

    let fileInput;
    let dropzone;
    let selectedFileBox;
    let selectedFileName;
    let selectedFileSize;
    let removeButton;
    let preview;
    let form;
    let uploadButton;

    /* ----------------------------------------------------------------------
       1. File handling
       ---------------------------------------------------------------------- */

    function handleFile(file) {

        if (!file) return;

        // --- MIME ---
        if (!hasAllowedType(file)) {
            showNotice('error', 'Only JPG, JPEG, PNG or WEBP images are allowed.');
            resetInput();
            return;
        }

        // --- Extension ---
        const lowerName = file.name.toLowerCase();
        const extOk = ALLOWED_EXTENSIONS.some(function (ext) {
            return lowerName.endsWith(ext);
        });

        if (!extOk) {
            showNotice('error', 'The file extension must be .jpg, .jpeg, .png or .webp.');
            resetInput();
            return;
        }

        // --- Size ---
        if (file.size > MAX_SIZE) {
            showNotice('error', 'The image is larger than 5 MB.');
            resetInput();
            return;
        }

        if (file.size <= 0) {
            showNotice('error', 'The selected image is empty.');
            resetInput();
            return;
        }

        // --- Dimensions ---
        const reader = new FileReader();

        reader.onload = function (event) {

            const img = new Image();

            img.onload = function () {

                if (img.width < MIN_DIMENSION || img.height < MIN_DIMENSION) {
                    showNotice('error',
                        'Image dimensions are too small. Minimum ' +
                        MIN_DIMENSION + ' × ' + MIN_DIMENSION + ' px.'
                    );
                    resetInput();
                    return;
                }

                if (img.width > MAX_DIMENSION || img.height > MAX_DIMENSION) {
                    showNotice('error',
                        'Image dimensions are too large. Maximum ' +
                        MAX_DIMENSION + ' × ' + MAX_DIMENSION + ' px.'
                    );
                    resetInput();
                    return;
                }

                // --- Valid — update UI ---
                preview.src = event.target.result;

                selectedFileName.textContent = file.name;
                selectedFileSize.textContent = formatFileSize(file.size);

                selectedFileBox.hidden = false;

                clearNotice();
            };

            img.onerror = function () {
                showNotice('error', 'The selected file is not a valid image.');
                resetInput();
            };

            img.src = event.target.result;
        };

        reader.readAsDataURL(file);
    }

    function resetInput() {
        if (fileInput)   fileInput.value = '';
        if (selectedFileBox) selectedFileBox.hidden = true;
        if (selectedFileName) selectedFileName.textContent = '';
        if (selectedFileSize) selectedFileSize.textContent = '';
    }

    /* ----------------------------------------------------------------------
       2. Dropzone
       ---------------------------------------------------------------------- */

    function initDropzone() {

        if (!dropzone || !fileInput) return;

        // Click on dropzone → open file picker
        dropzone.addEventListener('click', function (event) {

            // Avoid double-triggering when clicking the input directly
            if (event.target !== fileInput) {
                fileInput.click();
            }
        });

        // Keyboard accessibility
        dropzone.setAttribute('tabindex', '0');
        dropzone.setAttribute('role', 'button');

        dropzone.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                fileInput.click();
            }
        });

        // Drag visual states
        ['dragenter', 'dragover'].forEach(function (name) {
            dropzone.addEventListener(name, function (event) {
                event.preventDefault();
                event.stopPropagation();
                dropzone.classList.add('drag-active');
            });
        });

        ['dragleave', 'drop'].forEach(function (name) {
            dropzone.addEventListener(name, function (event) {
                event.preventDefault();
                event.stopPropagation();
                dropzone.classList.remove('drag-active');
            });
        });

        // Handle drop
        dropzone.addEventListener('drop', function (event) {

            const files = event.dataTransfer && event.dataTransfer.files;

            if (!files || !files.length) return;

            const file = files[0];

            // Assign the dropped file to the input for form submission
            try {
                const dt = new DataTransfer();
                dt.items.add(file);
                fileInput.files = dt.files;
            } catch (e) {
                // Older browsers — fallback
                console.warn('Could not assign dropped file to input:', e);
            }

            handleFile(file);
        });

        // Standard file picker
        fileInput.addEventListener('change', function () {
            if (this.files && this.files.length) {
                handleFile(this.files[0]);
            }
        });
    }

    /* ----------------------------------------------------------------------
       3. Remove selected file
       ---------------------------------------------------------------------- */

    function initRemoveButton() {

        if (!removeButton) return;

        removeButton.addEventListener('click', function () {
            resetInput();
            if (preview) {
                preview.src = preview.dataset.originalSrc || preview.src;
            }
        });
    }

    /* ----------------------------------------------------------------------
       4. Form submit — validate + loading state
       ---------------------------------------------------------------------- */

    function initFormSubmit() {

        if (!form) return;

        form.addEventListener('submit', function (event) {

            if (!fileInput || !fileInput.files || !fileInput.files.length) {
                event.preventDefault();
                showNotice('error', 'Please select a profile image before uploading.');
                return;
            }

            const file = fileInput.files[0];

            if (!hasAllowedType(file) || file.size > MAX_SIZE) {
                event.preventDefault();
                showNotice('error', 'Please select a valid image file under 5 MB.');
                return;
            }

            if (uploadButton) {
                uploadButton.disabled = true;
                uploadButton.dataset.originalHtml = uploadButton.innerHTML;
                uploadButton.innerHTML =
                    '<i class="fa-solid fa-spinner fa-spin"></i> Uploading…';
            }
        });
    }

    /* ----------------------------------------------------------------------
       5. Flash alert dismissal
       ---------------------------------------------------------------------- */

    function initAlertClose() {
        document.querySelectorAll('.alert-close').forEach(function (button) {
            button.addEventListener('click', function () {
                const alert = button.closest('.profile-alert');
                if (!alert) return;

                alert.style.transition = 'opacity .25s ease';
                alert.style.opacity    = '0';

                window.setTimeout(function () {
                    alert.remove();
                }, 260);
            });
        });
    }

    /* ----------------------------------------------------------------------
       6. Inline notice (client-side errors)
       ---------------------------------------------------------------------- */

    function showNotice(type, message) {

        clearNotice();

        const notice = document.createElement('div');
        notice.className = 'profile-alert profile-alert-' + type + ' js-inline-notice';
        notice.innerHTML =
            '<i class="fa-solid ' +
            (type === 'error' ? 'fa-circle-exclamation' : 'fa-circle-info') +
            '"></i>' +
            '<div><strong>' +
            (type === 'error' ? 'Upload Error' : 'Notice') +
            '</strong><p></p></div>' +
            '<button type="button" class="alert-close" aria-label="Close">' +
            '<i class="fa-solid fa-xmark"></i>' +
            '</button>';

        notice.querySelector('p').textContent = message;

        const header = document.querySelector('.profile-image-page-header');
        if (header && header.parentNode) {
            header.parentNode.insertBefore(notice, header.nextSibling);
        }

        notice.querySelector('.alert-close').addEventListener('click', function () {
            notice.remove();
        });

        notice.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function clearNotice() {
        const existing = document.querySelector('.js-inline-notice');
        if (existing) existing.remove();
    }

    /* ----------------------------------------------------------------------
       7. Utility — format file size
       ---------------------------------------------------------------------- */

    function formatFileSize(bytes) {

        if (bytes < 1024) {
            return bytes + ' B';
        }

        if (bytes < 1024 * 1024) {
            return (bytes / 1024).toFixed(1) + ' KB';
        }

        return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
    }

    /* ----------------------------------------------------------------------
       8. Boot
       ---------------------------------------------------------------------- */

    function cacheDom() {
        fileInput        = document.getElementById('profile_image');
        dropzone         = document.getElementById('uploadDropzone');
        selectedFileBox  = document.getElementById('selectedFile');
        selectedFileName = document.getElementById('selectedFileName');
        selectedFileSize = document.getElementById('selectedFileSize');
        removeButton     = document.getElementById('removeSelectedFile');
        preview          = document.getElementById('profileImagePreview');
        form             = document.getElementById('profileImageForm');
        uploadButton     = document.getElementById('uploadImageBtn');

        if (preview && !preview.dataset.originalSrc) {
            preview.dataset.originalSrc = preview.src;
        }
    }

    function ready(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback, { once: true });
        } else {
            callback();
        }
    }

    ready(function () {
        cacheDom();
        initDropzone();
        initRemoveButton();
        initFormSubmit();
        initAlertClose();
    });

})();