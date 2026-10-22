# Schedio Mobile App — Backend API Integration Guide (v1)

**Target Audience:** Mobile Flutter Engineering Team (`zain-saeed-mughal/Schedio-App`)  
**Backend:** Official Schedio Laravel Production Backend  
**Document Version:** 1.0 (Live & Verified)  
**Date:** October 2026  

---

## 1. Overview & Connection Settings

The Flutter mobile application does not have a local database; it reads and writes all records directly from the central Schedio Laravel backend. This ensures real-time parity between the Web and Mobile apps for multi-tenancy, companies, users, calendar meetings, client pipelines, tasks, and notifications.

### Base URLs
| Environment | Base URL | Protocol | Status |
| :--- | :--- | :--- | :--- |
| **Production** | `https://schedio.live/api/v1/` | HTTPS Only | **Active & Live** |
| **Staging** | `https://staging.schedio.live/api/v1/` | HTTPS Only | Active |

### Standard Request Headers
For every HTTP call, include the following headers:
```http
Accept: application/json
Content-Type: application/json
```
For all protected routes, include the Sanctum Bearer token:
```http
Authorization: Bearer <your_access_token>
```

> [!NOTE]
> All error responses return structured JSON with a `message` and an optional `errors` object.

---

## 2. Authentication Flow (Laravel Sanctum)

### A. Sign In (Get Bearer Token)
- **Endpoint:** `POST /api/v1/auth/login`
- **Auth:** None (Public)
- **Request Body:**
```json
{
  "email": "admin@schedio.com",
  "password": "password",
  "device_name": "Pixel 8 Pro"
}
```

- **Successful Response (`200 OK`):**
```json
{
  "token": "1|9f3c8a7b...",
  "user": {
    "id": 1,
    "name": "Alex Rivera",
    "email": "admin@schedio.com",
    "role": "developer",
    "role_label": "Platform Developer",
    "timezone": "America/New_York",
    "phone": "+1 (555) 019-2834",
    "title": "Chief Technology Officer",
    "avatar_url": null,
    "company": {
      "id": 1,
      "name": "Schedio Technologies HQ",
      "slug": "schedio-hq"
    },
    "department": {
      "id": 1,
      "name": "Engineering & Technology",
      "code": "ENG"
    },
    "booking_url": "https://schedio.live/book/admin",
    "booking_username": "admin",
    "can_administer": true,
    "can_manage_rooms": true,
    "notification_preferences": {
      "channels": { "in_app": true, "email": true },
      "categories": { "meetings": { "in_app": true, "email": true } }
    }
  }
}
```

- **Error Responses:**
  - `401 Unauthorized`: Wrong email or password.
    ```json
    { "message": "Invalid email or password." }
    ```
  - `403 Forbidden`: Account not approved or deactivated. Carries `account_status` (`pending`, `rejected`, or `inactive`).
    ```json
    {
      "message": "Your account registration is pending administrator approval.",
      "account_status": "pending"
    }
    ```
  - `422 Unprocessable Entity`: Validation failure on email or password.
  - `429 Too Many Requests`: Rate throttled.

### B. Current User Profile
- **Endpoint:** `GET /api/v1/auth/me`
- **Auth:** `Bearer <token>`
- **Response (`200 OK`):**
```json
{
  "data": {
    "id": 1,
    "name": "Alex Rivera",
    "email": "admin@schedio.com",
    "role": "developer",
    "role_label": "Platform Developer",
    "timezone": "America/New_York",
    "company": { "id": 1, "name": "Schedio Technologies HQ", "slug": "schedio-hq" },
    "department": { "id": 1, "name": "Engineering & Technology", "code": "ENG" },
    "booking_url": "https://schedio.live/book/admin",
    "booking_username": "admin",
    "can_administer": true,
    "can_manage_rooms": true,
    "notification_preferences": { ... }
  }
}
```

### C. Sign Out (Revoke Token)
- **Endpoint:** `POST /api/v1/auth/logout`
- **Auth:** `Bearer <token>`
- **Response (`200 OK`):**
```json
{ "message": "Logged out successfully." }
```

---

## 3. Push Notification Device Tokens (FCM)

### A. Register or Refresh Device Token
- **Endpoint:** `POST /api/v1/devices`
- **Auth:** `Bearer <token>`
- **Request Body:**
```json
{
  "token": "dK8_fcm_registration_token_here...",
  "platform": "android" 
}
```
*(Platform must be `android` or `ios`)*
- **Response:** `201 Created` (if new) or `200 OK` (if refreshed).

