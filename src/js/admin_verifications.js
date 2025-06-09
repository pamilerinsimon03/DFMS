/* stylelint-disable */
// Admin Verifications page JS

document.addEventListener('DOMContentLoaded', function() {
  const tabItems = document.querySelectorAll('.tab-item');
  const tableRows = document.querySelectorAll('.docs-table tbody tr');

  tabItems.forEach(tab => {
    tab.addEventListener('click', function() {
      tabItems.forEach(item => item.classList.remove('active'));
      this.classList.add('active');
      const statusMap = {
        'Documents Uploaded': () => true,
        'Documents to be Verified': () => row => row.classList.contains('status-pending'),
        'Documents Verified': () => row => row.classList.contains('status-verified'),
        'Rejected Documents': () => row => row.classList.contains('status-rejected')
      };
      const tabName = this.textContent.trim();
      tableRows.forEach(row => {
        let predicate;
        if (tabName === 'Documents Uploaded') predicate = () => true;
        else if (tabName === 'Documents to be Verified') predicate = row => row.classList.contains('status-pending');
        else if (tabName === 'Documents Verified') predicate = row => row.classList.contains('status-verified');
        else if (tabName === 'Rejected Documents') predicate = row => row.classList.contains('status-rejected');
        row.style.display = predicate(row) ? '' : 'none';
      });
    });
  });

  // Initialize on first tab
  const firstTab = document.querySelector('.tab-item.active');
  if (firstTab) firstTab.click();
});
