# Pending Features

This file tracks planned ERP features that should be built after the current production upload. Do not use destructive database commands when adding these features to a live installation.

## Bank Reconciliation

Match uploaded bank statements with ERP accounting records.

Scope:

- Bank accounts setup.
- Upload bank statements in CSV/XLSX first, with PDF support later if needed.
- Import bank transactions with date, description, debit, credit, balance, and bank reference.
- Auto-match credit transactions with tenant invoice payments and security deposits.
- Auto-match debit transactions with company expenses, owner-related expenses, owner payouts, and tenant refunds.
- Confidence scoring for matches, for example exact amount/reference/date match versus manual review.
- Manual match popup for unclear transactions.
- Create expense from unmatched bank debit.
- Create or approve payment from matched bank credit.
- Reconciliation dashboard and export report.

Required from business before build:

- Sample bank statement export from the real bank, preferably CSV or Excel.
- Bank name and account type.
- Current payment reference style used by the team.
- Expense payment methods used in real operations.
- Matching tolerance, for example same day, plus/minus 2 days, or plus/minus 3 days.

## Production Hardening

- Confirm cPanel document root points to `/public`.
- Confirm `SEED_DEMO_DATA=false` in production.
- Confirm queue worker setup for emails and notifications.
- Confirm S3 credentials are rotated before production if any keys were previously shared.
- Confirm backup process before every software update.
