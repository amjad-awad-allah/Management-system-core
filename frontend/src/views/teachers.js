import { api } from '../api.js';

export const TeachersView = {
  async render() {
    return `
      <div class="page-header">
        <h1 class="page-title">Teachers</h1>
        <button class="btn btn-primary" id="btn-add-teacher">+ New Teacher</button>
      </div>

      <div class="grid md:grid-cols-2 lg:grid-cols-3" id="teachers-grid">
        <div class="text-muted">Loading teachers...</div>
      </div>

      <!-- Add Teacher Modal -->
      <div class="modal-overlay" id="add-teacher-modal">
        <div class="modal-content">
          <div class="modal-header">
            <h2 class="modal-title">Add New Teacher</h2>
            <button class="modal-close" id="btn-close-modal">&times;</button>
          </div>
          <div class="modal-body">
            <form id="add-teacher-form">
              <!-- Mocking user_id for now since we don't have user selection -->
              <input type="hidden" name="user_id" value="01KX15XQVV03P3MMY5WR65WE6R">
              
              <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" class="form-control" name="name" required>
              </div>
              <div class="form-group">
                <label class="form-label">Qualification</label>
                <input type="text" class="form-control" name="qualification" required>
              </div>
              <div class="form-group">
                <label class="form-label">Hourly Rate (€)</label>
                <input type="number" step="0.5" class="form-control" name="hourly_rate" min="0" required>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button class="btn" id="btn-cancel-modal">Cancel</button>
            <button class="btn btn-primary" id="btn-submit-teacher">Save Teacher</button>
          </div>
        </div>
      </div>
    `;
  },

  async afterRender() {
    this.loadTeachers();

    // Modal logic
    const modal = document.getElementById('add-teacher-modal');
    const openBtn = document.getElementById('btn-add-teacher');
    const closeBtn = document.getElementById('btn-close-modal');
    const cancelBtn = document.getElementById('btn-cancel-modal');
    const submitBtn = document.getElementById('btn-submit-teacher');
    const form = document.getElementById('add-teacher-form');

    const closeModal = () => modal.classList.remove('active');
    const openModal = () => {
      form.reset();
      // Generate a random ULID placeholder for user_id
      form.querySelector('[name="user_id"]').value = '01H' + Math.random().toString(36).substring(2, 15).toUpperCase();
      modal.classList.add('active');
    };

    openBtn.addEventListener('click', openModal);
    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);

    submitBtn.addEventListener('click', async () => {
      if (!form.checkValidity()) {
        form.reportValidity();
        return;
      }
      
      const formData = new FormData(form);
      const data = Object.fromEntries(formData.entries());
      
      submitBtn.textContent = 'Saving...';
      submitBtn.disabled = true;

      try {
        await api.post('/teachers', data);
        closeModal();
        this.loadTeachers(); // Reload grid
      } catch (error) {
        alert(error.message);
      } finally {
        submitBtn.textContent = 'Save Teacher';
        submitBtn.disabled = false;
      }
    });
  },

  async loadTeachers() {
    const grid = document.getElementById('teachers-grid');
    try {
      const response = await api.get('/teachers');
      const teachers = response.data || [];
      
      if (teachers.length === 0) {
        grid.innerHTML = '<div class="text-muted col-span-full">No teachers found</div>';
        return;
      }

      grid.innerHTML = teachers.map(teacher => `
        <div class="glass-card">
          <div class="flex items-center mb-4">
            <div style="width: 48px; height: 48px; border-radius: 50%; background: var(--primary); display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.2rem; margin-right: 16px;">
              ${teacher.name.charAt(0)}
            </div>
            <div>
              <h3 style="margin: 0; font-size: 1.1rem;">${teacher.name}</h3>
              <div class="text-muted" style="font-size: 0.85rem;">${teacher.id.substring(0, 8)}...</div>
            </div>
          </div>
          <div class="form-group" style="margin-bottom: 8px;">
            <span class="text-muted" style="font-size: 0.85rem;">Qualification:</span>
            <div style="font-weight: 500;">${teacher.qualification}</div>
          </div>
          <div class="form-group" style="margin-bottom: 0;">
            <span class="text-muted" style="font-size: 0.85rem;">Rate:</span>
            <div style="font-weight: 600; color: var(--success);">€${teacher.hourly_rate} / hr</div>
          </div>
        </div>
      `).join('');
    } catch (error) {
      grid.innerHTML = '<div class="text-danger">Failed to load teachers</div>';
    }
  }
};
