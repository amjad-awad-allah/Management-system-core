import { api } from '../api.js';

export const LessonsView = {
  async render() {
    return `
      <div class="page-header">
        <h1 class="page-title">Bookings (Lessons)</h1>
        <button class="btn btn-primary" id="btn-add-lesson">+ Book Lesson</button>
      </div>

      <div class="glass-card table-container">
        <table class="table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Student</th>
              <th>Teacher</th>
              <th>Subject</th>
              <th>Date & Time</th>
              <th>Duration</th>
              <th>Price</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody id="lessons-table-body">
            <tr><td colspan="8" class="text-center">Loading...</td></tr>
          </tbody>
        </table>
      </div>

      <!-- Book Lesson Modal -->
      <div class="modal-overlay" id="add-lesson-modal">
        <div class="modal-content">
          <div class="modal-header">
            <h2 class="modal-title">Book a Lesson</h2>
            <button class="modal-close" id="btn-close-modal">&times;</button>
          </div>
          <div class="modal-body">
            <form id="add-lesson-form">
              <div class="grid grid-cols-2">
                <div class="form-group">
                  <label class="form-label">Student</label>
                  <select class="form-control" name="student_id" id="select-student" required>
                    <option value="">Select Student...</option>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label">Teacher</label>
                  <select class="form-control" name="teacher_id" id="select-teacher" required>
                    <option value="">Select Teacher...</option>
                  </select>
                </div>
              </div>
              
              <div class="form-group">
                <label class="form-label">Subject</label>
                <select class="form-control" name="subject_id" id="select-subject" required>
                  <option value="">Select Subject...</option>
                </select>
              </div>

              <div class="grid grid-cols-2">
                <div class="form-group">
                  <label class="form-label">Scheduled At</label>
                  <input type="datetime-local" class="form-control" name="scheduled_at" required>
                </div>
                <div class="form-group">
                  <label class="form-label">Duration (Minutes)</label>
                  <input type="number" class="form-control" name="duration_minutes" value="60" min="30" step="15" required>
                </div>
              </div>
              
              <div class="grid grid-cols-2">
                <div class="form-group">
                  <label class="form-label">Price (€)</label>
                  <input type="number" class="form-control" name="price" min="0" step="0.5" required>
                </div>
              </div>
              
              <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea class="form-control" name="notes" rows="2" placeholder="Optional notes..."></textarea>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button class="btn" id="btn-cancel-modal">Cancel</button>
            <button class="btn btn-primary" id="btn-submit-lesson">Confirm Booking</button>
          </div>
        </div>
      </div>
    `;
  },

  async afterRender() {
    this.loadLessons();

    // Modal logic
    const modal = document.getElementById('add-lesson-modal');
    const openBtn = document.getElementById('btn-add-lesson');
    const closeBtn = document.getElementById('btn-close-modal');
    const cancelBtn = document.getElementById('btn-cancel-modal');
    const submitBtn = document.getElementById('btn-submit-lesson');
    const form = document.getElementById('add-lesson-form');

    let lookupsLoaded = false;

    const closeModal = () => modal.classList.remove('active');
    const openModal = async () => {
      form.reset();
      modal.classList.add('active');
      if (!lookupsLoaded) {
        await this.loadLookups();
        lookupsLoaded = true;
      }
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
      
      // Format datetime-local to ISO 8601 string for backend
      data.scheduled_at = new Date(data.scheduled_at).toISOString();
      
      submitBtn.textContent = 'Booking...';
      submitBtn.disabled = true;

      try {
        await api.post('/lessons', data);
        closeModal();
        this.loadLessons(); // Reload table
      } catch (error) {
        alert(error.message);
      } finally {
        submitBtn.textContent = 'Confirm Booking';
        submitBtn.disabled = false;
      }
    });
  },

  async loadLookups() {
    try {
      const [studentsRes, teachersRes, subjectsRes] = await Promise.all([
        api.get('/students'),
        api.get('/teachers'),
        api.get('/subjects')
      ]);

      const selStudent = document.getElementById('select-student');
      selStudent.innerHTML = '<option value="">Select Student...</option>' + 
        (studentsRes.data || []).map(s => `<option value="${s.id}">${s.first_name} ${s.last_name}</option>`).join('');

      const selTeacher = document.getElementById('select-teacher');
      selTeacher.innerHTML = '<option value="">Select Teacher...</option>' + 
        (teachersRes.data || []).map(t => `<option value="${t.id}">${t.name} (€${t.hourly_rate}/hr)</option>`).join('');

      const selSubject = document.getElementById('select-subject');
      selSubject.innerHTML = '<option value="">Select Subject...</option>' + 
        (subjectsRes.data || []).map(s => `<option value="${s.id}">${s.name}</option>`).join('');

    } catch (e) {
      console.error('Error loading lookups', e);
    }
  },

  async loadLessons() {
    const tbody = document.getElementById('lessons-table-body');
    try {
      const response = await api.get('/lessons');
      const lessons = response.data || [];
      
      if (lessons.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No lessons booked yet</td></tr>';
        return;
      }

      tbody.innerHTML = lessons.map(lesson => {
        const date = new Date(lesson.scheduled_at);
        const formattedDate = date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        return `
        <tr>
          <td class="text-muted text-sm">${lesson.id.substring(0, 8)}...</td>
          <td class="text-primary">${lesson.student_id.substring(0, 8)}...</td>
          <td class="text-accent">${lesson.teacher_id.substring(0, 8)}...</td>
          <td>${lesson.subject_id.substring(0, 8)}...</td>
          <td><strong>${formattedDate}</strong></td>
          <td>${lesson.duration_minutes} min</td>
          <td>€${lesson.price}</td>
          <td>
             <span class="badge ${lesson.status === 'scheduled' ? 'badge-success' : ''}">${lesson.status}</span>
          </td>
        </tr>
      `}).join('');
    } catch (error) {
      tbody.innerHTML = '<tr><td colspan="8" class="text-center text-danger">Failed to load lessons</td></tr>';
    }
  }
};
