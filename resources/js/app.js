import './bootstrap';

window.salesflow = {
    toggleTheme() {
        const dark = document.documentElement.classList.toggle('dark');
        localStorage.setItem('salesflow-theme', dark ? 'dark' : 'light');
    },
};
