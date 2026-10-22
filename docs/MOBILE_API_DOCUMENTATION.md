# Schedio Mobile App — Complete Backend API Integration Contract (v1)

**Target Audience:** Mobile Flutter Engineering Team (`zain-saeed-mughal/Schedio-App`)  
**Backend:** Official Schedio Laravel Production Backend  
**Specification Version:** 2.0 (Complete 58-Route Contract — Live & Verified)  
**Date:** October 2026  

---

## 1. Overview & 58-Route Verification Matrix

The Schedio Flutter mobile application synchronizes directly with the central Schedio Laravel REST API. All 58 contract routes are implemented, registered under `/api/v1/*`, and verified via automated test suites.

### Base URLs
| Environment | Base URL | Protocol | Status |
| :--- | :--- | :--- | :--- |
| **Production** | `https://schedio.live/api/v1/` | HTTPS Only | **Active & Live** |
| **Staging** | `https://schedio.live/api/v1/` (with Sandbox Tenant) | HTTPS Only | Active |

### Standard Request Headers
```http
Accept: application/json
Content-Type: application/json
Authorization: Bearer <sanctum_access_token>
```

### Complete Route Inventory (58 / 58 Routes)

| # | Group | Method | Endpoint | Auth | Purpose |
| :- | :--- | :--- | :--- | :--- | :--- |
| 1 | Auth | `POST` | `/api/v1/auth/login` | Public | Authenticate user & issue Sanctum Bearer token |
| 2 | Auth | `POST` | `/api/v1/auth/register` | Public | Submit new company CEO or staff registration request |
| 3 | Auth | `GET` | `/api/v1/auth/registration-options` | Public | Fetch active companies & departments for signup form |
| 4 | Auth | `GET` | `/api/v1/auth/me` | Bearer | Retrieve authenticated profile & permission flags |
| 5 | Auth | `POST` | `/api/v1/auth/logout` | Bearer | Revoke active access token |
| 6 | Dashboard | `GET` | `/api/v1/dashboard` | Bearer | Executive overview (metrics, schedule, tasks, reminders) |
| 7 | Lookups | `GET` | `/api/v1/lookups` | Bearer | Consolidated departments, rooms, users, roles, statuses |
| 8 | Lookups | `GET` | `/api/v1/departments` | Bearer | List active departments |
| 9 | Lookups | `GET` | `/api/v1/users` | Bearer | List active approved team members |
| 10 | Devices | `POST` | `/api/v1/devices` | Bearer | Register or refresh FCM device push token |
| 11 | Devices | `DELETE` | `/api/v1/devices/{token}` | Bearer | Remove FCM device token on sign-out |
| 12 | Meetings | `GET` | `/api/v1/meetings` | Bearer | Paginated meetings list with filter & search |
| 13 | Meetings | `POST` | `/api/v1/meetings` | Bearer | Schedule internal/client/virtual meeting |
| 14 | Meetings | `GET` | `/api/v1/meetings/{id}` | Bearer | Meeting details, attendees, room, and notes |
| 15 | Meetings | `PUT` | `/api/v1/meetings/{id}` | Bearer | Reschedule/update meeting details |
| 16 | Meetings | `PATCH` | `/api/v1/meetings/{id}/cancel` | Bearer | Cancel meeting and notify participants |
| 17 | Meetings | `DELETE` | `/api/v1/meetings/{id}` | Bearer | Delete meeting record |
| 18 | Meetings | `POST` | `/api/v1/meetings/{id}/rsvp` | Bearer | Submit attendee RSVP (`accepted`, `declined`, `tentative`) |
| 19 | Meeting Notes | `GET` | `/api/v1/meetings/{id}/notes` | Bearer | List notes for specific meeting |
| 20 | Meeting Notes | `POST` | `/api/v1/meetings/{id}/notes` | Bearer | Add note to meeting session |
| 21 | Meeting Notes | `GET` | `/api/v1/meeting-notes/{id}` | Bearer | View single meeting note details |
| 22 | Meeting Notes | `PUT` | `/api/v1/meeting-notes/{id}` | Bearer | Update meeting note (agenda, discussion, decisions) |
| 23 | Meeting Notes | `DELETE` | `/api/v1/meeting-notes/{id}` | Bearer | Delete meeting note |
| 24 | Rooms | `GET` | `/api/v1/rooms` | Bearer | List meeting rooms with capacity, color, amenities |
| 25 | Rooms | `POST` | `/api/v1/rooms` | Bearer | Create new meeting suite or focus pod |
| 26 | Rooms | `GET` | `/api/v1/rooms/{id}` | Bearer | Room details and upcoming bookings timeline |
| 27 | Rooms | `PUT` | `/api/v1/rooms/{id}` | Bearer | Update room configuration & capacity |
| 28 | Rooms | `DELETE` | `/api/v1/rooms/{id}` | Bearer | Delete room |
| 29 | Events | `GET` | `/api/v1/events` | Bearer | List holidays, trainings, milestones, deadlines |
| 30 | Events | `POST` | `/api/v1/events` | Bearer | Schedule company or department event |
| 31 | Events | `GET` | `/api/v1/events/{id}` | Bearer | View single event details |
| 32 | Events | `PUT` | `/api/v1/events/{id}` | Bearer | Update event timing or details |
| 33 | Events | `DELETE` | `/api/v1/events/{id}` | Bearer | Delete event |
| 34 | Calendar | `GET` | `/api/v1/calendar/feed` | Bearer | Unified timeline feed of meetings & events |
| 35 | Calendar | `POST` | `/api/v1/availability/check` | Bearer | Detect room or participant conflicts |
| 36 | Calendar | `GET` | `/api/v1/availability/suggestions` | Bearer | Smart suggestion algorithm for conflict-free slots |
| 37 | Clients CRM | `GET` | `/api/v1/clients` | Bearer | Paginated deal pipeline / CRM prospects |
| 38 | Clients CRM | `POST` | `/api/v1/clients` | Bearer | Create new client record & prospect deal |
| 39 | Clients CRM | `GET` | `/api/v1/clients/{id}` | Bearer | View client details and past meetings history |
| 40 | Clients CRM | `PUT` | `/api/v1/clients/{id}` | Bearer | Update client contacts and deal value |
| 41 | Clients CRM | `DELETE` | `/api/v1/clients/{id}` | Bearer | Delete client record |
| 42 | Clients CRM | `PATCH` | `/api/v1/clients/{id}/status` | Bearer | Transition pipeline stage (`lead`, `discovery`, `proposal`, `won`, `lost`) |
| 43 | Clients CRM | `GET` | `/api/v1/clients/{id}/meetings` | Bearer | List all meetings tied to client |
| 44 | Tasks | `GET` | `/api/v1/tasks` | Bearer | Paginated tasks with status & priority filters |
| 45 | Tasks | `POST` | `/api/v1/tasks` | Bearer | Create actionable task / meeting action item |
| 46 | Tasks | `GET` | `/api/v1/tasks/{id}` | Bearer | View task details and assigned owner |
| 47 | Tasks | `PATCH` | `/api/v1/tasks/{id}/status` | Bearer | Transition task status |
| 48 | Notifications | `GET` | `/api/v1/notifications` | Bearer | Paginated notification feed (`all`, `unread`, `read`) |
| 49 | Notifications | `PATCH` | `/api/v1/notifications/{id}/read` | Bearer | Mark notification as read |
| 50 | Notifications | `PATCH` | `/api/v1/notifications/{id}/unread` | Bearer | Mark notification as unread |
| 51 | Notifications | `DELETE` | `/api/v1/notifications/{id}` | Bearer | Dismiss notification |
| 52 | Notifications | `POST` | `/api/v1/notifications/mark-all-read` | Bearer | Bulk mark all notifications as read |
| 53 | Notifications | `GET` | `/api/v1/notifications/preferences` | Bearer | User notification channel preferences |
| 54 | Notifications | `POST` | `/api/v1/notifications/preferences` | Bearer | Update channel & category toggles |
| 55 | Admin | `GET` | `/api/v1/admin/registration-requests` | Bearer | List pending/approved/rejected registration requests |
| 56 | Admin | `GET` | `/api/v1/admin/registration-requests/{id}/options` | Bearer | Available roles & departments for approval |
| 57 | Admin | `PATCH` | `/api/v1/admin/registration-requests/{id}/approve` | Bearer | Approve registration request & assign role/department |
| 58 | Admin | `PATCH` | `/api/v1/admin/registration-requests/{id}/reject` | Bearer | Reject registration request with reason |

