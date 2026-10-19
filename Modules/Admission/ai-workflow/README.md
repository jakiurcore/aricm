# Admission Module

## Purpose
The admission banner and page, the inquiry form and the inquiries list. **Depends on Core only**, so the desired class is free text chosen from a list the admin edits (`admission_classes` setting), not a foreign key to Academic.

## Public surface
- `Services\AdmissionSettings` (Core settings, `admission_*` keys; defaults in `config/admission.php`): banner, dates ("ভর্তি শুরু", "ক্লাস শুরু"), free-class note, program text, page content, classes offered.
- Implements `ProvidesHomepageSections` (`admission::home.banner`, order 10) and `ProvidesPermissions` (`admission.view|manage`).
- `POST /admission/inquiries` is throttled (5 per 10 minutes per IP), has a honeypot field (`website`, must stay empty) and accepts Bangla digits in the phone.