### B. Unregister Device on Sign-Out
- **Endpoint:** `DELETE /api/v1/devices/{token}`
- **Auth:** `Bearer <token>`
- **Response:** `204 No Content`
*(Call this right before calling `/auth/logout`)*.

---

## 4. Meetings & Workspaces

### A. List Meetings
- **Endpoint:** `GET /api/v1/meetings`
- **Query Params:**
  - `status`: `all`, `scheduled`, `completed`, `cancelled` (Default: `all`)
  - `scope`: `all`, `internal`, `external`, `organized_by_me` (Default: `all`)
  - `search`: Keyword string
  - `per_page`: Integer (Default: `15`)
- **Response (`200 OK`):**
```json
{
  "data": [
    {
      "id": 1,
      "title": "Q4 Sprint Planning & Architecture Review",
      "description": "Technical roadmap discussion",
      "start_time": "2026-10-23T14:00:00+00:00",
      "end_time": "2026-10-23T15:30:00+00:00",
      "timezone": "America/New_York",
      "status": "scheduled",
      "type": "hybrid",
      "location": "Apollo Executive Boardroom",
      "meeting_link": "https://meet.schedio.com/eng-sprint",
      "is_private": false,
      "can_manage": true,
      "can_rsvp": true,
      "organizer": { "id": 1, "name": "Alex Rivera", "email": "admin@schedio.com" },
      "department": { "id": 1, "name": "Engineering & Technology" },
      "room": { "id": 1, "name": "Apollo Executive Boardroom", "location": "Floor 4" },
      "participants": [
        {
          "id": 1,
          "name": "Alex Rivera",
          "role": "host",
          "status": "accepted",
          "response_notes": null
        }
      ],
      "metadata": {}
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 1
  }
}
```

### B. Get Single Meeting
- **Endpoint:** `GET /api/v1/meetings/{id}`
- **Response (`200 OK`):** Detailed meeting object including linked tasks, notes, and client info.

### C. Create Meeting (With Conflict Detection)
- **Endpoint:** `POST /api/v1/meetings`
- **Request Body:**
```json
{
  "title": "Client Architecture Onboarding",
  "description": "Initial system integration kickoff",
  "start_time": "2026-10-25T14:00:00Z",
  "end_time": "2026-10-25T15:00:00Z",
  "timezone": "America/New_York",
  "department_id": 1,
  "room_id": 1,
  "client_id": null,
  "type": "hybrid",
  "meeting_link": "https://meet.schedio.com/room-xyz",
  "location": "Apollo Boardroom",
  "is_private": false,
  "is_client_meeting": false,
  "reminder_minutes": 15,
  "participant_ids": [2, 3]
}
```
- **Success Response:** `201 Created` with created meeting data.
- **Conflict Response (`422 Unprocessable Entity`):**
```json
{
  "message": "Scheduling conflict detected for the selected time slot.",
  "errors": {
    "time": ["Scheduling conflict detected for the selected time slot."]
  },
  "conflicts": {
    "has_conflicts": true,
    "room": { "status": "conflict", "conflict": { "title": "Existing Meeting" } },
    "participants": [ ... ]
  }
}
```

### D. Update Meeting
- **Endpoint:** `PUT /api/v1/meetings/{id}`
- **Response:** `200 OK` (Re-checks conflicts excluding current meeting).

### E. Cancel Meeting
- **Endpoint:** `PATCH /api/v1/meetings/{id}/cancel`
- **Response (`200 OK`):** Automatically cancels upcoming reminders and dispatches notifications.

### F. Delete Meeting
- **Endpoint:** `DELETE /api/v1/meetings/{id}`
- **Response:** `204 No Content`.

### G. Submit RSVP
- **Endpoint:** `POST /api/v1/meetings/{id}/rsvp`
- **Request Body:**
```json
{
  "status": "accepted", 
  "response_notes": "Will join 5 minutes early."
}
```
*(Status must be: `accepted`, `declined`, or `tentative`)*
- **Response (`200 OK`):** `{"message": "RSVP submitted successfully.", "status": "accepted"}`

