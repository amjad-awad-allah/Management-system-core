import { api } from '../api.js';

export const DashboardView = {
  async render() {
    return `
      <div class="page-header">
        <h1 class="page-title">Dashboard</h1>
        <div class="text-muted" id="current-month-label">Loading...</div>
      </div>

      <div class="grid grid-cols-4 mb-4">
        <div class="glass-card">
          <h3 class="text-muted" style="font-size: 0.9rem; margin-bottom: 8px;">Total Students</h3>
          <div id="stat-students" style="font-size: 2rem; font-weight: bold;">-</div>
        </div>
        <div class="glass-card">
          <h3 class="text-muted" style="font-size: 0.9rem; margin-bottom: 8px;">Total Teachers</h3>
          <div id="stat-teachers" style="font-size: 2rem; font-weight: bold;">-</div>
        </div>
        <div class="glass-card">
          <h3 class="text-muted" style="font-size: 0.9rem; margin-bottom: 8px;">Total Subjects</h3>
          <div id="stat-subjects" style="font-size: 2rem; font-weight: bold;">-</div>
        </div>
        <div class="glass-card">
          <h3 class="text-muted" style="font-size: 0.9rem; margin-bottom: 8px;">Total Lessons</h3>
          <div id="stat-lessons" style="font-size: 2rem; font-weight: bold; color: var(--primary)">-</div>
        </div>
      </div>

      <div class="glass-card">
        <div class="flex justify-between items-center mb-4">
          <h2 style="font-size: 1.2rem;">Lesson Calendar</h2>
          <div class="flex gap-2">
            <button class="btn" id="btn-prev-month" style="padding: 4px 12px; background: rgba(255,255,255,0.05);">&lt;</button>
            <button class="btn" id="btn-next-month" style="padding: 4px 12px; background: rgba(255,255,255,0.05);">&gt;</button>
          </div>
        </div>
        
        <div class="calendar-grid">
          <div class="calendar-header-day">Sun</div>
          <div class="calendar-header-day">Mon</div>
          <div class="calendar-header-day">Tue</div>
          <div class="calendar-header-day">Wed</div>
          <div class="calendar-header-day">Thu</div>
          <div class="calendar-header-day">Fri</div>
          <div class="calendar-header-day">Sat</div>
        </div>
        <div class="calendar-grid" id="calendar-body">
          <!-- Calendar days injected here -->
        </div>
      </div>
    `;
  },

  currentDate: new Date(),
  lessonsData: [],

  async afterRender() {
    // Load stats
    this.loadStats();
    
    // Load and render calendar
    await this.fetchLessons();
    this.renderCalendar();

    document.getElementById('btn-prev-month').addEventListener('click', () => {
      this.currentDate.setMonth(this.currentDate.getMonth() - 1);
      this.renderCalendar();
    });

    document.getElementById('btn-next-month').addEventListener('click', () => {
      this.currentDate.setMonth(this.currentDate.getMonth() + 1);
      this.renderCalendar();
    });
  },

  async loadStats() {
    try {
      api.get('/students').then(r => document.getElementById('stat-students').innerText = r.data.length);
      api.get('/teachers').then(r => document.getElementById('stat-teachers').innerText = r.data.length);
      api.get('/subjects').then(r => document.getElementById('stat-subjects').innerText = r.data.length);
    } catch (e) {
      console.error('Failed to load stats', e);
    }
  },

  async fetchLessons() {
    try {
      const response = await api.get('/lessons');
      this.lessonsData = response.data || [];
      document.getElementById('stat-lessons').innerText = this.lessonsData.length;
    } catch (e) {
      console.error('Failed to load lessons for calendar', e);
      this.lessonsData = [];
    }
  },

  renderCalendar() {
    const month = this.currentDate.getMonth();
    const year = this.currentDate.getFullYear();
    
    document.getElementById('current-month-label').innerText = new Intl.DateTimeFormat('en-US', { month: 'long', year: 'numeric' }).format(this.currentDate);

    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    
    const calendarBody = document.getElementById('calendar-body');
    let html = '';

    // Empty slots before first day
    for (let i = 0; i < firstDay; i++) {
      html += `<div class="calendar-day empty"></div>`;
    }

    // Days of the month
    const today = new Date();
    for (let day = 1; day <= daysInMonth; day++) {
      const isToday = day === today.getDate() && month === today.getMonth() && year === today.getFullYear();
      
      // Filter lessons for this day
      const dayLessons = this.lessonsData.filter(l => {
        const lDate = new Date(l.scheduled_at);
        return lDate.getDate() === day && lDate.getMonth() === month && lDate.getFullYear() === year;
      });

      let indicatorsHtml = '';
      if (dayLessons.length > 0) {
        indicatorsHtml = `<div class="lesson-indicator">${dayLessons.length} Lesson${dayLessons.length > 1 ? 's' : ''}</div>`;
      }

      html += `
        <div class="calendar-day ${isToday ? 'today' : ''}">
          <span class="day-number">${day}</span>
          ${indicatorsHtml}
        </div>
      `;
    }

    calendarBody.innerHTML = html;
  }
};
