/* stylelint-disable */
document.addEventListener('DOMContentLoaded', () => {
  // Back button
  document.querySelector('.back-icon')?.addEventListener('click', () => {
    window.history.back();
  });

  // Zoom click to open modal
  const zoom = document.querySelector('.zoom-icon');
  const modal = document.querySelector('.doc-modal');
  const modalImg = document.getElementById('modal-img');
  const previewImg = document.querySelector('.doc-preview img');
  
  zoom?.addEventListener('click', () => {
    modal.style.display = 'flex';
    modalImg.src = previewImg.src;
  });

  // Close modal
  document.querySelector('.close-modal')?.addEventListener('click', () => {
    modal.style.display = 'none';
  });
});
