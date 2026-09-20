/**
 * The store is light-theme only. Saved dark/system preferences are cleared
 * so a previous visit cannot put `.dark` back on the document.
 */
export function initializeTheme() {
    document.documentElement.classList.remove('dark');
    document.documentElement.style.colorScheme = 'light';
    localStorage.removeItem('appearance');
}
