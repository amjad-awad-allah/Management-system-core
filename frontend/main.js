import './style.css';
import { DashboardView } from './src/views/dashboard.js';
import { StudentsView } from './src/views/students.js';
import { TeachersView } from './src/views/teachers.js';
import { SubjectsView } from './src/views/subjects.js';
import { LessonsView } from './src/views/lessons.js';

// Basic Router
const routes = {
  '/': DashboardView,
  '/students': StudentsView,
  '/teachers': TeachersView,
  '/subjects': SubjectsView,
  '/lessons': LessonsView,
};

const router = async () => {
  const path = window.location.hash.slice(1) || '/';
  const view = routes[path] || routes['/'];
  
  const contentDiv = document.getElementById('router-view');
  contentDiv.innerHTML = await view.render();
  if (view.afterRender) {
    await view.afterRender();
  }

  // Update active state in sidebar
  document.querySelectorAll('.nav-item').forEach(el => {
    el.classList.remove('active');
    if (el.getAttribute('href') === '#' + path) {
      el.classList.add('active');
    }
  });
};

// Render App Layout
document.querySelector('#app').innerHTML = `
  <div class="app-layout">
    <aside class="sidebar">
      <div class="sidebar-brand">BBP Admin</div>
      <nav>
        <a href="#/" class="nav-item">Dashboard & Calendar</a>
        <a href="#/lessons" class="nav-item" style="color: var(--primary);">Bookings</a>
        <a href="#/subjects" class="nav-item">Subjects</a>
        <a href="#/students" class="nav-item">Students</a>
        <a href="#/teachers" class="nav-item">Teachers</a>
      </nav>
    </aside>
    <main class="main-content" id="router-view">
      <!-- Content will be injected here by router -->
    </main>
  </div>
`;

// Listen on hash change
window.addEventListener('hashchange', router);
window.addEventListener('load', router);
