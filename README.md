![Tests](https://github.com/salimkanoun/GaelO/actions/workflows/tests.yml/badge.svg?branch=GaelO2)
![Publish](https://github.com/salimkanoun/GaelO/actions/workflows/publish.yml/badge.svg?branch=GaelO2)
![Php-Stan](https://github.com/salimkanoun/GaelO/actions/workflows/php-stan.yml/badge.svg?branch=GaelO2)

![Coverage](https://github.com/Pixilib/GaelO/blob/GaelO2_code_coverage/data/GaelO2/badge.svg)

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

sheetname limité a 31 caractère $sheetName =  substr($role, 0, 3)  . '_' . $visitGroupName . '_' . $visitTypeName; donc groupname et typename ensemble doivent etre < 25 caractères. Studyname a limiter a 10 caractères

# Release Cyles ; Upgrade version in composer.json

# Regenerate views email using mjml template
node_modules/mjml/bin/mjml ./app/GaelO/views/mails/mjml/qc_report_buttons.mjml -o ./app/GaelO/views/mails/mail_qc_report_buttons.blade.php
node_modules/mjml/bin/mjml ./app/GaelO/views/mails/mjml/qc_report_series.mjml -o ./app/GaelO/views/mails/mail_qc_report_series.blade.php
node_modules/mjml/bin/mjml ./app/GaelO/views/mails/mjml/qc_report_study.mjml -o ./app/GaelO/views/mails/mail_qc_report_study.blade.php
node_modules/mjml/bin/mjml ./app/GaelO/views/mails/mjml/qc_report_investigator_form.mjml -o ./app/GaelO/views/mails/mail_qc_report_investigator_form.blade.php
node_modules/mjml/bin/mjml ./app/GaelO/views/mails/mjml/radiomics_report.mjml -o ./app/GaelO/views/mails/mail_radiomics_report.blade.php
In blade generated files, edit file to keep only body content (remove header...)
