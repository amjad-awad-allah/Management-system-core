import { api } from '../api.js';

export const SubjectsView = {
  async render() {
    return `
      <div class="page-header">
        <h1 class="page-title">Subjects</h1>
        <button class="btn btn-primary" id="btn-add-subject">+ New Subject</button>
      </div>

      <div class="glass-card table-container">
        <table class="table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Description</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody id="subjects-table-body">
            <tr><td colspan="4" class="text-center">Loading...</td></tr>
          </tbody>
        </table>
      </div>

      <!-- Add Subject Modal -->
      <div class="modal-overlay" id="add-subject-modal">
        <div class="modal-content">
          <div class="modal-header">
            <h2 class="modal-title">Add New Subject</h2>
            <button class="modal-close" id="btn-close-modal">&times;</button>
          </div>
          <div class="modal-body">
            <form id="add-subject-form">
              <div class="form-group">
                <label class="form-label">Subject Name</label>
                <input type="text" class="form-control" name="name" required placeholder="e.g. Mathematics">
              </div>
              <div class="form-group">
                <label class="form-label">Description</label>
                <textarea class="form-control" name="description" rows="3" placeholder="Optional description..."></textarea>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button class="btn" id="btn-cancel-modal">Cancel</button>
            <button class="btn btn-primary" id="btn-submit-subject">Save Subject</button>
          </div>
        </div>
      </div>
    `;
  },

  async afterRender() {
    this.loadSubjects();

    // Modal logic
    const modal = document.getElementById('add-subject-modal');
    const openBtn = document.getElementById('btn-add-subject');
    const closeBtn = document.getElementById('btn-close-modal');
    const cancelBtn = document.getElementById('btn-cancel-modal');
    const submitBtn = document.getElementById('btn-submit-subject');
    const form = document.getElementById('add-subject-form');

    const closeModal = () => modal.classList.remove('active');
    const openModal = () => {
      form.reset();
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
      data.is_active = true; // Default active
      
      submitBtn.textContent = 'Saving...';
      submitBtn.disabled = true;

      try {
        await api.post('/subjects', data);
        closeModal();
        this.loadSubjects(); // Reload table
      } catch (error) {
        alert(error.message);
      } finally {
        submitBtn.textContent = 'Save Subject';
        submitBtn.disabled = false;
      }
    });
  },

  async loadSubjects() {
    const tbody = document.getElementById('subjects-table-body');
    try {
      const response = await api.get('/subjects');
      const subjects = response.data || [];
      
      if (subjects.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No subjects found</td></tr>';
        return;
      }

      tbody.innerHTML = subjects.map(subject => `
        <tr>
          <td class="text-muted text-sm">${subject.id.substring(0, 8)}...</td>
          <td><strong>${subject.name}</strong></td>
          <td class="text-muted">${subject.description || '-'}</td>
          <td>
             ${subject.is_active 
                ? '<span class="badge badge-success">Active</span>' 
                : '<span class="badge btn-danger">Inactive</span>'}
          </td>
        </tr>
      `).join('');
    } catch (error) {
      tbody.innerHTML = '<tr><td colspan="4" class="text-center text-danger">Failed to load subjects</td></tr>';
    }
  }
};
