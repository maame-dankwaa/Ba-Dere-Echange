/**
 * SweetAlert2 Helper Functions
 * Provides consistent alert styling across the platform
 */

// Show flash messages from PHP session
function showFlashMessages() {
    // Success messages
    const successMessages = document.querySelectorAll('.alert-success');
    successMessages.forEach(alert => {
        const message = alert.textContent.trim();
        if (message) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: message,
                confirmButtonColor: '#10b981',
                timer: 3000,
                timerProgressBar: true
            });
        }
        alert.style.display = 'none';
    });

    // Error messages
    const errorMessages = document.querySelectorAll('.alert-error, .alert-danger');
    errorMessages.forEach(alert => {
        const message = alert.textContent.trim();
        if (message) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: message,
                confirmButtonColor: '#ef4444'
            });
        }
        alert.style.display = 'none';
    });

    // Warning messages
    const warningMessages = document.querySelectorAll('.alert-warning');
    warningMessages.forEach(alert => {
        const message = alert.textContent.trim();
        if (message) {
            Swal.fire({
                icon: 'warning',
                title: 'Warning',
                text: message,
                confirmButtonColor: '#f59e0b'
            });
        }
        alert.style.display = 'none';
    });

    // Info messages
    const infoMessages = document.querySelectorAll('.alert-info');
    infoMessages.forEach(alert => {
        const message = alert.textContent.trim();
        if (message) {
            Swal.fire({
                icon: 'info',
                title: 'Information',
                text: message,
                confirmButtonColor: '#3b82f6'
            });
        }
        alert.style.display = 'none';
    });
}

// Confirm deletion with SweetAlert
function confirmDelete(message = 'This action cannot be undone!') {
    return Swal.fire({
        title: 'Are you sure?',
        text: message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    });
}

// Confirm action with SweetAlert
function confirmAction(title, message, confirmText = 'Yes, proceed') {
    return Swal.fire({
        title: title,
        text: message,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#6b7280',
        confirmButtonText: confirmText,
        cancelButtonText: 'Cancel'
    });
}

// Show success toast
function showSuccessToast(message) {
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });

    Toast.fire({
        icon: 'success',
        title: message
    });
}

// Show error toast
function showErrorToast(message) {
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
    });

    Toast.fire({
        icon: 'error',
        title: message
    });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    showFlashMessages();
});
