<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>

<?php require_once 'includes/config.php'; ?>




<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Keep your existing head content -->
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Registration - Megacare Pharmacy</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link href="assets/css/styles.css" rel="stylesheet">
        <style>
            @media print {
                body * { visibility: hidden; }
                #qrCodeContainer, #qrCodeContainer * { visibility: visible; }
                #qrCodeContainer { 
                    position: absolute;
                    left: 0;
                    top: 0;
                    width: 100%;
                }
            }

            .is-invalid {
                border-color: #dc3545 !important;
            }
            
            .invalid-feedback {
                color: #dc3545;
                font-size: 0.875em;
                display: none;
            }
            
            .is-invalid ~ .invalid-feedback {
                display: block;
            }
        </style>
    </head>
</head>
<body>
    <!-- Keep your existing header and form HTML -->
    <header class="bg-light py-3 border-bottom">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <a href="https://megacarepharmacyng.com/" target="_blank">
                        <img src="assets/img/logo.png" alt="Logo" class="logo me-3" width="100px" height="50">
                    </a>
                    
                    <h1 class="h4 mb-0 text-center">REGISTRATION</h1>
                </div>
                <div class="d-none d-md-block">
                    <span class="text-muted">
                    </i><a href="https://wa.me/2348166162631?text=Hello%2C%20I%20have%20an%20inquiry" target="_blank"><i class="fab fa-whatsapp me-2 whatsapp-icon"></i></a></span>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
    <form id="registrationForm" class="mb-5 mt-3" autocomplete="off">
            <div class="row g-3">
    <div class="col-md-6">
        <label for="firstName" class="form-label">First Name<span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="firstName" required>
        <div class="invalid-feedback">This field is required</div>
    </div>
    <div class="col-md-6">
        <label for="lastName" class="form-label">Last Name<span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="lastName" required>
        <div class="invalid-feedback">This field is required</div>
    </div>

   

    <div class="col-md-6">
        <label for="month" class="form-label">Month of birth<span class="text-danger">*</span></label>
        <select class="form-control" id="month" required>
            <option value="">--Month--</option>
            <option value="January">January</option>
            <option value="February">February</option>
            <option value="March">March</option>
            <option value="April">April</option>
            <option value="May">May</option>
            <option value="June">June</option>
            <option value="July">July</option>
            <option value="August">August</option>
            <option value="September">September</option>
            <option value="October">October</option>
            <option value="November">November</option>
            <option value="December">December</option>
        </select>
        <div class="invalid-feedback">This field is required</div>
    </div>
    <div class="col-md-6">
        <label for="day" class="form-label">Day of birth<span class="text-danger">*</span></label>
        <select class="form-control" id="day" required>
            <option value="">--Day--</option>
        </select>
        <p>Selected Date: <span id="selectedDate">None</span></p>
        <div class="invalid-feedback">This field is required</div>
    </div>

    <div class="col-md-6">
        <label for="reason" class="form-label">Reason<span class="text-danger">*</span></label>
        <select class="form-control" id="reason" required>
            <option>--Select reason--</option>
            <option value="Vaccination">Vaccination</option>
            <option value="Consultation">Consultation</option>
            <option value="Customer loyalty">Customer loyalty</option>
            <option value="Partnership">Partnership</option>
            <option value="Employment">Employment</option>
            <option value="Training">Training</option>
            <option value="Purchase">Purchase</option>
            <option value="Scholarship">Scholarship</option>
            <option value="Medication Review">Medication Review</option>
            <option value="Others">Others</option>
        </select>
        <div class="invalid-feedback">This field is required</div>
    </div>
    <div class="col-md-6">
        <label for="email" class="form-label">Email<span class="text-danger">*</span></label>
        <input type="email" class="form-control" id="email" required>
        <div class="invalid-feedback">This field is required</div>
    </div>

    <div class="col-md-6">
        <label for="mobile" class="form-label">WhatsApp Number<span class="text-danger">*</span></label>
        <input type="tel" class="form-control" id="mobile" required>
        <div class="invalid-feedback">This field is required</div>
    </div>
    <div class="col-md-6">
        <label for="jobTitle" class="form-label">Job Title<span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="jobTitle" required>
        <div class="invalid-feedback">This field is required</div>
    </div>

    <div class="col-md-6">
        <label for="company" class="form-label">Company/Organization<span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="company" required>
        <div class="invalid-feedback">This field is required</div>
    </div>
    <div class="col-md-6">
        <label for="city" class="form-label">City<span class="text-danger">*</span></label>
        <select class="form-control" id="city" required>
            <option value="" disabled selected>--Select City--</option>
            <option value="Aba">Aba</option>
            <option value="Abuja">Abuja</option>
            <option value="Ado-Ekiti">Ado-Ekiti</option>
            <option value="Akure">Akure</option>
            <option value="Benin City">Benin City</option>
            <option value="Calabar">Calabar</option>
            <option value="Enugu">Enugu</option>
            <option value="Ibadan">Ibadan</option>
            <option value="Ilorin">Ilorin</option>
            <option value="Jos">Jos</option>
            <option value="Kaduna">Kaduna</option>
            <option value="Kano">Kano</option>
            <option value="Lagos">Lagos</option>
            <option value="Maiduguri">Maiduguri</option>
            <option value="Minna">Minna</option>
            <option value="Onitsha">Onitsha</option>
            <option value="Owerri">Owerri</option>
            <option value="Osogbo">Osogbo</option>
            <option value="Port Harcourt">Port Harcourt</option>
            <option value="Uyo">Uyo</option>
            <option value="Warri">Warri</option>
        </select>
        <div class="invalid-feedback">This field is required</div>
    </div>

    <div class="col-md-6">
        <label for="country" class="form-label">Country<span class="text-danger">*</span></label>
        <select class="form-control" id="country" required>
            <option value="">--Select a country--</option>
            <option value="Nigeria">Nigeria</option>
            <option value="Argentina">Argentina</option>
            <option value="Australia">Australia</option>
            <option value="Austria">Austria</option>
            <option value="Bangladesh">Bangladesh</option>
            <option value="Belgium">Belgium</option>
            <option value="Brazil">Brazil</option>
            <option value="Canada">Canada</option>
            <option value="China">China</option>
            <option value="France">France</option>
            <option value="Germany">Germany</option>
            <option value="India">India</option>
            <option value="Italy">Italy</option>
            <option value="Japan">Japan</option>
            <option value="Kenya">Kenya</option>
            <option value="United Kingdom">United Kingdom</option>
            <option value="United States">United States</option>
        </select>
        <div class="invalid-feedback">This field is required</div>
    </div>
