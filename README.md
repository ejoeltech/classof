# Classof

**The alumni group manager with welfare and elections built in.**

Classof is a multi-tenant platform for primary, secondary and tertiary alumni communities. One account can belong to **many affiliations at once** — a general institution association, an exact class set, a department, a faculty, and an old boys/girls association — each with its own directory, dues, welfare fund, treasury, a member-owned **cooperative** and executive elections.

Membership is **proof-backed**: self-service join requests include evidence of affiliation and stay pending until a group admin reviews and approves them.

Built with **PHP 8.2 + MySQL**, no framework and no build step. It runs directly from XAMPP at `http://localhost/classof`.

---

## What is included

### Groups, scopes & membership
- Create unlimited alumni groups for any affiliation.
- **Scopes:** general institution (all years), class/set (specific year), department, faculty, association, or other.
- **Institution types:** primary, secondary, university, polytechnic, college of education, vocational/technical, other.
- One person can be an active member of any number of groups across different schools, sets, departments, faculties and associations.
- **Proof-backed joining:** evidence type, reference, note and an optional private document (image or PDF).
- Every self-service request is reviewed by an admin before access is granted.
- Invite by email with single-use, 14-day links. Invitations are pre-approved by the inviter and recorded as verified.
- Roles: **Owner → Admin → Exco → Member**, with optional position titles (President, Treasurer, etc.).
- Rejected applicants can correct their proof and reapply.
- Member approval, rejection reasons, role changes, removal, and self-service leave.
- Public or private group visibility and a searchable, scope-filtered directory.

#### Scope model

| Scope | Typical use | Required detail |
| --- | --- | --- |
| Institution-wide | General alumni association for every graduate | Institution name |
| Class / Set | A specific graduating year | Graduation year |
| Department | Eg. Department of Computer Science alumni | Department name |
| Faculty | Eg. Faculty of Science alumni | Faculty name |
| Association | Old boys/girls or alumni association | Institution or body name |
| Other | Any custom affiliation | Institution or body name |

A single university can therefore run five groups side by side — institution-wide, class of 2016, Faculty of Science, Computer Science department, and a distinct alumni association — and a member can join all of them.

### Institution catalogue & onboarding
- A curated catalogue of **156 verified Nigerian institutions** across federal, state and private ownership: universities, polytechnics, colleges of education, plus the seeded primary and secondary demo schools. Common aliases such as UNIBEN, UNILAG, OAU and FUTA are stored.
- Users can add **multiple education records** (institution, programme, faculty, department, graduation year) during onboarding or later from their profile.
- Unlisted institutions can be submitted for platform review and used by the submitter while pending; they become selectable for everyone once verified.
- **Matched suggestions** appear on the dashboard and in `Explore → Matched for you`. Matching covers institution-wide, class/set (year), institution-level association, faculty, department and combined class+faculty/department groups. Programme is display-only.
- **Unit catalogue:** faculties and departments are generated lazily per institution from a common Nigerian template and stored as catalogue IDs. The template is marked as such in the `source` column and is a scaffold for matching, not a claim of per-institution accreditation.
- **Education verification policy:** platform admins can choose whether unconfirmed education records drive suggestions, whether a confirmed record is required before requesting to join, whether unconfirmed claims are flagged for reviewers, and whether institution-wide groups auto-approve from a matching record. Defaults preserve proof-backed approval.
- **WhatsApp channels:** group admins can store labelled WhatsApp invite links (main group, exco, welfare) with an audience of all active members or managers only. Links are validated against WhatsApp hosts, shown only to authorised members, and can be revoked. The app never reads or posts inside WhatsApp groups.
- **WhatsApp outbox:** opted-in members can receive announcement and event-reminder template messages through the WhatsApp Cloud API. Queueing is opt-in, per-group daily-limited and idempotent. Without Meta credentials the worker writes to `storage/logs/whatsapp.log`, so the flow is testable offline.
- **Classmate discovery:** members who opt in can see other opted-in members with the same **confirmed** institution and graduation year. No contact upload, no global people search, and members who do not opt in stay hidden.
- **New-group notice:** a dashboard notice tells members when a newly verified group matches their education, using a persisted last-seen timestamp. It is in-app only — no email or push.
- Joining still requires the existing proof-backed admin approval. A suggestion is not an automatic membership.
- **Group creation is request-and-verify**: it lives under `Profile → Group requests`, not in the main navigation. A platform admin checks for duplicates and verifies the institution before publishing the group; the requester becomes owner.

