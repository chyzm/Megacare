document.addEventListener("DOMContentLoaded", function () {
    const monthSelect = document.getElementById("month");
    const daySelect = document.getElementById("day");
    const selectedDate = document.getElementById("selectedDate");

    function populateDays() {
        const month = monthSelect.value;
        daySelect.innerHTML = '<option value="">Day</option>'; // Reset days

        if (!month) return; // Exit if no month is selected

        let daysInMonth;

        // Set correct number of days per month
        if (["April", "June", "September", "November"].includes(month)) {
            daysInMonth = 30; // April, June, September, November
        } else if (month === "February") {
            daysInMonth = 28; // February (no leap year handling for now)
        } else {
            daysInMonth = 31; // Other months
        }

        // Populate day dropdown
        for (let i = 1; i <= daysInMonth; i++) {
            let option = document.createElement("option");
            option.value = i < 10 ? "0" + i : i; // Format 01, 02, etc.
            option.textContent = i;
            daySelect.appendChild(option);
        }
    }

    // Populate days when month changes
    monthSelect.addEventListener("change", populateDays);

    // Display selected date
    daySelect.addEventListener("change", function () {
        if (monthSelect.value && daySelect.value) {
            selectedDate.textContent = `${monthSelect.value}-${daySelect.value}`;
        }
    });
});

