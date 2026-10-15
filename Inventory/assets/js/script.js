// MangaRingMed Pharmacy - Inventory System JavaScript

// Initialize tooltips and popovers on page load
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Show alerts and auto-hide
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.3s ease';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
});

// Toggle sidebar on mobile
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const mainWrapper = document.querySelector('.main-wrapper');

    if (window.innerWidth <= 768) {
        sidebar.classList.toggle('show');
        return;
    }

    if (sidebar) {
        sidebar.classList.toggle('collapsed');
    }

    if (mainWrapper) {
        mainWrapper.classList.toggle('sidebar-collapsed');
    }
}

// Format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP'
    }).format(amount);
}

// Format date
function formatDate(dateString) {
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('en-US', options);
}

// Confirm before delete
function confirmDelete(itemName) {
    return confirm(`Are you sure you want to delete ${itemName}?`);
}

// Show loading spinner
function showLoading() {
    document.body.style.opacity = '0.6';
    const spinner = document.createElement('div');
    spinner.className = 'spinner';
    spinner.id = 'pageSpinner';
    document.body.appendChild(spinner);
}

// Hide loading spinner
function hideLoading() {
    document.body.style.opacity = '1';
    const spinner = document.getElementById('pageSpinner');
    if (spinner) spinner.remove();
}

// Export table to CSV
function exportTableToCSV(filename) {
    const csv = [];
    const rows = document.querySelectorAll("table tr");
    
    for (let i = 0; i < rows.length; i++) {
        const row = [], cols = rows[i].querySelectorAll("td, th");
        
        for (let j = 0; j < cols.length; j++) {
            let text = cols[j].innerText;
            text = text.replace(/"/g, '""');
            row.push(`"${text}"`);
        }
        
        csv.push(row.join(","));
    }
    
    downloadCSV(csv.join("\n"), filename);
}

// Download CSV file
function downloadCSV(csv, filename) {
    const csvFile = new Blob([csv], {type: "text/csv"});
    const downloadLink = document.createElement("a");
    downloadLink.href = URL.createObjectURL(csvFile);
    downloadLink.download = filename + "_" + new Date().toISOString().slice(0, 10) + ".csv";
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}

// Print page
function printPage() {
    window.print();
}

// Validate email
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Validate phone number
function validatePhone(phone) {
    const re = /^[0-9]{10,}$/;
    return re.test(phone.replace(/[^0-9]/g, ''));
}

// Set active navigation item
function setActiveNav(pageKey) {
    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach(item => item.classList.remove('active'));
    
    const activeItem = document.querySelector(`.nav-item[data-page="${pageKey}"]`);
    if (activeItem) activeItem.classList.add('active');
}

// Handle form submission with validation
function handleFormSubmit(formId, requiredFields) {
    const form = document.getElementById(formId);
    
    form.addEventListener('submit', function(e) {
        let isValid = true;
        
        requiredFields.forEach(fieldName => {
            const field = form.querySelector(`[name="${fieldName}"]`);
            if (field && !field.value.trim()) {
                field.classList.add('is-invalid');
                isValid = false;
            } else if (field) {
                field.classList.remove('is-invalid');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('Please fill in all required fields');
        }
    });
}

// Toggle Notification Panel
function toggleNotificationPanel(event) {
    event.stopPropagation();
    const notificationPanel = document.getElementById('notificationPanel');
    if (notificationPanel) {
        notificationPanel.classList.toggle('show');
    }
}

function openNotificationModal() {
    closeNotificationPanel();
    const notificationModal = document.getElementById('notificationModal');
    if (notificationModal) {
        notificationModal.classList.add('show');
    }
}

// Close Notification Panel
function closeNotificationPanel() {
    const notificationPanel = document.getElementById('notificationPanel');
    if (notificationPanel) {
        notificationPanel.classList.remove('show');
    }
}

function closeNotificationModal() {
    const notificationModal = document.getElementById('notificationModal');
    if (notificationModal) {
        notificationModal.classList.remove('show');
    }
}

// Close notification panel when clicking outside
document.addEventListener('click', function(event) {
    const notificationPanel = document.getElementById('notificationPanel');
    const notificationWrapper = document.querySelector('.notification-wrapper');
    const notificationModal = document.getElementById('notificationModal');
    const clickedModalContent = event.target.closest('.notification-modal-content');

    if (notificationPanel && notificationWrapper && !notificationWrapper.contains(event.target)) {
        notificationPanel.classList.remove('show');
    }

    if (notificationModal && !clickedModalContent && !event.target.closest('.notification-view-all')) {
        notificationModal.classList.remove('show');
    }
});

window.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeNotificationPanel();
        closeNotificationModal();
    }
});

