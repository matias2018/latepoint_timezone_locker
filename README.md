
## Define LTL_DEBUG as true in wp-config.php to enable PHP error logging
```define('LTL_DEBUG', true);```


## Key aspects of Latepoint and strategy

Looked into **start.php** and **feature_timezone_helper.php**.

### start.php
Create a message if everything fails:
Inside ```<div class="latepoint-body">```
```<div class="latepoint-alert-timezone" style="background:#25B0B4; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
        <span style="color:#fff; font-size:large; font-weight: bold;">Important:</span>
            <span style="color:#fff; font-size:large;">
                Please note that the time displayed is based on your local time zone, not the clinic's (UTC -4, Antigua, Cayman).
            </span>
    </div>```

get_wp_timezone() / get_wp_timezone_name(): These methods retrieve the timezone set in WordPress settings. 

get_timezone_name_from_session(): This method is key!

It attempts to get a timezone name, likely from a user's previous selection or detection.

It uses a cookie: $_COOKIE[LATEPOINT_SELECTED_TIMEZONE_COOKIE]. This suggests the user's browser timezone might be detected client-side (JavaScript) and stored in this cookie.

Crucially, it includes a filter: apply_filters('latepoint_timezone_name_from_session', $timezone_name);. This is the perfect place for LTL to intervene! Hook into this filter and force it to always return the WordPress timezone name.

set_timezone_name_in_cookie(): Confirms that Latepoint can set this cookie. Our filter should override the reading of this cookie for timezone calculations.