### Group websites
- Every group gets its own public, single-page landing website at `<subdomain>.<SITE_DOMAIN>` - for example `uniben.classof.com`. Locally, the reliable demo address is `http://localhost/classof/site/uniben`; `uniben.localhost` also works where wildcard localhost resolves.
- The page is deliberately simple: a hero with a headline and tagline, about text, latest news, upcoming events, leadership, contact details and social links, plus a proof-backed join call to action.
- Group owners and admins can edit the page, or assign a registered member or contracted professional as the **website editor** without giving them group admin rights.
- Each website has a draft/published switch, an editable subdomain, accent colour, optional hero image, and toggles for news, events, leadership, stats and the join button.
- **Draft with AI:** if an OpenAI-compatible endpoint is configured, a button drafts the headline, tagline and about copy from the group profile. Without a key it falls back to a structured draft from the same group data. Member records are never sent.
- The public page never exposes treasury, welfare case details, proofs or member contact data. Announcements are shown as short excerpts and only when the group enables the news section.
- A **Share** button creates a WhatsApp message with the group site URL, and event cards link to a calendar download.
- In development without wildcard DNS, the same page is available at `/site/{subdomain}`, and the editor offers a private preview link.

### AI assistants
- **Group-website assistant:** a floating **Ask** button on every public group site answers visitor questions about joining, events, news, leadership, contact details and the group itself. It can only see public group and page information.
- **Signed-in help assistant:** a **Help assistant** in the app sidebar and a floating button answer questions about joining groups, dues, payments, welfare, elections, co-ops, classmates, WhatsApp, websites and the member’s own groups.
- Answers use the configured OpenAI-compatible endpoint when available. Without `AI_API_KEY`, a built-in guide answers the common questions from the same database context.
- Conversations live in the PHP session only — there is no chat table, no transcript export and no member-to-member messaging. History is trimmed to the last 12 messages.
- Rate limiting caps each session at 30 messages per 10 minutes, and questions are limited to 800 characters.
- Platform admins can switch both assistants off from `/admin/settings`.

### Member experience
- Profile with photo, occupation, location, phone and bio.
- A private member page showing affiliation, membership number, role and proof status.
- Dashboard across every group: dues owed, welfare cases, upcoming events and live elections.
- Announcements with pinning.
- Events with RSVP (going / maybe / declined), guest counts, capacity and optional entry fees.
- **Calendar download:** every event has a public `.ics` file in the group timezone, plus WhatsApp sharing for reunion invites.
- Payment history and receipts.

### Dues
- Create dues cycles with amount, description and due date.
- Track paid members, collected totals, progress and outstanding members automatically.
- Per-member payment status and receipts.
- **WhatsApp chase:** each outstanding member has a one-tap WhatsApp reminder with the cycle, amount, due date and a link to their payment reference.

### Welfare
- Open cases by category: bereavement, medical, wedding, childbirth, education, emergency or other.
- Optional target amount with live funding progress.
- Every contribution recorded against a named member in a public ledger.
- Cases auto-mark as **funded** when the target is reached, and can be closed by an admin.
- Anonymous beneficiary option.

### Elections (exco voting)
- Create elections with an opening and closing date.
- Add positions with seats, descriptions and ordering.
- Add candidates with manifestos.
- **Secret ballot:** one vote per member, per position, enforced by a database unique key.
- Voting is restricted to active members and only while the election is live.
- Live turnout, one-click close, then publish results.
- Winners and ties computed from recorded ballots; individual votes are never exposed.
- Positions/candidates are locked once voting closes.

### Treasury & payments
- Unified ledger for dues, welfare, events and donations.
- Summary cards for collected, dues, welfare and pending amounts.
- Filter and search payments; confirm or reject offline payments.
- **Record cash received** at meetings or for a transfer confirmed outside Classof, with an audit trail.
- **“I have sent this transfer”** lets a member flag a manual transfer; the treasury shows a “Member says paid” badge before an admin confirms.
- CSV export for AGM finance reports.
- Full audit trail of group and payment actions.

### Cooperative society
Any group can enable **one member-owned cooperative** with a full financial suite:
- **Enrolment & roles:** members opt in separately from the alumni group; coordinator, treasurer, loan officer and secretary roles, plus group admins as managers.
- **Shares:** configurable share value; members buy share units and build share capital.
- **Savings:** deposits and withdrawals with a live member balance.
- **Loan products:** interest rate, duration, minimum/maximum amount, savings multiple and number of guarantors.
- **Loan lifecycle:** member application → guarantors accept/decline → officer approval → disbursement → auto-generated instalment schedule → gateway or manual repayments → completion.
- **Guarantor portal:** members see and respond to guarantee requests; outstanding loans cannot be approved without enough accepted guarantors.
- **Fines:** issue manually or for absence at a meeting; pay via checkout or mark paid/waived by an officer.
- **Meetings:** agenda, roll call (present/absent/apology/excused), minutes, and one-click absence fines.
- **Dividends:** declare a surplus distribution by shares or savings, preview allocations, then distribute to every member.
- **Statements & ledger:** member statement with CSV export, co-op cash position, savings pool, loans out, unpaid fines, manual cash/bank entries and full-ledger CSV export.
- **Payments:** savings, shares, loan repayments and fines can be paid through the existing manual/Paystack/Stripe checkout or recorded by an officer. Gateway payments are applied exactly once.

