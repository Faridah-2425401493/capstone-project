document.addEventListener('DOMContentLoaded', () => {
  const toggleBtn = document.getElementById('darkModeToggle');
  const htmlElement = document.documentElement;

  // Load preference
  const savedTheme = localStorage.getItem('theme');
  if (savedTheme) {
    htmlElement.setAttribute('data-bs-theme', savedTheme);
  } else {
    // Default to light if not set, or you can use prefers-color-scheme
    const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    htmlElement.setAttribute('data-bs-theme', systemPrefersDark ? 'dark' : 'light');
  }

  // Update button text based on current theme
  const updateBtnText = () => {
    const isDark = htmlElement.getAttribute('data-bs-theme') === 'dark';
    if(toggleBtn) {
        toggleBtn.innerHTML = isDark ? '☀️ Light' : '🌙 Dark';
    }
  };
  updateBtnText();

  // Toggle listener
  if (toggleBtn) {
    toggleBtn.addEventListener('click', () => {
      const currentTheme = htmlElement.getAttribute('data-bs-theme');
      const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
      
      htmlElement.setAttribute('data-bs-theme', newTheme);
      localStorage.setItem('theme', newTheme);
      updateBtnText();
    });
  }
});
