<?php
/**
 * Reusable Modal Component
 * 
 * Parameters:
 * @param string $id                 Unique identifier for the modal
 * @param string $title              Modal title
 * @param string $size               Modal size: 'sm', 'md', 'lg', 'xl', '2xl', 'full' (default: 'md')
 * @param string $modalVar           Alpine.js variable controlling modal visibility (default: 'showModal')
 * @param string $primaryBtn         Primary button text (default: 'Zapisz')
 * @param string $secondaryBtn       Secondary button text (default: 'Anuluj')
 * @param string $dangerBtn          Danger button text if needed (default: 'Usuń')
 * @param string $primaryAction      JS code for primary button action
 * @param string $secondaryAction    JS code for secondary button action (default: close modal)
 * @param string $dangerAction       JS code for danger button action
 * @param bool $showPrimaryBtn       Whether to show primary button (default: true)
 * @param bool $showSecondaryBtn     Whether to show secondary button (default: true)
 * @param bool $showDangerBtn        Whether to show danger button (default: false)
 * @param bool $preventCloseOutside  Prevent closing when clicking outside (default: false)
 * @param string $htmxPrimaryTarget  HTMX target for primary action
 * @param string $htmxPrimarySwap    HTMX swap mode for primary action
 * @param string $htmxPrimaryUrl     HTMX URL for primary action
 * @param string $htmxPrimaryMethod  HTMX method for primary action (default: determined from URL)
 * @param string $htmxPrimaryVals    HTMX values for primary action
 * @param string $contentUrl         URL to load content dynamically with HTMX
 * @param string $loadingText        Loading text when content is being loaded (default: 'Ładowanie...')
 * @param bool $isForm               Whether the modal contains a form (default: false)
 * @param string $formId             Form ID if the modal contains a form
 * @param bool $htmxIndicator        Whether to show HTMX indicator (default: true)
 * @param string $htmxLoading        Loading text for HTMX indicator (default: loadingText)
 */

// Default values
$id = $id ?? 'modal-' . uniqid();
$title = $title ?? 'Modal';
$size = $size ?? 'md';
$modalVar = $modalVar ?? 'showModal';
$primaryBtn = $primaryBtn ?? 'Zapisz';
$secondaryBtn = $secondaryBtn ?? 'Anuluj';
$dangerBtn = $dangerBtn ?? 'Usuń';
$primaryAction = $primaryAction ?? '';
$secondaryAction = $secondaryAction ?? "$modalVar = false";
$dangerAction = $dangerAction ?? '';
$showPrimaryBtn = $showPrimaryBtn ?? true;
$showSecondaryBtn = $showSecondaryBtn ?? true;
$showDangerBtn = $showDangerBtn ?? false;
$preventCloseOutside = $preventCloseOutside ?? false;
$htmxPrimaryTarget = $htmxPrimaryTarget ?? '';
$htmxPrimarySwap = $htmxPrimarySwap ?? 'innerHTML';
$htmxPrimaryUrl = $htmxPrimaryUrl ?? '';
$htmxPrimaryMethod = $htmxPrimaryMethod ?? '';
$htmxPrimaryVals = $htmxPrimaryVals ?? '';
$contentUrl = $contentUrl ?? '';
$loadingText = $loadingText ?? 'Ładowanie...';
$isForm = $isForm ?? false;
$formId = $formId ?? $id . '-form';
$htmxIndicator = $htmxIndicator ?? true;
$htmxLoading = $htmxLoading ?? $loadingText;

// Determine HTMX method from URL if not explicitly provided
if (!$htmxPrimaryMethod && $htmxPrimaryUrl) {
    $htmxPrimaryMethod = strpos($htmxPrimaryUrl, 'get:') === 0 ? 'get' : 'post';
    $htmxPrimaryUrl = str_replace(['get:', 'post:'], '', $htmxPrimaryUrl);
}

// Size classes
$sizeClasses = [
    'sm' => 'sm:max-w-sm',
    'md' => 'sm:max-w-md',
    'lg' => 'sm:max-w-lg',
    'xl' => 'sm:max-w-xl',
    '2xl' => 'sm:max-w-2xl',
    'full' => 'sm:max-w-full sm:w-full'
];
$modalSizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];

// Handle outside click action
$outsideClickAction = !$preventCloseOutside ? "@click.away=\"$modalVar = false\"" : "";
$backdropClickAction = !$preventCloseOutside ? "@click=\"$modalVar = false\"" : "";

// Alpine component init
$alpineInit = "
    @keydown.escape.window=\"$modalVar = false\"
    @keydown.tab=\"handleTab\"
    @keydown.shift.tab=\"handleShiftTab\"
    x-init=\"setTimeout(() => { $modalVar && setFocusToFirstInput() }, 100)\"
";

