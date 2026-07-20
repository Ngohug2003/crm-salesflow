window.salesflow = {
    toggleTheme() {
        const dark = document.documentElement.classList.toggle('dark');
        localStorage.setItem('salesflow-theme', dark ? 'dark' : 'light');
    },
};

document.addEventListener('livewire:navigating', () => {
    document.documentElement.classList.add('is-navigating');
});

document.addEventListener('livewire:navigated', () => {
    requestAnimationFrame(() => document.documentElement.classList.remove('is-navigating'));
});