*(Public booking routes `GET /api/v1/public/booking/{username}`, `GET .../slots`, `POST ...` are also supported).*

---

## 2. Authentication & Registration Specifications

### A. Registration Options
- **Endpoint:** `GET /api/v1/auth/registration-options`
- **Auth:** None (Public)
- **Response (`200 OK`):**
```json
{
  "companies": [
    { "id": 1, "name": "Schedio Technologies HQ", "slug": "schedio-hq" }
  ],
  "departments": [
    { "id": 1, "name": "Engineering & Technology", "code": "ENG", "company_id": 1 },
    { "id": 2, "name": "Business Development & Sales", "code": "BD", "company_id": 1 }
  ]
}
```

### B. Submit Registration
- **Endpoint:** `POST /api/v1/auth/register`
- **Auth:** None (Public)
- **Employee Payload:**
```json
{
  "name": "Jane Doe",
  "email": "jane.doe@example.com",
  "password": "Password123!",
  "password_confirmation": "Password123!",
  "company_id": 1,
  "department_id": 1,
  "title": "Flutter Mobile Architect",
  "phone": "+1-555-0199",
  "registration_notes": "Joining mobile app team."
}
```
- **New Company CEO Payload:**
```json
{
  "registration_type": "company_super_admin",
  "company_name": "Nexus Innovations Ltd",
  "name": "David Sterling",
  "email": "david@nexusinnovations.com",
  "password": "Password123!",
  "password_confirmation": "Password123!",
  "title": "CEO & Founder"
}
```
- **Response (`201 Created`):**
```json
{
  "message": "Your staff registration request has been submitted successfully! Your Company Super Admin will review and approve your account shortly.",
  "account_status": "pending",
  "data": {
    "id": 15,
    "name": "Jane Doe",
    "email": "jane.doe@example.com",
    "role": "employee",
    "approval_status": "pending"
  }
}
```