/////////////////////////////////////////////

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('registrationForm');
    const qrCodeContainer = document.getElementById('qrCodeContainer');
    const qrcodeDiv = document.getElementById('qrcode');
    const submitBtn = form.querySelector('button[type="submit"]');
    const emailInput = document.getElementById('email');
    
    // Create feedback message element
    const feedbackMessage = document.createElement('div');
    feedbackMessage.className = 'alert alert-success mt-3 d-none';
    form.parentNode.insertBefore(feedbackMessage, form.nextSibling);
    
    // Required fields validation array (removed 'selectedDate' since it's a span)
    const requiredFields = ['firstName', 'lastName', 'month', 'day', 'reason', 'email', 'mobile', 'jobTitle', 'company', 'city', 'country'];
    
    // Email validation function
    const validateEmail = (email) => {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(String(email).toLowerCase());
    };

    // Improved field validation function
    const validateField = (fieldElement) => {
        // First check if the element exists and has a value property
        if (!fieldElement || fieldElement.value === undefined) {
            console.error('Invalid field element:', fieldElement);
            return false;
        }
        
        const value = fieldElement.value ? fieldElement.value.trim() : '';
        const isValid = value !== '';
        fieldElement.classList.toggle('is-invalid', !isValid);
        return isValid;
    };
    
    // Enhanced form validation function
    const validateForm = () => {
        let allValid = true;
        
        // Validate regular fields
        requiredFields.forEach(fieldId => {
            const element = document.getElementById(fieldId);
            if (!validateField(element)) {
                allValid = false;
            }
        });
        
        // Validate date selection separately
        const month = document.getElementById('month').value;
        const day = document.getElementById('day').value;
        if (!month || !day) {
            document.getElementById('month').classList.add('is-invalid');
            document.getElementById('day').classList.add('is-invalid');
            allValid = false;
        }
        
        // Email format validation
        const email = emailInput.value.trim();
        if (!validateEmail(email)) {
            emailInput.classList.add('is-invalid');
            const emailError = document.getElementById('email-error') || document.createElement('div');
            emailError.textContent = 'Invalid email format';
            emailError.id = 'email-error';
            emailError.className = 'invalid-feedback';
            emailInput.parentNode.appendChild(emailError);
            allValid = false;
        }
        
        return allValid;
    };
    
    async function handleRegistration(formData) {
        try {
            const response = await fetch('api/register.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            });

            const responseText = await response.text();
            console.log('Raw server response:', responseText);

            let data;
            try {
                data = JSON.parse(responseText);
            } catch (e) {
                console.error('JSON parse error:', e);
                throw new Error('Invalid server response format');
            }

            if (!response.ok) {
                if (data.error && data.error.includes('already registered')) {
                    emailInput.classList.add('is-invalid');
                    const emailError = document.getElementById('email-error') || document.createElement('div');
                    emailError.textContent = data.error;
                    emailError.id = 'email-error';
                    emailError.className = 'invalid-feedback';
                    emailInput.parentNode.appendChild(emailError);
                }
                throw new Error(data.error || 'Registration failed');
            }

            return data;
        } catch (error) {
            console.error('Registration error:', error);
            throw error;
        }
    }
    
    // Form submit handler
    form.addEventListener('submit', async function(event) {
        event.preventDefault();
        
        if (!validateForm()) {
            feedbackMessage.textContent = 'Please fill in all required fields correctly.';
            feedbackMessage.className = 'alert alert-danger mt-3';
            return;
        }

        // If reason requires a modal form, ensure it's completed first
        const reasonValue = document.getElementById('reason').value.trim();
        if (window.checkModalFormCompletion && (reasonValue === 'Training' || reasonValue === 'Employment')) {
            const ok = window.checkModalFormCompletion(reasonValue);
            if (!ok) {
                feedbackMessage.textContent = `Please complete the ${reasonValue} form before registering.`;
                feedbackMessage.className = 'alert alert-warning mt-3';
                return;
            }
        }

        const payload = {
            firstName: document.getElementById('firstName').value.trim(),
            lastName: document.getElementById('lastName').value.trim(),
            selectedDate: document.getElementById('selectedDate').textContent.trim(), // Get from span text
            reason: document.getElementById('reason').value.trim(),
            email: document.getElementById('email').value.trim(),
            mobile: document.getElementById('mobile').value.trim(),
            jobTitle: document.getElementById('jobTitle').value.trim(),
            company: document.getElementById('company').value.trim(),
            city: document.getElementById('city').value.trim(),
            country: document.getElementById('country').value.trim()
        };

    // No longer attach modal data; forms are saved directly when modals submit.

        try {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Processing...';
            feedbackMessage.className = 'alert alert-info mt-3';
            feedbackMessage.textContent = 'Processing your registration...';

            const responseData = await handleRegistration(payload);
            const registrantId = responseData.id;
            
            // Generate QR Code URL
            // Build details URL relative to current directory (works when app is in a subfolder)
            const detailsUrl = new URL(`registrant.php?id=${registrantId}`, window.location.href).toString();
            const qrCodeUrl = `https://api.qrcode-monkey.com/qr/custom?size=300&data=${encodeURIComponent(detailsUrl)}`;
            
            // Create download link for QR code
            const globalDownloadLink = document.createElement('a');
            globalDownloadLink.href = qrCodeUrl;
            globalDownloadLink.download = `registration-${registrantId}.png`;
            globalDownloadLink.style.display = 'none';
            document.body.appendChild(globalDownloadLink);
            
            // Display QR code and success message
            qrcodeDiv.innerHTML = `
                <div class="text-center">
                    <img src="${qrCodeUrl}" alt="QR Code" class="img-fluid mb-3">
                    <p class="text-success mb-3"><i class="fas fa-check-circle me-2"></i>Registration successful!</p>
                    <p class="small text-muted mb-3">Your registration ID: ${registrantId}</p>
                    <div class="d-flex gap-2 mt-3">
                        <button onclick="window.print()" class="btn btn-primary flex-grow-1">
                            <i class="fas fa-print me-2"></i>Print
                        </button>
                        <button onclick="triggerDownload()" class="btn btn-success flex-grow-1">
                            <i class="fas fa-download me-2"></i>Download QR
                        </button>
                    </div>
                    <div class="mt-3">
                        <a href="${detailsUrl}" class="btn btn-outline-primary flex-grow-1">
                            <i class="fas fa-id-card me-2"></i>View Registration Details
                        </a>
                    </div>
                </div>
            `;
            
            window.triggerDownload = function() {
                globalDownloadLink.click();
            };

            qrCodeContainer.classList.remove('d-none');
            form.classList.add('d-none');
            feedbackMessage.className = 'alert alert-success mt-3 d-none';
            qrCodeContainer.scrollIntoView({ behavior: 'smooth' });

            // Clear staged data and reset completion flags after successful registration
            if (window.clearModalFormData) window.clearModalFormData();
            if (window.modalCompletion) {
                window.modalCompletion.training = false;
                window.modalCompletion.employment = false;
            }

        } catch (error) {
            console.error('Registration error:', error);
            feedbackMessage.className = 'alert alert-danger mt-3';
            feedbackMessage.innerHTML = `
                <strong>Registration failed:</strong> ${error.message}<br>
                Please try again or contact support.
            `;
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Register';
        }
    });
});