// Additional Alpine methods
$alpineMethods = "
    x-data=\"{
        setFocusToFirstInput() {
            let firstFocusable = this.\$el.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex=\"-1\"])');
            if (firstFocusable) firstFocusable.focus();
        },
        handleTab(e) {
            let focusables = [...this.\$el.querySelectorAll('button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex=\"-1\"])')];
            if (focusables.length === 0) return;
            let first = focusables[0];
            let last = focusables[focusables.length - 1];
            if (e.target === last && !e.shiftKey) {
                e.preventDefault();
                first.focus();
            }
        },
        handleShiftTab(e) {
            let focusables = [...this.\$el.querySelectorAll('button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex=\"-1\"])')];
            if (focusables.length === 0) return;
            let first = focusables[0];
            let last = focusables[focusables.length - 1];
            if (e.target === first) {
                e.preventDefault();
                last.focus();
            }
        }
    }\"
";
?>

<div id="<?= $id ?>"
     x-show="<?= $modalVar ?>" 
     class="fixed inset-0 z-50 overflow-y-auto" 
     role="dialog"
     aria-modal="true"
     aria-labelledby="<?= $id ?>-title"
     x-cloak
     <?= $alpineInit ?>
     <?= $alpineMethods ?>>
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div x-show="<?= $modalVar ?>" 
             x-transition:enter="ease-out duration-300" 
             x-transition:enter-start="opacity-0" 
             x-transition:enter-end="opacity-100" 
             x-transition:leave="ease-in duration-200" 
             x-transition:leave-start="opacity-100" 
             x-transition:leave-end="opacity-0" 
             class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" 
             aria-hidden="true"
             <?= $backdropClickAction ?>>
        </div>

        <!-- Centering trick -->
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <!-- Modal panel -->
        <div x-show="<?= $modalVar ?>" 
             x-transition:enter="ease-out duration-300" 
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
             x-transition:leave="ease-in duration-200" 
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
             class="inline-block w-full px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle <?= $modalSizeClass ?> sm:p-6"
             <?= $outsideClickAction ?>
             @click.outside="document.activeElement.blur()">
            
            <!-- Modal header -->
            <div class="flex items-center justify-between pb-3 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800" id="<?= $id ?>-title">
                    <?= $title ?>
                </h3>
                <button @click="<?= $modalVar ?> = false" 
                        type="button" 
                        class="text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 rounded-full p-1"
                        aria-label="Zamknij">
                    <span class="sr-only">Zamknij</span>
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <!-- Modal content -->
            <div class="mt-4" id="<?= $id ?>-content">
                <?php if ($contentUrl): ?>
                    <div hx-get="<?= $contentUrl ?>"
                         hx-trigger="load"
                         hx-indicator="#<?= $id ?>-spinner">
                        <div id="<?= $id ?>-spinner" class="htmx-indicator flex justify-center items-center py-8">
                            <div class="animate-spin h-8 w-8 mr-3 rounded-full border-2 border-gray-200 border-t-blue-600"></div>
                            <span class="text-gray-600"><?= $loadingText ?></span>
                        </div>
                    </div>
                <?php elseif (isset($content)): ?>
                    <?= $content ?>
                <?php else: ?>
                    <!-- Slot for dynamic content -->
                    <div class="py-2 text-gray-600">
                        <!-- Content will be inserted here -->
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if ($isForm): ?>
            <form id="<?= $formId ?>">
            <?php endif; ?>

            <!-- Modal footer with actions -->
            <div class="mt-5 sm:mt-6 flex flex-row-reverse gap-2 pt-3 border-t border-gray-200">
                <?php if ($showPrimaryBtn): ?>
                <button 
                    <?php if ($htmxPrimaryUrl): ?>
                        hx-<?= $htmxPrimaryMethod ?>="<?= $htmxPrimaryUrl ?>"
                        hx-target="<?= $htmxPrimaryTarget ?>"
                        hx-swap="<?= $htmxPrimarySwap ?>"
                        <?= $htmxPrimaryVals ? "hx-vals='$htmxPrimaryVals'" : "" ?>
                        hx-indicator="#<?= $id ?>-primary-spinner"
                    <?php endif; ?>
                    @click="<?= $primaryAction ?>"
                    type="<?= $isForm ? 'submit' : 'button' ?>"
                    class="inline-flex justify-center px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 items-center">
                    <span id="<?= $id ?>-primary-spinner" class="htmx-indicator inline-block mr-2">
                        <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </span>
                    <?= $primaryBtn ?>
                </button>
                <?php endif; ?>
                
                <?php if ($showDangerBtn): ?>
                <button 
                    @click="<?= $dangerAction ?>"
                    type="button"
                    class="inline-flex justify-center px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-md shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    <?= $dangerBtn ?>
                </button>
                <?php endif; ?>
                
                <?php if ($showSecondaryBtn): ?>
                <button 
                    @click="<?= $secondaryAction ?>"
                    type="button"
                    class="inline-flex justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <?= $secondaryBtn ?>
                </button>
                <?php endif; ?>
            </div>
            
            <?php if ($isForm): ?>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>
