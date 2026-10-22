<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property int $id
 * @property int|null $user_id
 * @property string $action
 * @property string|null $auditable_type
 * @property int|null $auditable_id
 * @property string|null $description
 * @property array<array-key, mixed>|null $old_values
 * @property array<array-key, mixed>|null $new_values
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $company_id
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent|null $auditable
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereAuditableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereAuditableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereNewValues($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereOldValues($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereUserAgent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereUserId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperAuditLog {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $company_name
 * @property string $contact_name
 * @property string|null $contact_email
 * @property string|null $contact_phone
 * @property string|null $contact_role
 * @property string|null $campaign
 * @property string|null $progress
 * @property string $status
 * @property int|null $assigned_to
 * @property int|null $created_by
 * @property numeric|null $deal_value
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $last_activity_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int|null $company_id
 * @property-read \App\Models\User|null $assignedTo
 * @property-read \App\Models\Company|null $company
 * @property-read \App\Models\User|null $creator
 * @property-read string|null $next_action_label
 * @property-read string|null $next_status
 * @property-read int $pipeline_step_index
 * @property-read string $status_label
 * @property-read array $status_meta
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Meeting> $meetings
 * @property-read int|null $meetings_count
 * @property-read \App\Models\Meeting|null $upcomingMeeting
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereAssignedTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereCampaign($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereCompanyName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereContactEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereContactName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereContactPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereContactRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereDealValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereLastActivityAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereProgress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperClient {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $domain
 * @property string $status
 * @property int|null $created_by
 * @property array<array-key, mixed>|null $settings
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Client> $clients
 * @property-read int|null $clients_count
 * @property-read \App\Models\User|null $creator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Department> $departments
 * @property-read int|null $departments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Event> $events
 * @property-read int|null $events_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Meeting> $meetings
 * @property-read int|null $meetings_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Room> $rooms
 * @property-read int|null $rooms_count
 * @property-read \App\Models\User|null $superAdmin
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Task> $tasks
 * @property-read int|null $tasks_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Database\Factories\CompanyFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereDomain($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereSettings($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperCompany {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string|null $description
 * @property int|null $manager_id
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int|null $company_id
 * @property-read \App\Models\Company|null $company
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Event> $events
 * @property-read int|null $events_count
 * @property-read \App\Models\User|null $manager
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Meeting> $meetings
 * @property-read int|null $meetings_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $members
 * @property-read int|null $members_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Task> $tasks
 * @property-read int|null $tasks_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereManagerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperDepartment {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property int $user_id
 * @property int|null $department_id
 * @property string $category
 * @property \Illuminate\Support\Carbon $start_time
 * @property \Illuminate\Support\Carbon $end_time
 * @property bool $is_all_day
 * @property string $color
 * @property string|null $location
 * @property bool $is_company_wide
 * @property array<array-key, mixed>|null $recurrence
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int|null $company_id
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AuditLog> $auditLogs
 * @property-read int|null $audit_logs_count
 * @property-read \App\Models\Company|null $company
 * @property-read \App\Models\Department|null $department
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reminder> $reminders
 * @property-read int|null $reminders_count
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereIsAllDay($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereIsCompanyWide($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereRecurrence($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperEvent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property int $organizer_id
 * @property int|null $department_id
 * @property int|null $room_id
 * @property \Illuminate\Support\Carbon $start_time
 * @property \Illuminate\Support\Carbon $end_time
 * @property string $timezone
 * @property string $status
 * @property string $type
 * @property string|null $meeting_link
 * @property string|null $location
 * @property bool $is_private
 * @property array<array-key, mixed>|null $recurrence
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int|null $client_id
 * @property int|null $company_id
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AuditLog> $auditLogs
 * @property-read int|null $audit_logs_count
 * @property-read \App\Models\Client|null $client
 * @property-read \App\Models\Company|null $company
 * @property-read \App\Models\Department|null $department
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MeetingNote> $notes
 * @property-read int|null $notes_count
 * @property-read \App\Models\User|null $organizer
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MeetingParticipant> $participantRecords
 * @property-read int|null $participant_records_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $participants
 * @property-read int|null $participants_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reminder> $reminders
 * @property-read int|null $reminders_count
 * @property-read \App\Models\Room|null $room
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Task> $tasks
 * @property-read int|null $tasks_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting whereClientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting whereIsPrivate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting whereLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting whereMeetingLink($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting whereOrganizerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting whereRecurrence($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting whereRoomId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting whereTimezone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Meeting withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperMeeting {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $meeting_id
 * @property int $user_id
 * @property string|null $agenda
 * @property string|null $discussion
 * @property string|null $decisions
 * @property string|null $action_items
 * @property \Illuminate\Support\Carbon|null $follow_up_date
 * @property array<array-key, mixed>|null $attachments
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int|null $company_id
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AuditLog> $auditLogs
 * @property-read int|null $audit_logs_count
 * @property-read \App\Models\User|null $author
 * @property-read \App\Models\Company|null $company
 * @property-read \App\Models\Meeting|null $meeting
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingNote newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingNote newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingNote onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingNote query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingNote whereActionItems($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingNote whereAgenda($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingNote whereAttachments($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingNote whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingNote whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingNote whereDecisions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingNote whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingNote whereDiscussion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingNote whereFollowUpDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingNote whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingNote whereMeetingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingNote whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingNote whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingNote withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingNote withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperMeetingNote {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $meeting_id
 * @property int $user_id
 * @property string $status
 * @property string $role
 * @property string|null $response_notes
 * @property \Illuminate\Support\Carbon|null $responded_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Meeting|null $meeting
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingParticipant newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingParticipant newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingParticipant query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingParticipant whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingParticipant whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingParticipant whereMeetingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingParticipant whereRespondedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingParticipant whereResponseNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingParticipant whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingParticipant whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingParticipant whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeetingParticipant whereUserId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperMeetingParticipant {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string|null $message
 * @property string|null $remindable_type
 * @property int|null $remindable_id
 * @property \Illuminate\Support\Carbon $remind_at
 * @property string $channel
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $sent_at
 * @property array<array-key, mixed>|null $payload
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent|null $remindable
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reminder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reminder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reminder query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reminder whereChannel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reminder whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reminder whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reminder whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reminder wherePayload($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reminder whereRemindAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reminder whereRemindableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reminder whereRemindableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reminder whereSentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reminder whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reminder whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reminder whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reminder whereUserId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperReminder {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string|null $location
 * @property int $capacity
 * @property array<array-key, mixed>|null $amenities
 * @property string $color
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int|null $company_id
 * @property-read \App\Models\Company|null $company
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Meeting> $meetings
 * @property-read int|null $meetings_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room whereAmenities($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room whereCapacity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room whereLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperRoom {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property int|null $assigned_to
 * @property int $created_by
 * @property int|null $department_id
 * @property int|null $meeting_id
 * @property string $status
 * @property string $priority
 * @property \Illuminate\Support\Carbon|null $due_date
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int|null $company_id
 * @property-read \App\Models\User|null $assignee
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AuditLog> $auditLogs
 * @property-read int|null $audit_logs_count
 * @property-read \App\Models\Company|null $company
 * @property-read \App\Models\User|null $creator
 * @property-read \App\Models\Department|null $department
 * @property-read \App\Models\Meeting|null $meeting
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reminder> $reminders
 * @property-read int|null $reminders_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereAssignedTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereDueDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereMeetingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task wherePriority($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperTask {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $department_id
 * @property string $role
 * @property string|null $title
 * @property string|null $phone
 * @property string|null $avatar_url
 * @property string $timezone
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property string $approval_status
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property int|null $approved_by
 * @property string|null $rejection_reason
 * @property string|null $registration_notes
 * @property array<array-key, mixed>|null $notification_preferences
 * @property string|null $username
 * @property string|null $booking_headline
 * @property string|null $booking_bio
 * @property array<array-key, mixed>|null $booking_durations
 * @property string|null $default_meeting_link
 * @property int|null $company_id
 * @property-read User|null $approver
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Task> $assignedTasks
 * @property-read int|null $assigned_tasks_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AuditLog> $auditLogs
 * @property-read int|null $audit_logs_count
 * @property-read \App\Models\Company|null $company
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Task> $createdTasks
 * @property-read int|null $created_tasks_count
 * @property-read \App\Models\Department|null $department
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Event> $events
 * @property-read int|null $events_count
 * @property-read string $role_label
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MeetingParticipant> $meetingParticipants
 * @property-read int|null $meeting_participants_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Meeting> $organizedMeetings
 * @property-read int|null $organized_meetings_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Meeting> $participatingMeetings
 * @property-read int|null $participating_meetings_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reminder> $reminders
 * @property-read int|null $reminders_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User approved()
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereApprovalStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAvatarUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereBookingBio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereBookingDurations($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereBookingHeadline($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDefaultMeetingLink($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereNotificationPreferences($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRegistrationNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRejectionReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTimezone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUsername($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperUser {}
}

