// modal.js - Complete modal form validation, storage, and integration
window.modalFormData = {};
window.modalCompletion = { training: false, employment: false };
let selectedReason = '';
let codeVerified = false; // track if code verification succeeded

document.addEventListener("DOMContentLoaded", function () {
    // Helper to build absolute API URLs relative to current page
    const apiUrl = (rel) => new URL(rel, window.location.href).toString();
    
    // Show code verification modal when Training/Employment is selected
    const reasonSelect = document.getElementById("reason");
    if (reasonSelect) {
        reasonSelect.addEventListener("change", function () {
            if (reasonSelect.value === "Training" || reasonSelect.value === "Employment") {
                // Require main email before proceeding
                const mainEmailEl = document.getElementById('email');
                const mainEmailVal = mainEmailEl ? mainEmailEl.value.trim() : '';
                if (!mainEmailVal) {
                    showModalToast('Please enter your email before selecting this option.', 'warning');
                    reasonSelect.value = '';
                    return;
                }
                selectedReason = reasonSelect.value;
                codeVerified = false; // reset when opening code modal
                const codeModal = new bootstrap.Modal(document.getElementById('codeVerificationModal'));
                codeModal.show();
            }
        });
    }

    // Code verification handler
    const verifyCodeBtn = document.getElementById('verifyCodeBtn');
    if (verifyCodeBtn) {
        verifyCodeBtn.addEventListener('click', function() {
            const code = document.getElementById('accessCode').value;
            const errorDiv = document.getElementById('codeError');
            
            if (!code || code.length !== 6) {
                showError(errorDiv, 'Please enter a 6-digit code');
                return;
            }

            // Verify code via API
            fetch(apiUrl('api/verify_code.php'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ code: code, purpose: selectedReason.toLowerCase() })
            })
            .then(response => response.json())
        .then(data => {
                if (data.valid) {
            codeVerified = true;
                    // Hide code modal
                    bootstrap.Modal.getInstance(document.getElementById('codeVerificationModal')).hide();
                    
                    // Show appropriate form modal
                    if (selectedReason === 'Training') {
                        const trainingModal = new bootstrap.Modal(document.getElementById('trainingModal'));
                        trainingModal.show();
                    } else if (selectedReason === 'Employment') {
                        const employmentModal = new bootstrap.Modal(document.getElementById('employmentModal'));
                        employmentModal.show();
                    }
                    
                    // Clear and hide error
                    document.getElementById('accessCode').value = '';
                    hideError(errorDiv);
                } else {
                    showError(errorDiv, 'Invalid or expired code');
                }
            })
            .catch(error => {
                console.error('Code verification error:', error);
                showError(errorDiv, 'Error verifying code. Please try again.');
            });
        });
    }

    // Reset reason dropdown when code modal is closed without verification
    const codeModal = document.getElementById('codeVerificationModal');
    if (codeModal) {
        codeModal.addEventListener('hidden.bs.modal', function () {
            // If code was verified, keep the selected reason intact
            // Only reset the reason when the code modal is dismissed without successful verification
            if (!codeVerified) {
                if (reasonSelect && reasonSelect.value === selectedReason) {
                    reasonSelect.value = '';
                }
            }
            document.getElementById('accessCode').value = '';
            hideError(document.getElementById('codeError'));
        });
    }

    // Training Modal Navigation
    setupTrainingModalNavigation();
    
    // Training Form Handler
    setupTrainingFormHandler();
    
    // Employment Form Handler  
    setupEmploymentFormHandler();

    // Utility Functions
    function showError(errorDiv, message) {
        if (errorDiv) {
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
        }
    }

    function hideError(errorDiv) {
        if (errorDiv) {
            errorDiv.style.display = 'none';
        }
    }

    function showModalToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-bg-${type} border-0 position-fixed bottom-0 end-0 m-3`;
        toast.setAttribute('role', 'alert');
        toast.style.zIndex = '9999';
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        document.body.appendChild(toast);
        const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
        bsToast.show();
        
        toast.addEventListener('hidden.bs.toast', function() { 
            toast.remove(); 
        });
    }

    // Training Modal Setup
    function setupTrainingModalNavigation() {
        const nextBtn = document.getElementById('nextTrainingBtn');
        const backBtn = document.getElementById('backTrainingBtn');
        const part1 = document.getElementById('trainingPart1');
        const part2 = document.getElementById('trainingPart2');
        
        if (nextBtn && backBtn && part1 && part2) {
            nextBtn.addEventListener('click', function() {
                if (validateTrainingPart1()) {
                    part1.style.display = 'none';
                    part2.style.display = 'block';
                } else {
                    showModalToast('Please fill in all required fields in Section A and B', 'danger');
                }
            });
            
            backBtn.addEventListener('click', function() {
                part2.style.display = 'none';
                part1.style.display = 'block';
            });
        }
        
        // Reset modal to part 1 when opened
        const trainingModal = document.getElementById('trainingModal');
        if (trainingModal) {
            trainingModal.addEventListener('shown.bs.modal', function () {
                if (part1 && part2) {
                    part1.style.display = 'block';
                    part2.style.display = 'none';
                }
                // Auto-fill training email from main form and make read-only
                const mainEmail = document.getElementById('email') ? document.getElementById('email').value.trim() : '';
                ['email'].forEach(name => {
                    const el = trainingModal.querySelector(`[name="${name}"]`);
                    if (el && mainEmail) {
                        el.value = mainEmail;
                        el.readOnly = true;
                    }
                });
            });
        }
    }

    // Training Form Validation for Part 1
    function validateTrainingPart1() {
        const requiredFields = [
            'fullName', 'dob', 'age', 'gender', 'address', 'phone', 'email', 'nextOfKin',
            'qualification', 'institution', 'graduationYear'
        ];
        let isValid = true;
        
        requiredFields.forEach(fieldName => {
            const field = document.querySelector(`[name="${fieldName}"]`);
            if (field) {
                const value = field.value.trim();
                if (!value) {
                    field.classList.add('is-invalid');
                    isValid = false;
                } else {
                    field.classList.remove('is-invalid');
                }
            }
        });
        
        return isValid;
    }

    // Training Form Handler
    function setupTrainingFormHandler() {
        const trainingForm = document.getElementById('trainingForm');
        if (!trainingForm) return;

        trainingForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!validateFullTrainingForm()) {
                showModalToast('Please fill in all required fields', 'danger');
                return;
            }
            
            // Collect and submit form data directly to server
            const formData = new FormData(trainingForm);
            // Convert areas[] into training_areas[] for backend flexibility
            const areas = formData.getAll('areas[]');
            if (areas && areas.length) {
                formData.delete('areas[]');
                areas.forEach(v => formData.append('training_areas[]', v));
            }

            fetch(apiUrl('api/save_training_form.php'), {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.error || 'Failed to save training form');
                }
                // Clear any staged modal data to avoid duplicate sending
                window.modalCompletion.training = true;
                if (window.clearModalFormData) window.clearModalFormData();
                // Hide modal and show success
                bootstrap.Modal.getInstance(document.getElementById('trainingModal')).hide();
                showModalToast('Training form saved! Continue registration.', 'success');
                // Reset form
                trainingForm.reset();
                resetTrainingModal();
            })
            .catch(err => {
                console.error('Training save error:', err);
                showModalToast('Error saving training form: ' + err.message, 'danger');
            });
        });
    }

    // Employment Form Handler
    function setupEmploymentFormHandler() {
        const employmentForm = document.getElementById('employmentForm');
        if (!employmentForm) return;

        // Auto-fill employment email from main form when modal shows
        const employmentModal = document.getElementById('employmentModal');
        if (employmentModal) {
            employmentModal.addEventListener('shown.bs.modal', function () {
                const mainEmail = document.getElementById('email') ? document.getElementById('email').value.trim() : '';
                // Try both emp_email and email
                ['emp_email', 'email'].forEach(name => {
                    const el = employmentModal.querySelector(`[name="${name}"]`);
                    if (el && mainEmail) {
                        el.value = mainEmail;
                        el.readOnly = true;
                    }
                });
            });
        }

        employmentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!validateEmploymentForm()) {
                showModalToast('Please fill in all required fields', 'danger');
                return;
            }
            
            // Submit employment form directly to server
            const formData = new FormData(employmentForm);
            fetch(apiUrl('api/save_employment_form.php'), {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.error || 'Failed to save employment form');
                }
                window.modalCompletion.employment = true;
                if (window.clearModalFormData) window.clearModalFormData();
                bootstrap.Modal.getInstance(document.getElementById('employmentModal')).hide();
                showModalToast('Employment form saved! Continue registration.', 'success');
                employmentForm.reset();
            })
            .catch(err => {
                console.error('Employment save error:', err);
                showModalToast('Error saving employment form: ' + err.message, 'danger');
            });
        });
    }

    // Full Training Form Validation
    function validateFullTrainingForm() {
        const form = document.getElementById('trainingForm');
        if (!form) return false;
        
        let valid = true;
        
        // Get all form elements
        Array.from(form.elements).forEach(el => {
            if (el.hasAttribute('required') && el.offsetParent !== null) {
                if (el.type === 'checkbox') {
                    // For checkbox groups, check if at least one is selected
                    const group = form.querySelectorAll(`[name='${el.name}']`);
                    const hasChecked = Array.from(group).some(e => e.checked);
                    
                    if (!hasChecked) {
                        valid = false;
                        Array.from(group).forEach(e => e.classList.add('is-invalid'));
                    } else {
                        Array.from(group).forEach(e => e.classList.remove('is-invalid'));
                    }
                } else if (el.type !== 'submit' && el.type !== 'button') {
                    const value = el.value.trim();
                    if (!value) {
                        valid = false;
                        el.classList.add('is-invalid');
                    } else {
                        el.classList.remove('is-invalid');
                    }
                }
            }
        });

        // Special validation for training areas (at least one must be selected)
        const areaCheckboxes = form.querySelectorAll('[name="areas[]"]');
        const hasSelectedArea = Array.from(areaCheckboxes).some(cb => cb.checked);
        
        if (areaCheckboxes.length > 0 && !hasSelectedArea) {
            valid = false;
            areaCheckboxes.forEach(cb => cb.classList.add('is-invalid'));
            showModalToast('Please select at least one training area', 'danger');
        } else {
            areaCheckboxes.forEach(cb => cb.classList.remove('is-invalid'));
        }
        
        return valid;
    }

    // Employment Form Validation
    function validateEmploymentForm() {
        const form = document.getElementById('employmentForm');
        if (!form) return false;
        
        let valid = true;
        
        Array.from(form.elements).forEach(el => {
            if (el.hasAttribute('required') && el.offsetParent !== null && el.type !== 'submit' && el.type !== 'button') {
                const value = el.value.trim();
                if (!value) {
                    valid = false;
                    el.classList.add('is-invalid');
                } else {
                    el.classList.remove('is-invalid');
                }
            }
        });
        
        return valid;
    }

    // Reset Training Modal to Part 1
    function resetTrainingModal() {
        const part1 = document.getElementById('trainingPart1');
        const part2 = document.getElementById('trainingPart2');
        
        if (part1 && part2) {
            part1.style.display = 'block';
            part2.style.display = 'none';
        }
        
        // Clear any validation classes
        const form = document.getElementById('trainingForm');
        if (form) {
            Array.from(form.elements).forEach(el => {
                el.classList.remove('is-invalid');
            });
        }
    }

    // Public function to check if modal forms are completed for main registration
    window.checkModalFormCompletion = function(reason) {
        if (reason === 'Training') {
            return !!window.modalCompletion.training;
        } else if (reason === 'Employment') {
            return !!window.modalCompletion.employment;
        }
        return true; // For other reasons, no modal form required
    };

    // Public function to get modal form data for main registration
    window.getModalFormData = function() {
        return window.modalFormData;
    };

    // Public function to clear modal form data after successful registration
    window.clearModalFormData = function() {
        window.modalFormData = {};
        // Do not reset modalCompletion here; it indicates a successful save.
        // It will be reset after full registration completes on the main page if desired.
    };
});