// assets/js/sponsor_levels.js

document.addEventListener('DOMContentLoaded', () => {

  const container = document.getElementById('conboard-sponsor-levels');
  const addBtn = document.getElementById('conboard-add-sponsor-level');

  if (!container || !addBtn) return;

  addBtn.addEventListener('click', () => {

    const slug = 'level_' + Date.now();

    const row = document.createElement('div');
    row.className = 'conboard-repeater-row';

    row.innerHTML = `
      <input name="conboard_sponsor_levels[${slug}][label]" placeholder="Label">
      <input name="conboard_sponsor_levels[${slug}][singular]" placeholder="Singular">
      <input name="conboard_sponsor_levels[${slug}][plural]" placeholder="Plural">
      <input type="hidden" name="conboard_sponsor_levels[${slug}][key]" value="${slug}">
      <button type="button" class="button conboard-remove-level">Remove</button>
    `;

    container.appendChild(row);
  });

  container.addEventListener('click', e => {
    if (e.target.classList.contains('conboard-remove-level')) {
      e.target.closest('.conboard-repeater-row').remove();
    }
  });

document.addEventListener('click', e => {

  const row = e.target.closest('.conboard-level-row');
  if (!row) return;

  if (e.target.classList.contains('conboard-edit-level')) {
    row.dataset.editing = 'true';
  }

  if (e.target.classList.contains('conboard-cancel-edit')) {
    row.dataset.editing = 'false';
  }

  if (e.target.classList.contains('conboard-remove-level')) {
    row.remove();
  }

});

row.dataset.editing = 'true';



});
