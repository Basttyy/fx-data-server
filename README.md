# fx-data-server
fx historical data feed

# Gregwar/Captcha should be changed as follows

src/Gregwar/Captcha/CaptchaBuilder.php
344: $size = (int) round($width / $length) - $this->rand(0, 3) - 1;
348: $x = (int) round(($width - $textWidth) / 2);
349: $y = (int) round(($height - $textHeight) / 2) + $size;

# pragmarx/google2fa should be changed as follows

src/pragmarx/google2fa/src/Google2FA.php
268: if (strlen((string)$secret) < 8) {