### C. Login (Sanctum Bearer Token)
- **Endpoint:** `POST /api/v1/auth/login`
- **Payload:**
```json
{
  "email": "mobile.tester@schedio.live",
  "password": "<new_credential>",
  "device_name": "Pixel 8 Pro"
}
```
- **Response (`200 OK`):**
```json
{
  "token": "2|3c89f...",
  "user": {
    "id": 1,
    "name": "Mobile Tester",
    "email": "mobile.tester@schedio.live",
    "role": "super_admin",
    "role_label": "Company Super Admin",
    "timezone": "UTC",
    "can_administer": true,
    "can_manage_rooms": true,
    "company": { "id": 1, "name": "Schedio Technologies HQ", "slug": "schedio-hq" },
    "department": { "id": 1, "name": "Engineering & Technology", "code": "ENG" }
  }
}
```

---

## 3. Executive Dashboard & Consolidated Lookups

### A. Dashboard Overview
- **Endpoint:** `GET /api/v1/dashboard`
- **Auth:** Bearer
- **Response (`200 OK`):**
```json
{
  "metrics": {
    "pending_requests": 2,
    "today_meetings_count": 3,
    "total_users": 18,
    "upcoming_meetings": 6,
    "client_meetings": 2,
    "active_reminders": 4,
    "pending_tasks": 5,
    "unread_notifications": 3
  },
  "today_meetings": [
    {
      "id": 5,
      "title": "Sprint Planning & Standup",
      "start_time": "2026-10-23T09:00:00Z",
      "end_time": "2026-10-23T10:00:00Z",
      "status": "scheduled",
      "room": { "id": 1, "name": "Executive Boardroom A" },
      "organizer": { "id": 1, "name": "Alex Rivera" }
    }
  ],
  "today_events": [],
  "upcoming_meetings": [],
  "next_meeting": null,
  "pending_tasks": [
    {
      "id": 8,
      "title": "Review Mobile API Release Contract",
      "priority": "urgent",
      "status": "pending",
      "due_date": "2026-10-24T18:00:00Z"
    }
  ],
  "upcoming_reminders": []
}
```

