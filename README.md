# University of Ibadan Digital File Management System (DFMS)

A web portal for students, administrators, and lecturers to manage academic documents.

## Project Overview

The University of Ibadan DFMS is a web-based system that allows:

- Students to upload academic documents (fees receipts, course registration forms, O'Level results, birth certificates)
- Administrators to verify, approve, or reject uploads
- Lecturers to view only verified documents

## Tech Stack

- HTML5 for markup
- CSS3 for styling
- Vanilla JavaScript (ES6+) for interactivity & state
- Supabase JS Client via CDN for Auth, Database, and Storage (Phase 2)
- No build tools—all files served statically

## Project Structure

```
DFMS/
├── index.html               # Login page
├── src/
│   ├── assets/
│   │   └── images/          # Images and icons
│   ├── css/
│   │   ├── global.css       # Global reset and base styles
│   │   ├── style.css        # Main styling for the login page
│   │   └── styleguide.css   # Design tokens and variables
│   ├── js/
│   │   └── login.js         # Login page functionality
│   └── pages/               # Other HTML pages (to be added)
└── README.md                # Project documentation
```

## Development Phases

### Phase 1 – Frontend Development
- [x] Stage 1: Login & Authentication screen
- [ ] Stage 2: Role-based Dashboard (student, admin, lecturer)
- [ ] Stage 3: Document Upload Form
- [ ] Stage 4: Verification Queue & Detail View (admin)
- [ ] Stage 5: Search & Document Preview (lecturer)

### Phase 2 – Backend Integration
- [ ] Hook static pages up to Supabase Auth and Realtime DB
- [ ] Configure Row-Level Security policies

### Phase 3 – Core File Management
- [ ] File upload, versioning, metadata entry, tagging
- [ ] Notifications (email/SMS/webhooks)

### Phase 4 – Admin & Reporting
- [ ] Audit trail UI, reporting dashboards, role management

### Phase 5 – QA & Accessibility
- [ ] WCAG 2.1 AA compliance, performance tuning, cross-browser testing

### Phase 6 – Deployment & Handover
- [ ] Docker static host configs, CI/CD scripts, training docs

## Getting Started

1. Replace the placeholder logo images in `src/assets/images/` with the actual University of Ibadan logo files:
   - `ui-logo.png` - The logo for the sidebar (142px × 152px)
   - `ui-logo-watermark.png` - The logo for the background watermark (568px × 608px)

2. Open `index.html` in a web browser to view the login page.

## Notes for Future Development

- The login page is currently static with client-side validation only.
- Backend integration with Supabase will be implemented in Phase 2.
- Role-based access control will be implemented once the dashboard pages are created.