### Payments
Three gateways, switchable with one environment variable:
- **Manual bank transfer** — works out of the box; members get bank details and a unique reference, admins confirm in the treasury. A group can store its own bank account in **Group settings**; otherwise the platform-wide `MANUAL_BANK_*` account is used.
- **Paystack** — hosted checkout, server-side verification, signed webhooks.
- **Stripe** — Checkout Sessions, server-side verification, signed webhooks.

If an online gateway is selected but its keys are missing, Classof safely falls back to bank transfer instead of breaking checkout.

### Platform administration
- Platform admin console with user, group, election and money totals.
- Suspend/reactivate users and archive/restore groups.
- Platform-wide activity log.

---

## Requirements

- PHP **8.2+** with `pdo_mysql`, `curl`, `fileinfo`, `mbstring` (all enabled by default in XAMPP)
- MySQL **8.0+** or MariaDB **10.4+**
- Apache with `mod_rewrite` and `AllowOverride All` (XAMPP defaults)
- No Composer or Node dependencies

---

## Quick start (XAMPP)

1. Place the project at `C:\xampp\htdocs\classof`.
2. Start **Apache** and **MySQL** from the XAMPP control panel.
3. Open a terminal in the project folder and run:

```powershell
php database/install.php --seed
```

That creates the `classof` database, all tables, the institution catalogue and the demo data.

For an **existing installation** with live data, do not re-run the installer. Apply additive migrations instead:

```powershell
php database/migrate.php
php database/backfill_institutions.php
```

`migrate.php` adds the new catalogue, education, group-request, WhatsApp, group-website and group-bank tables/columns. `backfill_institutions.php` imports the catalogue and maps existing groups by normalised institution name, reporting any unmatched groups. `install.php` also records the current migration version, so a fresh install and a migrated install end up at the same schema.

4. Visit **http://localhost/classof/**

> If you start from `.env.example` instead of the bundled `.env`, generate a key first:
> `php -r "echo bin2hex(random_bytes(32));"` and paste it into `APP_KEY`.

### Demo accounts

| Email | Password | Role |
| --- | --- | --- |
| `demo@classof.test` | `password123` | Owner of King's College Class of 2010 |
| `admin@classof.test` | `password123` | Platform administrator |

Other seeded members (`chioma@`, `emeka@`, `fatima@`, `kelechi@`, `ngozi@`, `seyi@`, `ibrahim@`, `bisi@`, `jude@classof.test`) all use `password123`.

`demo@classof.test` has a verified UNILAG Class of 2016 record and classmate discovery enabled, so **Profile → Classmates** lists Chioma, Emeka, Fatima and Bisi. The other seeded members also have WhatsApp and classmate opt-ins with demo numbers, which makes the outbox testable in log mode.

### Demo affiliations

The seeder creates seven groups so the scope model is visible immediately:

| Group | Scope |
| --- | --- |
| King's College, Lagos — Class of 2010 | Class / Set |
| University of Lagos — Class of 2016 | Class / Set |
| Greenfield Primary School — Class of 2004 | Class / Set (private, invite only) |
| University of Lagos Alumni Association | Institution-wide |
| Faculty of Science, University of Lagos — Alumni | Faculty |
| Department of Computer Science, UNILAG — Alumni | Department |
| King's College Lagos Old Boys Association | Association |

`demo@classof.test` belongs to all seven. Ibrahim Musa has a pending proof-backed request in the King's College group, so you can try the review-and-approve flow from **Members → Review proof**.

Six additional **University of Benin** groups are seeded for the matching demo: institution-wide, class of 2004, old students association, Faculty of Engineering, Department of Electrical/Electronic Engineering, and a combined class+faculty group.

`jude@classof.test` / `password123` is the onboarding demo: a UNIBEN 2004 Electrical/Electronic Engineering graduate who is **not yet a member** of those groups. His dashboard and `Explore → Matched for you` should show **six matches**: institution-wide, class of 2004, old students association, Faculty of Engineering, Department of Electrical/Electronic Engineering, and the combined class+faculty group.

There is also a pending institution request (“Lagos City College of Technology”), a pending group request (“Faculty of Engineering, University of Lagos — Alumni”), and unverified education records in the admin review queues. The King’s College group has two seeded WhatsApp channels: a members' main group and a managers-only exco link.

