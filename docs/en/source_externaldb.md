[Programs documentation](index.md) / [Program management](management_index.md) / External database allocation

# External database allocation

The **External database allocation** source can create and archive program allocations based on records stored in an
external database table. This mirrors the behaviour of the standard **Database enrolment** plugin but acts at the
program level.

## Connection configuration

1. Go to *Site administration → Plugins → Programs → Program settings*.
2. Enable connection details in the **External database allocation** section:
   - **Database type** – ADODB driver identifier (for example `mysqli`).
   - **Database host**, **name**, **user**, and **password** – standard connection credentials.
   - **Remote encoding** – the character set used by your external database (defaults to `utf-8`).
   - Optional settings allow you to run setup SQL, enable Sybase style quoting, or turn on debug output while testing.

The connection must be configured before the source can be enabled in a program.

## Program-level settings

Within a program open **Allocation settings → Allocation sources → External database allocation**. The following
fields control the synchronisation:

- **Remote table name** and **Remote program field** – identify the table and column that store allocation rows.
- **Remote program value** – optional override; when blank the current program ID number is used. If the program does
  not have an ID number the synchronisation is skipped, so make sure one is set or provide an explicit value.
- **Remote user field** and **Match users via** – specify which column identifies each user and how it maps to Moodle
  (`idnumber`, `username`, or `email`).
- **Remote allocation, start, due, and end date fields** – optional columns containing either Unix timestamps or
date/time strings understood by `strtotime()`.
- **Remote unique allocation field** – optional; if the column is non-numeric a stable integer hash is generated so the
  value can be stored in the `sourceinstanceid` column.
- **Remote status field / Active status value** – filter incoming rows to the specified status value.
- **Archive missing allocations** – when enabled, allocations previously created by this source are archived when the
  corresponding row disappears from the external dataset.

After saving the form the plugin writes any new allocations and updates schedule details for existing users. The
scheduled *Programs cron* task performs the same action regularly.

## Manual synchronisation

Administrators with the `tool/muprog:edit` capability see a **Run External Database Allocation** button on the
allocation settings page. Use this button to trigger synchronisation immediately, for example after loading new data
into the external table.

## Notes

- Rows that reference users who cannot be matched in Moodle are skipped.
- When date fields are left empty the allocation falls back to standard program scheduling rules.
- The synchronisation never removes allocations created by other sources; manual or cohort assignments are left
a untouched.
