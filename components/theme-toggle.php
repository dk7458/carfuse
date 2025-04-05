<?php
/**
 * Theme Toggle Component
 * 
 * @param string $style   Toggle style (icon|switch|button)
 * @param string $class   Additional CSS classes
 * @param string $id      Optional ID for the toggle
 */

$style = $style ?? 'icon';
$class = $class ?? '';
$id = $id ?? 'theme-toggle';
$position = $position ?? 'inline'; // inline, fixed
?>

<?php if ($style === 'icon'): ?>
<button 
  id="<?= $id ?>" 
  class="theme-toggle <?= $position === 'fixed' ? 'theme-toggle-fixed' : '' ?> <?= $class ?>" 
  aria-label="Toggle theme" 
  title="Toggle light/dark theme"
  role="switch"
  tabindex="0">
  
  <!-- Sun icon for light mode -->
  <svg xmlns="http://www.w3.org/2000/svg" class="theme-toggle-icon theme-toggle-light-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
    <circle cx="12" cy="12" r="5"></circle>
    <line x1="12" y1="1" x2="12" y2="3"></line>
    <line x1="12" y1="21" x2="12" y2="23"></line>
    <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
    <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
    <line x1="1" y1="12" x2="3" y2="12"></line>
    <line x1="21" y1="12" x2="23" y2="12"></line>
    <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
    <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
  </svg>
  
  <!-- Moon icon for dark mode -->
  <svg xmlns="http://www.w3.org/2000/svg" class="theme-toggle-icon theme-toggle-dark-icon hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
  </svg>
  
  <span class="theme-toggle-text sr-only">Toggle theme</span>
</button>

<?php elseif ($style === 'switch'): ?>
<button 
  id="<?= $id ?>" 
  class="theme-toggle theme-toggle-switch <?= $class ?>" 
  aria-label="Toggle theme" 
  title="Toggle light/dark theme"
  role="switch"
  tabindex="0">
  <span class="theme-toggle-text sr-only">Toggle theme</span>
</button>

<?php elseif ($style === 'button'): ?>
<button 
  id="<?= $id ?>" 
  class="theme-toggle flex items-center px-3 py-2 rounded-btn bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 transition-theme <?= $class ?>" 
  aria-label="Toggle theme"
  role="switch"
  tabindex="0">
  
  <!-- Sun icon for light mode -->
  <svg xmlns="http://www.w3.org/2000/svg" class="theme-toggle-icon theme-toggle-light-icon mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
    <circle cx="12" cy="12" r="5"></circle>
    <line x1="12" y1="1" x2="12" y2="3"></line>
    <line x1="12" y1="21" x2="12" y2="23"></line>
    <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
    <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
    <line x1="1" y1="12" x2="3" y2="12"></line>
    <line x1="21" y1="12" x2="23" y2="12"></line>
    <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
    <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
  </svg>
  
  <!-- Moon icon for dark mode -->
  <svg xmlns="http://www.w3.org/2000/svg" class="theme-toggle-icon theme-toggle-dark-icon hidden mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
  </svg>
  
  <span class="theme-toggle-label">
    <span class="light-label">Light</span>
    <span class="dark-label hidden">Dark</span>
  </span>
</button>
<?php endif; ?>
