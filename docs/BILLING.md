# Billing — SupportFlow Cloud

Two separate money flows — never mixed in code, UI or reports.

## A. SaaS subscriptions (you pay for the platform)

- Plans: **Starter £19 / Growth £49 / Professional £99** monthly, annual = 10× monthly.
  Editable at `/admin/plans` (prices, seats, asset/ticket limits, features, trial).
- Trial: 14 days (configurable), then manual or Stripe activation.
- **Without Stripe keys** the app runs in manual mode: plan changes are recorded as
  `billing_events` for offline invoicing; UI states this explicitly.
- **With Stripe**: Checkout Sessions (verified server-side — success URLs alone never
  activate), Billing Portal for payment methods, webhooks (`checkout.session.completed`,
  `invoice.payment_failed` → past-due + 7-day grace, `subscription.deleted`).
- Webhook endpoint: `POST /billing/webhook` (CSRF-exempt, signature-verified with
  `STRIPE_WEBHOOK_SECRET`, idempotent via `provider_event_id`).
- Setup: test keys from dashboard.stripe.com/test/apikeys → `.env` (`STRIPE_KEY`,
  `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`) → register webhook URL → test with
  `stripe trigger checkout.session.completed`.
- Enforcement: seat/asset/ticket limits checked at invite/create time with friendly
  upgrade prompts; feature gates (`plan:sla`, `plan:invoicing`, webhooks → Professional).

## B. Service invoices (your customers pay you)

- Draft → Issued → Partially Paid → Paid, plus Overdue (automatic) and Void.
- Billable time converts to line items in one click (`/invoices/create?ticket_id=`).
- Decimal-safe maths (`bc*` functions), configurable VAT/discounts/terms per tenant.
- Partial + multiple payments, duplicate-reference and overpayment protection,
  void blocked after payments, DomPDF download, email delivery, customer portal view.
- Optional Stripe per-invoice payments recorded with provider IDs; manual/bank/cash
  always available. Raw card data never touches the application.
