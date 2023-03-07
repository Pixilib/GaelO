![Laravel](https://github.com/salimkanoun/GaelO/workflows/Laravel/badge.svg?branch=Gaelo2)

# GaelO v2
Copyright Pixilib 2018-2022
Licence AGPL v.3

GaelO is a free and open source web platform for medical imaging processing in clinical trials.

Website : http://www.GaelO.fr

Developpers : <br>
Leader & Maintainer  : Salim Kanoun <br>
Contributors : Bastien Proudhom, Ludwig Chieng, Emilie Olivié

# Tips

- To know backend version
php artisan --version 

# Release Cyles ; Upgrade version in composer.json

# Regenerate views email using mjml template
node_modules/mjml/bin/mjml ./app/GaelO/views/mails/mjml/qc_report_buttons.mjml -o ./app/GaelO/views/mails/mail_qc_report_buttons.blade.php
node_modules/mjml/bin/mjml ./app/GaelO/views/mails/mjml/qc_report_series.mjml -o ./app/GaelO/views/mails/mail_qc_report_series.blade.php
node_modules/mjml/bin/mjml ./app/GaelO/views/mails/mjml/qc_report_study.mjml -o ./app/GaelO/views/mails/mail_qc_report_study.blade.php
node_modules/mjml/bin/mjml ./app/GaelO/views/mails/mjml/qc_report_investigator_form.mjml -o ./app/GaelO/views/mails/mail_qc_report_investigator_form.blade.php
