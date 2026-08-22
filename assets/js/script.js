// =============================================
// SkillSwap Campus — Modern UI/UX Interactions
// =============================================

document.addEventListener('DOMContentLoaded', function () {
    // 1. Auto-dismiss alerts after 4.5 seconds with fade effect
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            try {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                bsAlert.close();
            } catch (e) {
                alert.remove();
            }
        }, 4500);
    });

    // 2. Initialize Bootstrap Tooltips if available
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // 3. Star Rating Hover and Selection Feedback
    const starInputs = document.querySelectorAll('input[name="rating"]');
    if (starInputs.length > 0) {
        starInputs.forEach(function (input) {
            input.addEventListener('change', function () {
                const val = this.value;
                const label = this.nextElementSibling;
                if (label) {
                    label.classList.add('fw-bold');
                }
            });
        });
    }
});

// Helper for quick image preview before profile upload
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (input.files && input.files[0] && preview) {
        const reader = new FileReader();
        reader.onload = function (e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
