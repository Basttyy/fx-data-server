# FX-DATA-SERVER DOCUMENTATION
##### GETTING STARTED / INSTALLATION
##### DETAILED USER MANUAL
##### API DOCUMENTATION
##### TROUBLESHOOTING AND FAQ
##### VERSIONING AND CHANGE LOGS


## GETTING STARTED / INSTALLATION
#### PROCESS A
* Open CLI on your desktop and Clone the repository
* Create your new-branch
* Checkout to dev
* Run git pull
* Checkout to your new-branch/features
* Run git push —set-origin your new-branch/features

#### PROCESS B
* On your browser download and install Laragon
* Start Laragon
* Click on start all
* Right Click on Laragon and select Quick app and select blank App
* Input fx-data-server as the name of the app
* When its done, visit http://fx-data-server.test on your browser it should show Index of/
* Click on root to open Laragon’s Root Directory
* Copy the contents of the folder in process A to fx-data-server folder in process B
* Cd to this new folder and Run composer install
* Run cp .env.example .env
* Edit .env configuration to match the db credentials you have in Laragon
* Create .htaccess file and request the content of .htaccess file from a team member/lead dev
* Visit or reload http://fx-data-server.test again it should show {"message": "the requested resource is not found"} an api response instead of index of/


## CommandLine Helper

* composer run-script queue-dev      //runs the background job worker
* composer run-script queue-buried-dev      //runs the background buried job worker
* composer run-script add-model
* composer run-script add-controller
* composer run-script add-api-controller
* composer run-script add-migration
* composer run-script add-seed
* composer run-script migrate
* composer run-script seed


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

# commands to generate FX History data

* download from dukascopy: python fx-data-download.py -p USDJPY -y 2016,2017,2018,2019,2020,2021,2022,2023 -m all -d all -t 1min,1hr,1day -c -C
* parse original 1min into different timeframes: php dataprocessor.php -c parse_timeframe -t xauusd -y=2016,2017,2018,2019,2020,2021,2022,2023 -T 1,2,5,10,15,30,45
* parse original 60min into 60,120 and 240min: php dataprocessor.php -c parse_timeframe -t eurusd -T=60,120,240 -S=60 -y=2016,2017,2018,2019,2020,2021,2022,2023
* parse daily: php dataprocessor.php -c parse_timeframe -t xauusd,gbpjpy,usdjpy -T=1440 -S=1440 -y=2016,2017,2018,2019,2020,2021,2022,2023
* compact to weekly data: php dataprocessor.php -c compact_week -t xauusd -s=2016 -e=2023 -T 1,2,5,10,15,30,45
* compact to weekly data and zip: php dataprocessor.php -c compact_week -t xauusd -s=2016 -e=2023 -T 1,2,5,10,15,30,45 -z

# TODO

implement web scrapping on https://www.x-rates.com/table/?from=USD&amount=1 to get current exchange rates