</div>

            <button type="submit" class="btn btn-primary mt-4">Register</button>
        </form>

        <div id="qrCodeContainer" class="mt-4 text-center d-none">
            <h2>Your Registration QR Code</h2>
            <div id="qrcode" class="mb-3"></div><br>
            <!--<p class="text-muted">Scan this code to verify your registration</p>-->
            <button class="btn btn-info mt-3" >
               <a href="index.php" style="text-decoration: none;" class="text-white">Return</a>
            </button>
            
        </div>
    </div>

    <!-- Training Documentation Modal -->
                <div class="modal fade" id="trainingModal" tabindex="-1" aria-labelledby="trainingModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="trainingModalLabel">MEGACARE PHARMACY TRAINING DOCUMENTATION FORM</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="trainingForm">
                                                            <!-- Part 1 -->
                                                            <div id="trainingPart1">
                                                                <h6>Section A: Candidate Information</h6>
                                                                <div class="mb-3"><label>Full Name:</label><input type="text" class="form-control" name="fullName" required></div>
                                                                <div class="mb-3"><label>Date of Birth:</label><input type="date" class="form-control" name="dob" required></div>
                                                                <div class="mb-3"><label>Age:</label><input type="number" class="form-control" name="age" required></div>
                                                                <div class="mb-3"><label>Gender:</label><select class="form-control" name="gender" required><option value="">--Select--</option><option>Male</option><option>Female</option><option>Other</option></select></div>
                                                                <div class="mb-3"><label>Contact Address:</label><input type="text" class="form-control" name="address" required></div>
                                                                <div class="mb-3"><label>Phone Number:</label><input type="tel" class="form-control" name="phone" required></div>
                                                                <div class="mb-3"><label>Email:</label><input type="email" class="form-control" name="email" required></div>
                                                                <div class="mb-3"><label>Next of Kin (Name & Phone):</label><input type="text" class="form-control" name="nextOfKin" required></div>
                                                                <hr>
                                                                <h6>Section B: Educational Background</h6>
                                                                <div class="mb-3"><label>Highest Qualification:</label><input type="text" class="form-control" name="qualification" required></div>
                                                                <div class="mb-3"><label>Institution Attended:</label><input type="text" class="form-control" name="institution" required></div>
                                                                <div class="mb-3"><label>Year of Graduation:</label><input type="text" class="form-control" name="graduationYear" required></div>
                                                                <div class="mb-3"><label>Relevant Certifications (if any):</label><input type="text" class="form-control" name="certifications"></div>
                                                                <div class="d-flex justify-content-end">
                                                                    <button type="button" class="btn btn-primary" id="nextTrainingBtn">Next</button>
                                                                </div>
                                                            </div>
                                                            <!-- Part 2 -->
                                                            <div id="trainingPart2" style="display:none;">
                                                                <h6>Section C: Training Details</h6>
                                                                <div class="mb-3"><label>Training Start Date:</label><input type="date" class="form-control" name="startDate" required></div>
                                                                <div class="mb-3"><label>Expected Completion Date:</label><input type="date" class="form-control" name="completionDate" required></div>
                                                                <div class="mb-3"><label>Area(s) of Training:</label><br>
                                                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="areas[]" value="Use of Medications"><label class="form-check-label">Use of Medications</label></div>
                                                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="areas[]" value="Dispensing Practices"><label class="form-check-label">Dispensing Practices</label></div>
                                                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="areas[]" value="Patient Counseling"><label class="form-check-label">Patient Counseling</label></div>
                                                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="areas[]" value="Pharmacy Record Keeping"><label class="form-check-label">Pharmacy Record Keeping</label></div>
                                                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="areas[]" value="Inventory Management"><label class="form-check-label">Inventory Management</label></div>
                                                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="areas[]" value="Regulatory & Ethical Standards"><label class="form-check-label">Regulatory & Ethical Standards</label></div>
                                                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="areas[]" value="Other"><label class="form-check-label">Other (Specify):</label><input type="text" class="form-control mt-1" name="otherArea"></div>
                                                                </div>
                                                                <hr>
                                                                <h6>Section D: Candidate’s Commitment</h6>
                                                                <div class="mb-3">
                                                                    <p>I, <input type="text" class="form-control d-inline w-auto" name="candidateName" required>, agree to undergo training at MegaCare Pharmacy on the proper use of medications and the professional running of a pharmacy. I understand that this training is for educational and practical exposure purposes.</p>
                                                                </div>
                                                                <div class="mb-3"><label>Signature of Candidate:</label><input type="text" class="form-control" name="candidateSignature"></div>
                                                                <div class="mb-3"><label>Date:</label><input type="date" class="form-control" name="candidateDate"></div>
                                                                <hr>
                                                                <h6>Section E: For Official Use Only</h6>
                                                                <div class="mb-3"><label>Trainer’s Name:</label><input type="text" class="form-control" name="trainerName"></div>
                                                                <div class="mb-3"><label>Position:</label><input type="text" class="form-control" name="trainerPosition"></div>
                                                                <div class="mb-3"><label>Remarks/Notes:</label><textarea class="form-control" name="remarks"></textarea></div>
                                                                <div class="mb-3"><label>Signature of Trainer:</label><input type="text" class="form-control" name="trainerSignature"></div>
                                                                <div class="mb-3"><label>Date:</label><input type="date" class="form-control" name="trainerDate"></div>
                                                                <div class="d-flex justify-content-between">
                                                                    <button type="button" class="btn btn-secondary" id="backTrainingBtn">Back</button>
                                                                    <button type="submit" class="btn btn-success">Submit</button>
                                                                </div>
                                                            </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

        <!-- Code Verification Modal -->
        <div class="modal fade" id="codeVerificationModal" tabindex="-1" aria-labelledby="codeVerificationLabel" aria-hidden="true">
            <div class="modal-dialog modal-sm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="codeVerificationLabel">Enter Access Code</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="accessCode" class="form-label">6-Digit Access Code</label>
                            <input type="text" class="form-control text-center" id="accessCode" maxlength="6" placeholder="000000">
                            <div id="codeError" class="text-danger mt-2" style="display: none;"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="verifyCodeBtn">Verify</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Employment Application Modal -->
        <div class="modal fade" id="employmentModal" tabindex="-1" aria-labelledby="employmentModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="employmentModalLabel">MEGACARE PHARMACY EMPLOYMENT APPLICATION</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="employmentForm">
                            <h6>Personal Information</h6>
                            <div class="mb-3"><label>Full Name:</label><input type="text" class="form-control" name="emp_full_name" required></div>
                            <div class="mb-3"><label>Date of Birth:</label><input type="date" class="form-control" name="emp_dob" required></div>
                            <div class="mb-3"><label>Age:</label><input type="number" class="form-control" name="emp_age" required></div>
                            <div class="mb-3"><label>Gender:</label><select class="form-control" name="emp_gender" required><option value="">--Select--</option><option>Male</option><option>Female</option><option>Other</option></select></div>
                            <div class="mb-3"><label>Contact Address:</label><textarea class="form-control" name="emp_address" required></textarea></div>
                            <div class="mb-3"><label>Phone Number:</label><input type="tel" class="form-control" name="emp_phone" required></div>
                            <div class="mb-3"><label>Email:</label><input type="email" class="form-control" name="emp_email" required></div>
                            
                            <hr>
                            <h6>Employment Details</h6>
                            <div class="mb-3"><label>Position Applied For:</label><input type="text" class="form-control" name="position_applied" required></div>
                            <div class="mb-3"><label>Years of Experience:</label><input type="number" class="form-control" name="experience_years" required></div>
                            <div class="mb-3"><label>Previous Employer:</label><input type="text" class="form-control" name="previous_employer"></div>
                            <div class="mb-3"><label>Qualifications:</label><textarea class="form-control" name="qualifications" required></textarea></div>
                            <div class="mb-3"><label>Skills:</label><textarea class="form-control" name="skills" required></textarea></div>
                            <div class="mb-3"><label>Availability Date:</label><input type="date" class="form-control" name="availability_date" required></div>
                            <div class="mb-3"><label>Expected Salary:</label><input type="text" class="form-control" name="expected_salary"></div>
                            <div class="mb-3"><label>References (Name & Contact):</label><textarea class="form-control" name="references"></textarea></div>
                            
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-success">Submit Application</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    <footer class="bg-dark text-white py-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5 class="mb-3">Contact Us</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="fas fa-map-marker-alt me-2"></i> Health Land Building</li>
                        <li class="mb-2"><i class="fas fa-city me-2"></i> Lakeview Park 1 Estate, Lekki</li>
                        <li class="mb-2"><i class="fas fa-phone-alt me-2"></i><a href="tel:+2348166162631" style="text-decoration: none;" class="text-white">+234 816 616 2631</a> </li>
                        <li><a href="mailto:info@megacarepharmacyng.com" target="_blank" class="text-white" style="text-decoration: none;"><i class="fas fa-envelope me-2"></i> info@megacarepharmacyng.com</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-4 mb-md-0">
                    
                </div>
                <div class="col-md-4">
                    <h5 class="mb-3">Connect With Us</h5>
                    <div class="social-links">
                        
                        <a href="https://www.facebook.com/share/162NzuD6n6/" class="text-white me-3"><i class="fab fa-facebook-f fa-lg"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-linkedin-in fa-lg"></i></a>
                        <a href="https://www.instagram.com/megacare.pharmacy?igsh=MWs1aWF5ZTlwY2EyNg==" class="text-white me-3"><i class="fab fa-instagram fa-lg"></i></a>
                        <a href="https://x.com/Megacare_pharm?t=q-KKMw8ap2DdtHGyHgqaBQ&s=09" class="text-white">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" class="x-logo">
                                <path d="M18.901 1.153h3.68l-8.04 9.19L24 22.846h-7.406l-5.8-7.584-6.638 7.584H.474l8.6-9.83L0 1.154h7.594l5.243 6.932ZM17.61 20.644h2.039L6.486 3.24H4.298Z"/>
                            </svg>
                        </a>
                        <a href="dashboard.php" class="text-white ms-3" aria-label="Official Login">
                            <span class="d-inline-flex align-items-center justify-content-center rounded-circle"
                                  style="width:20px;height:20px;border:1px solid currentColor;font-weight:700;font-size:12px;line-height:1;">M</span>
                        </a>
                    </div>
                    <div class="mt-4">
                        <p class="small mb-0">&copy; 2025 Megacare Pharmacy. All rights reserved.</p>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/modal.js?v=20250915"></script>
    <script src="assets/js/script.js?v=20250915"></script>

    <script>
    
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
        
        
        
        

    </script>
   
   <!-- <script>
    // --- Modal Data Integration ---
    let modalFormData = {};
    let selectedReason = '';

    document.addEventListener("DOMContentLoaded", function () {
        // Month/Day logic
        const monthSelect = document.getElementById("month");
        const daySelect = document.getElementById("day");
        const selectedDate = document.getElementById("selectedDate");
        function populateDays() {
            const month = monthSelect.value;
            daySelect.innerHTML = '<option value="">Day</option>';
            if (!month) return;
            let daysInMonth;
            if (["April", "June", "September", "November"].includes(month)) {
                daysInMonth = 30;
            } else if (month === "February") {
                daysInMonth = 28;
            } else {
                daysInMonth = 31;
            }
            for (let i = 1; i <= daysInMonth; i++) {
                let option = document.createElement("option");
                option.value = i < 10 ? "0" + i : i;
                option.textContent = i;
                daySelect.appendChild(option);
            }
        }
        monthSelect.addEventListener("change", populateDays);
        daySelect.addEventListener("change", function () {
            if (monthSelect.value && daySelect.value) {
                selectedDate.textContent = `${monthSelect.value}-${daySelect.value}`;
            }
        });

        // Show code verification modal when 'Training' or 'Employment' is selected
        const reasonSelect = document.getElementById("reason");
        reasonSelect.addEventListener("change", function () {
            if (reasonSelect.value === "Training" || reasonSelect.value === "Employment") {
                selectedReason = reasonSelect.value;
                const codeModal = new bootstrap.Modal(document.getElementById('codeVerificationModal'));
                codeModal.show();
            }
        });

        // Code verification
        document.getElementById('verifyCodeBtn').addEventListener('click', function() {
            const code = document.getElementById('accessCode').value;
            const errorDiv = document.getElementById('codeError');
            if (!code || code.length !== 6) {
                errorDiv.textContent = 'Please enter a 6-digit code';
                errorDiv.style.display = 'block';
                return;
            }
            fetch('api/verify_code.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ code: code, purpose: selectedReason.toLowerCase() })
            })
            .then(response => response.json())
            .then(data => {
                if (data.valid) {
                    bootstrap.Modal.getInstance(document.getElementById('codeVerificationModal')).hide();
                    if (selectedReason === 'Training') {
                        const trainingModal = new bootstrap.Modal(document.getElementById('trainingModal'));
                        trainingModal.show();
                    } else if (selectedReason === 'Employment') {
                        const employmentModal = new bootstrap.Modal(document.getElementById('employmentModal'));
                        employmentModal.show();
                    }
                    document.getElementById('accessCode').value = '';
                    errorDiv.style.display = 'none';
                } else {
                    errorDiv.textContent = 'Invalid or expired code';
                    errorDiv.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                errorDiv.textContent = 'Error verifying code';
                errorDiv.style.display = 'block';
            });
        });

        // Do NOT reset reason dropdown after modal submit
        document.getElementById('codeVerificationModal').addEventListener('hidden.bs.modal', function () {
            // No reset here
        });

        // Training Modal Navigation
        const nextBtn = document.getElementById('nextTrainingBtn');
        const backBtn = document.getElementById('backTrainingBtn');
        const part1 = document.getElementById('trainingPart1');
        const part2 = document.getElementById('trainingPart2');
        if (nextBtn && backBtn && part1 && part2) {
            nextBtn.addEventListener('click', function() {
                part1.style.display = 'none';
                part2.style.display = 'block';
            });
            backBtn.addEventListener('click', function() {
                part2.style.display = 'none';
                part1.style.display = 'block';
            });
        }

        // --- Modal Form Data Collection ---
        // Training Modal
        document.getElementById('submitTrainingModal').addEventListener('click', function() {
            const trainingForm = document.getElementById('trainingForm');
            let valid = true;
            // Validate required fields
            Array.from(trainingForm.elements).forEach(el => {
                if (el.hasAttribute('required') && el.offsetParent !== null) {
                    if ((el.type === 'checkbox' || el.type === 'radio')) {
                        // For checkboxes/radios, check if any is checked
                        const group = trainingForm.querySelectorAll(`[name='${el.name}']`);
                        if (!Array.from(group).some(e => e.checked)) {
                            valid = false;
                            el.classList.add('is-invalid');
                        } else {
                            el.classList.remove('is-invalid');
                        }
                    } else if (!el.value) {
                        valid = false;
                        el.classList.add('is-invalid');
                    } else {
                        el.classList.remove('is-invalid');
                    }
                }
            });
            if (!valid) return;
            const formData = new FormData(trainingForm);
            modalFormData.training = {};
            for (let [key, value] of formData.entries()) {
                if (key === 'areas[]') {
                    if (!modalFormData.training.areas) modalFormData.training.areas = [];
                    modalFormData.training.areas.push(value);
                } else {
                    modalFormData.training[key] = value;
                }
            }
            setTimeout(function() {
                bootstrap.Modal.getInstance(document.getElementById('trainingModal')).hide();
            }, 100);
        });

        // Employment Modal
        document.getElementById('submitEmploymentModal').addEventListener('click', function() {
            const employmentForm = document.getElementById('employmentForm');
            let valid = true;
            Array.from(employmentForm.elements).forEach(el => {
                if (el.hasAttribute('required') && el.offsetParent !== null) {
                    if ((el.type === 'checkbox' || el.type === 'radio')) {
                        const group = employmentForm.querySelectorAll(`[name='${el.name}']`);
                        if (!Array.from(group).some(e => e.checked)) {
                            valid = false;
                            el.classList.add('is-invalid');
                        } else {
                            el.classList.remove('is-invalid');
                        }
                    } else if (!el.value) {
                        valid = false;
                        el.classList.add('is-invalid');
                    } else {
                        el.classList.remove('is-invalid');
                    }
                }
            });
            if (!valid) return;
            const formData = new FormData(employmentForm);
            modalFormData.employment = {};
            for (let [key, value] of formData.entries()) {
                modalFormData.employment[key] = value;
            }
            setTimeout(function() {
                bootstrap.Modal.getInstance(document.getElementById('employmentModal')).hide();
            }, 100);
        });

    // --- Main Registration AJAX ---
    // (Removed here so assets/js/script.js can handle registration form submission)
    });
    </script> -->
   
</body>
</html>