### H. Meeting Notes
- **List Notes:** `GET /api/v1/meetings/{id}/notes`
- **Add Note:** `POST /api/v1/meetings/{id}/notes`
  ```json
  {
    "title": "Sprint Key Takeaways",
    "content": "Decided to move Redis cluster to port 6380.",
    "action_items": ["Run benchmark tests", "Update env file"]
  }
  ```

---

## 5. Calendar & Availability Engine

### A. Calendar Feed (Meetings + Events)
- **Endpoint:** `GET /api/v1/calendar/feed`
- **Query Params:**
  - `start`: ISO 8601 start timestamp (e.g. `2026-10-01T00:00:00Z`)
  - `end`: ISO 8601 end timestamp (e.g. `2026-10-31T23:59:59Z`)
  - `type`: `all`, `meetings`, `events` (Default: `all`)
- **Response (`200 OK`):**
```json
{
  "data": [
    {
      "id": 1,
      "entity_type": "meeting",
      "title": "Q4 Sprint Planning",
      "start_time": "2026-10-23T14:00:00+00:00",
      "end_time": "2026-10-23T15:30:00+00:00",
      "status": "scheduled",
      "type": "hybrid",
      "location": "Apollo Boardroom",
      "color": "#6366f1",
      "organizer": "Alex Rivera",
      "room": "Apollo Executive Boardroom"
    },
    {
      "id": 1,
      "entity_type": "event",
      "title": "Annual Schedio Engineering Summit",
      "start_time": "2026-10-29T09:00:00+00:00",
      "end_time": "2026-10-29T18:00:00+00:00",
      "status": "confirmed",
      "category": "milestone",
      "color": "#6366f1",
      "is_all_day": true
    }
  ],
  "count": 2
}
```

### B. Pre-Flight Availability Check
- **Endpoint:** `POST /api/v1/availability/check`
- **Request Body:**
```json
{
  "start_time": "2026-10-24T10:00:00Z",
  "end_time": "2026-10-24T11:00:00Z",
  "room_id": 1,
  "participant_ids": [2, 3],
  "exclude_meeting_id": null
}
```
- **Response (`200 OK`):**
```json
{
  "has_conflicts": false,
  "room": { "status": "available", "room_id": 1 },
  "participants": [
    { "user_id": 2, "status": "available" },
    { "user_id": 3, "status": "available" }
  ]
}
```

### C. Smart Slot Suggestions
- **Endpoint:** `GET /api/v1/availability/suggestions`
- **Query Params:** `date=2026-10-24&duration_minutes=45&room_id=1&participant_ids[]=2`
- **Response (`200 OK`):**
```json
{
  "slots": [
    { "start_time": "2026-10-24T09:00:00Z", "end_time": "2026-10-24T09:45:00Z" },
    { "start_time": "2026-10-24T11:30:00Z", "end_time": "2026-10-24T12:15:00Z" }
  ],
  "count": 2
}
```

---

## 6. Clients CRM Pipeline

### A. List Pipeline Clients
- **Endpoint:** `GET /api/v1/clients`
- **Query Params:**
  - `status`: `discovery`, `scheduled`, `paperwork_onboarding`, `onboarded`, `deadend`, `pause`
  - `search`: Company or contact name query
  - `per_page`: `100` (Use 100 for Kanban column board counts)
- **Response (`200 OK`):** Includes `meta.total` on each status query.
```json
{
  "data": [
    {
      "id": 1,
      "company_name": "Acme Global Logistics",
      "contact_name": "David Sterling",
      "contact_email": "david@acme.com",
      "contact_phone": "+1 555-0158",
      "contact_role": "Enterprise Lead",
      "campaign": "Direct Outreach",
      "progress": "Contract in legal review",
      "status": "paperwork_onboarding",
      "deal_value": 75000.00,
      "notes": "Interested in 500 seat rollout.",
      "last_activity_at": "2026-10-22T12:00:00+00:00",
      "assigned_to": { "id": 1, "name": "Alex Rivera" },
      "created_by": { "id": 1, "name": "Alex Rivera" }
    }
  ],
  "meta": {
    "total": 1,
    "current_page": 1,
    "per_page": 100
  }
}
```

### B. Create Client
- **Endpoint:** `POST /api/v1/clients`
- **Request Body:**
```json
{
  "company_name": "Nexus Dynamics",
  "contact_name": "Sarah Connor",
  "contact_email": "sarah@nexus.io",
  "status": "discovery",
  "deal_value": 35000
}
```
- **Response:** `201 Created`.