Every seeded group also has a public website. Try `http://localhost/classof/site/uniben` — or `http://uniben.localhost/classof/` if your machine resolves `*.localhost`. Then open **Group → Website** as `demo@classof.test` for the King’s College page. `jude@classof.test` is the assigned website editor for the UNIBEN Alumni Association, so his dashboard shows a **Websites you manage** card even though he is not yet a member of that group.

Each group site also has a floating **Ask** assistant, and signed-in members get the **Help assistant** from the app sidebar. Without an AI key they use the built-in guide, so the demo works offline.

The King's College group also has a seeded **cooperative society** with 8 members, share purchases, savings, two loan products, one active loan, one pending loan, guarantor requests, a meeting with attendance and fines, and a distributed dividend. Open **Co-op** in the group navigation to explore it.

### Manual installation

If you prefer the MySQL client:

```powershell
# PowerShell
Get-Content database/schema.sql | C:\xampp\mysql\bin\mysql.exe -u root
php database/seed.php

# cmd.exe
C:\xampp\mysql\bin\mysql.exe -u root < database\schema.sql
php database\seed.php
```

Re-running `install.php` or `schema.sql` **drops and recreates** all tables. Use it to reset the demo.

---

## Configuration

All configuration lives in `.env` (see `.env.example`).

| Variable | Purpose |
| --- | --- |
| `APP_URL` | Public base URL, used for invite and reset links |
| `APP_ENV` | `local` shows reset/invite links on screen; use `production` when live |
| `APP_DEBUG` | Must be `false` in production |
| `APP_KEY` | Random application key (already generated in the bundled `.env`) |
| `DB_*` | MySQL connection |
| `PAYMENT_GATEWAY` | `manual`, `paystack` or `stripe` |
| `PAYMENT_CURRENCY` | Default currency for new groups (e.g. `NGN`) |
| `MANUAL_BANK_*` | Fallback bank name, account number and account holder when a group has not set its own |
| `WHATSAPP_DRIVER` | `log` (default, writes to `storage/logs/whatsapp.log`) or `cloud` |
| `WHATSAPP_API_VERSION` | Graph API version, default `v20.0` |
| `WHATSAPP_PHONE_NUMBER_ID` | Meta WhatsApp Business phone number ID |
| `WHATSAPP_ACCESS_TOKEN` | Meta Cloud API access token |
| `WHATSAPP_WEBHOOK_VERIFY_TOKEN` | Verify token for `GET /webhooks/whatsapp` |
| `WHATSAPP_APP_SECRET` | App secret used to verify `X-Hub-Signature-256` |
| `WHATSAPP_TEMPLATE_*` | Approved template names and language code |
| `SITE_DOMAIN` | Root domain for group websites, e.g. `classof.com`. Empty uses only `/site/{subdomain}` |
| `AI_API_KEY` | Optional key for an OpenAI-compatible chat completion endpoint |
| `AI_BASE_URL` | API base URL, default `https://api.openai.com/v1` |
| `AI_MODEL` | Model used for website copy drafts |
| `AI_TIMEOUT` | AI request timeout in seconds, default `20` |

### Paystack

```env
PAYMENT_GATEWAY=paystack
PAYSTACK_PUBLIC_KEY=pk_test_...
PAYSTACK_SECRET_KEY=sk_test_...
```

Webhook URL: `https://your-domain/classof/webhooks/paystack`  
Subscribe to `charge.success`. The webhook signature is verified with your secret key, and each transaction is re-verified against the Paystack API before being marked successful.

### Stripe

```env
PAYMENT_GATEWAY=stripe
STRIPE_PUBLISHABLE_KEY=pk_test_...
STRIPE_SECRET_KEY=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

Webhook URL: `https://your-domain/classof/webhooks/stripe`  
Event: `checkout.session.completed`. Signatures are verified with a five-minute tolerance.

For local testing, expose your machine with a tunnel (ngrok, Cloudflare Tunnel) and set `APP_URL` to the public URL.

### Email

`MAIL_DRIVER=log` (default) writes messages to `storage/logs/mail.log` and never fails. In local mode, password reset and invite links are also shown in the UI so you can test without an SMTP account. Swap in your own SMTP implementation in `app/Services/Mailer.php` for production.

### WhatsApp Cloud API

Classof sends **announcement** and **event reminder** template messages through the Meta WhatsApp Cloud API. It never reads or posts inside a WhatsApp group, and it only messages members who explicitly opt in from **Profile → WhatsApp updates**.

The outbox is **off by default**. Two separate layers control it:

1. **Environment** — `.env` credentials:

