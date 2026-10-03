WILDTRAIL LANKA v23 - FINAL UNIVERSITY DEMO BUILD

FRESH INSTALL
1. Put the wildtrail folder in C:/xampp/htdocs/.
2. Start Apache and MySQL.
3. Import sql/schema.sql in phpMyAdmin.
4. Open http://localhost/wildtrail/.
5. Default payment mode is local_demo, suitable for localhost.

UPGRADING FROM v22
- Replace the project files with v23.
- Run sql/upgrade_v23_final_demo_qa.sql once for optional indexes.
- No other v23 schema changes are required.

DEMO FLOW
Admin approves professional user account -> guide/driver logs in -> submits licence/vehicle documents and park assignments -> admin verifies professional profile/vehicle -> guide/driver publishes exact monthly availability -> tourist selects park/date/safari time -> only verified park-authorised available guides/jeeps appear -> tourist reserves -> local PayHere demo payment -> confirmed booking -> QR permit -> guide/driver receive confirmed assignment -> safari completes after date -> tourist can review.

IMPORTANT
- Missing availability means unavailable. A guide/driver must save availability before appearing in tourist search.
- Guide/vehicle park assignments are enforced both in search and server-side booking.
- Same guide/vehicle cannot be booked in the same date + safari-time key across different parks.
- Per-slot capacity and park daily vehicle cap are enforced.
- Paid cancellation before the safari date uses the project policy: 50% refund / 50% cancellation charge. Refund completion is manually recorded by admin in this university demo.
- Real PayHere callbacks require a public HTTPS URL; localhost uses local_demo.