### B. Consolidated Lookups
- **Endpoint:** `GET /api/v1/lookups`
- **Auth:** Bearer
- **Response (`200 OK`):** Returns all application lookup tables in one call for offline caching:
```json
{
  "departments": [...],
  "rooms": [...],
  "users": [...],
  "companies": [...],
  "roles": [
    { "key": "super_admin", "label": "Company Super Admin" },
    { "key": "manager", "label": "Department Manager" },
    { "key": "employee", "label": "Team Member / Staff" },
    { "key": "client", "label": "Client / External Partner" }
  ],
  "task_statuses": [
    { "key": "todo", "label": "To Do" },
    { "key": "pending", "label": "Pending" },
    { "key": "in_progress", "label": "In Progress" },
    { "key": "review", "label": "In Review" },
    { "key": "completed", "label": "Completed" },
    { "key": "cancelled", "label": "Cancelled" }
  ],
  "task_priorities": [
    { "key": "low", "label": "Low Priority" },
    { "key": "medium", "label": "Medium Priority" },
    { "key": "high", "label": "High Priority" },
    { "key": "urgent", "label": "Urgent Priority" }
  ],
  "event_categories": [
    { "key": "company_holiday", "label": "Company Holiday", "color": "#ec4899" },
    { "key": "training", "label": "Training & Development", "color": "#8b5cf6" },
    { "key": "milestone", "label": "Milestone / Deliverable", "color": "#f59e0b" },
    { "key": "deadline", "label": "Project Deadline", "color": "#ef4444" },
    { "key": "out_of_office", "label": "Out of Office", "color": "#64748b" },
    { "key": "general", "label": "General Event", "color": "#3b82f6" }
  ]
}
```

---

## 4. Meeting Notes Specification

### Official Request & Response Fields
| Field Name | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `agenda` | string | Optional | Planned meeting agenda or discussion topic |
| `discussion` | string | Optional | Body notes, minutes, and conversation summary |
| `decisions` | string | Optional | Key consensus and approved outcomes |
| `action_items` | string/array | Optional | Assigned next steps (newline-delimited string or array) |
| `follow_up_date` | string (ISO-8601) | Optional | Next scheduled check-in or deadline |
| `attachments` | array of objects | Optional | Linked documents `[{"name": "Spec", "url": "https://...", "text": "..."}]` |
| `attachments_text`| string | Optional | Comma/newline separated URLs or document names |

### Endpoints
- **List Meeting Notes:** `GET /api/v1/meetings/{meeting_id}/notes`
- **Create Note for Meeting:** `POST /api/v1/meetings/{meeting_id}/notes`
- **Get Note by ID:** `GET /api/v1/meeting-notes/{id}`
- **Update Note:** `PUT /api/v1/meeting-notes/{id}`
- **Delete Note:** `DELETE /api/v1/meeting-notes/{id}`

- **Create Note Request:**
```json
{
  "agenda": "Mobile Contract Verification Review",
  "discussion": "Reviewed 58 endpoints and verified Flutter integration.",
  "decisions": "Approved production deployment.",
  "action_items": "1. Run full contract check.\n2. Verify write endpoints.",
  "follow_up_date": "2026-10-25T15:00:00Z",
  "attachments": [
    { "name": "API Spec", "url": "https://schedio.live/docs/MOBILE_API_DOCUMENTATION.md" }
  ]
}
```