```env
WHATSAPP_DRIVER=cloud
WHATSAPP_PHONE_NUMBER_ID=123456789012345
WHATSAPP_ACCESS_TOKEN=EAAG...
WHATSAPP_WEBHOOK_VERIFY_TOKEN=choose-a-random-string
WHATSAPP_APP_SECRET=your-meta-app-secret
WHATSAPP_TEMPLATE_LANGUAGE=en_US
WHATSAPP_TEMPLATE_ANNOUNCEMENT=classof_announcement
WHATSAPP_TEMPLATE_EVENT=classof_event_reminder
```

2. **Admin settings** — `/admin/settings`:

| Setting | Default | Purpose |
| --- | --- | --- |
| Enable WhatsApp Cloud API delivery | Off | Master switch for the outbox and worker |
| Queue WhatsApp for announcements | Off | Queue templates when an announcement is published |
| Queue WhatsApp event reminders | Off | Allow the reminder CLI to queue 24-hour reminders |
| WhatsApp messages per group per day | 100 | Rolling per-group daily cap |

**Test without Meta:** leave `WHATSAPP_DRIVER=log` (the default). The worker then appends each message to `storage/logs/whatsapp.log` instead of calling Meta, so the whole queue-and-send flow works offline. If the driver is `cloud` but the phone number ID or access token is missing, the worker falls back to the log file instead of failing.

**Templates:** create and get approval for two utility templates in Meta Business Manager before going live. The seeded names are `classof_announcement` (body parameters: group name, announcement title, announcement excerpt, author) and `classof_event_reminder` (group name, event title, start time, location). Rename them in `.env` if yours differ. Meta rejects unapproved template names.

**Webhook:** point the Meta app at

```
https://your-domain/classof/webhooks/whatsapp
```

- `GET` answers Meta's subscription challenge using `WHATSAPP_WEBHOOK_VERIFY_TOKEN`.
- `POST` verifies `X-Hub-Signature-256` with `WHATSAPP_APP_SECRET` and records `sent`, `delivered`, `read` or `failed` statuses against the outbox row.
- Without `WHATSAPP_APP_SECRET`, `POST` requests are rejected. This is intentional.

**Cron:** run both commands on the server.

```cron
# process the outbox every minute
* * * * * php /path/to/classof/database/whatsapp_worker.php

# queue 24-hour event reminders hourly
0 * * * * php /path/to/classof/database/whatsapp_queue_reminders.php
```

On Windows, create two Task Scheduler jobs that run `php.exe` with the full path to each script. The worker accepts `--limit=100` to process a larger batch.

**Where to watch it:** `/admin/whatsapp` shows counts by status, the active driver, recent deliveries, provider status and retry/cancel actions. Queueing is idempotent, so re-running the worker or re-publishing the same announcement cannot create duplicate rows.

### Group websites

Each group has a public single-page website. Open it from **Group → Website** as an owner/admin, or from the **Websites you manage** card when you are the assigned editor.

**Production DNS and Apache.** Point a wildcard record at the application and accept wildcard hosts:

```apache
<VirtualHost *:80>
    ServerName classof.com
    ServerAlias *.classof.com
    DocumentRoot "C:/xampp/htdocs/classof"
    <Directory "C:/xampp/htdocs/classof">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

```env
APP_URL=https://classof.com
SITE_DOMAIN=classof.com
```

Requests to `uniben.classof.com` are served by the same front controller, which resolves the first host label to a published `group_sites` row. `www`, `admin`, `api`, `mail` and similar reserved labels stay on the main application.

Use a wildcard TLS certificate (`*.classof.com`) so every group site is HTTPS. If only per-host certificates are available, issue one for each published subdomain.

**Local development.** The bundled `.env` sets `SITE_DOMAIN=localhost`. While browsing on `localhost`, Classof keeps links on the reliable path fallback: `http://localhost/classof/site/uniben`. The subdomain form `http://uniben.localhost/classof/` works when your browser or hosts file resolves `*.localhost`; otherwise add entries:

```text
127.0.0.1 uniben.localhost
127.0.0.1 uniben-2004.localhost
```

Group editors also have a private preview link that works before publishing.

**Managing a page.**
1. Group owner/admin opens **Group → Website**.
2. Set the subdomain, headline, tagline, about text, accent colour and optional hero image.
3. Add contact details and social links, and choose which sections appear.
4. Switch between **Draft** and **Published**, then preview.
5. To delegate, enter the email of any active Classof member under **Assigned editor**. They can edit the page without group admin rights and see it on their dashboard.
6. Use **Draft with AI** to generate a first draft. Without `AI_API_KEY`, the same button generates a structured draft from the group name, institution and description.

The public page is read-only and does not require an account. Join and sign-in buttons point back to the main application, so membership still goes through the existing proof-and-approval flow.

### Chatbots

Two assistants share one service:

- **Group sites:** the floating **Ask** button uses the group’s public profile, page content, selected announcements, upcoming events, leadership and public contact details. It cannot see proofs, payments, welfare cases, member records or private groups.
- **Signed-in app:** the **Help assistant** in the sidebar and the floating button use the app’s feature guide plus the member’s own groups and upcoming events.

