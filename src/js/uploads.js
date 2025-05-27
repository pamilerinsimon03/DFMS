// JavaScript for uploads page functionality

document.addEventListener('DOMContentLoaded', function() {
    // Tab switching functionality
    const tabItems = document.querySelectorAll('.tab-item');
    
    tabItems.forEach(tab => {
        tab.addEventListener('click', function() {
            // Remove active class from all tabs
            tabItems.forEach(item => item.classList.remove('active'));
            
            // Add active class to clicked tab
            this.classList.add('active');
            
            // Here you would typically filter the table based on the selected tab
            // For now, we'll just show a notification
            const tabName = this.textContent.trim();
            console.log(`Switched to ${tabName} tab`);
            
            // Filter table rows based on selected tab
            filterTableByTab(tabName);
        });
    });
    
    // Date filter functionality
    const dateInputs = document.querySelectorAll('.date-input');
    
    dateInputs.forEach(input => {
        input.addEventListener('click', function() {
            // In a real implementation, this would open a date picker
            console.log('Date picker would open here');
        });
    });
    
    // Action button functionality
    const actionButtons = document.querySelectorAll('.td-action button');
    
    actionButtons.forEach(button => {
        button.addEventListener('click', function() {
            const action = this.textContent.trim();
            const row = this.closest('.table-row');
            const documentName = row.querySelector('.td-document').textContent;
            
            if (action === 'Reupload Document') {
                // In a real implementation, this would open a file upload dialog
                console.log(`Reupload requested for ${documentName}`);
                alert(`Reupload requested for ${documentName}`);
            } else {
                console.log(`${action} clicked for ${documentName}`);
                alert(`${action} clicked for ${documentName}`);
            }
        });
    });
    
    // Function to filter table rows based on selected tab
    function filterTableByTab(tabName) {
        const tableRows = document.querySelectorAll('.table-row');
        
        tableRows.forEach(row => {
            const status = row.querySelector('.td-status').classList;
            
            if (tabName === 'Total Uploads') {
                row.style.display = '';
            } else if (tabName === 'Pending Verification' && status.contains('pending')) {
                row.style.display = '';
            } else if (tabName === 'Verified' && status.contains('verified')) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
    
    // Add calendar icon images
    const calendarIcons = document.querySelectorAll('.date-input img');
    calendarIcons.forEach(icon => {
        if (!icon.src.includes('calendar.svg')) {
            // If we're missing the actual calendar.svg file, we could create a fallback
            // But for now, we'll just log a message
            console.log('Calendar icon image is missing');
        }
    });
    
    // Initialize with the first tab (Total Uploads) selected
    const firstTab = document.querySelector('.tab-item.active');
    if (firstTab) {
        filterTableByTab(firstTab.textContent.trim());
    }
});