- **Note Response (`200 OK` / `201 Created`):**
```json
{
  "data": {
    "id": 4,
    "meeting_id": 1,
    "user_id": 1,
    "agenda": "Mobile Contract Verification Review",
    "discussion": "Reviewed 58 endpoints and verified Flutter integration.",
    "decisions": "Approved production deployment.",
    "action_items": "1. Run full contract check.\n2. Verify write endpoints.",
    "follow_up_date": "2026-10-25T15:00:00Z",
    "attachments": [
      { "name": "API Spec", "url": "https://schedio.live/docs/MOBILE_API_DOCUMENTATION.md" }
    ],
    "author": {
      "id": 1,
      "name": "Alex Rivera",
      "email": "admin@schedio.com"
    },
    "created_at": "2026-10-22T20:30:00Z",
    "updated_at": "2026-10-22T20:30:00Z"
  }
}
```

---

## 5. Tasks & Action Items

### Supported Task Statuses
The backend supports the following canonical status enum values:
- `todo` — Newly created task in backlog
- `pending` — Awaiting execution or assignee kickoff
- `in_progress` — Active development or work in flight
- `review` — Work submitted and undergoing review
- `completed` — Task finished (automatically records `completed_at` timestamp)
- `cancelled` — Task dismissed or aborted

### Supported Task Priorities
- `low`
- `medium` (default)
- `high`
- `urgent`

### Endpoints
- **List Tasks:** `GET /api/v1/tasks?status=in_progress&priority=high&per_page=25`
- **Create Task:** `POST /api/v1/tasks`
```json
{
  "title": "Implement Flutter Offline Cache",
  "description": "Store lookups and calendar feed in secure storage.",
  "assigned_to": 2,
  "meeting_id": 1,
  "priority": "high",
  "due_date": "2026-10-28T18:00:00Z"
}
```
- **Update Status:** `PATCH /api/v1/tasks/{id}/status`
```json
{
  "status": "completed"
}
```

---

## 6. Meeting Rooms & Focus Pods

- **List Rooms:** `GET /api/v1/rooms?search=Tower&status=active`
- **Get Room Details & Upcoming Sessions:** `GET /api/v1/rooms/{id}`
- **Create Room (Admin/Manager):** `POST /api/v1/rooms`
```json
{
  "name": "Apollo Presentation Theatre",
  "code": "APOLLO-01",
  "location": "Level 4, East Wing",
  "capacity": 45,
  "color": "#8b5cf6",
  "amenities": ["4K Laser Projector", "Dolby Atmos AV", "Dual Microphones"]
}
```
- **Update Room:** `PUT /api/v1/rooms/{id}`
- **Delete Room:** `DELETE /api/v1/rooms/{id}`

---

## 7. Company Events, Holidays, & Training

- **List Events:** `GET /api/v1/events?category=training&start_date=2026-10-01&end_date=2026-10-31`
- **Create Event:** `POST /api/v1/events`
```json
{
  "title": "Flutter 3.x Advanced Architecture Workshop",
  "description": "Hands-on engineering workshop on state management.",
  "category": "training",
  "start_time": "2026-10-27T10:00:00Z",
  "end_time": "2026-10-27T14:00:00Z",
  "is_all_day": false,
  "location": "Main Conference Hall",
  "is_company_wide": true
}
```
- **Get Event:** `GET /api/v1/events/{id}`
- **Update Event:** `PUT /api/v1/events/{id}`
- **Delete Event:** `DELETE /api/v1/events/{id}`

---

## 8. Administrative Registration Requests

Super Administrators can manage staff and applicant approvals directly from the mobile app:
- **List Requests:** `GET /api/v1/admin/registration-requests?status=pending`
- **Approval Assignment Options:** `GET /api/v1/admin/registration-requests/{id}/options`
  *(Returns applicant details alongside available roles and active departments for assignment).*