**AI or built-in guide.** With `AI_API_KEY`, `AI_BASE_URL` and `AI_MODEL` configured, answers come from that chat-completions endpoint. Without a key, or if the request fails, the assistant falls back to concise guide answers built from the same context. The response source is marked **AI** or **Guide** in the widget.

**Privacy and limits.**
- Conversations are stored only in the PHP session and trimmed to 12 messages. Clearing the session or using **New chat** removes the transcript.
- Public group chat sends only public group/page information to the AI provider. The signed-in assistant sends the member’s name, groups and upcoming events, never other members’ details.
- Each session is limited to 30 messages per 10 minutes and 800 characters per message.
- Turn both assistants off in `/admin/settings` with **Enable chatbots**.

The endpoint routes are `POST /site/{subdomain}/chat` and `POST /assistant/chat`; both are CSRF-protected and return JSON.

---

## Project structure

```
classof/
├── index.php               Front controller
├── routes/web.php          All application routes
├── config/config.php       Environment + app configuration
├── app/
│   ├── Core/               Router, Request, Response, Database, Auth, CSRF,
│   │                       Validator, View, Session, Guard, helpers
│   ├── Controllers/        HTTP handlers (auth, groups, members, proof streaming,
│   │                       dues, welfare, elections, payments, treasury,
│   │                       cooperative, co-op loans/fines/meetings/dividends, admin,
│   │                       classmates, WhatsApp webhook, group websites, chatbot)
│   ├── Models/             Data access (users, groups, memberships, events,
│   │                       payments, welfare, elections, votes, audit,
│   │                       cooperatives, co-op members/loans/meetings/dividends,
│   │                       notifications, group sites)
│   ├── Services/           Uploader, Mailer, CoopService, EligibilityService,
│   │                       ClassmateService, WhatsAppService, SiteContentService,
│   │                       AssistantService, payment gateways + PaymentService
│   └── Views/              Server-rendered templates and layouts, including
│                           public group-site pages, layouts/site.php and the
│                           shared assistant widget
├── database/
│   ├── schema.sql          Full MySQL schema
│   ├── install.php         CLI installer (fresh database)
│   ├── migrate.php         Additive migration runner (existing installs)
│   ├── backfill_institutions.php  Map existing groups to the catalogue
│   ├── whatsapp_worker.php  CLI outbox worker (cron, every minute)
│   ├── whatsapp_queue_reminders.php  CLI 24-hour event reminder queue (cron, hourly)
│   ├── migrations/         Numbered additive SQL migrations (001–005)
│   ├── data/
│   │   └── institutions_ng.php   Curated Nigerian institution catalogue
│   └── seed.php            Demo data seeder
├── public/
│   ├── assets/             CSS, JS, favicon, group-site and assistant styles
│   └── uploads/            Group logos, avatars and website hero images
└── storage/
    ├── logs/               Application and mail logs
    └── proofs/             Private membership proof documents (never served directly)
```

---

## How the key flows work

### Join / invite / proof
- **Approval (default):** the applicant submits a proof type, reference, optional note and optional document. Membership is created as `pending`; an owner/admin reviews the evidence on the member page, then approves or rejects with a reason.
- **Invite:** only a valid, unexpired link matching the signed-in email can join. The inviter is recorded as the reviewer and the proof type is stored as `invitation`.
- **Reapply:** a rejected applicant sees the reason, can submit corrected proof, and returns to `pending` with a fresh review timestamp.

Proof states shown to members and managers:
`No proof` → `Submitted` → `Verified`, or `Rejected` with a reason. Documents are stored in `storage/proofs` and streamed through `/proofs/{file}` only to the member or a group manager.

### Dues payment
1. A dues cycle is created with a fixed amount.
2. A member starts payment — the amount comes from the cycle, never from the browser.
3. Manual: the group’s bank account (or platform fallback) and a unique reference are shown. Paystack/Stripe: the member is redirected to checkout.
4. A member can mark **I have sent this transfer**; the treasury shows a “Member says paid” badge and optional note.
5. On webhook/callback verification (or admin confirmation), the payment becomes `success`. Admins can also record cash received.
6. Paid counts, collected totals, defaulters and receipts update automatically. Each defaulter has a one-tap WhatsApp reminder.

### Event calendar
1. Any published event has a public `.../calendar` link that returns a valid `.ics` VEVENT in the app timezone.
2. Public groups serve it to anyone; private groups require an active membership.
3. Members can also share the event, or its calendar link, on WhatsApp from the event page and the public group site.

