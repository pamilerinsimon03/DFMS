/* stylelint-disable */
// Bar chart: “Uploads This Session”
const barCanvas = document.getElementById('barChart');
if (barCanvas) {
  const barCtx = barCanvas.getContext('2d');
  new Chart(barCtx, {
    type: 'bar',
    data: {
      labels: ['Apr 10','11','15','16','20','22','23','24','25','26','28','29','30','Jun 3','4'],
      datasets: [{
      label: 'Uploads',
      data: [350,700,550,700,150,350,700,550,700,30,350,700,550,700,500],
      backgroundColor: '#01017A',
      borderRadius: 4,
      barThickness: 10
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      layout: { padding: 0 },
      scales: {
      y: {
        beginAtZero: true,
        ticks: { stepSize: 200, color: '#999999', font: { family: 'Poppins', size: 14 } },
        grid: { drawBorder: false, color: 'rgba(128,128,128,0.3)', borderDash: [5,5], lineWidth: 1 }
      },
      x: {
        ticks: { maxRotation: 0, autoSkip: true, color: '#999999', font: { family: 'Poppins', size: 12 } },
        grid: { display: false }
      }
      },
      plugins: { legend:{ display:false } }
    }
  });
}

// Pie chart: “Verified / Pending / Rejected”
const pieCanvas = document.getElementById('pieChart');
if (pieCanvas) {
  const pieCtx = pieCanvas.getContext('2d');
  new Chart(pieCtx, {
  type: 'pie',
  data: {
    labels: ['Verified','Pending','Rejected'],
    datasets: [{
    data: [4900,50,100],
    backgroundColor: ['#009A49','#FAA62C','#FF3B30']
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    layout: { padding: 0 },
    plugins: {
    legend: {
      position: 'bottom',
      labels: { usePointStyle: true, pointStyle: 'circle', boxWidth: 10, padding: 20, color:'#999999', font:{ family:'Poppins', size:12 } }
    },
    tooltip: { callbacks:{ label: ctx => `${ctx.label}: ${ctx.parsed}` } }
    }
  }
});
}

document.addEventListener('DOMContentLoaded', function() {
  // Menu item click functionality
  const menuItems = document.querySelectorAll('.menu-item');
  
  menuItems.forEach(item => {
    item.addEventListener('click', function() {
      // Skip if it's already active or it's the logout item
      if (this.classList.contains('active') || this.classList.contains('logout')) return;
      
      // Remove active class from current active item
      document.querySelector('.menu-item.active').classList.remove('active');
      document.querySelector('.menu-text.active-text').classList.remove('active-text');
      document.querySelector('.menu-bg')?.remove();
      
      // Add active class to clicked item
      this.classList.add('active');
      this.querySelector('.menu-text').classList.add('active-text');
      
      // Create background element
      const bg = document.createElement('div');
      bg.classList.add('menu-bg');
      this.prepend(bg);
      
      // Handle page navigation
      const menuText = this.querySelector('.menu-text').textContent.trim();
      console.log(`Navigating to: ${menuText}`);
      
      // Navigate to the appropriate page
      if (menuText === 'Dashboard') {
        window.location.href = 'admin.html';
      } else if (menuText === 'Verifications') {
        window.location.href = 'verifications.html';
      } else {
        // Other pages not yet implemented
        alert(`The ${menuText} page will be implemented in a future phase.`);
      }
    });
  });
  
  // Logout functionality
  const logoutBtn = document.querySelector('.menu-item.logout');
  
  if (logoutBtn) {
    logoutBtn.addEventListener('click', function() {
      if (confirm('Are you sure you want to log out?')) {
        // Redirect to login page
        window.location.href = '/';
      }
    });
  }

  // Action dropdown for 'more' buttons
  const moreIcons = document.querySelectorAll('.docs-table img[src$="more.svg"]');
  moreIcons.forEach(icon => {
    const cell = icon.closest('td');
    if (!cell.classList.contains('td-action')) {
      cell.classList.add('td-action');
      icon.classList.add('action-btn');
      // build dropdown
      const dropdown = document.createElement('div');
      dropdown.classList.add('action-dropdown');
      dropdown.innerHTML = `
        <div class="action-item" style="color: #01017A;"><img src="../assets/images/Bold Eye.svg" alt="">View</div>
        <div class="action-item" style="color: #FF3B30;"><img src="../assets/images/Trash.svg" alt="">Delete</div>
      `;
      cell.appendChild(dropdown);
      // add View/Delete click handlers
      const actionItems = dropdown.querySelectorAll('.action-item');
      if (actionItems.length >= 2) {
        const [viewItem, deleteItem] = actionItems;
        viewItem.addEventListener('click', e => {
          e.stopPropagation();
          window.location.href = '../pages/verification_detail.html';
        });
        deleteItem.addEventListener('click', e => {
          e.stopPropagation();
          alert('Delete clicked');
          dropdown.style.display = 'none';
        });
      }
      // toggle on click
      icon.addEventListener('click', e => {
        e.stopPropagation();
        dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        const {top,left,height} = icon.getBoundingClientRect();
        dropdown.style.top = (icon.offsetTop + icon.offsetHeight) + 'px';
        dropdown.style.left = icon.offsetLeft + 'px';
      });
    }
  });
  // close dropdown on outside click
  document.addEventListener('click', () => document.querySelectorAll('.action-dropdown').forEach(dd => dd.style.display = 'none'));
});