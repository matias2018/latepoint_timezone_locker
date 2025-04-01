/**
 * Latepoint Timezone Locker Script
 *
 * Attempts to overrides the timezone used by Latepoint frontend components
 * with the timezone set in WordPress settings, primarily as a fallback
 * in case the PHP filter ('latepoint_timezone_name_from_session') is bypassed
 * by a specific JS component.
 */
(function($) { // Use jQuery safely

    // Wait for the DOM to be ready
    $(function() {

        // Check if our localized data and the WordPress timezone are available
        if (typeof ltl_data === 'undefined' || typeof ltl_data.wp_timezone === 'undefined') {
            if (typeof ltl_data !== 'undefined' && ltl_data.debug_mode) {
                 console.warn('LTL JS: Localized data (ltl_data) or wp_timezone not found.');
            } else if (typeof ltl_data === 'undefined') {
                 console.warn('LTL JS: Localized data (ltl_data) object not found.');
            }
            return; // Exit if data is missing
        }

        const wpTimezone = ltl_data.wp_timezone;
        const isDebug = ltl_data.debug_mode;

        if (!wpTimezone) {
             if (isDebug) {
                console.warn('LTL JS: WordPress timezone string received from PHP is empty.');
             }
            return; // Exit if timezone is empty
        }

        if (isDebug) {
            console.log('LTL JS: Timezone Locker script running.');
            console.log('LTL JS: WordPress timezone received from PHP:', wpTimezone);
            console.log('LTL JS: Attempting JS override (may be redundant if PHP filter worked)...');
        }

        // --- *** THE CRITICAL PART - FIND AND OVERRIDE LATEPOINT'S TIMEZONE *** ---
        //
        // This part attempts to directly modify JS variables. It's less ideal than the
        // PHP filter but acts as a backup.
        // You MUST investigate Latepoint's JavaScript to find where it stores
        // or uses the timezone. Inspect the JavaScript variables in your browser's
        // developer console on the booking page.
        //
        // Look for objects like: window.latepoint_settings, window.latepoint_data,
        // or objects associated with the booking form components.
        // Check for properties like: timezone, timeZone, tz, selectedTimezone etc.

        let overridden = false;

        // Function to check and override timezone property
        function checkAndOverride(targetObject, propertyName) {
            if (typeof targetObject !== 'undefined' && typeof targetObject[propertyName] !== 'undefined') {
                if (isDebug) {
                    console.log(`LTL JS: Found ${propertyName} in target object. Current value:`, targetObject[propertyName]);
                }
                if (targetObject[propertyName] !== wpTimezone) {
                    console.warn(`LTL JS: Overriding ${propertyName} from "${targetObject[propertyName]}" to "${wpTimezone}".`);
                    targetObject[propertyName] = wpTimezone;
                    overridden = true;
                } else {
                    if (isDebug) {
                         console.log(`LTL JS: ${propertyName} already matches WP timezone. No JS override needed.`);
                    }
                }
                return true; // Property exists
            }
            return false; // Property doesn't exist
        }

        // --- Try common patterns ---
        let foundTarget = false;
        if (typeof window.latepoint_settings !== 'undefined') {
            foundTarget = true;
            if (isDebug) console.log('LTL JS: Checking window.latepoint_settings...');
             checkAndOverride(window.latepoint_settings, 'timezone') || checkAndOverride(window.latepoint_settings, 'timeZone');
        }
        if (typeof window.latepoint_data !== 'undefined') {
             foundTarget = true;
             if (isDebug) console.log('LTL JS: Checking window.latepoint_data...');
             checkAndOverride(window.latepoint_data, 'timezone') || checkAndOverride(window.latepoint_data, 'timeZone');
        }
         // Add other potential objects here if you find them during inspection
         // e.g., if (typeof window.someLatepointComponent !== 'undefined') { ... }


        // --- If no common targets found ---
        if (!foundTarget && isDebug) {
             console.warn('LTL JS: Could not find common Latepoint global objects (latepoint_settings, latepoint_data). Manual JS inspection needed if filter alone isn\'t working.');
        }

        // --- Triggering a refresh (if necessary and if override happened) ---
        if (overridden) {
            if (isDebug) console.log('LTL JS: A JS variable was overridden. Checking if refresh needed...');
            // This part is highly speculative and depends entirely on Latepoint's internal functions.
            // You would need to find a function that re-renders or re-calculates time slots.
            // Example (PURE GUESS - uncomment and adapt if needed):
            // if (typeof window.LatePointBookingForm !== 'undefined' && typeof window.LatePointBookingForm.refreshTimeslots === 'function') {
            //    if (isDebug) console.log('LTL JS: Triggering hypothetical Latepoint refreshTimeslots().');
            //    // Debounce or delay slightly? Might be needed.
            //    // setTimeout(function() { window.LatePointBookingForm.refreshTimeslots(); }, 100);
            // } else if (isDebug) {
            //     console.log('LTL JS: No known refresh function found to trigger.');
            // }
        }

        if (isDebug) {
             console.log('LTL JS: Override attempt complete.');
        }

    }); // End DOM ready

})(jQuery); // End jQuery wrapper