### Welfare contribution
1. A case is opened (for yourself, or by an admin for a member).
2. A contribution creates a payment with purpose `welfare` and a note.
3. When confirmed, exactly one `welfare_contributions` row is written (idempotent via the payment ID), the ledger updates, and the case flips to `funded` at target.

### Election
1. Admin creates a draft election, adds positions and candidates.
2. Admin opens voting (start date is pulled forward if needed).
3. Members see the ballot while the election is live. Voted positions lock.
4. Closing voting freezes positions/candidates and reveals the tally to admins.
5. Publishing makes results visible to every active member.

### Classmate discovery
1. A member opts in at **Profile → Classmates** and adds a confirmed education record.
2. Classof looks only at education records a platform admin has **verified**, matching institution and graduation year.
3. Both the viewer and the person shown must be opted in; anyone who opts out disappears immediately.
4. No phone book or contact upload is used, and there is no platform-wide people search.

### WhatsApp notification
1. A member opts in from **Profile → WhatsApp updates** and saves an international number (`+234…`).
2. When an authorised member publishes an announcement (or the reminder CLI finds an event starting within 24 hours), opted-in active members of that group are queued in `notification_deliveries`.
3. Each row carries an idempotency key, so republishing or re-running the queue cannot duplicate a message.
4. `whatsapp_worker.php` claims pending rows, sends a template through the Cloud API (or writes to the log in `log` mode), and marks the delivery `sent` or `failed` with the error.
5. Meta status callbacks update the provider status. Admins can retry or cancel failed deliveries at `/admin/whatsapp`.

### Group website
1. Each group has one `group_sites` row, created on first access or when an admin approves a new group.
2. Public groups default to **Published**; private groups start as **Draft**.
3. The public page is served at `<subdomain>.<SITE_DOMAIN>` or `/site/{subdomain}` and reads only the group profile, selected announcements, upcoming events and leadership.
4. Owners/admins and the assigned editor can update content, change the handle, toggle sections and publish. Every change is audited.
5. Join and sign-in links point to `APP_URL`, so public visitors enter the normal proof-and-approval flow.

### Chatbot
1. The group-site widget posts a question to `/site/{subdomain}/chat`; the signed-in widget posts to `/assistant/chat`. Both require the session CSRF token.
2. The controller builds a narrow context: public group/page data for visitors, or the member’s own groups and events for signed-in users.
3. `AssistantService` calls the configured AI endpoint when a key exists, otherwise it answers from the built-in guide. AI failures fall back to the guide.
4. Only the last 12 messages are kept in the PHP session. Each session is limited to 30 messages per 10 minutes.
5. No chat transcript is written to MySQL, and public chat never reads private member, payment or welfare data.

### Cooperative loan
1. Member applies against a loan product; system checks savings × multiple and selected guarantors.
2. Guarantors accept or decline from their co-op statement.
3. A loan officer approves once enough guarantors accept, then records disbursement.
4. Disbursement creates the loan-out transaction and the full instalment schedule.
5. Repayments via gateway or officer entry are allocated oldest-instalment first; the loan completes when the balance reaches zero.

Co-op financial integrity:
- Only active co-op members can save, buy shares or borrow.
- Only co-op officers and group admins can issue fines, approve loans, disburse, record manual money or distribute dividends.
- Every gateway payment writes one co-op transaction keyed to the payment ID, so replays and double confirmations are harmless.
- Members only see their own statement; officers see the full ledger.

Vote integrity:
- Active membership required.
- Live window enforced server-side.
- Candidate must belong to the submitted position and be approved.
- `UNIQUE(election_id, position_id, voter_id)` makes double voting impossible, even under concurrent requests.

---

## Security notes

- **Passwords** hashed with `password_hash()`; automatic rehash on login when needed.
- **SQL** uses PDO prepared statements throughout (no string interpolation of user input).
- **CSRF** token required on every state-changing request; webhooks use gateway signatures instead.
- **XSS** output escaped with a global `e()` helper; only intentional HTML (icons/layout) is raw.
- **Role checks** are server-side (`Guard::requireMember`, `requireManager`) — hiding buttons is never the only control.
- **Login throttling**: five failed attempts per email/IP in 15 minutes triggers a temporary lock.
- **Secret ballot**: votes are never joined to member names in any member-facing query.
- **Uploads**: public images (logos, avatars) are verified with `finfo`, renamed randomly, and execution is disabled in `public/uploads`.
- **Proof documents** are stored outside the web root, served only through an authorised controller, sent with `nosniff` and `no-store`, and visible only to the owner or a group manager.
- **Direct access** to `app/`, `config/`, `database/`, `routes/` and `storage/` is denied by `.htaccess`; `.env` and `.sql` files are blocked at the root.
- **Sessions** are HttpOnly, SameSite=Lax, regenerated on login, and use a dedicated cookie name.
- **Audit trail** records group creation, membership requests, proof reviews, announcements, dues, welfare, payments, invites and votes.

