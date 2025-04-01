# Latepoint Timezone Locker (LTL)

Latepoint Timezone Locker (LTL) is an add-on for the Latepoint appointment booking plugin in WordPress. 

### IMPORTANT: 
At this moment, LTL is not an official Latepoint© Add-on. It is not built or endorsed by Latepoint©. 
Hopefully it will be.

  *LTL was created by me to solve my problem.*  

### **!!! Knowing this use it at your own risk !!!**

Latepoint Timezone Locker ensures that all displayed appointment times strictly follow the timezone set in the WordPress settings, preventing adjustments based on the user’s local timezone.

LTL is designed for a very specific use case: when all appointments take place at a fixed physical location. For example, a surgery clinic in one country that schedules appointments with patients from different time zones. Since patients are expected to travel to the clinic, maintaining a consistent local time for bookings is essential.

By locking appointment times to the site’s configured timezone, LTL provides a unified and reliable scheduling experience for both administrators and clients.

Version: LTL 1.2.2
Date: 31-03-2025


## Key aspects of Latepoint and strategy

Looked into **start.php** and **feature_timezone_helper.php**.

get_wp_timezone() / get_wp_timezone_name(): These methods retrieve the timezone set in WordPress settings. 

get_timezone_name_from_session(): This method is key!

It attempts to get a timezone name, likely from a user's previous selection or detection.

It uses a cookie: $_COOKIE[Latepoint_SELECTED_TIMEZONE_COOKIE]. This suggests the user's browser timezone might be detected client-side (JavaScript) and stored in this cookie. [Latepoint_selected_timezone_70e10ec56ce92acf0cfbc7d18546983e]

Crucially, it includes a filter: apply_filters('Latepoint_timezone_name_from_session', $timezone_name);. This is the perfect place for LTL to intervene! Hook into this filter and force it to always return the WordPress timezone name.

set_timezone_name_in_cookie(): Confirms that Latepoint can set this cookie. Our filter should override the reading of this cookie for timezone calculations.

## Debug
## Define LTL_DEBUG as true in wp-config.php to enable PHP error logging
```define('LTL_DEBUG', true);```