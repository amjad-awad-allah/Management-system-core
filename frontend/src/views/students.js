import { api } from '../api.js';

export const StudentsView = {
  async render() {
    return `
      <div class="page-header">
        <h1 class="page-title">Students</h1>
        <button class="btn btn-primary" id="btn-add-student">+ New Student</button>
      </div>

      <div class="glass-card table-container">
        <table class="table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>School</th>
              <th>Grade</th>
            </tr>
          </thead>
          <tbody id="students-table-body">
            <tr><td colspan="4" class="text-center">Loading...</td></tr>
          </tbody>
        </table>
      </div>

      <!-- Add Student Modal -->
      <div class="modal-overlay" id="add-student-modal">
        <div class="modal-content">
          <div class="modal-header">
            <h2 class="modal-title">Add New Student</h2>
            <button class="modal-close" id="btn-close-modal">&times;</button>
          </div>
          <div class="modal-body">
            <form id="add-student-form">
              <div class="grid grid-cols-2">
                <div class="form-group">
                  <label class="form-label">First Name</label>
                  <input type="text" class="form-control" name="first_name" required>
                </div>
                <div class="form-group">
                  <label class="form-label">Last Name</label>
                  <input type="text" class="form-control" name="last_name" required>
                </div>
              </div>
              <div class="form-group">
                <label class="form-label">Birth Date</label>
                <input type="date" class="form-control" name="birth_date" required>
              </div>
              <div class="form-group">
                <label class="form-label">School</label>
                <input type="text" class="form-control" name="school" required>
              </div>
              <div class="form-group">
                <label class="form-label">Grade</label>
                <input type="number" class="form-control" name="grade" min="1" max="13" required>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button class="btn" id="btn-cancel-modal">Cancel</button>
            <button class="btn btn-primary" id="btn-submit-student">Save Student</button>
          </div>
        </div>
      </div>
    `;
  },

  async afterRender() {
    this.loadStudents();

    // Modal logic
    const modal = document.getElementById('add-student-modal');
    const openBtn = document.getElementById('btn-add-student');
    const closeBtn = document.getElementById('btn-close-modal');
    const cancelBtn = document.getElementById('btn-cancel-modal');
    const submitBtn = document.getElementById('btn-submit-student');
    const form = document.getElementById('add-student-form');

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
      
      submitBtn.textContent = 'Saving...';
      submitBtn.disabled = true;

      try {
        await api.post('/students', data);
        closeModal();
        this.loadStudents(); // Reload table
      } catch (error) {
        alert(error.message);
      } finally {
        submitBtn.textContent = 'Save Student';
        submitBtn.disabled = false;
      }
    });
  },

  async loadStudents() {
    const tbody = document.getElementById('students-table-body');
    try {
      const response = await api.get('/students');
      const students = response.data || [];
      
      if (students.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No students found</td></tr>';
        return;
      }

      tbody.innerHTML = students.map(student => `
        <tr>
          <td class="text-muted text-sm">${student.id.substring(0, 8)}...</td>
          <td><strong>${student.first_name} ${student.last_name}</strong></td>
          <td>${student.school}</td>
          <td><span class="badge badge-success">Grade ${student.grade}</span></td>
        </tr>
      `).join('');
    } catch (error) {
      tbody.innerHTML = '<tr><td colspan="4" class="text-center text-danger">Failed to load students</td></tr>';
    }
  }
};