- **Approve Request:** `PATCH /api/v1/admin/registration-requests/{id}/approve`
```json
{
  "role": "employee",
  "department_id": 1,
  "title": "Senior Mobile Developer"
}
```
- **Reject Request:** `PATCH /api/v1/admin/registration-requests/{id}/reject`
```json
{
  "rejection_reason": "Unable to verify previous employment credentials."
}
```

---

## 9. Pagination & Permission Standard

### Pagination Standard
All list responses (`/meetings`, `/events`, `/clients`, `/tasks`, `/notifications`, `/admin/registration-requests`) adhere to standard Laravel pagination:
```json
{
  "data": [ ... ],
  "meta": {
    "current_page": 1,
    "last_page": 4,
    "per_page": 25,
    "total": 92
  },
  "links": {
    "first": "https://schedio.live/api/v1/tasks?page=1",
    "last": "https://schedio.live/api/v1/tasks?page=4",
    "prev": null,
    "next": "https://schedio.live/api/v1/tasks?page=2"
  }
}
```

### Permission Fields
- `user.can_administer`: Boolean flag on user profile (`true` for Super Admin & Developer). Allows access to `/admin/*` and workspace management.
- `user.can_manage_rooms`: Boolean flag (`true` for Super Admin & Department Managers). Allows configuring rooms.
- `meeting.can_manage`: Boolean flag on meeting objects (`true` if user is Organizer or Admin). Allows editing/cancelling.
- `meeting.can_rsvp`: Boolean flag (`true` if user is a listed attendee).

---

## 10. Firebase Cloud Messaging (FCM) & Reminder Integration

### A. Device Registration
When the user logs in on Flutter, register the FCM push token:
- **Endpoint:** `POST /api/v1/devices`
- **Payload:**
```json
{
  "token": "eX_fcm_device_token_xyz...",
  "platform": "android"
}
```
*(Supports `android` and `ios`).*

### B. Push Dispatch Triggers
The backend automatically sends FCM push notifications for:
1. **Meeting Invitations:** When added as a participant.
2. **Meeting Updates & Cancellations:** Real-time push alert to all participants.
3. **RSVP Confirmations:** Instant push to the organizer when an attendee accepts/declines.
4. **Task Assignment & Status Changes:** Notifies assignee and creator.
5. **Scheduled Meeting Reminders:** Triggered 15/30/60 minutes before start time via the background daemon:
```bash
php artisan schedio:process-reminders
```

---

## 11. Staging Environment & Safe Testing Isolation

To conduct safe authenticated write tests without compromising live website data:
1. **Dedicated Testing Company (Sandbox Tenant):**
   - The test account `mobile.tester@schedio.live` is scoped to a dedicated tenant workspace.
   - Any meetings, events, rooms, clients, or tasks created during Flutter testing are completely isolated from production business users via multi-tenant global scopes (`BelongsToTenant`).
2. **Safe Test Data Cleanup:**
   - Any test entities created with titles starting with `[TEST]` can be automatically purged or soft-deleted via the API `DELETE` endpoints.

---

## 12. Rotated Test Credentials

The previously exposed API token has been revoked and deleted from the database. Use the updated replacement credentials:

| Role | Email | Password | Access Level |
| :--- | :--- | :--- | :--- |
| **Super Admin / Tester** | `mobile.tester@schedio.live` | `Schedio#Test2026!Secure` | Full administrative access, room management, user approval |
| **Staff Member (Scoped)** | `staff.tester@schedio.live` | `Schedio#Staff2026!Secure` | Regular employee access for testing role isolation & permissions |

### Running the Flutter Contract Checker
From your Flutter repository root:
```bash
# 1. Unauthenticated route check
dart run tool/api_contract_check.dart https://schedio.live/api/v1/

# 2. Authenticated contract check
SCHEDIO_TOKEN="<token_from_login>" dart run tool/api_contract_check.dart https://schedio.live/api/v1/
```
All 58 routes will now return expected status codes (`200`, `201`, `204`, `401`, `403` with valid JSON).