### C. Update Pipeline Stage / Progress
- **Endpoint:** `PATCH /api/v1/clients/{id}/status`
- **Request Body:**
```json
{
  "status": "scheduled",
  "progress": "Kickoff meeting scheduled for Tuesday.",
  "deal_value": 45000
}
```
- **Response:** `200 OK`.

### D. Get Client Meetings
- **Endpoint:** `GET /api/v1/clients/{id}/meetings`

---

## 7. Actionable Tasks

- **List Tasks:** `GET /api/v1/tasks?status=&priority=&meeting_id=&per_page=25`
- **Create Task:** `POST /api/v1/tasks`
  ```json
  {
    "title": "Review PostgreSQL Partition Plan",
    "description": "Analyze table fragmentation for tenant tables.",
    "assigned_to": 3,
    "meeting_id": 1,
    "priority": "high",
    "due_date": "2026-10-28T17:00:00Z"
  }
  ```
- **Get Task:** `GET /api/v1/tasks/{id}`
- **Transition Task Status:** `PATCH /api/v1/tasks/{id}/status`
  ```json
  { "status": "completed" }
  ```
  *(Status must be: `pending`, `in_progress`, or `completed`)*

---

## 8. Public Booking (No Bearer Token Required)

Public bookings allow external prospects to book meetings via an organizer's booking link.

- **Host Information:** `GET /api/v1/public/booking/{username}`
- **Available Time Slots:** `GET /api/v1/public/booking/{username}/slots?date=2026-10-25&duration=30`
- **Book Slot:** `POST /api/v1/public/booking/{username}`
  ```json
  {
    "client_name": "Bruce Wayne",
    "client_email": "bruce@wayneenterprises.com",
    "client_company": "Wayne Enterprises",
    "start_time": "2026-10-25T14:00:00Z",
    "duration_minutes": 30,
    "notes": "Confidential tech advisory meeting."
  }
  ```
  - **Response (`201 Created`):**
  ```json
  {
    "message": "Appointment booked successfully.",
    "data": {
      "meeting_id": 14,
      "title": "Consultation: Bruce Wayne & Alex Rivera",
      "start_time": "2026-10-25T14:00:00Z",
      "end_time": "2026-10-25T14:30:00Z",
      "meeting_link": "https://meet.schedio.com/room-abc",
      "calendar_export_url": "https://schedio.live/book/admin/export-ical/14?signature=..."
    }
  }
  ```

---

## 9. Notification Center & Preferences

- **List Notifications:** `GET /api/v1/notifications?filter=all&per_page=20` (Supports `all`, `unread`, `read`)
- **Mark as Read:** `PATCH /api/v1/notifications/{id}/read`
- **Mark as Unread:** `PATCH /api/v1/notifications/{id}/unread`
- **Mark All Read:** `POST /api/v1/notifications/mark-all-read`
- **Delete Notification:** `DELETE /api/v1/notifications/{id}`
- **Get Preferences:** `GET /api/v1/notifications/preferences`
- **Update Preferences:** `POST /api/v1/notifications/preferences`
  ```json
  {
    "channels": { "in_app": true, "email": false },
    "categories": { "meetings": { "in_app": true, "email": false } }
  }
  ```

---

## 10. Dropdowns & Lookup Data

Use these endpoints to populate meeting creation and assignment forms:
- **Departments:** `GET /api/v1/departments`
- **Meeting Rooms:** `GET /api/v1/rooms` (includes room `color`, `capacity`, and `amenities`)
- **Team Members:** `GET /api/v1/users` (includes `role`, `department_id`, and `avatar_url`)

---

## 11. Test Credentials & Verification

Use these accounts to test login, permissions, and tenant isolation:

| Company | Role | Email | Password |
| :--- | :--- | :--- | :--- |
| **Schedio Technologies HQ** | Developer / Super Admin | `admin@schedio.com` | `password` |
| **Schedio Technologies HQ** | Staff Employee | `marcus.v@schedio.com` | `password` |
| **Apex Global Logistics** | Company CEO (Super Admin) | `ceo@apexlogistics.com` | `password` |

### Running the Contract Verification Tool
From the `schedio_mobile` folder in your repository:
```bash
dart run tool/api_contract_check.dart https://schedio.live/api/v1/
```
With an authentication token:
```bash
SCHEDIO_TOKEN="<token_from_login>" dart run tool/api_contract_check.dart https://schedio.live/api/v1/
```
