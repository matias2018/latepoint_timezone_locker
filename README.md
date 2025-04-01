# Latepoint Timezone Locker (LTL)

Latepoint Timezone Locker (LTL) is an add-on for the Latepoint appointment booking plugin in WordPress. LTL ensures that all displayed appointment times align with the timezone set in WordPress settings, rather than adjusting based on the user's local timezone. This guarantees consistency across all bookings, providing a unified scheduling experience for both administrators and clients. 

Version: LTL 1.2.0
Date: 31-03-2025


## Key aspects of Latepoint and strategy

Looked into **start.php** and **feature_timezone_helper.php**.

get_wp_timezone() / get_wp_timezone_name(): These methods retrieve the timezone set in WordPress settings. 

get_timezone_name_from_session(): This method is key!

It attempts to get a timezone name, likely from a user's previous selection or detection.

It uses a cookie: $_COOKIE[LATEPOINT_SELECTED_TIMEZONE_COOKIE]. This suggests the user's browser timezone might be detected client-side (JavaScript) and stored in this cookie.

Crucially, it includes a filter: apply_filters('latepoint_timezone_name_from_session', $timezone_name);. This is the perfect place for LTL to intervene! Hook into this filter and force it to always return the WordPress timezone name.

set_timezone_name_in_cookie(): Confirms that Latepoint can set this cookie. Our filter should override the reading of this cookie for timezone calculations.

## Debug
## Define LTL_DEBUG as true in wp-config.php to enable PHP error logging
```define('LTL_DEBUG', true);```