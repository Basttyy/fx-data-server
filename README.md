# fx-data-server
fx historical data feed


the queue worker is meant to work with CRON Jobs
start a cron job with the script path (5 min interval for example)
cron_command "server_home/src/console/jobrunner.php"
cron_command "server_home/src/console/burriedjobrunner.php"

# Gregwar/Captcha should be changed as follows

src/Gregwar/Captcha/CaptchaBuilder.php
344: $size = (int) round($width / $length) - $this->rand(0, 3) - 1;
348: $x = (int) round(($width - $textWidth) / 2);
349: $y = (int) round(($height - $textHeight) / 2) + $size;

# pragmarx/google2fa should be changed as follows

src/pragmarx/google2fa/src/Google2FA.php
268: if (strlen((string)$secret) < 8) {

# TODO

implement web scrapping on https://www.x-rates.com/table/?from=USD&amount=1 to get current exchange rates