### Production checklist
1. Set `APP_ENV=production`, `APP_DEBUG=false` and the real `APP_URL`.
2. Serve over HTTPS and set a strong `APP_KEY`.
3. Use a dedicated MySQL user (not `root`) with only the required privileges.
4. Change the demo passwords or reset the database without seeding.
5. Configure real email delivery and gateway keys; add webhook endpoints.
6. For WhatsApp, create and get Meta approval for the templates, set the Cloud API credentials and app secret, enable delivery in `/admin/settings`, point the webhook at `/webhooks/whatsapp`, and schedule both cron scripts.
7. For group websites, point a wildcard DNS record (`*.your-domain`) and an Apache vhost at the app, set `SITE_DOMAIN`, and use a wildcard TLS certificate.
8. Keep `storage/` writable but outside the public document root where possible.
9. Schedule backups of the database, `public/uploads` and `storage/proofs`.

---

## Known limitations

- WhatsApp delivery requires a Meta Business account, an approved sender number and approved message templates. Only the log driver works without them.
- The WhatsApp integration is outbound only: it sends announcement and event-reminder templates. Inbound replies are not parsed, and free-form messages outside the 24-hour customer service window are not sent.
- Event reminders rely on the hourly cron; if cron is not running, no reminders are queued.
- A delivery left in `processing` after a worker crash is not reclaimed automatically. Set it back to `pending` in the database or add a stale-row sweep to the worker.
- Classmate discovery matches on exact institution and graduation year, rather than faculty, department or programme. Near-miss years and name variants are not matched.
- Group websites are a single landing page, not a full CMS. There is no page builder, blog, gallery, multi-page navigation or revision history.
- A site has one assigned editor. There are no separate editor roles, approvals or change history beyond the audit trail.
- Changing a site subdomain breaks old links. There is no automatic redirect from the previous address.
- Public sites show selected announcements, events and leadership names. There is no per-announcement or per-event public/hidden flag.
- `SITE_DOMAIN` requires wildcard DNS and usually a wildcard TLS certificate. Without it, group sites are reachable only through `/site/{subdomain}`.
- When `AI_API_KEY` is configured, the group profile and description are sent to that external provider when the editor requests an AI draft. Without a key, drafting is local and template-based.
- AI-generated copy can be inaccurate or generic; it is a starting point and must be reviewed by a person before publishing.
- Chatbot rate limiting is session-based, not IP-based, so clearing cookies resets the counter. Put a reverse-proxy rate limit in front for public deployments.
- Chatbot conversations are not persisted, searchable or exportable, and there is no human escalation or ticket creation.
- Chatbot answers are not authoritative for payments, balances, election results or membership status. Members should use the linked pages for those actions.
- When AI is configured, the visitor’s question and the public group context, or the signed-in member’s own context, are sent to that external provider.
- Faculties and departments are generated from a common template the first time an institution is used. The template is not a verified per-institution NUC structure and should be replaced with curated data where accuracy matters.
- Programmes are a national reference list and are display-only; they do not affect matching.
- The institution catalogue is a curated Nigerian snapshot. It has no admin merge/rename workflow yet, and `name_key` is globally unique, so same-named institutions in different states collapse to one row.
- Education records are self-declared and only drive suggestions and classmate matching; platform verification confirms a record but is not an identity check. Membership still requires proof and group-admin approval.
- There is no per-user rate limit on group requests beyond duplicate detection.
- Groups with different scopes are independent; there is no automatic parent-child link between an institution group and its class/department groups yet.
- A cooperative belongs to one alumni group; cross-group or platform-wide cooperatives are not supported yet.
- Co-op dividends are recorded as member distributions but are not paid out through a payment gateway.
- Co-op withdrawals and share purchases by officers are ledger entries; there is no separate maker-checker approval step yet.
- Email uses a log driver by default (no SMTP transport bundled).
- One owner per group; ownership transfer is intentionally restricted to the platform administrator.
- Elections are single-choice per position (no ranked-choice or run-off automation).
- Manual bank transfers rely on an admin or co-op officer confirming the funds.
- A member can mark a manual transfer as sent, but receipt image upload is not part of that flow yet.
- Cash recording is limited to group owners/admins; elected exco members cannot open the treasury or record cash unless they have an admin role.
- Per-group bank details are stored as entered by an admin and are not verified against the bank; use a trusted admin account.
- Event `.ics` files assume the app timezone and a two-hour duration when no end time is set.
- WhatsApp share and chase buttons open WhatsApp with prefilled text; Classof cannot confirm that a message was sent.
- No 2FA, push notifications or mobile apps yet.

---

## License

Provided as-is for your alumni community. Add your preferred license before distributing.
